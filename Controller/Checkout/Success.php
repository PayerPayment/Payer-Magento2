<?php
/**
 * Succes controller.
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Controller\Checkout;

use Magento\Sales\Model\OrderRepository;
use Payer\Checkout\Model\Payment\CallbackValidator;
use Payer\Checkout\Model\Payment\CallbackHandler;
use \Psr\Log\LoggerInterface as logger;

class Success extends
    \Magento\Framework\App\Action\Action
{
    protected $logger;
    protected $checkoutSession;
    protected $resultFactory;
    protected $orderRepository;
    protected $callbackValidator;
    protected $callbackHandler;

    /**
     * Success constructor.
     *
     * @param Magento\Framework\App\Action\Context              $context
     * @param Magento\Checkout\Model\Session                    $checkoutSession
     * @param Magento\Sales\Model\OrderRepository               $orderRepository
     * @param Payer\Checkout\Model\Payment\CallbackValidator    $callbackValidator
     * @param Payer\Checkout\Model\Payment\CallbackHandler      $callbackHandler
     * @param \Psr\Log\LoggerInterface                          $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderRepository $orderRepository,
        CallbackValidator $callbackValidator,
        CallbackHandler $callbackHandler,
        logger $logger
    )
    {
        $this->checkoutSession      = $checkoutSession;
        $this->resultFactory        = $context->getResultFactory();
        $this->orderRepository      = $orderRepository;
        $this->callbackValidator    = $callbackValidator;
        $this->callbackHandler      = $callbackHandler;
        $this->logger               = $logger;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller/Result/RedirectFactory
     */
    public function execute()
    {
        $payerData = $this->getRequest()->getParams();

        try {
            $validation = $this->callbackValidator->validate($payerData);
            if ($validation['isValid'] === false) {
                $this->messageManager->addError(__('invalid request.'));
                $message = $result['message'] ?? 'Validation failed.';

                return $this->reportAndReturn(false, $message);
            }
            $result  = $this->callbackHandler->handleCallback($payerData);
            $status  = $result['status']  ?? false;
            $message = $result['message'] ?? '';

            return $this->reportAndReturn($status, $message);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            return $this->reportAndReturn(false, $e->getMessage());
        }
    }

    /**
     * log event and redirect.
     *
     * @param bool   $status
     * @param string $logMessage
     *
     * @return \Magento\Framework\Controller/Result/RedirectFactory
     */
    protected function reportAndReturn($status, $logMessage)
    {
        $this->logger->info($logMessage);
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($status == true) {
            $resultRedirect->setPath('checkout/onepage/success');
        } else {
            $resultRedirect->setPath('checkout/cart');
        }

        return $resultRedirect;
    }
}
