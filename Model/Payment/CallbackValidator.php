<?php

namespace Payer\Checkout\Model\Payment;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Payer\Checkout\Model\Api\Init as PayerConfig;
use Payer\Checkout\Model\Config\Api\Configuration;

/**
 * Class CallbackValidator
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */
class CallbackValidator
{
    protected $checkoutSession;
    protected $orderRepository;
    protected $auth;
    protected $config;

    /**
     * Constructor CallbackValidator.
     *
     * @param Session                       $checkoutSession
     * @param OrderRepository               $orderRepository
     * @param Payer\Checkout\Model\Api\Init $auth
     * @param Configuration                 $config
     */
    public function __construct(
        Session           $checkoutSession,
        OrderRepository   $orderRepository,
        PayerConfig       $auth,
        Configuration     $config
    )
    {
        $this->checkoutSession   = $checkoutSession;
        $this->orderRepository   = $orderRepository;
        $this->auth              = $auth;
        $this->config            = $config;
    }

    /**
     * Validate payer Callback.
     *
     * @param array $params
     */
    public function validate($params)
    {
        if (!array_key_exists('payer_callback_type', $params)) {

            return [
                'isValid'    => false,
                'httpStatus' => 404,
                'message'    => 'Invalid Callback'
            ];
        }
        switch ($params['payer_callback_type']) {
            case 'auth':

                return $this->auth($params);
            case 'settle':

                return $this->settle($params);
            case 'success':

                return $this->success($params);
            case 'cancel':

                return $this->cancel($params);
            default:

                return [
                    'isValid'    => false,
                    'httpStatus' => 404,
                    'message'    => 'Unknown Callback'
                ];
        }
    }

    /**
     * Acknowledge order.
     *
     * @param array $data
     *
     * @return array
     */
    protected function auth($data)
    {
        $payerValidation = $this->payerValidation($data);
        if ($payerValidation['isValid'] === false) {

            return $payerValidation;
        }

        $expectedParams = [
            'payer_payment_type',
            'payer_callback_type',
            'payer_merchant_reference_id',
            'md5sum',
        ];
        foreach ($expectedParams as $param) {
            if (!isset($param, $data)) {

                return [
                    'isValid'    => false,
                    'httpStatus' => 206,
                    'message'    => 'Callback Request: Params missing.'
                ];
            }
        }

        $orderId = substr(
            $data['payer_merchant_reference_id'],
            strpos($data['payer_merchant_reference_id'], "_") + 1
        );
        if (!$orderId) {

            return [
                'isValid'    => false,
                'httpStatus' => 203,
                'message'    => 'Callback param data contain invalid data.'
            ];
        }

        $order      = $this->orderRepository->get($orderId);
        $orderState = $order->getState();
        switch ($orderState) {
            case Order::STATE_PENDING_PAYMENT:
            case Order::STATE_PROCESSING:
            case Order::STATE_COMPLETE:

                return [
                    'isValid'    => false,
                    'httpStatus' => 208,
                    'message'    => 'Callback Request already Handled'
                ];
            case Order::STATE_CANCELED:

                return [
                    'isValid'    => false,
                    'httpStatus' => 206,
                    'message'    => "Cant authorize payment, order: {$orderId} is Canceled"
                ];
        }

        return [
            'isValid' => true,
            'message' => 'Callback Request is valid'
        ];
    }

    /**
     * Acknowledge order.
     *
     * @param array $data
     *
     * @return array
     */
    protected function settle($data)
    {
        $payerValidation = $this->payerValidation($data);
        if ($payerValidation['isValid'] === false) {

            return $payerValidation;
        }

        $expectedParams = [
            'payer_payment_type',
            'payer_callback_type',
            'payer_payment_id',
            'payer_merchant_reference_id',
            'md5sum',
        ];
        foreach ($expectedParams as $param) {
            if (!isset($param, $data)) {

                return [
                    'isValid'    => false,
                    'httpStatus' => 206,
                    'message'    => 'Callback Request: Params missing.'
                ];
            }
        }

        $orderId = substr(
            $data['payer_merchant_reference_id'],
            strpos($data['payer_merchant_reference_id'], "_") + 1
        );
        if (!$orderId) {

            return [
                'isValid'    => false,
                'httpStatus' => 203,
                'message'    => 'Callback param data contain invalid data.'
            ];
        }

        $order      = $this->orderRepository->get($orderId);
        $orderState = $order->getState();
        switch ($orderState) {
            case Order::STATE_PENDING_PAYMENT:
            case Order::STATE_PROCESSING:
            case Order::STATE_COMPLETE:

                return [
                    'isValid'    => false,
                    'httpStatus' => 208,
                    'message'    => 'Callback Request already Handled'
                ];
            case Order::STATE_CANCELED:

                return [
                    'isValid'    => false,
                    'httpStatus' => 206,
                    'message'    => "Cant authorize payment, order: {$orderId} is Canceled"
                ];
        }

        return [
            'isValid' => true,
            'message' => 'Callback Request is valid'
        ];
    }

