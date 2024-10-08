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

class Pix extends AbstractInfo
{
    protected $_template = 'PicPay_Checkout::payment/info/pix.phtml';

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
    public function getCopyPasteInfo()
    {
        $payment = $this->getInfo();
        return $payment->getAdditionalInformation('pix1-qrCode');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQRCodeImage()
    {
        $payment = $this->getInfo();
        return $payment->getAdditionalInformation('pix1-qrCodeBase64');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQrCodeExpirationTime()
    {
        $payment = $this->getInfo();
        if ($payment->getAdditionalInformation('t1-createdAt')) {
            $expirationDate = $this->helper->getConvertedDate(
                $payment->getAdditionalInformation('t1-createdAt'),
                (int) $this->helper->getConfig('expiration_time','picpay_checkout_pix')
            );
            $currentDateTime = $this->helper->getConvertedDate('now');

            return $this->helper->getDiffBetweenDates($currentDateTime, $expirationDate);
        }

        return $payment->getAdditionalInformation('qrcode_expiration_time') ?: 300;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderStatus()
    {
        $payment = $this->getInfo();
        return $payment->getAdditionalInformation('t1-transactionStatus');
    }


//    /**
//     * @param \Magento\Framework\DataObject|array|null $transport
//     * @return \Magento\Framework\DataObject
//     * @throws \Magento\Framework\Exception\LocalizedException
//     */
//    protected function _prepareSpecificInformation($transport = null)
//    {
//        /** @var \Magento\Sales\Model\Order $order */
//        $order = $this->getInfo()->getOrder();
//
//
//        return parent::_prepareSpecificInformation($transport);
//    }
}
