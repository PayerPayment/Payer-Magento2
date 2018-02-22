<?php

namespace Payer\Checkout\Model\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface as scope;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Payer\Checkout\Helper\Transaction as transactionHelper;
use Payer\Checkout\Model\Config\Api\Configuration;
use \Psr\Log\LoggerInterface as logger;

/**
 * Class CallbackHandler
 *
 * @package Payer\Checkout\Model\Payment
 * @module  Payer_checkout
 * @author  Webbhuset <info@webbhuset.se>
 */
class CallbackHandler
{
    protected $orderRepository;
    protected $scopeConfig;
    protected $transactionHelper;
    protected $config;
    protected $logger;
    protected $orderEmail;

    /**
     * CallbackHandler constructor.
     *
     * @param OrderRepository $orderRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param transactionHelper $transactionHelper
     * @param Configuration $config
     * @param LoggerInterface $logger
     * @param OrderSender $orderSender
     */
    public function __construct(
        OrderRepository   $orderRepository,
        scope             $scopeConfig,
        transactionHelper $transactionHelper,
        Configuration     $config,
        logger            $logger,
        OrderSender       $orderSender
    )
    {
        $this->orderRepository   = $orderRepository;
        $this->scopeConfig       = $scopeConfig;
        $this->transactionHelper = $transactionHelper;
        $this->config            = $config;
        $this->logger            = $logger;
        $this->orderEmail        = $orderSender;
    }

    /**
     * Handle payer Callback.
     *
     * @param array $response
     */
    public function handleCallback($response)
    {
        if(!array_key_exists('payer_callback_type', $response)) {

            return [
                'status'     => false,
                'httpStatus' => 404,
                'message'    => 'Invalid Callback'
            ];
        }
        switch ($response['payer_callback_type']) {
            case 'auth':

                return $this->auth($response);
            case 'settle':

                return $this->settle($response);
            case 'success':

                return $this->success($response);
            case 'cancel':

                return $this->cancel($response);
            default:

                return [
                    'status '    => false,
                    'httpStatus' => 404,
                    'message'    => 'Unknown Callback'
                ];
        }
    }

    /**
     * Handle Auth callback
     *
     * @param array $data
     */
    protected function auth($data)
    {
        try {
            $orderId = substr(
                $data['payer_merchant_reference_id'],
                strpos($data['payer_merchant_reference_id'], "_") + 1
            );
            $order   = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            $method  = $payment->getMethod();

            $methodFromCallback = $this->config->payerCodeToConfigCode($data['payer_payment_type']);
            $authedStatus = $this->config->getAuthOrderStatus($payment->getMethod());

            if ($method != $methodFromCallback) {
                $payment->setMethod($methodFromCallback);

                $this->logger->info(
                    "Changed payment Method for OrderId: {$orderId}
                     from: {$method} to: {$methodFromCallback}"
                );

                $order->addStatusToHistory($authedStatus,
                    "Payment Method has changed: from {$method} to {$methodFromCallback}.
                    Verify if invoice fee needs to be refunded."
                );
            }
            $order->addStatusToHistory($authedStatus, 'Order was authed.');

            $payment->setAdditionalInformation(
                'payer_auth',
                $this->transactionHelper->flattenDataArray($data)
            );

            $this->transactionHelper->addTransaction(
                $payment,
                $data,
                TransactionInterface::TYPE_AUTH,
                false
            );
            $this->orderRepository->save($order);

            return [
                'httpResponseCode' => 202,
                'message' => "Auth Callback for OrderId: {$orderId}  Handled."
            ];
        } catch(\Exception $e) {
            $this->logger->critical($e->getMessage());

            return [
                'httpResponseCode' => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle Settle callback
     *
     * @param array $data
     */
    protected function settle($data)
    {
        try {
            $orderId        = substr(
                $data['payer_merchant_reference_id'],
                strpos($data['payer_merchant_reference_id'], "_") + 1
            );
            $order          = $this->orderRepository->get($orderId);
            $payment        = $order->getPayment();
            $paymentMethod  = $payment->getMethod();
            $status         = $this->config->getAcknowledgedOrderStatus($paymentMethod);
            $autoCapture    = $this->config->getCaptureOnConfirmation($payment->getMethod());

            if ($autoCapture) {
                $payment->registerCaptureNotification($order->getBaseTotalDue());
                $order->setStatus(Order::STATE_PROCESSING);
            } else {
                $payment->authorize($isOnline = true, $order->getBaseTotalDue());
            }

            $payment->save();

            $order->addStatusToHistory($status, 'Recieved Settle callback.');
            $payment->setAdditionalInformation(
                'payer_settle',
                $this->transactionHelper->flattenDataArray($data)
            );
            $this->transactionHelper->addTransaction(
                $payment,
                $data,
                TransactionInterface::TYPE_PAYMENT,
                false
            );
            $this->orderRepository->save($order);

            return [
                'httpResponseCode' => 202,
                'message' => "Settlement Callback for OrderId: {$orderId}  Handled."
            ];
        } catch(\Exception $e) {
            $this->logger->critical($e->getMessage());

            return [
                'httpResponseCode' => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * AutoCapture or authorize order.
     *
     * @param array $data
     */
    protected function success($data)
    {
        try {
            $orderId     = substr(
                $data['payer_merchant_reference_id'],
                strpos($data['payer_merchant_reference_id'], "_") + 1
            );
            $order       = $this->orderRepository->get($orderId);

            $this->orderEmail->send($order);

            return [
                'status'  => true,
                'message' => 'Order Success'
            ];
        } catch(\Exception $e) {
            $this->logger->critical($e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel Order if payment not completed.
     *
     * @param array $data
     */
    protected function cancel($data)
    {
        try {
            $orderId = substr(
                $data['payer_merchant_reference_id'],
                strpos($data['payer_merchant_reference_id'], "_") + 1
            );
            $order   = $this->orderRepository->get($orderId);

            $order->setState(Order::STATE_CANCELED);
            $order->addStatusToHistory(
                Order::STATE_CANCELED,
                'Order Canceled (payment step not completed)'
            );
            $this->logger->info(
                "Canceled Order: {$order->getIncrementId()}, (Payment step not completed.)"
            );

            $this->orderRepository->save($order);

            return [
                'status'  => true,
                'message' => 'Order successfully Canceled'
            ];
        } catch(\Exception $e) {
            $this->logger->critical($e->getMessage());

            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
