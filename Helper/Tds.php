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

namespace PicPay\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Payment as ResourcePayment;

class Tds extends AbstractHelper
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var ResourcePayment
     */
    protected $resourcePayment;

    public function __construct(
        Context $context,
        QuoteCollectionFactory $quoteCollectionFactory,
        QuoteRepository $quoteRepository,
        ResourcePayment $resourcePayment
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->resourcePayment = $resourcePayment;
        parent::__construct($context);
    }


    /**
     * @param string $chargeId
     * @return \Magento\Framework\DataObject
     */
    public function loadQuoteByChargeId(string $chargeId)
    {
        $collection = $this->quoteCollectionFactory->create();
        $collection->addFieldToFilter('picpay_charge_id', $chargeId);
        return $collection->getFirstItem();
    }

    /**
     * @param $quote
     * @param $content
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateQuote($quote, $content)
    {
        $quote->setPicpayChallengeStatus($content['status']);
        $quote->setPicpayMerchantId($content['merchantChargeId']);
        $this->quoteRepository->save($quote);
    }

}
