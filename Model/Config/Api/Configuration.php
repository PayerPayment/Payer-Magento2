<?php
/**
 * Api config
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Model\Config\Api;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Payer\Checkout\Model\Ui\ConfigProvider;

/**
 * Payer configuration provider that uses values from system config
 *
 * @package Payer\Checkout\Model
 */
class Configuration
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var EncryptorInterface
     */
    protected $crypt;

    /**
     * @var \Magento\Framework\Locale\Resolver;
     */
    protected $locale;

    protected $storeManager;

    /**
     * Configuration constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
     * @param \Magento\Framework\UrlInterface                       $urlBuilder
     * @param \Magento\Framework\Locale\Resolver                    $locale
     * @param \Magento\Framework\Encryption\EncryptorInterface      $crypt
     * @param \Magento\Store\Model\StoreManager                     $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        Resolver $locale,
        EncryptorInterface $crypt,
        StoreManager $storeManager
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->urlBuilder   = $urlBuilder;
        $this->locale       = $locale;
        $this->crypt        = $crypt;
        $this->storeManager = $storeManager;
    }

    /**
     * Get config value
     *
     * @param $type
     * @param $key
     * @param bool $storedEncrypted
     * @return mixed|string
     */
    protected function getConfigValue($method, $key, $storedEncrypted = false)
    {
        $method  = $this->payerCodeToConfigCode($method);
        $fullKey = strtolower("payment/{$method}/{$key}");
        $value   = $this->scopeConfig->getValue($fullKey, ScopeInterface::SCOPE_STORE);

        if ($storedEncrypted) {
            $value = $this->crypt->decrypt($value);
        }

        return $value;
    }

    /**
     * Get Payer Checkout method code from Magneto payment method code
     *
     * @param $methodCode Magento payment method code
     * @return string Payer Checkout constant for the method
     * @throws \Exception If $methodCode isn't valid
     */
    public function paymentCodeToPayerCode($methodCode)
    {
        $codes = [
            ConfigProvider::SWISH_CODE          => 'swish',
            ConfigProvider::BANK_CODE           => 'bank',
            ConfigProvider::CARD_CODE           => 'card',
            ConfigProvider::INSTALLMENT_CODE    => 'installment',
            ConfigProvider::INVOICE_CODE        => 'invoice',
        ];

        if (!isset($codes[$methodCode])) {

            throw new \Exception("Invalid method code");
        }

        return $codes[$methodCode];
    }

    /**
     * Map payer internal payment method code to our method codes
     *
     * @param $payerCode
     * @return string
     * @throws \Exception
     */
    public function payerCodeToConfigCode($payerCode)
    {
        if ($payerCode == 'payer_settings/api' ||
            $payerCode == 'payer_settings/advanced_callback'
        ) {

            return $payerCode;
        }

        if (strpos($payerCode, 'payer_checkout_') !== false) {

            return $payerCode;
        }

        $codes = [
            'swish'         => ConfigProvider::SWISH_CODE,
            'bank'          => ConfigProvider::BANK_CODE,
            'card'          => ConfigProvider::CARD_CODE,
            'installment'   => ConfigProvider::INSTALLMENT_CODE,
            'invoice'       => ConfigProvider::INVOICE_CODE,
        ];

        if (!isset($codes[$payerCode])) {

            throw new \Exception('Payment method config not found');
        }

        return $codes[$payerCode];
    }

    /**
     * Check if payment method is active
     *
     * @param string $type Payment method type, like 'invoice', _not_ payment method code.
     *
     * @return bool
     */
    public function isActive($method)
    {
        $value = $this->getConfigValue($method, 'active');

        return (bool) $value;
    }

    /**
     * Check for test mode
     *
     * @param $type
     * @return bool
     */
    public function isTestMode($method)
    {
        $value = $this->getConfigValue($method, 'test_mode');

        return (bool) $value;
    }

    public function getAgentId()
    {
        $value = $this->getConfigValue('payer_settings/api', 'agent_id');

        return $value;
    }

    public function getPostKeys()
    {
        $key1 = $this->getConfigValue('payer_settings/api', 'key_1', $encrypted = false);
        $key2 = $this->getConfigValue('payer_settings/api', 'key_2', $encrypted = false);

        return [
            'key_1' => $key1,
            'key_2' => $key2,
        ];
    }

    public function getSoapCredentials()
    {
        $username = $this->getConfigValue('payer_settings/api', 'soap_username', $encrypted = false);
        $password = $this->getConfigValue('payer_settings/api', 'soap_password', $encrypted = false);

        if ($username && $password) {

            return [
                'username' => $username,
                'password' => $password,
            ];
        }

        return false;
    }

    public function getCallbackUrls($method, $referenceId)
    {
        $urls = [
            'authorize' => $this->urlBuilder->getUrl('payer/checkout/authorize'),
            'settle'    => $this->urlBuilder->getUrl('payer/checkout/settle'),
            'redirect'  => $this->urlBuilder->getUrl(
                'payer/checkout/fail',
                [
                    '_query' => [
                        'payer_merchant_reference_id' => $referenceId,
                        'payer_callback_type'         => 'cancel'
                    ]
                ]
            ),
            'success'   => $this->urlBuilder->getUrl(
                'payer/checkout/success',
                [
                    '_query' => [
                        'payer_merchant_reference_id' => $referenceId,
                        'payer_callback_type'         => 'success'
                    ]
                ]
            )
        ];

        return $urls;
    }

    public function getISO639language()
    {
        $locale     = $this->locale->getLocale();
        $language   = substr($locale, 0, 2);

        return $language;
    }

    /**
     * Get baseURL.
     *
     * @param  string $type
     *
     * @return mixed
     */
    public function getBaseUrl($type = '')
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get CaptureOnConfirmation.
     *
     * @param  string $method
     *
     * @return bool
     */
    public function getCaptureOnConfirmation($method)
    {
        $autoCapture = [
            ConfigProvider::SWISH_CODE          => true,
            ConfigProvider::BANK_CODE           => true,
            ConfigProvider::INVOICE_CODE        => false,
            ConfigProvider::INSTALLMENT_CODE    => false,
        ];

        if (array_key_exists($method, $autoCapture)) {

            return $autoCapture[$method];
        }

        return (bool)$this->getConfigValue($method, 'order_confirmation_capture');
    }

    /**
     * is Interaction Mode minimal set?
     *
     * @param  string $method
     *
     * @return bool
     */
    public function isInteractionMinimal($method)
    {
        return (bool)$this->getConfigValue($method, 'interaction_minimal');
    }

    /**
     * Get status for New order
     *
     * @return string
     */
    public function getNewOrderStatus($method)
    {
        return $this->getConfigValue($method, 'order_status');
    }

    /**
     * Get status for acknowledged order
     *
     * @return string
     */
    public function getAcknowledgedOrderStatus($method)
    {
        return $this->getConfigValue($method, 'acknowledged_order_status');
    }

    /**
     * Get status for acknowledged order
     *
     * @return string
     */
    public function getAuthOrderStatus($method)
    {
        return $this->getConfigValue($method, 'auth_order_status');
    }

    /**
     * @return bool
     */
    public function getIsProxy()
    {
        return (bool)$this->getConfigValue('payer_settings/advanced_callback', 'is_proxy');
    }

    /**
     * @return bool
     */
    public function getSkipIpValidation()
    {
        return (bool)$this->getConfigValue('payer_settings/advanced_callback', 'skip_ip_validation');
    }
}
