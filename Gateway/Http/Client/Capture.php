<?php
/**
 * Biz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Biz.com license that is
 * available through the world-wide-web at this URL:
 * https://www.bizcommerce.com.br/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 * @copyright   Copyright (c) Biz (https://www.bizcommerce.com.br/)
 * @license     https://www.bizcommerce.com.br/LICENSE.txt
 */

namespace PicPay\Checkout\Gateway\Http\Client;

use PicPay\Checkout\Helper\Order as HelperOrder;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class Capture implements ClientInterface
{
    const LOG_NAME = 'picpay_checkout-capture';

    /**
     * @var Transaction
     */
    private $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * PicPay doens't have a capture method, so we use this to have an ONLINE Invoice and then, be able to refund online
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestBody = $transferObject->getBody();
        $config = $transferObject->getClientConfig();

        if ($this->isAlreadyCaptured($config['status'])) {
            return ['status' => HelperOrder::STATUS_PAID, 'status_code' => 200, 'transaction' => []];
        }

        $this->api->logRequest($requestBody, self::LOG_NAME);
        $transaction = $this->api->capture()->execute(
            $requestBody,
            $config['order_id']
        );
        $this->api->logResponse($transaction, self::LOG_NAME);

        $statusCode = $transaction['status'] ?? null;
        $status = $transaction['response']['status'] ?? $statusCode;

        $this->api->saveRequest(
            $requestBody,
            $transaction['response'],
            $statusCode,
            self::LOG_NAME
        );

        return ['status' => $status, 'status_code' => $statusCode, 'transaction' => $transaction['response']];
    }

    protected function isAlreadyCaptured(string $picPayStatus): bool
    {
        return ($picPayStatus === HelperOrder::STATUS_PAID);
    }
}
