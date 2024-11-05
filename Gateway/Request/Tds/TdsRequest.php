<?php

namespace PicPay\Checkout\Gateway\Request\Tds;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use PicPay\Checkout\Gateway\Request\PaymentsRequest;
use PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider;

class TdsRequest extends PaymentsRequest
{
    protected function getSetupTransactions(Quote $quote, float $amount): array
    {
        return [
            'paymentSource' => 'GATEWAY',
            'transactions' => $this->getTdsTransactionInfo($quote, $amount)
        ];
    }

    protected function getCardData(Quote $quote): array
    {
        $payment = $quote->getPayment();
        $installments = (int) $payment->getAdditionalInformation('cc_installments') ?: 1;
        $taxvat = (string) $payment->getAdditionalInformation('picpay_customer_taxvat');
        return [
            'cardType' => ConfigProvider::DEFAULT_TYPE,
            'cardNumber' => $payment->getCcNumber(),
            'cvv' => $payment->getCcCid(),
            'brand' => 'MASTERCARD',
            'cardholderName' => $payment->getCcOwner(),
            'cardholderDocument' => $this->helper->digits($taxvat),
            'expirationMonth' => (int) $payment->getCcExpMonth(),
            'expirationYear' => (int) $payment->getCcExpYear(),
            'installmentNumber' => $installments,
            'installmentType' => $installments > 1 ? 'MERCHANT' : 'NONE',
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
     * @param float $orderAmount
     * @return array[]
     * @throws \Exception
     */
    protected function getTdsTransactionInfo(Quote $quote, float $orderAmount): array
    {
        $cardData = $this->getCardData($quote);
        $response = $this->api->card()->execute($cardData);

        if (isset($response['response']) && isset($response['response']['cardId'])) {
            $cardData['cardId'] = $response['response']['cardId'];
        }
        $test['cardId'] = $cardData['cardId'];
        $test['billingAddress'] = $cardData['billingAddress'];

        $transactionInfo = [
            'amount' => $orderAmount * 100,
            'paymentType' => 'CREDIT',
            'card' => $test
        ];
        return [$transactionInfo];
    }

    protected function getCustomerFullName(Quote $quote): string
    {
        $firstName = $quote->getCustomerFirstname();
        $lastName = $quote->getCustomerLastname();
        return $firstName . ' ' . $lastName;
    }
}
