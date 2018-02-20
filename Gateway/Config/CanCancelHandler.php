<?php
/**
 * Can cancel handler
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

class CanCancelHandler implements ValueHandlerInterface
{
    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDO  = $subject['payment'];
        $payment    = $paymentDO->getPayment();
        $info       = $payment->getAdditionalInformation();

        if (isset($info['payer_auth']) ||
            isset($info['payer_settle'])
        ) {

            return false;
        }

        return $payment instanceof Payment && !(bool)$payment->getAmountPaid();
    }
}
