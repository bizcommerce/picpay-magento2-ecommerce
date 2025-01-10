<?php

namespace PicPay\Checkout\Gateway\Request\Tds;

use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order;
use PicPay\Checkout\Helper\Data;

class AuthorizationRequest
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getRequest(Order $order): array
    {
        return [
            'chargeId' => $order->getPicpayChargeId(),
            'capture' => !$this->helper->isLateCapture(),
            'transactions' => $this->getAuthTransactionInfo($order)
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getAuthTransactionInfo(Order $order): array
    {
        $installments = (int) $order->getPayment()->getAdditionalInformation('cc_installments') ?: 1;

        $transactionInfo = [
            'installmentNumber' => $installments,
            'installmentType' => $installments > 1 ? 'MERCHANT' : 'NONE',
            'card' => $this->getCardData($order, $order->getPayment()),
        ];
        return [$transactionInfo];
    }


    protected function getCardData(Order $order, Payment $payment): array
    {
        return [
            'cvv' => $payment->getCcCid(),
            'cardholderAuthenticationId' => $order->getPicpayCardholderAuthId()
        ];
    }
}
