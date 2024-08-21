<?php

namespace PicPay\Checkout\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Helper\Data as PaymentHelper;

class Success extends Template
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->httpContext = $httpContext;
        $this->order = $this->checkoutSession->getLastRealOrder();
    }

    /**
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getPayment()
    {
        return $this->order->getPayment()->getMethodInstance();
    }

    /**
     * Return payment info block as html
     * @return string
     * @throws \Exception
     */
    public function getInfoBlock(): string
    {
        $infoBlock = $this->paymentHelper->getInfoBlock(
            $this->order->getPayment()
        );
        $infoBlock->setIsSecureMode(true);

        return $infoBlock->toHtml();
    }
}
