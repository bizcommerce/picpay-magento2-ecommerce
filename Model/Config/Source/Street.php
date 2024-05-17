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

namespace PicPay\Checkout\Model\Config\Source;

class Street implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            '0' => __('Street Line %1', 1),
            '1' => __('Street Line %1', 2),
            '2' => __('Street Line %1', 3),
            '3' => __('Street Line %1', 4),
        ];
    }
}
