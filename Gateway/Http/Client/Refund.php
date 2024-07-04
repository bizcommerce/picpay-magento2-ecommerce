<?php

namespace PicPay\Checkout\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PicPay\Checkout\Helper\Data;

class Refund implements ClientInterface
{
    const LOG_NAME = 'picpay-refund';

    /**
     * @var Api
     */
    private $api;

    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestBody = $transferObject->getBody();
        $config = $transferObject->getClientConfig();
        $transaction = $this->executeRequest($requestBody, $config);
        $statusCode = $transaction['status'] ?? null;

        return [
            'status' => $transaction['response']['status'] ?? $statusCode,
            'status_code' => $transaction['status'] ?? null,
            'transaction' => $transaction['response']
        ];
    }

    protected function executeRequest($requestBody, $config): array
    {
        $this->api->logRequest($requestBody, self::LOG_NAME);
        $transaction = $this->api->refund()->execute(
            $requestBody,
            $config['store_id']
        );
        $this->api->logResponse($transaction, self::LOG_NAME);
        $this->api->saveRequest(
            $requestBody,
            $transaction['response'],
            $transaction['status'] ?? null,
            self::LOG_NAME
        );

        return $transaction;
    }
}
