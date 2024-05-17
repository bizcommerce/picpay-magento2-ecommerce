<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 */

namespace PicPay\Checkout\Gateway\Request\CreditCard;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PicPay\Checkout\Gateway\Request\PaymentsRequest;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider;

class TransactionRequest extends PaymentsRequest implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var Payment $payment */
        $payment = $buildSubject['payment']->getPayment();
        $order = $payment->getOrder();

        $this->validateCard($order, $payment);
        $request = $this->getTransactions($order, $buildSubject['amount']);

        return ['request' => $request, 'client_config' => ['store_id' => $order->getStoreId()]];
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @return void
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function validateCard(Order $order, Payment $payment): void
    {
        $cardData = $this->getCardData($order, $payment);
        $cardData['cardType'] = ConfigProvider::DEFAULT_TYPE;

        $this->api->logRequest($cardData, 'validate-card');
        $response = $this->api->card()->execute($cardData);
        $this->api->logResponse($response, 'validate-card');

        $this->api->saveRequest($cardData, $response, $response['status'], 'validate-card');

        if ($response['status'] >= 300) {
            throw new LocalizedException(__('There was an error with your card data, verify and try again.'));
        }
    }

    protected function getCardData(Order $order, Payment $payment): array
    {
        return [
            'cardNumber' => $payment->getCcNumber(),
            'cvv' => $payment->getCcCid(),
            'cardholderDocument' => $this->getCustomerTaxVat($order),
            'expirationMonth' => (int) $payment->getCcExpMonth(),
            'expirationYear' => (int) $payment->getCcExpYear()
        ];
    }

    protected function getPaymentMethodData(Order $order, array $transactionInfo): array
    {
        $transactionInfo['paymentType'] = 'CREDIT';
        $transactionInfo['credit'] = $this->getCardData($order, $order->getPayment());
        $transactionInfo['credit']['cardholderName'] = $this->getCustomerName($order);
        $transactionInfo['credit']['installmentNumber'] = $this->getInstallments($order);
        $transactionInfo['credit']['installmentType'] = $this->getInstallments($order) > 1 ? 'MERCHANT' : 'NONE';
        $transactionInfo['credit']['billingAddress'] = $this->getBillingAddress($order);

        return $transactionInfo;
    }

    protected function getInstallments($order): int
    {
        return (int) $order->getPayment()->getAdditionalInformation('cc_installments') ?: 1;
    }
}
