<?php
/**
 * Payer Order Client Interface
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Client;

interface PayerClientInterface
{
    /**
     * Place request
     * @param $data
     * @param $paymentMethod
     * @return mixed
     */
    public function placeRequest($data, $paymentMethod);
}
