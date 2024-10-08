<?php

namespace PicPay\Checkout\Gateway\Request\Wallet;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PicPay\Checkout\Gateway\Request\PaymentsRequest;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class TransactionRequest extends PaymentsRequest implements BuilderInterface
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
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var Payment $payment */
        $payment = $buildSubject['payment']->getPayment();
        $order = $payment->getOrder();

        $request = $this->getTransactions($order, $buildSubject['amount']);
        unset($request['lateCapture']);
        return ['request' => $request, 'client_config' => ['store_id' => $order->getStoreId()]];
    }

    /**
     * @param Order $order
     * @param array $transactionInfo
     * @return array
     */
    protected function getPaymentMethodData(Order $order, array $transactionInfo): array
    {
        $transactionInfo['paymentType'] = 'WALLET';
        return $transactionInfo;
    }
}
