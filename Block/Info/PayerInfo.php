<?php
/**
 * Payer info block
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Block\Info;

class PayerInfo extends \Magento\Payment\Block\ConfigurableInfo
{
    protected $_template = 'Payer_Checkout::info/payer_order_info.phtml';

    /**
     * Prepare Payer-specific payment information.
     *
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport  = parent::_prepareSpecificInformation($transport);
        $info       = $this->getPayerInfo();

        return $transport->addData($info);
    }

    /**
     * Get Payer-specific payment information.
     *
     * @return array
     */
    protected function getPayerInfo()
    {
        $testMode   = '';
        $payment    = $this->getInfo();
        $data       = $payment->getAdditionalInformation();
        $order      = $payment->getOrder();
        if (isset($data['payer_settle']['payer_testmode'])) {
            $testMode = (int)filter_var($data['payer_settle']['payer_testmode'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['payer_settle'])) {
            $payerInfo = [
                'Payment Method'    => $this->getMethod()->getTitle(),
                'Mode'              => $testMode ? 'TEST' : 'LIVE',
                'Status'            => __('Payment Authorized'),
                'Payer Order Id'    => $data['payer_settle']['payer_merchant_reference_id'],
                'Payer Payment Id'  => $data['payer_settle']['payer_payment_id'],
            ];
        } elseif (isset($data['payer_auth'])) {
            $payerInfo = [
                'Payment Method'    => $this->getMethod()->getTitle(),
                'Mode'              => $testMode ? 'TEST' : 'LIVE',
                'Status'            => __('Order Authorized'),
                'Payer Order Id'    => $data['payer_auth']['payer_merchant_reference_id'],
            ];
        } else {
            $payerInfo = [
                'Payment Method'    => $this->getMethod()->getTitle(),
                'Status'            => __('Order not Verified'),
            ];
        }

        if (isset($data['payer_capture'])) {
            $payerInfo['Payer Invoice id']  = $data['payer_capture']['invoice_number'];
            $payerInfo['Status']            = __('Order Invoiced');
        } elseif ($order->getTotalDue() == 0) {
            $payerInfo['Status']            = __('Order Invoiced');
        }

        return $payerInfo;
    }
}
