<?php

namespace Payer\Checkout\Helper;

/**
 * Class Transaction
 *
 * @package Payer\Checkout\Helper
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */
class Transaction
{
    /**
     * Create transaction.
     *
     * @param      $payment
     * @param      $data
     * @param      $type
     * @param bool $status
     *
     */
    public function addTransaction($payment, $data, $type, $status = false)
    {
        if (isset($data['payer_merchant_reference_id'])) {
            $id    = $data['payer_merchant_reference_id'];
            $txnId = "{$id}_{$data['payer_callback_type']}";
        } elseif (isset($data['purchase']['reference_id'])) {
            $id    = $data['purchase']['reference_id'];
            $txnId = "{$id}_create";
        } else {

            throw new \Exception('no txnId');
        }

        $parentTransId = $payment->getLastTransId();

        $payment->setTransactionId($txnId)
            ->setIsTransactionClosed($status)
            ->setTransactionAdditionalInfo(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $this->flattenDataArray($data)
            );

        $transaction = $payment->addTransaction($type, null, true);

        if ($parentTransId) {
            $transaction->setParentTxnId($parentTransId);
        }

        $transaction->save();
        $payment->save();
    }

    /**
     * Flatten array.
     *
     * @param array  $array
     * @param string $prefix
     *
     * @return array
     */
    public function flattenDataArray($array, $prefix = '')
    {
        $result = [];
        foreach ((array)$array as $key => $value) {
            if (is_array($value)) {
                if (!empty($prefix)) {
                    $index = sprintf("%s-%s", $prefix, $key);
                } else {
                    $index = $key;
                }
                $result += $this->flattenDataArray($value, $index);
            } else {
                if (!empty($prefix)) {
                    $index = sprintf("%s-%s", $prefix, $key);
                } else {
                    $index = $key;
                }
                $result[$index] = $value;
            }
        }

        return $result;
    }
}
