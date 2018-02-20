<?php
/**
 * Ui config provider. Handles frontend payment info
 *
 * @package Payer_Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Payer\Checkout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Payer\Checkout\Gateway\Config\Config;
use Payer\Checkout\Model\Config\Api\Configuration;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    protected $escaper;
    protected $assetRepository;
    protected $paymentHelper;

    const BANK_CODE         = 'payer_checkout_bank';
    const CARD_CODE         = 'payer_checkout_card';
    const INSTALLMENT_CODE  = 'payer_checkout_installment';
    const INVOICE_CODE      = 'payer_checkout_invoice';
    const SWISH_CODE        = 'payer_checkout_swish';

    const REDIRECT_URL      = 'payer/checkout/redirect';

    const XML_PATH_DISPLAY_CART_PAYMENT_HANDLING_FEE = 'tax/cart_display/payment_handling_fee';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * ConfigProvider constructor.
     *
     * @param Escaper $escaper
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder
    ) {
        $this->escaper          = $escaper;
        $this->assetRepository  = $assetRepository;
        $this->paymentHelper    = $paymentHelper;
        $this->scopeConfig      = $scopeConfig;
        $this->urlBuilder       = $urlBuilder;
    }

    /**
     * Get configuration
     */
    public function getConfig()
    {
        $data        = [];
        $redirectUrl =  $this->urlBuilder->getUrl(self::REDIRECT_URL);
        $methods     = [
            self::INVOICE_CODE,
            self::INSTALLMENT_CODE,
            self::CARD_CODE,
            self::BANK_CODE,
            self::SWISH_CODE,
        ];

        foreach ($methods as $method) {
            if ($this->getMethod($method)->isActive()) {
                $data[$method]['icons'] = $this->getIcons($method);
                $data[$method]['redirectOnSuccessUrl'] = $redirectUrl;
            }
        }

        return [
            'payment' => $data,
            'reviewHandlingFeeDisplayMode' => $this->getHandlingFeeDisplayMode(),
        ];
    }

    /**
     * Get handling fee display mode
     *
     * @param null $store
     * @return string
     */
    protected function getHandlingFeeDisplayMode($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_CART_PAYMENT_HANDLING_FEE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        if ($value == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH) {
            $displayMode = 'both';
        } elseif ($value == \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX) {
            $displayMode = 'excluding';
        } else {
            $displayMode = 'including';
        }

        return $displayMode;
    }

    /**
     * Get payment method for code
     *
     * @param $code
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getMethod($code)
    {
        return $this->paymentHelper->getMethodInstance($code);
    }

    /**
     * Get list of icons that should be displayed together with the payment method
     *
     * @return array List of icons
     */
    protected function getIcons($method)
    {
        $icons = [
            self::BANK_CODE => [
                [
                    'label' => 'Bank',
                    'url'   => 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/2018/payer-icon-payment_method-bank.png',
                ],
            ],
            self::CARD_CODE => [
                [
                    'label' => 'Card1',
                    'url'   => 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/2018/payer-icon-payment_method-card_01.png',
                ],
                //[
                    //'label' => 'Card2',
                    //'url'   => 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/2018/payer-icon-payment_method-card_02.png',
                //],
            ],
            self::INSTALLMENT_CODE => [
                [
                    'label' => 'Installment',
                    'url'   => 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/2018/payer-icon-payment_method-collector.png',
                ],
            ],
            self::INVOICE_CODE => [
                [
                    'label' => 'Invoice',
                    'url'   => 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/2018/payer-icon-payment_method-invoice.png',
                ],
            ],
            self::SWISH_CODE => [
                [
                    'label' => 'Swish',
                    'url'   => 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/2018/payer-icon-payment_method-swish.png',
                ],
            ],
        ];

        if (!isset($icons[$method])) {

            return [];
        }

        $icons = $icons[$method];
        $result = [];
        foreach ($icons as $icon) {
            $result[] = [
                'label' => __($icon['label']),
                'url' => $icon['url'],
            ];
        }

        return $result;
    }
}
