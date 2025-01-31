<?php
/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 *
 */

namespace PicPay\Checkout\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PicPay\Checkout\Helper\Data;

class Transaction implements ClientInterface
{
    public const LOG_NAME = 'picpay-transaction';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var string
     */
    protected $methodCode;

    /**
     * @param Data $helper
     * @param Api $api
     */
    public function __construct(
        Api $api,
        string $methodCode = 'picpay_checkout'
    ) {
        $this->methodCode = $methodCode;
        $this->api = $api;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $requestBody = $transferObject->getBody();
        $config = $transferObject->getClientConfig();

        $this->api->logRequest($requestBody, self::LOG_NAME);
        switch ($this->methodCode) {
            case \PicPay\Checkout\Model\Ui\Pix\ConfigProvider::CODE:
                $transaction = $this->api->createPix()->execute($requestBody, $config['store_id']);
                break;

            case \PicPay\Checkout\Model\Ui\Wallet\ConfigProvider::CODE:
                $transaction = $this->api->createWallet()->execute($requestBody, $config['store_id']);
                break;

            default:
                $transaction = $this->executeCardTransaction($config, $requestBody);
        }

        $this->api->logResponse($transaction, self::LOG_NAME);

        $statusCode = $transaction['status'] ?? null;
        $status = $transaction['response']['chargeStatus'] ?? $statusCode;

        $this->api->saveRequest($requestBody, $transaction['response'], $statusCode, $this->methodCode);

        return ['status' => $status, 'status_code' => $statusCode, 'transaction' => $transaction['response']];
    }

    /**
     * @param $config
     * @param $requestBody
     * @return array
     * @throws \Exception
     */
    protected function executeCardTransaction($config, $requestBody): array
    {
        if ($config['use_tds']) {
            $transaction = $this->api->tds()->authorization($requestBody);
            $transaction['response'] = $transaction['response']['charge'] ?? $transaction['response'];
            return $transaction;
        }

        return $this->api->create()->execute($requestBody, $config['store_id']);
    }
}
