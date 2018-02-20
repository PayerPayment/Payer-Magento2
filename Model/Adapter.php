<?php

namespace Payer\Checkout\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\Adapter as paymentAdapter;

/**
 * Class Adapter
 *
 * @package Payer\Checkout\Model
 */
class Adapter
    extends paymentAdapter
{
    /**
     * Is Available? As canUseMultiShipping is
     *  no longer available we utilize this method for our check.
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool|mixed
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($quote->getIsMultiShipping()) {

            return false;
        }

        return parent::isAvailable($quote);
    }
}
