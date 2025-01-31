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
use Laminas\Http\Response;

class Payments extends Callback
{
    public const DEFAULT_STATUS_CODE = 500;
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
        $statusCode = self::DEFAULT_STATUS_CODE;
        $orderIncrementId = '';

        try {
            $webhookData = $this->getContent($this->getRequest());
            $this->logParams($webhookData);
            $method = 'picpay-payments';
            $content = isset($webhookData['type']) ? $webhookData['data'] : $webhookData;
            $chargeId = $content['chargeId'] ?? $content['merchantChargeId'];

            $statusCode = $this->processTds($webhookData, $chargeId, $content, $statusCode);
            if (
                isset($content['status'])
                && $statusCode === self::DEFAULT_STATUS_CODE
                && $order = $this->helperOrder->loadOrderByMerchantChargeId($chargeId)
            ) {
                $status = $content['status'];
                $orderIncrementId = $order->getIncrementId();
                $method = $order->getPayment()->getMethod();
                $amount = $content['amount'] ? $content['amount'] / 100 : $order->getGrandTotal();
                $refundedAmount = $content['refundedAmount'] ? $content['refundedAmount'] / 100 : 0;
                $this->helperOrder->updateOrder($order, $status, $content, $amount, $method, true, $refundedAmount);
                $statusCode = Response::STATUS_CODE_200;
            }

            /** @var \PicPay\Checkout\Model\Callback $callBack */
            $callBack = $this->callbackFactory->create();
            $callBack->setStatus($content['status'] ?? '');
            $callBack->setMethod($method);
            $callBack->setIncrementId($orderIncrementId);
            $callBack->setPayload($this->helperData->jsonEncode($content));
            $this->callbackResourceModel->save($callBack);
        } catch (\Exception $e) {
            $statusCode = self::DEFAULT_STATUS_CODE;
            $this->helperData->getLogger()->error($e->getMessage());
        }

        $result->setHttpResponseCode($statusCode);
        return $result;
    }

    public function processTds(array $webhookData, string $chargeId, array $content, int $statusCode): int
    {
        $quote = $this->helperTds->loadQuoteByChargeId($chargeId);
        if (isset($webhookData['type']) && $webhookData['type'] == 'THREE_DS_CHALLENGE' && $quote->getId()) {
            $quote = $this->helperTds->loadQuoteByChargeId($chargeId);
            $this->helperTds->updateQuote($quote, $content);
            $statusCode = Response::STATUS_CODE_200;
        }
        return $statusCode;
    }
}
