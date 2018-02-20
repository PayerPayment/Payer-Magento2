<?php

namespace Payer\Checkout\Controller\Checkout;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Data\Form\FormKey\Validator;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Payer\Checkout\Model\Api\RequestAddress;

/**
 * Class Getaddress
 *
 * @package Payer\Checkout\Controller\Checkout
 */
class getAddress
    extends Action
{
    protected $context;
    protected $requestAddress;
    protected $formKeyValidator;
    protected $resultJsonFactory;
    protected $requestAddressModel;

    /**
     * getAddress constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator   $formKeyValidator
     * @param \Payer\Checkout\Model\Api\RequestAddress         $requestAddress
     */
    public function __construct(
        Context        $context,
        JsonFactory    $resultJsonFactory,
        Validator      $formKeyValidator,
        RequestAddress $requestAddress
    )
    {
        $this->context             = $context;
        $this->requestAddressModel = $requestAddress;
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->formKeyValidator    = $formKeyValidator;

        parent::__construct($context);
    }

    /**
     * Handle Request.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultPage = $this->resultJsonFactory->create();
        $request    = $this->context->getRequest();
        $in         = $request->getParam('in');
        $zip        = $request->getParam('zip');

        if (!$in) {
            $resultPage->setHttpResponseCode(401);
            $resultPage->setData(['status' => __('Missing Id')]);

            return $resultPage;
        }

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultPage->setHttpResponseCode(401);
            $resultPage->setData(['status' => __('Invalid form key')]);

            return $resultPage;
        }

        $addressData = $this->requestAddressModel->getData($in, $zip);
        if (
            isset($addressData['httpStatusCode'])
            && isset($addressData['errorMessage'])
        ) {
            $resultPage->setHttpResponseCode($addressData['httpStatusCode']);

            return $resultPage->setData($addressData['errorMessage']);
        }

        return $resultPage->setData($addressData);
    }
}
