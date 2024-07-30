<?php

namespace PicPay\Checkout\Controller\Payment;

use PicPay\Checkout\Helper\Data as HelperData;
use PicPay\Checkout\Helper\Order as HelperOrder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Status extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var Json */
    protected $json;

    /** @var Session */
    protected $checkoutSession;

    /** @var SessionManagerInterface */
    protected $session;

    /** @var HelperData */
    protected $helperData;

    public function __construct(
        Context                 $context,
        Session                 $checkoutSession,
        SessionManagerInterface $session,
        JsonFactory             $resultJsonFactory,
        Json                    $json,
        HelperData              $helperData
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->helperData = $helperData;

        parent::__construct($context);
    }

    public function execute()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $lastOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastOrder->getId()) {
            $payment = $lastOrder->getPayment();
            $result = [
                'order_id' => $lastOrder->getId(),
                'order_status' => $lastOrder->getStatus(),
                'payment_status' => $payment->getAdditionalInformation('status'),
                'is_paid' => $payment->getAdditionalInformation('status') == HelperOrder::STATUS_PAID,
                'redirect' => $this->_url->getUrl('sales/order/view/', ['order_id' => $lastOrder->getId()])
            ];

            //echo "data: " . json_encode($result) . "\n\n";
            $this->getResponse()->setBody(json_encode($result));
            flush();

        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(403);
        return new InvalidRequestException(
            $result
        );
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
