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

namespace PicPay\Checkout\Gateway\Response\CreditCard;

use Magento\Sales\Api\Data\TransactionInterface;
use PicPay\Checkout\Gateway\Response\PaymentsHandler;
use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Helper\Data;
use PicPay\Checkout\Helper\Order as HelperOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TransactionHandler extends PaymentsHandler implements HandlerInterface
{
    /** @var SessionManagerInterface */
    protected $session;

    /** @var Data */
    protected $helper;

    /** @var \PicPay\Checkout\Helper\Order  */
    protected $helperOrder;

    /** @var Api */
    protected $api;

    public function __construct(
        SessionManagerInterface $session,
        Data $helper,
        HelperOrder $helperOrder,
        Api $api
    ) {
        $this->session = $session;
        $this->helper = $helper;
        $this->helperOrder = $helperOrder;
        $this->api = $api;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        list($paymentData, $transaction) = $this->validateResponse($handlingSubject, $response);

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();
        $payment = $this->helperOrder->updateDefaultAdditionalInfo($payment, $transaction);
        $payment = $this->helperOrder->updatePaymentAdditionalInfo($payment, $transaction['transactions'], 'credit');

        if (
            $transaction['chargeStatus'] == HelperOrder::STATUS_PRE_AUTHORIZED
            || $transaction['chargeStatus'] == HelperOrder::STATUS_CHARGE_PRE_AUTHORIZED
        ) {
            $payment->getOrder()->setState('new');
            $payment->setSkipOrderProcessing(true);
        }

        $merchantChargeId =  $transaction['merchantChargeId'] ?? $payment->getOrder()->getPicpayMerchantId();
        $payment->getOrder()->setData('picpay_charge_id', $merchantChargeId);
    }
}
