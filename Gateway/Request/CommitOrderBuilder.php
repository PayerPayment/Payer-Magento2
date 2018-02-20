<?php
/**
 * Capture order builder
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Request;

use Payer\Checkout\Model\Config\Api\Configuration;

class CommitOrderBuilder implements \Payer\Checkout\Gateway\Request\OrderActionBuilderInterface
{
    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $additionalInfo = $payment->getAdditionalInformation();
        $referenceId    = $additionalInfo['payer_settle']['payer_merchant_reference_id'] ?? false;

        return [
            'reference_id'  => $referenceId
        ];
    }
}
