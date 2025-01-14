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

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class PaymentsHandler
{
    /**
     * @param array $handlingSubject
     * @param array $response
     * @return array
     * @throws LocalizedException
     */
    public function validateResponse(array $handlingSubject, array $response): array
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException(__('Payment data object should be provided'));
        }

        if (!isset($response['status_code']) || $response['status_code'] >= 300) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }

        $transaction = $response['transaction'];
        if (
            (!isset($transaction['merchantChargeId']) && !isset($transaction['id']))
            || !isset($transaction['chargeStatus'])
        ) {
            throw new LocalizedException(__('There was an error processing your request.'));
        }
        return array($handlingSubject['payment'], $transaction);
    }

}
