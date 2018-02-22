<?php
/**
 * Add fee to order observer
 *
 * @package Payer_Chekcout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddFeeToOrderObserver
 *
 * @package Payer\Checkout\Observer
 */
class AddFeeToOrderObserver
    implements ObserverInterface
{
    /**
     * Move handling fee data from quote to order
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        $quote = $observer->getQuote();
        $order = $observer->getOrder();

        $order->setBaseHandlingFeeAmount($quote->getBaseHandlingFeeAmount());
        $order->setHandlingFeeAmount($quote->getBaseHandlingFeeAmount());
        $order->setHandlingFeeTaxAmount($quote->getHandlingFeeTaxAmount());
        $order->setBaseHandlingFeeTaxAmount($quote->getBaseHandlingFeeTaxAmount());
        $order->setHandlingFeeInclTax($quote->getHandlingFeeInclTax());
        $order->setBaseHandlingFeeInclTax($quote->getBaseHandlingFeeInclTax());
    }
}
