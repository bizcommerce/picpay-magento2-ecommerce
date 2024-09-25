<?php

/**
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 */

namespace PicPay\Checkout\Gateway\Http;

use Magento\Framework\Encryption\EncryptorInterface;
use Laminas\Http\Client as HttpClient;
use Magento\Framework\Serialize\Serializer\Json;
use PicPay\Checkout\Helper\Data;

class Client implements ClientInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var HttpClient
     */
    protected $api;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var string
     */
    protected $bearerToken;

    /**
     * @param Data $helper
     * @param EncryptorInterface $encryptor
     * @param Json $json
     */
    public function __construct(
        Data $helper,
        EncryptorInterface $encryptor,
        Json $json
    ) {
        $this->helper = $helper;
        $this->encryptor = $encryptor;
        $this->json = $json;
    }

    public function execute(array $data, string $orderId = null): array
    {
        return [];
    }

    public function getBearerToken(): string
    {
        return (string) $this->bearerToken;
    }

    public function setBearerToken(string $bearerToken): void
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @return string[]
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'caller-origin' => 'M2-v' . $this->helper->getModuleVersion()
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultOptions(): array
    {
        return [
            'timeout' => 30
        ];
    }

    /**
     * @param string $endpoint
     * @param string $orderId
     * @return string
     */
    public function getEndpointPath(string $endpoint, string $orderId = ''): string
    {
        $fullEndpoint = $this->helper->getEndpointConfig($endpoint);
        return str_replace(
            ['{order_id}'],
            [$orderId],
            $fullEndpoint
        );
    }

    public function getApi(string $path, $type = 'payments'): HttpClient
    {
        $uri = $this->helper->getEndpointConfig($type . '_uri');

        if ($this->helper->getGeneralConfig('use_sandbox')) {
            $uri = $this->helper->getEndpointConfig($type . '_uri_sandbox');
        }

        $this->api = new HttpClient(
            $uri . $path,
            $this->getDefaultOptions()
        );

        $headers = $this->getDefaultHeaders();
        if ($this->getBearerToken()) {
            $headers['Authorization'] = 'Bearer ' . $this->getBearerToken();
        }

        $this->api->setHeaders($headers);
        $this->api->setEncType('application/json');

        return $this->api;
    }

    public function makeRequest(string $path, string $method, string $type = 'payments', array $data = []): array
    {
        $api = $this->getApi($path, $type);
        $api->setMethod($method);
        if (!empty($data)) {
            $api->setRawBody($this->json->serialize($data));
        }
        $response = $api->send();
        $content = $response->getBody();
        if ($content && $response->getStatusCode() != 204) {
            $content = $this->helper->jsonDecode($content);
        }

        return [
            'status' => $response->getStatusCode(),
            'response' => $content
        ];
    }
}
