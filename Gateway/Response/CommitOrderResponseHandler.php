<?php
/**
 * Handle invoice id in response
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CommitOrderResponseHandler implements HandlerInterface
{
    /**
     * Save Transaction details on transaction & payment
     *
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO  = SubjectReader::readPayment($handlingSubject);
        $payment    = $paymentDO->getPayment();

        $payment->setAdditionalInformation('payer_capture', $response['payer']);

        $payment->setTransactionAdditionalInfo(
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            [
                'payer_capture' => $response['payer'],
            ]
        );
    }
}
