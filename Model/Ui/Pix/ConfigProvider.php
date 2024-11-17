<?php

namespace PicPay\Checkout\Model\Ui\Pix;

use PicPay\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;

/**
 * Class ConfigProvider
 */
class ConfigProvider extends CcGenericConfigProvider
{
    public const CODE = 'picpay_checkout_pix';

    public const DEFAULT_TYPE = 'PIX';

    protected $icons = [];

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        CustomerSession $customerSession,
        Session $checkoutSession,
        Source $assetSource,
        UrlInterface $urlBuilder,
        Data $helper
    ) {
        parent::__construct($ccConfig, $paymentHelper, [self::CODE]);
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->assetSource = $assetSource;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $customer = $this->customerSession->getCustomer();
        $customerTaxvat = ($customer && $customer->getTaxvat()) ? $customer->getTaxvat() : '';

        return [
            'payment' => [
                self::CODE => [
                    'grand_total' => $this->checkoutSession->getQuote()->getGrandTotal(),
                    'sandbox' => (int) $this->helper->getGeneralConfig('use_sandbox'),
                    'checkout_instructions' => $this->helper->getConfig('checkout_instructions', self::CODE),
                    'customer_taxvat' => $customerTaxvat,
                ]
            ]
        ];
    }
}
