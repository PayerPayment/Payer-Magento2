<?php
/**
 * "CommitOrder" client, Capture order for payer_checkout_invoice & payer_checkout_installment
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Client;

use Payer\Sdk\Resource\Order;
use Payer\Checkout\Model\Api\Init as PayerConfig;

class CommitOrderClient implements PayerClientInterface
{
    protected $auth;

    /**
     * CommitOrderClient constructor.
     *
     * @param Payer\Checkout\Model\Api\Init $auth
     */
    public function __construct(
        PayerConfig $auth
    ) {
        $this->auth = $auth;
    }

    /**
     * @param array     $data
     * @param string    $paymentMethod
     *
     * @return mixed
     */
    public function placeRequest($data, $paymentMethod)
    {
        $payerResource = new Order($this->auth->setupClient($paymentMethod));

        return $payerResource->commit($data);
    }
}
