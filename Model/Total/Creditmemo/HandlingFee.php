<?php
/**
 * Credit memo handling fee total
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Model\Total\Creditmemo;

class HandlingFee extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    /**
     * @inheritDoc
     */
    public function collect(
        \Magento\Sales\Model\Order\Creditmemo $creditmemo
    ) {
        $order   = $creditmemo->getOrder();
        $invoice = $creditmemo->getInvoice();
        $creditmemo->setHandlingFeeAmount(0);
        $creditmemo->setBaseHandlingFeeAmount(0);
        $creditmemo->setHandlingFeeInclTax(0);
        $creditmemo->setBaseHandlingFeeInclTax(0);

        foreach ($order->getCreditmemosCollection() as $previousCreditmemo) {
            if ((float)$previousCreditmemo->getHandlingFeeAmount()) {

                return $this;
            }
        }

        if (!$creditmemo->getInvoice()) {
            $handlingFeeAmount      = $order->getHandlingFeeAmount();
            $baseHandlingFeeAmount  = $order->getBaseHandlingFeeAmount();
            $handlingFeeInclTax     = $order->getHandlingFeeInclTax();
            $baseHandlingFeeInclTax = $order->getBaseHandlingFeeInclTax();
        } else {
            $handlingFeeAmount      = $invoice->getHandlingFeeAmount();
            $baseHandlingFeeAmount  = $invoice->getBaseHandlingFeeAmount();
            $handlingFeeInclTax     = $invoice->getHandlingFeeInclTax();
            $baseHandlingFeeInclTax = $invoice->getBaseHandlingFeeInclTax();
        }

        if ($handlingFeeAmount) {
            $creditmemo->setHandlingFeeAmount($handlingFeeAmount);
            $creditmemo->setBaseHandlingFeeAmount($baseHandlingFeeAmount);
            $creditmemo->setHandlingFeeInclTax($handlingFeeInclTax);
            $creditmemo->setBaseHandlingFeeInclTax($baseHandlingFeeInclTax);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $handlingFeeAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseHandlingFeeAmount);
        }

        return $this;
    }
}
