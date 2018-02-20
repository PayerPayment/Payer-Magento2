<?php

namespace Payer\Checkout\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Sales\Api\Data\TransactionInterface;
use Payer\Checkout\Gateway\Request\CustomerBuilder;
use Payer\Checkout\Helper\Transaction as transactionHelper;
use Payer\Checkout\Model\Api\Init as PayerConfig;
use Payer\Checkout\Model\Config\Api\Configuration;
use Payer\Checkout\Model\Ui\ConfigProvider;

/**
 * Class BuildOrder
 *
 * @package Payer\Checkout
 * @module  Payer_Checkout
 * @author  Webbhuset <info@webbhuset.se>
 */
class BuildOrder implements \Payer\Checkout\Gateway\Request\OrderActionBuilderInterface
{
    protected $auth;
    protected $checkoutSession;
    protected $customerBuilder;
    protected $config;
    protected $transactionHelper;
    protected $nextLineNumber = 0;
    protected $orderRows = [];

    /**
     * BuildOrder constructor.
     *
     * @param \Payer\Checkout\Model\Api\Init                    $auth
     * @param \Magento\Checkout\Model\Session                   $session
     * @param \Payer\Checkout\Gateway\Request\CustomerBuilder   $customerBuilder
     * @param \Payer\Checkout\Model\Config\Api\Configuration    $config
     * @param \Payer\Checkout\Helper\Transaction                $transactionHelper
     */
    public function __construct(
        PayerConfig $auth,
        Session $session,
        CustomerBuilder $customerBuilder,
        Configuration $config,
        transactionHelper $transactionHelper
    )
    {
        $this->auth              = $auth;
        $this->checkoutSession   = $session;
        $this->customerBuilder   = $customerBuilder;
        $this->config            = $config;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     */
    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order          = $payment->getOrder();
        $paymentMethod  = $payment->getMethod();
        $payerMethod    = $this->config->paymentCodeToPayerCode($paymentMethod);

        try {
            $purchase   = new \Payer\Sdk\Resource\Purchase($this->auth->setupClient($paymentMethod));
            $address    = $order->getBillingAddress();

            $customer   = $this->customerBuilder->build($address);
            $this->addItems($order);
            $this->addShipping($order);
            $this->addHandlingFee($order);

            //To avoid order already being created, if you for example have
            //stageEnv/devEnv and ProductionEnv with quote id in same range.
            $orderId            = $order->getId();
            $allowedLength      = 16;
            $separator          = '_';
            $lengthOfHash       = $allowedLength - (strlen((string)$orderId) + strlen($separator));
            $hashedBaseUrl      = sha1($this->config->getBaseUrl());
            $referenceId        = substr($hashedBaseUrl, 0, $lengthOfHash) . $separator . $orderId;
            $paymentOptions     = $this->getPaymentOptions($paymentMethod);

            $orderData = [
                'payment' => [
                    'language'  => $this->config->getISO639language(),
                    'method'    => $payerMethod,
                    'url'       => $this->config->getCallbackUrls($paymentMethod, $referenceId),
                    'options'   => $paymentOptions,
                ],
                'purchase' => [
                    'charset'       => 'UTF-8',
                    'currency'      => $order->getOrderCurrencyCode(),
                    'description'   => $order->getIncrementId(),
                    'reference_id'  => $referenceId,
                    'test_mode'     => $this->config->isTestMode($paymentMethod),
                    'customer'      => $customer,
                    'items'         => $this->orderRows,
                ],
            ];

            $response = $purchase->getPostData($orderData);

            $payment->setAdditionalInformation('payer_create', $this->transactionHelper->flattenDataArray($orderData));
            $type = TransactionInterface::TYPE_ORDER;
            $this->transactionHelper->addTransaction($payment, $orderData, $type, false);

            $order->setExtOrderId($referenceId);
            $order->save();

            return $response;
        } catch (\Exception $e) {
            $this->checkoutSession->setPayerGotError($e->getMessage());

            return;
        }
    }

    /**
     * Go through the order and add orderItems to the invoiceOrder.
     *
     * @param  \Magento\Sales\Model\Order $order
     */
    protected function addItems($order)
    {
        $sortedItems = [];

        foreach ($order->getAllItems() as $item) {
            if ($item->getHasChildren() || !$item->getParentItemId()) {
                $sortedItems[$item->getId()]['item'] = $item;
            } else {
                $parentId = $item->getParentItemId();

                if (empty($sortedItems[$parentId])) {
                    $sortedItems[$parentId] = ['children' => []];
                }

                $sortedItems[$parentId]['children'][] = $item;
            }
            unset($item);
        }

        foreach ($sortedItems as $data) {
            $item = isset($data['item'])
                ? $data['item']
                : null;

            $children = isset($data['children'])
                ? $data['children']
                : [];

            if (!$item) {
                continue;
            }

            if ($item->isChildrenCalculated()) {
                foreach ($children as $child) {
                    $this->processItem($child, $item->getId());
                }
            } else {
                $this->processItem($item);
            }
        }
    }

