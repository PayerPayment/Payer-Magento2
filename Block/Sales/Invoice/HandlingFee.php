<?php
/**
 * Handling fee block
 *
 * @package Payer_checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Block\Sales\Invoice;

class HandlingFee extends \Magento\Sales\Block\Order\Invoice\Totals
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * HandlingFee constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
    }

    /**
     * Initialize all order totals relates with tax
     *
     */
    protected function initTotals()
    {
        $invoice         = $this->getSource();
        $handlingFee     = $invoice->getHandlingFeeInclTax();
        $baseHandlingFee = $invoice->getBaseHandlingFeeInclTax();

        if (!(float)$handlingFee) {

            return $this;
        }

        $fee = new \Magento\Framework\DataObject(
            [
                'code'       => 'handling_fee',
                'value'      => $handlingFee,
                'base_value' => $baseHandlingFee,
                'label'      => __('Payment handling fee'),
            ]
        );

        $this->getParentBlock()->addTotalBefore($fee, 'grand_total');

        return $this;
    }
}
