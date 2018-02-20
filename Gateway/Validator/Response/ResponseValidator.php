<?php
/**
 * Response validator
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Validator\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResponseValidator extends AbstractValidator
{
    /**
     * Performs validation of response.
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $paymentDO      = SubjectReader::readPayment($validationSubject);
        $response       = SubjectReader::readResponse($validationSubject);
        $orderIsValid   = $this->isSuccessfulTransaction($response);

        if ($orderIsValid) {

            return $this->createResult(
                true,
                []
            );
        } else {

            return $this->createResult(
                false,
                ['error: no invoice_id']
            );
        }
    }

    /**
     * Check response data to see if transaction is successful
     *
     * @param  array
     *
     * @return boolean
     */
    protected function isSuccessfulTransaction(array $response)
    {
        if (!isset($response['payer']['invoice_number'])) {

            return false;
        }

        return true;
    }
}
