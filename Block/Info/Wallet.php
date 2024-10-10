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
use PicPay\Checkout\Helper\Data;

class Wallet extends AbstractInfo
{
    protected $_template = 'PicPay_Checkout::payment/info/wallet.phtml';

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        Config $paymentConfig,
        Data $helper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $config, $data);
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSuccessPageInstructions()
    {
        return $this->helper->getConfig('success_page_instructions', 'picpay_checkout_wallet');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCopyPasteInfo()
    {
        $payment = $this->getInfo();
        return $payment->getAdditionalInformation('wallet1-qrCode');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQRCodeImage()
    {
        $payment = $this->getInfo();
        return $payment->getAdditionalInformation('wallet1-qrCodeBase64');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderStatus()
    {
        $payment = $this->getInfo();
        return $payment->getAdditionalInformation('status') ?: $payment->getAdditionalInformation('t1-transactionStatus');
    }
}
