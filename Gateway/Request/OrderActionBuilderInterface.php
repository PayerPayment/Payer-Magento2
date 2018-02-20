<?php
/**
 * Builder interface
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Request;

interface OrderActionBuilderInterface
{
    public function build(\Magento\Sales\Model\Order\Payment $payment);
}
