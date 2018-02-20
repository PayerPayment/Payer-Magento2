<?php
/**
 * Set can send email observer
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetCanSendNewEmailFlag implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order      = $observer->getOrder();
        $payment    = $order->getPayment();
        $method     = $payment->getMethod();

        if (strpos($method, 'payer_checkout_') !== false) {
            $order->setCanSendNewEmailFlag(false);
        }
    }
}
