<?php
/**
 * New order request validator
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Validator\Request;

class CommitOrderRequestValidator extends AbstractValidator
{
    protected $allowedPaymentMethods = [
        'payer_checkout_invoice',
        'payer_checkout_installment',
    ];

    /**
     * Performs validation of request.
     *
     * @param array $request
     * @param \Magento\Sales\Model\Order\Payment $payment
     *
     * @return ResultInterface
     */
    public function validate($request, \Magento\Sales\Model\Order\Payment $payment)
    {
        $method = $payment->getMethod();

        if (!in_array($method, $this->allowedPaymentMethods)) {

            return $this->createResult(
                false,
                [__('Payer error 1101 : CommitOrder only allowed for payer_checkout_invoice & payer_checkout_installment')]
            );
        }

        if (!isset($request['reference_id'])) {

            return $this->createResult(
                false,
                [__('Payer error 1102 : payer_merhcant_reference_id is not set')]
            );
        }

        return $this->createResult(true, []);
    }
}
