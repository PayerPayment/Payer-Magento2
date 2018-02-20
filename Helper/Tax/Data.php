<?php
/**
 * Tax helper Override.
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Helper\Tax;

use Magento\Tax\Api\Data\OrderTaxDetailsItemInterface;
use Magento\Sales\Model\EntityInterface;

class Data extends \Magento\Tax\Helper\Data
{
    /**
     * Accumulates the pre-calculated taxes for each tax class
     *
     * This method accepts and returns the 'taxClassAmount' array with format:
     * array(
     *  $index => array(
     *      'tax_amount'        => $taxAmount,
     *      'base_tax_amount'   => $baseTaxAmount,
     *      'title'             => $title,
     *      'percent'           => $percent
     *  )
     * )
     *
     * @param  array                        $taxClassAmount
     * @param  OrderTaxDetailsItemInterface $itemTaxDetail
     * @param  float                        $ratio
     * @return array
     */
    private function _aggregateTaxes($taxClassAmount, OrderTaxDetailsItemInterface $itemTaxDetail, $ratio)
    {
        $itemAppliedTaxes = $itemTaxDetail->getAppliedTaxes();
        foreach ($itemAppliedTaxes as $itemAppliedTax) {
            $taxAmount = $itemAppliedTax->getAmount() * $ratio;
            $baseTaxAmount = $itemAppliedTax->getBaseAmount() * $ratio;

            if (0 == $taxAmount && 0 == $baseTaxAmount) {
                continue;
            }
            $taxCode = $itemAppliedTax->getCode();
            if (!isset($taxClassAmount[$taxCode])) {
                $taxClassAmount[$taxCode]['title'] = $itemAppliedTax->getTitle();
                $taxClassAmount[$taxCode]['percent'] = $itemAppliedTax->getPercent();
                $taxClassAmount[$taxCode]['tax_amount'] = $taxAmount;
                $taxClassAmount[$taxCode]['base_tax_amount'] = $baseTaxAmount;
            } else {
                $taxClassAmount[$taxCode]['tax_amount'] += $taxAmount;
                $taxClassAmount[$taxCode]['base_tax_amount'] += $baseTaxAmount;
            }
        }

        return $taxClassAmount;
    }

    /**
     * @param  EntityInterface $order
     * @param  EntityInterface $salesItem
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function calculateTaxForItems(EntityInterface $order, EntityInterface $salesItem)
    {
        $taxClassAmount = [];

        $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($order->getId());

        // Apply any taxes for the items
        /** @var $item \Magento\Sales\Model\Order\Invoice\Item|\Magento\Sales\Model\Order\Creditmemo\Item */
        foreach ($salesItem->getItems() as $item) {
            $orderItem = $item->getOrderItem();
            $orderItemId = $orderItem->getId();
            $orderItemTax = $orderItem->getTaxAmount();
            $itemTax = $item->getTaxAmount();
            if (!$itemTax || !floatval($orderItemTax)) {
                continue;
            }
            //An invoiced item or credit memo item can have a different qty than its order item qty
            $itemRatio = $itemTax / $orderItemTax;
            $itemTaxDetails = $orderTaxDetails->getItems();
            foreach ($itemTaxDetails as $itemTaxDetail) {
                //Aggregate taxable items associated with an item
                if ($itemTaxDetail->getItemId() == $orderItemId) {
                    $taxClassAmount = $this->_aggregateTaxes($taxClassAmount, $itemTaxDetail, $itemRatio);
                } elseif ($itemTaxDetail->getAssociatedItemId() == $orderItemId) {
                    $taxableItemType = $itemTaxDetail->getType();
                    $ratio = $itemRatio;
                    if ($item->getTaxRatio()) {
                        $taxRatio = $this->serializer->unserialize($item->getTaxRatio());
                        if (isset($taxRatio[$taxableItemType])) {
                            $ratio = $taxRatio[$taxableItemType];
                        }
                    }
                    $taxClassAmount = $this->_aggregateTaxes($taxClassAmount, $itemTaxDetail, $ratio);
                }
            }
        }

        // Apply any taxes for shipping
        $shippingTaxAmount = $salesItem->getShippingTaxAmount();
        $originalShippingTaxAmount = $order->getShippingTaxAmount();
        if ($shippingTaxAmount && $originalShippingTaxAmount &&
            $shippingTaxAmount != 0 && floatval($originalShippingTaxAmount)
        ) {
            //An invoice or credit memo can have a different qty than its order
            $shippingRatio = $shippingTaxAmount / $originalShippingTaxAmount;
            $itemTaxDetails = $orderTaxDetails->getItems();
            foreach ($itemTaxDetails as $itemTaxDetail) {
                //Aggregate taxable items associated with shipping
                if ($itemTaxDetail->getType() == \Magento\Quote\Model\Quote\Address::TYPE_SHIPPING) {
                    $taxClassAmount = $this->_aggregateTaxes($taxClassAmount, $itemTaxDetail, $shippingRatio);
                }
            }
        }

        // Begin override, apply any taxes for handling_fee
        $handlingFeeTaxAmount = $salesItem->getHandlingFeeTaxAmount();
        $originalHandlingFeeTaxAmount = $order->getHandlingFeeTaxAmount();
        if ($handlingFeeTaxAmount && $originalHandlingFeeTaxAmount &&
            $handlingFeeTaxAmount != 0 && floatval($originalHandlingFeeTaxAmount)
        ) {
            //An invoice or credit memo can have a different qty than its order
            $handlingFeeRatio = $handlingFeeTaxAmount / $originalHandlingFeeTaxAmount;
            $itemTaxDetails = $orderTaxDetails->getItems();
            foreach ($itemTaxDetails as $itemTaxDetail) {
                //Aggregate taxable items associated with handlingFee
                if ($itemTaxDetail->getType() == 'handling_fee') {
                    $taxClassAmount = $this->_aggregateTaxes($taxClassAmount, $itemTaxDetail, $handlingFeeRatio);
                }
            }
        }
        // End override

        return $taxClassAmount;
    }
}