    /**
     * Adding a row to the Order.
     *
     * @param \Magento\Sales\Model\Order\Item   $item
     * @param string                            =item_id | parent_item_id $prefix
     */
    protected function processItem($item, $prefix = '')
    {
        $itemRows = [];
        $qty = $item->getQtyOrdered() ?: $item->getQty();
        $qty = (float)$qty;
        if ($qty > 0) {
            if ($prefix) {
                $prefix = $prefix . '-';
            }

            $this->orderRows[] = [
                'type'                => 'freeform',
                'line_number'         => $this->nextLineNumber,
                'article_number'      => $prefix . $item->getSku(),
                'description'         => (string)$item->getName(),
                'unit_price'          => (float)$item->getPriceInclTax(),
                'unit_vat_percentage' => (float)$item->getTaxPercent(),
                'quantity'            => $qty,
            ];
            $this->nextLineNumber++;

            if ((float)$item->getDiscountAmount()) {
                $this->orderRows[] = [
                    'type'                => 'freeform',
                    'line_number'         => $this->nextLineNumber,
                    'article_number'      => substr(sprintf('discount-%s', $prefix . $item->getSku()), 0, 40),
                    'description'         => substr(sprintf('discount-%s', $item->getName()), 0, 40),
                    'unit_price'          => -(float)$item->getDiscountAmount(),
                    'unit_vat_percentage' => (float)$item->getTaxPercent(),
                    'quantity'            => 1,
                ];
                $this->nextLineNumber++;
            }
        }
    }

    /**
     * Add ShippingRow to Payer Order.
     *
     * @param  \Magento\Sales\Model\Order   $order
     *
     */
    protected function addShipping($order)
    {
        $methodTitle    = $order->getShippingDescription();
        $shippingTitle  = ($methodTitle)
            ? substr($methodTitle, 0, 40)
            : __('Shipping');

        $shippingExclTax     = (float)$order->getShippingAmount();
        $shippingIncTax      = (float)$order->getShippingInclTax();
        $shippingTax         = (float)$order->getShippingTaxAmount();
        $shippingTaxDiscount = (float)$order->getShippingDiscountTaxCompensationAmount();

        if ($shippingTaxDiscount > 0) {
            $shippingTax = $shippingTax + $shippingTaxDiscount;
        }

        if($shippingExclTax > 0 && $shippingTax > 0) {
            $vatPercent = ($shippingTax / $shippingExclTax) * 100;
        } else {
            $vatPercent = 0;
        }

        if ($shippingIncTax > 0){
            $this->orderRows[] = [
                'type'                  => 'freeform',
                'line_number'           => $this->nextLineNumber,
                'article_number'        => 'Shipping-fee',
                'description'           => $shippingTitle,
                'unit_price'            => $shippingIncTax,
                'unit_vat_percentage'   => $vatPercent,
                'quantity'              => 1,
            ];
            $this->nextLineNumber++;

            $shippingDiscount = (float)$order->getShippingDiscountAmount();
            if ($shippingDiscount > 0) {
                $this->orderRows[] = [
                    'type'                => 'freeform',
                    'line_number'         => $this->nextLineNumber,
                    'article_number'      => 'discount-Shipping-fee',
                    'description'         => substr(sprintf('discount-%s', $shippingTitle), 0, 40),
                    'unit_price'          => -$shippingDiscount,
                    'unit_vat_percentage' => $vatPercent,
                    'quantity'            => 1,
                ];
                $this->nextLineNumber++;
            }
        }
    }

    /**
     * Add handlingFeeRow to payer Order.
     *
     * @param Magento\Sales\Model\Order  $order
     */
    protected function addHandlingFee($order)
    {
        $handlingFeeExclTax = $order->getHandlingFeeAmount();
        $handlingFeeInclTax = $order->getHandlingFeeInclTax();
        $handlingFeeTax     = $order->getHandlingFeeTaxAmount();

        if ($handlingFeeExclTax > 0 && $handlingFeeTax > 0) {
            $vatPercent = ($handlingFeeTax / $handlingFeeExclTax) * 100;
        } else {
            $vatPercent = 0;
        }

        if ($handlingFeeInclTax > 0) {
            $this->orderRows[] = [
                'type'                  => 'freeform',
                'line_number'           => $this->nextLineNumber,
                'article_number'        => 'handling-fee',
                'description'           => 'Payment Handling Fee',
                'unit_price'            => (float)$handlingFeeInclTax,
                'unit_vat_percentage'   => $vatPercent,
                'quantity'              => 1,
            ];
            $this->nextLineNumber++;
        }
    }

    /**
     * get Payer Payment options.
     *
     * @param string $paymentMethod
     *
     * @return array
     */
    protected function getPaymentOptions($paymentMethod, $installmentMonths = null)
    {
        $options = [];

        switch ($paymentMethod) {
            case ConfigProvider::CARD_CODE:
                $autoCapture = $this->config->getCaptureOnConfirmation($paymentMethod);
                if(!$autoCapture) {
                    //$options['auth_only'] = true;
                }

                return $options;
            case ConfigProvider::INVOICE_CODE:
                $minimal = $this->config->isInteractionMinimal($paymentMethod);
                if($minimal) {
                    $options['interaction'] = 'minimal';
                }

                return $options;
            case ConfigProvider::INSTALLMENT_CODE:
                $minimal = $this->config->isInteractionMinimal($paymentMethod);
                if($minimal) {
                    $options['interaction'] = 'minimal';
                }
                if(isset($installmentMonths)) {
                    $options['installment_months'] = $installmentMonths;
                }

                return $options;
            default:

                return $options;
        }
    }
}
