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

namespace PicPay\Checkout\Block\Sales\Order\Totals;

use Magento\Sales\Model\Order;

/**
 * Class Interest
 *
 * @package MercadoPago\Core\Block\Sales\Order\Totals
 */
class Interest extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Add this total to parent
     */
    public function initTotals()
    {
        if ($this->getSource()->getPicpayInterestAmount() > 0) {
            $total = new \Magento\Framework\DataObject([
                'code'  => 'picpay_interest',
                'field' => 'picpay_interest_amount',
                'value' => $this->getSource()->getPicpayInterestAmount(),
                'label' => __('Interest Rate'),
            ]);
            //@phpstan-ignore-next-line
            $this->getParentBlock()->addTotalBefore($total, $this->getBeforeCondition());
        }

        return $this;
    }
}
