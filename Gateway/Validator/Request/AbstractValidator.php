<?php
/**
 * Abstract validator
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */
namespace Payer\Checkout\Gateway\Validator\Request;

use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

abstract class AbstractValidator implements RequestValidatorInterface
{
    /**
     * @var ResultInterfaceFactory
     */
    protected $resultInterfaceFactory;

    /**
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory
    ) {
        $this->resultInterfaceFactory = $resultFactory;
    }

    /**
     * Factory method
     *
     * @param bool $isValid
     * @param array $fails
     * @return ResultInterface
     */
    protected function createResult($isValid, array $fails = [])
    {
        return $this->resultInterfaceFactory->create(
            [
                'isValid'           => (bool)$isValid,
                'failsDescription'  => $fails
            ]
        );
    }
}
