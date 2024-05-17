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

namespace PicPay\Checkout\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $creditmemo->setPicpayInterestAmount(0);
        $creditmemo->setBasePicpayInterestAmount(0);

        if (!$order->hasCreditmemos()) {
            $amount = $order->getPicpayInterestAmount();
            $baseAmount = $order->getBasePicpayInterestAmount();
            if ($amount) {
                $creditmemo->setPicpayInterestAmount($amount);
                $creditmemo->setBasePicpayInterestAmount($baseAmount);
                $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
                $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
            }
        }

        return $this;
    }
}
