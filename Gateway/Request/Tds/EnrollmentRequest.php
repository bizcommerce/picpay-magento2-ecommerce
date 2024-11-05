<?php

namespace PicPay\Checkout\Gateway\Request\Tds;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use PicPay\Checkout\Gateway\Request\PaymentsRequest;
use PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider;

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
        $request = $this->getEnrollmentTransactions($quote, $buildSubject['chargeId'], $buildSubject['amount']);
        return ['request' => $request, 'client_config' => ['store_id' => $quote->getStoreId()]];
    }

    protected function getEnrollmentTransactions(Quote $quote, string $chargeId, float $amount): array
    {
        return [
            'chargeId' => $chargeId,
            'customer' => $this->getTdsCustomerData($quote),
            'browser' => $this->getBrowserData(),
            'transactions' => $this->getTdsTransactionInfo($quote, $amount),
            'shipping' => $this->getShipping($quote)
        ];
    }

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
//            'threeDomainSecureSettings' => $this->getTdsSecureSettings()
        ];

        if ($quote->getCustomerDob()) {
            $customerData['birth_date'] = $this->helper->formatDate($quote->getCustomerDob());
        }

        return $customerData;
    }

    protected function getBrowserData()
    {
        return [
            'httpAcceptBrowserValue' => $_SERVER['HTTP_ACCEPT'] ,
            'httpAcceptContent' => $_SERVER['HTTP_ACCEPT'] ,
            'httpBrowserLanguage' => '',
            'httpBrowserJavaEnabled' => '',
            'httpBrowserJavaScriptEnabled' => '',
            'httpBrowserColorDepth' => '',
            'httpBrowserScreenHeight' => '',
            'httpBrowserTimeDifference' => '',
            'userAgentBrowserValue' => '',
        ];
    }

    /**
     * @param Quote $quote
     * @param float $orderAmount
     * @return array
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
}
