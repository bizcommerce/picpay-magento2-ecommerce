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

namespace PicPay\Checkout\Gateway\Response;

use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Helper\Order as HelperOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundHandler implements HandlerInterface
{
    /**
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * @var Api
     */
    protected $api;

    /**
     * constructor.
     * @param HelperOrder $helperOrder
     * @param Api $api
     */
    public function __construct(
        HelperOrder $helperOrder,
        Api $api
    ) {
        $this->helperOrder = $helperOrder;
        $this->api = $api;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
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

        if (isset($response['status_code']) && $response['status_code'] >= 300) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }

        $responseTransaction = $transaction['transaction'];

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentData->getPayment();
        $payment = $this->helperOrder->updateRefundedAdditionalInformation($payment, $responseTransaction);
        $payment->setAdditionalInformation('refunded', true);
    }
}
