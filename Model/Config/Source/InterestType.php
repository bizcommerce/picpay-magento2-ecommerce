<?php

/**
 * Biz
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 */

namespace PicPay\Checkout\Model\Config\Source;

class InterestType implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            'per_installments' => __('Per Installments'),
            'price' => __('Amortization'),
            'compound' => __('Compound'),
            'simple' => __('Simple')
        ];
    }

    public function toArray(): array
    {
        $options = [];
        foreach ($this->toOptionArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $options;
    }
}
