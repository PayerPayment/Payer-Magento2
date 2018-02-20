<?php
/**
 * Invoice handling fee totals
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Model\Total\Invoice;

class HandlingFee extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * @inheritDoc
     */
    public function collect(
        \Magento\Sales\Model\Order\Invoice $invoice
    ) {
        $invoice->setHandlingFeeAmount(0);
        $invoice->setBaseHandlingFeeAmount(0);
        $invoice->setHandlingFeeInclTax(0);
        $invoice->setBaseHandlingFeeInclTax(0);
        $handlingFeeAmount      = $invoice->getOrder()->getHandlingFeeAmount();
        $baseHandlingFeeAmount  = $invoice->getOrder()->getBaseHandlingFeeAmount();
        $handlingFeeInclTax     = $invoice->getOrder()->getHandlingFeeInclTax();
        $baseHandlingFeeInclTax = $invoice->getOrder()->getBaseHandlingFeeInclTax();

        if ($handlingFeeAmount) {
            foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
                if ((float)$previousInvoice->getHandlingFeeAmount() && !$previousInvoice->isCanceled()) {

                    return $this;
                }
            }
            $invoice->setHandlingFeeAmount($handlingFeeAmount);
            $invoice->setBaseHandlingFeeAmount($baseHandlingFeeAmount);
            $invoice->setHandlingFeeInclTax($handlingFeeInclTax);
            $invoice->setBaseHandlingFeeInclTax($baseHandlingFeeInclTax);

            $invoice->setGrandTotal($invoice->getGrandTotal() + $handlingFeeAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseHandlingFeeAmount);
        }

        return $this;
    }
}
