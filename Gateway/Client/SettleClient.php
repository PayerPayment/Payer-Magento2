<?php
/**
 * "Settlement" client, Capture order for payer_checkout_card with option delayed_settlement
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Client;

use Payer\Sdk\Resource\Purchase;
use Payer\Checkout\Model\Api\Init as PayerConfig;

class SettleClient implements PayerClientInterface
{
    protected $auth;

    /**
     * SettleClient constructor.
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
    public function placeRequest($data, $pamentMethod)
    {
        $payerResource = new Purchase($this->auth->setupClient($paymentMethod));

        return $payerResource->settlement($data);
    }
}
