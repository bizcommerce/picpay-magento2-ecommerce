<?php

/**
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

class Token extends Client
{
    public function execute(array $data, string $orderId = null): array
    {
        $path = $this->getEndpointPath('payments/auth');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data);
    }
}
