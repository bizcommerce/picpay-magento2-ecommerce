<?php

/**
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 */

namespace PicPay\Checkout\Controller\Callback;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use PicPay\Checkout\Controller\Callback;
use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Helper\Order as HelperOrder;
use Magento\Sales\Model\Order as SalesOrder;

class Payments extends Callback
{
    /**
     * @var string
     */
    protected $eventName = 'pix';

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $bearerToken = $request->getHeader('Authorization');
        $bearerToken = str_replace('Bearer ', '', $bearerToken);
        $tokenHash = $this->helperData->getWebhookToken();
        return ($bearerToken == $tokenHash);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helperData->log(__('Webhook %1', __CLASS__), self::LOG_NAME);

        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $statusCode = 500;
        $orderIncrementId = '';

        try {
            $content = $this->getContent($this->getRequest());
            $this->logParams($content);
            $method = 'picpay-payments';

            if (isset($content['status'])) {
                $chargeId = $content['merchantChargeId'];
                if (isset($content['status'])) {
                    $picpayStatus = $content['status'];
                    $order = $this->helperOrder->loadOrderByMerchantChargeId($chargeId);
                    if ($order->getId()) {
                        $orderIncrementId = $order->getIncrementId();
                        $method = $order->getPayment()->getMethod();
                        $amount = $content['amount'] ?? $order->getGrandTotal();
                        $this->helperOrder->updateOrder($order, $picpayStatus, $content, $amount, true);
                        $statusCode = 200;
                    }
                }
            }

            /** @var \PicPay\Checkout\Model\Callback $callBack */
            $callBack = $this->callbackFactory->create();
            $callBack->setStatus($content['status'] ?? '');
            $callBack->setMethod($method);
            $callBack->setIncrementId($orderIncrementId);
            $callBack->setPayload($this->helperData->jsonEncode($content));
            $this->callbackResourceModel->save($callBack);
        } catch (\Exception $e) {
            $statusCode = 500;
            $this->helperData->getLogger()->error($e->getMessage());
        }

        $result->setHttpResponseCode($statusCode);
        return $result;
    }
}
