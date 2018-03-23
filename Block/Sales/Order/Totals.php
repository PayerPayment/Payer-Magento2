<?php
/**
 * Handling fee block
 *
 * @package Payer_checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Block\Sales\Order;

class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get totals source object
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Create the weee ("FPT") totals summary
     *
     * @return $this
     */
    public function initTotals()
    {
        $order           = $this->getSource();
        $handlingFee     = $order->getHandlingFeeInclTax();
        $baseHandlingFee = $order->getBaseHandlingFeeInclTax();

        if ($handlingFee) {
            $fee = new \Magento\Framework\DataObject(
                [
                    'code'       => 'handling_fee',
                    'value'      => $handlingFee,
                    'base_value' => $baseHandlingFee,
                    'label'      => __('Payment handling fee'),
                ]
            );

            $this->getParentBlock()->addTotalBefore($fee, 'grand_total');
        }

        return $this;
    }
}

