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

namespace PicPay\Checkout\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Interest extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     *
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $invoice->setPicpayInterestAmount(0);
        $invoice->setBasePicpayInterestAmount(0);

        if (!$order->hasInvoices()) {
            $amount = $order->getPicpayInterestAmount();
            $baseAmount = $order->getBasePicpayInterestAmount();
            if ($amount) {
                $invoice->setPicpayInterestAmount($amount);
                $invoice->setBasePicpayInterestAmount($baseAmount);
                $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
            }
        }

        return $this;
    }
}