    /**
     * AutoCapture or authorize order.
     *
     * @param array $data
     *
     * @return array
     */
    protected function success($data)
    {
        $expectedParams = [
            'payer_merchant_reference_id',
            'payer_callback_type',
        ];

        foreach ($expectedParams as $param) {
            if (!isset($param, $data)) {

                return [
                    'isValid' => false,
                    'message' => 'Callback Request: Params missing.'
                ];
            }
        }

        $lastOrderId = $this->checkoutSession->getLastOrderId();
        $orderId     = substr(
            $data['payer_merchant_reference_id'],
            strpos($data['payer_merchant_reference_id'], "_") + 1
        );

        if ($lastOrderId && ($lastOrderId == $orderId)) {
            $order      = $this->orderRepository->get($orderId);
            $orderState = $order->getState();
            $payment    = $order->getPayment();
            $info       = $payment->getAdditionalInformation();

            $allowedStates = [
                Order::STATE_NEW,
                Order::STATE_PENDING_PAYMENT,
                Order::STATE_PROCESSING
            ];
            if (!in_array($orderState, $allowedStates)) {

                return [
                    'isValid' => false,
                    'message' => "Order: {$orderId}. OrderState: {$orderState} not allowed in SuccessHandler."
                ];
            }
        }

        return ['isValid' => true];
    }

    /**
     * Cancel order.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function cancel($data)
    {
        $expectedParams = [
            'payer_merchant_reference_id',
            'payer_callback_type',
        ];

        foreach ($expectedParams as $param) {
            if (!isset($param, $data)) {

                return [
                    'isValid' => false,
                    'message' => 'Callback Request: Params missing.'
                ];
            }
        }

        $lastOrderId    = $this->checkoutSession->getLastOrderId();
        $payerOrderId   = $data['payer_merchant_reference_id'];
        $orderId        = substr(
            $payerOrderId,
            strpos($payerOrderId, "_") + 1
        );

        if (!$lastOrderId) {

            return [
                'isValid' => false,
                'message' => "Payer Order {$payerOrderId}: LastOrderId not set."
            ];
        }
        if ($lastOrderId != $orderId) {

            return [
                'isValid' => false,
                'message' => "Payer Order {$payerOrderId}: does not match LastOrderId"
            ];
        }

        $order      = $this->orderRepository->get($orderId);
        $orderState = $order->getState();
        if (
            $orderState == Order::STATE_PROCESSING ||
            $orderState == Order::STATE_COMPLETE
        ) {

            return [
                'isValid' => false,
                'message' => "Order: {$orderId}. Not Allowed to cancel at OrderState: {$orderState}."
            ];
        }

        $payment = $order->getPayment();
        $info    = $payment->getAdditionalInformation();
        if (isset($info['payer_auth']) || isset($info['payer_settle'])) {

            return [
                'isValid' => false,
                'message' => "Order: {$orderId}. Not Allowed to cancel, Order Created in Payer."
            ];
        }

        return ['isValid' => true];
    }

    /**
     * Payer ip & data validation
     *
     * @param array $payerData
     *
     * @return array
     */
    protected function payerValidation($payerData)
    {
        $overrideCallback = $this->config->getOverrideCallbackUrl($payerData['payer_payment_type']);
        $isTestMode       = $this->config->isTestMode($payerData['payer_payment_type']);
        $gateway          = $this->auth->setupClient($payerData['payer_payment_type']);
        $purchase         = new \Payer\Sdk\Resource\Purchase($gateway);
        $post             = $gateway->getPostService();

        if ($overrideCallback && $isTestMode) {

            return [
                'isValid'    => true,
                'message'    => 'Callback overridden in TestMode: No IP or Callback Validation'
            ];
        }
        if(!$post->is_valid_ip()) {

            return [
                'isValid'    => false,
                'httpStatus' => 406,
                'message'    => "INVALID IP {$_SERVER['REMOTE_ADDR']} \n"
            ];
        }
        if(!$post->is_valid_callback()) {

            return [
                'isValid'    => false,
                'httpStatus' => 406,
                'message'    => 'INVALID CALLBACK REQUEST'
            ];
        }

        return ['isValid'    => true];
    }
}
