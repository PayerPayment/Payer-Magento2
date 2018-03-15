<?php
/**
 * Payer Settle callback endpoint.
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use Payer\Checkout\Model\Payment\CallbackHandler;
use Payer\Checkout\Model\Payment\CallbackValidator;
use \Psr\Log\LoggerInterface as logger;

class Settle extends
    \Magento\Framework\App\Action\Action
{
    protected $logger;
    protected $callbackValidator;
    protected $callbackHandler;

    /**
     * Settle constructor.
     *
     * @param Magento\Framework\App\Action\Context              $context
     * @param \Psr\Log\LoggerInterface                          $logger
     * @param Payer\Checkout\Model\Payment\CallbackValidator    $callbackValidator
     * @param Payer\Checkout\Model\Payment\CallbackHandler      $callbackHandler
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        logger $logger,
        CallbackValidator $callbackValidator,
        CallbackHandler $callbackHandler
    )
    {
        $this->logger               = $logger;
        $this->callbackValidator    = $callbackValidator;
        $this->callbackHandler      = $callbackHandler;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $payerData = $this->getRequest()->getParams();

        try {
            $validation = $this->callbackValidator->validate($payerData);
            if ($validation['isValid'] === false) {

                return $this->reportAndReturn(
                    $validation['httpStatus'],
                    $validation['message']
                );
            }

            $result = $this->callbackHandler->handleCallback($payerData);

            return $this->reportAndReturn(
                $result['httpResponseCode'],
                $result['message']
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            return $this->reportAndReturn(500, $e->getMessage());
        }
    }

    /**
     * Set http status code log event and return.
     *
     * @see https://httpstatuses.com for references.
     *
     * @param int    $httpStatus HTTP status code
     * @param string $logMessage
     *
     * @return \Magento\Framework\View\Result\Page|bool=false
     */
    protected function reportAndReturn($httpStatus, $logMessage)
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $resultPage->setHttpResponseCode(202);

        $this->logger->info($httpStatus .' - '. $logMessage);

        if ($httpStatus == 202) {
            $resultPage->setContents('TRUE');

            return $resultPage;
        }

        $resultPage->setContents('FALSE - ' . $logMessage);

        return $resultPage;
    }
}
