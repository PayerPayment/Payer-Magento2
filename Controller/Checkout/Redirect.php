<?php
/**
 * Get Payer FormData Endpoint.
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Payer\Checkout\Gateway\Request\BuildOrder;

class Redirect extends
    \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $context;
    protected $builder;

    /**
     * Redirect constructor.
     *
     * @param Magento\Framework\App\Action\Context              $context
     * @param Magento\Framework\Controller\Result\JsonFactory   $resultJsonFactory
     * @param Magento\Checkout\Model\Session                    $checkoutSession
     * @param Payer\Checkout\Gateway\Request\BuildOrder         $builder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        BuildOrder $builder
    ) {
        $this->context           = $context;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession   = $checkoutSession;
        $this->builder           = $builder;

        parent::__construct($context);
    }

    /**
     * Get form data to be posted to payer.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request        = $this->getRequest();
        $formKeyIsValid = $this->context->getFormKeyValidator()->validate($request);
        $isPost         = $this->getRequest()->isPost();

        if (!$formKeyIsValid || !$isPost) {

            return $this->errorResponse(__('Error: Invalid fromKey'));
        }
        if (!$isPost) {

            return $this->errorResponse(__('Error: Invalid Request'));
        }

        $order  = $this->checkoutSession->getLastRealOrder();
        $data   = $this->builder->build($order->getPayment());
        $result = $this->resultJsonFactory->create();

        $data['htmlFormAction'] = 'post';
        $result->setData(['form' => $data]);

        return $result;
    }

    /**
     * Return json error message
     *
     * @param $errorMessage
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function errorResponse($errorMessage)
    {
        $response = $this->resultJsonFactory->create();
        $response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST)
            ->setData(array('message' => $errorMessage));

        return $response;
    }
}
