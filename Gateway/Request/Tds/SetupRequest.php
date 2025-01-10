<?php

namespace PicPay\Checkout\Gateway\Request\Tds;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use PicPay\Checkout\Gateway\Http\Client\Api\Tds;
use PicPay\Checkout\Gateway\Request\PaymentsRequest;
use PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider;

class SetupRequest extends TdsRequest implements BuilderInterface
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

        $quote = $buildSubject['quote'];
        $request = $this->getSetupTransactions($quote, $buildSubject['amount']);
        return ['request' => $request, 'client_config' => ['store_id' => $quote->getStoreId()]];
    }

    protected function getSetupTransactions(Quote $quote, float $amount): array
    {
        return [
            'paymentSource' => 'GATEWAY',
            'transactions' => $this->getTdsTransactionInfo($quote, $amount)
        ];
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

        unset($cardData['cardType']);
        $transactionInfo = [
            'amount' => $orderAmount * 100,
            'paymentType' => 'CREDIT',
            'card' => $cardData
        ];
        return [$transactionInfo];
    }
}
