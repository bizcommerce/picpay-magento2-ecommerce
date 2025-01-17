<?php

namespace PicPay\Checkout\Model\Ui\CreditCard;

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
    public const CODE = 'picpay_checkout_cc';

    public const DEFAULT_TYPE = 'CREDIT';

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
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $methodCode = self::CODE;

        $customer = $this->customerSession->getCustomer();
        $customerTaxvat = ($customer && $customer->getTaxvat()) ? $customer->getTaxvat() : '';

        return [
            'payment' => [
                self::CODE => [
                    'grand_total' => $this->checkoutSession->getQuote()->getGrandTotal(),
                    'customer_taxvat' => $customerTaxvat,
                    'sandbox' => (int) $this->helper->getGeneralConfig('use_sandbox'),
                    'icons' => $this->getPaymentIcons(),
                    'availableTypes' => $this->getCcAvailableTypes($methodCode),
                    'use_tds' => (int) $this->canUseTds($grandTotal),
                    'place_not_authenticated_order' => (int) $this->helper->getConfig('place_not_authenticated_tds'),
                ],
                'ccform' => [
                    'grandTotal' => [$methodCode => $grandTotal],
                    'months' => [$methodCode => $this->getCcMonths()],
                    'years' => [$methodCode => $this->getCcYears()],
                    'hasVerification' => [$methodCode => $this->hasVerification($methodCode)],
                    'cvvImageUrl' => [$methodCode => $this->getCvvImageUrl()],
                    'urls' => [
                        $methodCode => [
                            'retrieve_installments' => $this->urlBuilder->getUrl('picpay_checkout/installments/retrieve')
                        ]
                    ]
                ]
            ]
        ];
    }

    public function canUseTds($amount)
    {
        $isActive = $this->helper->getConfig('tds_active');
        $minAmount = $this->helper->getConfig('min_tds_order_total');

        return $isActive && $minAmount <= $amount;
    }

    /**
     * Get icons for available payment methods
     *
     * @return array
     */
    public function getPaymentIcons(): array
    {
        if (empty($this->icons)) {
            $types = $this->getCcAvailableTypes(self::CODE);
            foreach ($types as $code => $label) {
                if (!array_key_exists($code, $this->icons)) {
                    $asset = $this->ccConfig->createAsset('PicPay_Checkout::images/cc/' . strtolower($code) . '.png');
                    $placeholder = $this->assetSource->findSource($asset);
                    if ($placeholder) {
                        list($width, $height) = getimagesize($asset->getSourceFile());
                        $this->icons[$code] = [
                            'url' => $asset->getUrl(),
                            'width' => $width,
                            'height' => $height,
                            'title' => __($label),
                        ];
                    }
                }
            }
        }
        return $this->icons;
    }
}
