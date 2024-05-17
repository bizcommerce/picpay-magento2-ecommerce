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
use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Helper\Data;
use PicPay\Checkout\Helper\Order as HelperOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TransactionHandler implements HandlerInterface
{
    /** @var \PicPay\Checkout\Helper\Order  */
    protected $helperOrder;

    /** @var Data */
    protected $helper;

    /** @var SessionManagerInterface */
    protected $session;

    /** @var Api */
    protected $api;

    public function __construct(
        HelperOrder $helperOrder,
        Data $helper,
        SessionManagerInterface $session,
        Api $api
    ) {
        $this->helperOrder = $helperOrder;
        $this->helper = $helper;
        $this->session = $session;
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
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException(__('Payment data object should be provided'));
        }

        /** @var PaymentDataObjectInterface $paymentData */
        $paymentData = $handlingSubject['payment'];
        $transaction = $response['transaction'];

        if (!isset($response['status_code']) || $response['status_code'] >= 300) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }

        if (!isset($transaction['merchantChargeId']) || !isset($transaction['chargeStatus'])) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentData->getPayment();
        $payment = $this->helperOrder->updateDefaultAdditionalInfo($payment, $transaction);
        $payment = $this->helperOrder->updateCcAdditionalInfo($payment, $transaction['transactions']);

        if ($transaction['chargeStatus'] == HelperOrder::STATUS_PRE_AUTHORIZED) {
            $payment->getOrder()->setState('new');
            $payment->setSkipOrderProcessing(true);
        }

        $payment->getOrder()->setData('picpay_charge_id', $transaction['merchantChargeId']);
    }

}
