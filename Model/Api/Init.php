<?php

namespace Payer\Checkout\Model\Api;

use Payer\Checkout\Model\Config\Api\Configuration;
use \Psr\Log\LoggerInterface as logger;

/**
 * Class Api
 *
 * @package Payer\Model\Api
 */
class Init
{
    protected $logger;
    protected $config;

    /**
     * Init constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param Payer\Checkout\Model\Config\Api\Configuration $config
     */
    public function __construct(
        logger $logger,
        Configuration $config
    )
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Setup new payer client with credentials.
     *
     * @param $method
     *
     * @return \Payer\Sdk\PayerGatewayInterface
     */
    public function setupClient($method)
    {
        $credentials = [
            'agent_id' => $this->config->getAgentId($method),
            'post'     => $this->config->getPostKeys($method),
        ];

        $soapCredentials = $this->config->getSoapCredentials($method);
        if ($soapCredentials) {
            $credentials['soap'] = $soapCredentials;
        }

        try {
            $gateway = \Payer\Sdk\Client::create($credentials);

            return $gateway;
        } catch (\Payer\Sdk\Exception\PayerException $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
