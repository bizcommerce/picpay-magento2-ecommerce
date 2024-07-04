<?php

/**
 * PicPay
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 * @copyright   Copyright (c) PicPay
 *
 */

namespace PicPay\Checkout\Block\Info;

use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Config;

class CreditCard extends AbstractInfo
{
    protected $_template = 'PicPay_Checkout::payment/info/cc.phtml';

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $config, $data);
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $installments = $this->getInfo()->getAdditionalInformation('installments') ?: 1;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->getInfo()->getOrder();
        $installmentValue = $order->getGrandTotal() / $installments;

        $body = [
            (string) __('Credit Card Type') => $this->getCcTypeName(),
            (string) __('Credit Card Owner') => $this->getInfo()->getCcOwner(),
            (string) __('Card Number') => sprintf('xxxx-%s', $this->getInfo()->getCcLast4()),
            (string) __('Installments') => $this->getInstallmentsText($installments, $installmentValue)
        ];

        $transport = new DataObject($body);

        return parent::_prepareSpecificInformation($transport);
    }

    protected function getInstallmentsText($installments, $value): string
    {
        return sprintf('%s x of %s', $installments, $this->priceCurrency->format($value, false));
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCcTypeName(): string
    {
        $ccType = $this->getInfo()->getCcType();
        return $this->paymentConfig->getCcTypes()[$ccType] ?? __(ucwords($ccType ?: 'N/A'));

    }

}
