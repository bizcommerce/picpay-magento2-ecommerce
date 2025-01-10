<?php

namespace PicPay\Checkout\Gateway\Request\Tds;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;

class EnrollmentRequest extends TdsRequest implements BuilderInterface
{

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var Payment $payment */
        $payment = $buildSubject['payment'];

        $quote = $buildSubject['quote'];
        $request = $this->getEnrollmentTransactions($quote, $buildSubject, $payment->getAdditionalData());
        return ['request' => $request, 'client_config' => ['store_id' => $quote->getStoreId()]];
    }

    /**
     * @param Quote $quote
     * @param $paymentData
     * @param $additionalData
     * @return array
     */
    protected function getEnrollmentTransactions(Quote $quote, $paymentData, $additionalData): array
    {
        return [
            'chargeId' => $paymentData['chargeId'],
            'customer' => $this->getTdsCustomerData($quote),
            'browser' => $this->getBrowserData($additionalData['browser_data']),
            'transactions' => $this->getTdsTransactionInfo($quote, $paymentData['amount'])
        ];
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getTdsCustomerData(Quote $quote): array
    {
        $payment = $quote->getPayment();
        $taxvat = (string) $payment->getAdditionalInformation('picpay_customer_taxvat');

        $address = $quote->getBillingAddress();
        $fullName = $this->getCustomerFullName($quote);
        $phoneNumber = $this->helper->formatPhoneNumber($address->getTelephone());

        $customerData = [
            'name' => $fullName,
            'email' => $quote->getCustomerEmail(),
            'documentType' => $this->getDocumentType($taxvat),
            'document' => $taxvat,
            'phone' => $phoneNumber,
        ];

        if ($quote->getCustomerDob()) {
            $customerData['birth_date'] = $this->helper->formatDate($quote->getCustomerDob());
        }

        return $customerData;
    }

    /**
     * @param $data
     * @return array
     */
    protected function getBrowserData($data)
    {
        $browserData = [
            'httpAcceptBrowserValue' => $_SERVER['HTTP_ACCEPT'] ,
            'httpAcceptContent' => $_SERVER['HTTP_ACCEPT'] ,
        ];
        return array_merge($browserData, $data);
    }

    /**
     * @param Quote $quote
     * @param float $orderAmount
     * @return array
     */
    protected function getTdsTransactionInfo(Quote $quote, float $orderAmount): array
    {
        $cardData = $this->getCardData($quote);
        unset($cardData['cardType']);

        $cardData['number'] = $cardData['cardNumber'];
        unset($cardData['cardNumber']);

        $transactionInfo = [
            'amount' => $orderAmount * 100,
            'card' => $cardData
        ];
        return [$transactionInfo];
    }
}
