<?php
/**
 * Assign data from frontend to payment observer
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * Assign data from frontend to payment
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $method = $data->getMethod();
        $isPayerOrder = strpos($method, 'payer_') !== false;

        if (!$isPayerOrder) {
            return;
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $payment = $this->readPaymentModelArgument($observer);

        foreach ($additionalData as $key => $value) {
            if (is_object($value)) {
                continue;
            }

            $payment->setAdditionalInformation(
                $key,
                $value
            );
        }
    }
}
