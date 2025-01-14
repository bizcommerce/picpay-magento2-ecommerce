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

class Tds extends Client
{
    public function setup(array $data): array
    {
        $path = $this->getEndpointPath('payments/tds_setup');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data);
    }

    public function enrollment(array $data): array
    {
        $path = $this->getEndpointPath('payments/tds_enrollment');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data);
    }

    public function challengeStatus($chargeId): array
    {
        $path = $this->getEndpointPath('payments/tds_challenge_status');
        $method = Request::METHOD_GET;
        return $this->makeRequest($path, $method);
    }

    public function authorization($data): array
    {
        $path = $this->getEndpointPath('payments/tds_authorization');
        $method = Request::METHOD_POST;
        return $this->makeRequest($path, $method, 'payments', $data);
    }
}
