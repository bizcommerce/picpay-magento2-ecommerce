<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 */

namespace PicPay\Checkout\Gateway\Http\Client\Api;

use PicPay\Checkout\Gateway\Http\Client;
use Laminas\Http\Request;

class Query extends Client
{
    public function get(string $storeId): array
    {
        return $this->execute([], $storeId);
    }

    public function execute(array $data, string $orderId = null): array
    {
        $path = $this->getEndpointPath('payments/get', $orderId);
        $method = Request::METHOD_GET;
        return $this->makeRequest($path, $method);
    }
}
