<?php

namespace PicPay\Checkout\Gateway\Http;

use Laminas\Http\Client as HttpClient;

interface ClientInterface
{
    public function execute(array $data, string $orderId = null): array;

    public function getBearerToken(): string;

    public function setBearerToken(string $bearerToken): void;

    public function getEndpointPath(string $endpoint, string $orderId = ''): string;

    public function getApi(string $path, $type = 'payments'): HttpClient;

    public function makeRequest(string $path, string $method, string $type = 'payments', array $data = []): array;

}
