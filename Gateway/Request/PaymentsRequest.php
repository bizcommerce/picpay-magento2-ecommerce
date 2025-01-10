<?php

/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 *
 */

namespace PicPay\Checkout\Gateway\Request;

use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Gateway\Request\Tds\AuthorizationRequest;
use PicPay\Checkout\Helper\Data;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;

class PaymentsRequest
{
    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var AuthorizationRequest
     */
    protected $authorizationRequest;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var string
     */
    protected $currencyCode;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(
        ManagerInterface $eventManager,
        Data $helper,
        DateTime $date,
        DateTime $dateTime,
        ConfigInterface $config,
        CustomerSession $customerSession,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        Api $api,
        AuthorizationRequest $authorizationRequest
    ) {
        $this->eventManager = $eventManager;
        $this->helper = $helper;
        $this->date = $date;
        $this->dateTime = $dateTime;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->api = $api;
        $this->authorizationRequest = $authorizationRequest;
    }

    protected function getTransactions(Order $order, float $amount): array
    {
        return [
            'paymentSource' => 'GATEWAY',
            'customer' => $this->getCustomerData($order),
            'transactions' => $this->getTransactionInfo($order, $amount),
            'deviceInformation' => $this->getDeviceInformation($order),
            'sourceId' => $this->helper->getStoreName(),
            'lateCapture' => $this->helper->isLateCapture(),
        ];
    }

    /**
     * @param Order $order
     * @param float $orderAmount
     * @return array
     */
    protected function getTransactionInfo(Order $order, float $orderAmount): array
    {
        $transactionInfo = [
            'amount' => $orderAmount * 100,
            'softDescriptor' => $this->helper->getSoftDescriptor(),
            'transactionId' => $order->getIncrementId()
        ];
        return [$this->getPaymentMethodData($order, $transactionInfo)];
    }

    protected function getPaymentMethodData(Order $order, array $transactionInfo): array
    {
        return $transactionInfo;
    }

    protected function getDeviceInformation(Order $order): array
    {
        return [
            'ip' => $order->getRemoteIp(),
            'id' => $this->helper->getUserAgent(),
            'sessionId' => $this->helper->getSessionId()
        ];
    }

    public function getDiscountAmount(Order $order, $orderAmount): float
    {
        $discountAmount = (float) $order->getDiscountAmount();
        $transactionAmount = $order->getBaseSubtotal() + $order->getShippingAmount() + $discountAmount;
        if ($transactionAmount > $orderAmount) {
            $discountAmount = $transactionAmount - $orderAmount;
        }
        return $discountAmount;
    }

    protected function getPriceAdditional(Order $order, float $orderAmount): float
    {
        $priceAdditional = 0;
        $transactionAmount = $order->getBaseSubtotal() + $order->getShippingAmount() + $order->getDiscountAmount();
        if ($transactionAmount < $orderAmount) {
            $priceAdditional = $orderAmount - $transactionAmount;
        }
        return (float) $priceAdditional;
    }

    public function getCustomerData(Order $order): array
    {
        $customerTaxVat = $this->getCustomerTaxVat($order);

        $address = $order->getBillingAddress();
        $fullName = $this->getCustomerName($order);
        $phoneNumber = $this->helper->formatPhoneNumber($address->getTelephone());

        $customerData = [
            'name' => $fullName,
            'email' => $order->getCustomerEmail(),
            'documentType' => $this->getDocumentType($customerTaxVat),
            'document' => $customerTaxVat,
            'phone' => $phoneNumber
        ];

        if ($order->getCustomerDob()) {
            $customerData['birth_date'] = $this->helper->formatDate($order->getCustomerDob());
        }

        return $customerData;
    }

    protected function getDocumentType(string $customerTaxVat): string
    {
        $taxVat = preg_replace('/[^0-9]/', '', $customerTaxVat);

        if (strlen($taxVat) == 14) {
            return 'CNPJ';
        } else if (strlen($taxVat) == 11) {
            return 'CPF';
        }

        return 'PASSPORT';
    }

    protected function getCustomerTaxVat(Order $order): string
    {
        $customerTaxVat = $order->getBillingAddress()->getVatId() ?: $order->getCustomerTaxvat();
        $picpayCustomerTaxVat = $order->getPayment()->getAdditionalInformation('picpay_customer_taxvat');
        if ($picpayCustomerTaxVat) {
            $customerTaxVat = $picpayCustomerTaxVat;
        }
        return $this->helper->digits($customerTaxVat);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getBillingAddress(Order $order): array
    {
        $billingAddress = $order->getBillingAddress();
        $number = $billingAddress->getStreetLine($this->getStreetField('number')) ?: 0;
        $complement = $billingAddress->getStreetLine($this->getStreetField('complement'));
        $address = [
            'street' => $billingAddress->getStreetLine($this->getStreetField('street')),
            'number' => $number,
            'neighborhood' => $billingAddress->getStreetLine($this->getStreetField('district')),
            'city' => $billingAddress->getCity(),
            'state' => $billingAddress->getRegionCode(),
            'country' => $billingAddress->getCountryId(),
            'zipCode' => $this->helper->clearNumber($billingAddress->getPostcode()),
        ];

        if ($complement) {
            $address['complement'] = $complement;
        }

        return $address;
    }

    public function getStreetField(string $config): int
    {
        return (int) $this->helper->getConfig($config, 'address', 'picpay_checkout') + 1;
    }

    protected function getCustomerName(Order $order): string
    {
        $firstName = $order->getCustomerFirstname();
        $lastName = $order->getCustomerLastname();
        return $order->getCustomerName() ?: $firstName . ' ' . $lastName;
    }
}
