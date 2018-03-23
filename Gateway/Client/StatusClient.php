<?php
/**
 * "getStatus" client, Capture order for payer_checkout_invoice created with Create New Order
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Client;

use Payer\Sdk\Resource\Order;
use Payer\Checkout\Model\Api\Init as PayerConfig;

class StatusClient implements PayerClientInterface
{
    protected $auth;

    /**
     * StatusClient constructor.
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
     *
     * @return mixed
     */
    public function placeRequest($data)
    {
        $payerResource = new Order($this->auth->setupClient());

        return $payerResource->getStatus($data);
    }
}
