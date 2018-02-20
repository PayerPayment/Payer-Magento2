<?php
/**
 * Customer data request builder
 *
 * @package Payer_Checkout
 * @module  payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Gateway\Request;

class CustomerBuilder
{
    public function build($address)
    {
        $customer = [
            'identity_number'   => $address->getVatId(),
            'organisation'      => $address->getCompany(),
            'first_name'        => $address->getFirstname(),
            'last_name'         => $address->getLastname(),
            'address' => [
                'address_1'     => $address->getStreet()[0] ?? '',
                'address_2'     => $address->getStreet()[1] ?? '',
                'co'            => $address->getData('coAddress'),
            ],
            'zip_code'      => $address->getPostCode(),
            'city'          => $address->getCity(),
            'country_code'  => $address->getCountryId(),
            'phone' => [
                'home'      => $address->getTelephone(),
                'work'      => $address->getTelephone(),
                'mobile'    => $address->getTelephone(),
            ],
            'email' => $address->getEmail(),
        ];

        return $customer;
    }
}
