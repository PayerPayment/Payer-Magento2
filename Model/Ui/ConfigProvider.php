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
use Magento\Framework\Currency;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Payer\Checkout\Gateway\Config\Config;
use Magento\Checkout\Model\Session;
use Payer\Checkout\Model\Config\Api\Configuration;
use Magento\Framework\Pricing\Helper\Data as CurrencyHelper;


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

    const INITIAL_FEE       = 0;
    const INTEREST          = 1;
    const MONTHLY_FEE       = 2;

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
    protected $session;
    protected $currency;

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Framework\Escaper                         $escaper
     * @param \Magento\Framework\View\Asset\Repository           $assetRepository
     * @param \Magento\Payment\Helper\Data                       $paymentHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session                    $session
     * @param \Magento\Framework\UrlInterface                    $urlBuilder
     * @param \Magento\Framework\Pricing\Helper\Data             $currency
     */
    public function __construct(
        Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Session $session,
        UrlInterface $urlBuilder,
        CurrencyHelper $currency
    ) {
        $this->escaper          = $escaper;
        $this->assetRepository  = $assetRepository;
        $this->paymentHelper    = $paymentHelper;
        $this->scopeConfig      = $scopeConfig;
        $this->urlBuilder       = $urlBuilder;
        $this->session          = $session;
        $this->currency         = $currency;
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
                switch ($method) {
                    case 'payer_checkout_installment':
                        $data[$method]['installmentOptions'] = $this->calculateInstallmentOptions();
                    break;
                    default: break;
                }
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
        $configIcon = $this->scopeConfig->getValue(
            "payment/{$method}/developers/icon_url",
            ScopeInterface::SCOPE_STORE
        );
        $configIcon = !empty(trim($configIcon)) ? $configIcon : null;
        $cdnBase = 'https://raw.githubusercontent.com/PayerPayment/Payer-ImagePack/master/icons/checkout/';

        $icons = [
            self::BANK_CODE => [
                [
                    'label' => 'Bank',
                    'url'   => $configIcon ?? $cdnBase . '2018/payer-icon-payment_method-bank.png',
                ],
            ],
            self::CARD_CODE => [
                [
                    'label' => 'Card1',
                    'url'   => $configIcon ?? $cdnBase . '2018/payer-icon-payment_method-card_01.png',
                ],
            ],
            self::INSTALLMENT_CODE => [
                [
                    'label' => 'Installment',
                    'url'   => $configIcon ?? $cdnBase . '2018/payer-icon-payment_method-collector.png',
                ],
            ],
            self::INVOICE_CODE => [
                [
                    'label' => 'Invoice',
                    'url'   => $configIcon ?? $cdnBase . '2018/payer-icon-payment_method-invoice.png',
                ],
            ],
            self::SWISH_CODE => [
                [
                    'label' => 'Swish',
                    'url'   => $configIcon ?? $cdnBase . '2018/payer-icon-payment_method-swish.png',
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

    /**
     *
     * @return array
     */
    protected function calculateInstallmentOptions()
    {
        $returnData     = [];
        $quote          = $this->session->getQuote();
        $grandTotal     = $quote->getGrandTotal();

        $minimalInteraction = $this->scopeConfig->getValue(
            'payment/payer_checkout_installment/interaction_minimal',
            ScopeInterface::SCOPE_STORE
        );

        if ($grandTotal > 50000 || !$minimalInteraction) {

            return $returnData;
        }

        $data = [
            3  => [95, 0, 29],
            6  => [195, 0, 29],
            12 => [295, 0, 29],
            24 => [295, 9.95, 29],
            36 => [295, 9.95, 29]
        ];

        $monthly = null;
        foreach ($data as $k => $months) {
            $interest            = ($months[self::INTEREST] / 100) / 12;
            $fee                 = $months[self::MONTHLY_FEE];
            $oneTimeFee          = $months[self::INITIAL_FEE];
            $monthly[$k]['text'] = '';
            $oneTimeFeeFormatted = $this->currency->currency(
                ($months[self::INTEREST])?$months[self::INITIAL_FEE]:0,
                true,
                false
            );

            $monthly[$k]['months'] = $k;
            if (0 === ($months[self::INTEREST])) {
                $monthly[$k]['value'] = round((($grandTotal + $oneTimeFee + ($fee * $k))) / $k);
            } else {
                $monthly[$k]['text']  = __(
                    sprintf(
                        "A setup fee of% s is charged at first payment and is therefore not included in the monthly calculation.",
                        $oneTimeFeeFormatted
                    )
                );
                $monthly[$k]['value'] = round($grandTotal * ($interest / (pow((1 + $interest), $k) - 1))
                                              + $grandTotal * $interest + $fee);
            }
        }
        foreach ($monthly as $months => $values) {
            $additionalText = $values['text'];
            $cost           = $values['value'];
            $terms          = ($data[$months][self::INTEREST]) ?__('annual instalment'):__('interest free');
            $formattedCost = $this->currency->currency($cost, true, false);
            $returnData[] = [
                'value'      => $values['months'],
                'label'      => __("%1 months, %2 / month, %3", $months, $formattedCost, $terms),
                'additional' => $additionalText,
            ];
        }

        return $returnData;

    }
}
