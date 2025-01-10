<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 */

namespace PicPay\Checkout\Observer;

use PicPay\Checkout\Helper\Data;
use PicPay\Checkout\Helper\Installments;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Model\Quote\Payment;

class CreditCardAssignObserver extends AbstractDataAssignObserver
{
    /** @var Session  */
    protected $checkoutSession;

    /** @var Installments  */
    protected $installmentsHelper;

    /** @var Data */
    protected $helper;

    /** @var Json  */
    protected $json;

    public function __construct(
        Session $checkoutSession,
        Installments $installmentsHelper,
        Data $helper,
        Json $json
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->installmentsHelper = $installmentsHelper;
        $this->helper = $helper;
        $this->json = $json;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        /** @var array $additionalData */
        $additionalData = $data->getAdditionalData();

        if (!empty($additionalData) && isset($additionalData['cc_number'])) {
            $installments = $additionalData['installments'] ?? 1;
            $ccOwner = $additionalData['cc_owner'] ?? null;
            $ccType = $additionalData['cc_type'] ?? null;
            $ccLast4 = substr((string) $additionalData['cc_number'], -4);
            $ccBin = substr((string) $additionalData['cc_number'], 0, 6);
            $ccExpMonth = $additionalData['cc_exp_month'] ?? null;
            $ccExpYear = $additionalData['cc_exp_year'] ?? null;

            $this->checkoutSession->setData('picpay_installments', $installments);
            $quote = $this->checkoutSession->getQuote();
            $quote->setTotalsCollectedFlag(false)->collectTotals();

            /** @var Payment $paymentInfo */
            $paymentInfo = $this->readPaymentModelArgument($observer);

            $paymentInfo->addData([
                'cc_type' => $ccType,
                'cc_owner' => $ccOwner,
                'cc_number' => $additionalData['cc_number'],
                'cc_last_4' => $ccLast4,
                'cc_cid' => $additionalData['cc_cid'],
                'cc_exp_month' => $ccExpMonth,
                'cc_exp_year' => $ccExpYear
            ]);

            $extraInfo = [
                'installments' => $installments,
                'cc_installments' => $installments,
                'cc_bin' => $ccBin,
                'use_tds_authorization' => $additionalData['use_tds_authorization'] ?? 0,
            ];
            foreach ($extraInfo as $key => $value) {
                $paymentInfo->setAdditionalInformation($key, $value);
            }
        }
    }
}
