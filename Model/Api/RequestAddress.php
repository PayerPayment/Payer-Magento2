<?php

namespace Payer\Checkout\Model\Api;

use \Payer\Checkout\Model\Api\Init as payerApi;
use \Payer\Sdk\Exception\PayerException;
use \Payer\Sdk\Resource\GetAddress as ApiGetAddress;
use \Payer\Sdk\Resource\Challenge;
use \Psr\Log\LoggerInterface as logger;

/**
 * Class Getaddress
 *
 * @package Payer\Checkout\Controller\Checkout
 */
class RequestAddress
{
    protected $payerCheckoutModel;
    protected $payerApi;
    protected $logger;
    protected $apiGetAddress;
    protected $apiChallenge;

    /**
     * RequetAddress constructor.
     *
     * @param \Psr\Log\LoggerInterface       $logger
     * @param \Payer\Checkout\Model\Api\Init $payerApi
     */
    public function __construct(
        logger   $logger,
        payerApi $payerApi
    )
    {
        $this->logger   = $logger;
        $this->payerApi = $payerApi;
    }

    /**
     * Request Data from API and return as array.
     *
     * @param string $in
     * @param string $zip
     *
     * @return array
     */
    public function getData($in, $zip)
    {
        try {
            $gateway    = $this->payerApi->setupClient();
            $getAddress = new apiGetAddress($gateway);
            $challenge  = new Challenge($gateway);
            $challenge  = (array)$challenge->create();

            if (!isset($challenge['challenge_token'])) {

                return [
                    'errorMessage'   => 'Could not get access token.',
                    'httpStatusCode' => 401
                ];
            }

            $data       = [
                'identity_number' => (string)$in,
                'zip_code'        => (string)$zip,
                'challenge_token' => (string)$challenge['challenge_token'],
            ];

            $getAddressResponse = (array)$getAddress->create($data);

            return $getAddressResponse;
        } catch (PayerException $e) {
            $this->logger->error($e->getMessage());

            return [
                'errorMessage'   => $e->getMessage(),
                'httpStatusCode' => 401
            ];
        }
    }
}
