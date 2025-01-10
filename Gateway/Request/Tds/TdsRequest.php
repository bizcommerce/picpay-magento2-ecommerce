<?php

namespace PicPay\Checkout\Gateway\Request\Tds;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use PicPay\Checkout\Gateway\Request\PaymentsRequest;
use PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider;

class TdsRequest extends PaymentsRequest
{
    /**
     * @param Quote $quote
     * @return array
     */
    protected function getCardData(Quote $quote): array
    {
        $payment = $quote->getPayment();
        $taxvat = (string) $payment->getAdditionalInformation('picpay_customer_taxvat');

        return [
            'cardType' => 'CREDIT',
            'cardNumber' => $payment->getCcNumber(),
            'cvv' => $payment->getCcCid(),
            'brand' => $this->getCardType($payment->getCcType()),
            'cardholderName' => $payment->getCcOwner(),
            'cardholderDocument' => $this->helper->digits($taxvat),
            'expirationMonth' => (int) $payment->getCcExpMonth(),
            'expirationYear' => (int) $payment->getCcExpYear(),
            'billingAddress' => $this->getQuoteBillingAddress($quote)
        ];
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getQuoteBillingAddress(Quote $quote): array
    {
        $billingAddress = $quote->getBillingAddress();
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

    /**
     * @param Quote $quote
     * @return string
     */
    protected function getCustomerFullName(Quote $quote): string
    {
        $firstName = $quote->getCustomerFirstname();
        $lastName = $quote->getCustomerLastname();
        return $firstName . ' ' . $lastName;
    }

    /**
     * @param $type
     * @return string
     */
    protected function getCardType($type)
    {
        $types = [
            'MC' => 'MASTERCARD',
            'VI' => 'VISA',
            'ELO' => 'ELO',
        ];

        return $types[$type] ?? '';
    }
}
