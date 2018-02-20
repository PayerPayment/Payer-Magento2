<?php
/**
 * Request validator interface
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Validator\Request;

interface RequestValidatorInterface
{
    public function validate($request, \Magento\Sales\Model\Order\Payment $payment);
}
