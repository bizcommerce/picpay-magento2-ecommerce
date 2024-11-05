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
 *
 */

namespace PicPay\Checkout\Gateway\Http\Client;

use PicPay\Checkout\Gateway\Http\Client;
use PicPay\Checkout\Gateway\Http\Client\Api\Card;
use PicPay\Checkout\Gateway\Http\Client\Api\Create;
use PicPay\Checkout\Gateway\Http\Client\Api\CreatePix;
use PicPay\Checkout\Gateway\Http\Client\Api\CreateWallet;
use PicPay\Checkout\Gateway\Http\Client\Api\Query;
use PicPay\Checkout\Gateway\Http\Client\Api\Refund;
use PicPay\Checkout\Gateway\Http\Client\Api\Capture;
use PicPay\Checkout\Gateway\Http\Client\Api\Token;
use PicPay\Checkout\Gateway\Http\Client\Api\Tds;
use PicPay\Checkout\Gateway\Http\ClientInterface;
use PicPay\Checkout\Helper\Data;

class Api
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Create
     */
    private $create;

    /**
     * @var CreatePix
     */
    private $createPix;

    /**
     * @var CreateWallet
     */
    private $createWallet;

    /**
     * @var Refund
     */
    private $refund;

    /**
     * @var Capture
     */
    private $capture;

    /**
     * @var Card
     */
    private $card;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var Tds
     */
    private $tds;

    /**
     * @var string
     */
    private $bearerToken = '';

    public function __construct(
        Data $helper,
        Token $token,
        Create $create,
        CreatePix $createPix,
        CreateWallet $createWallet,
        Refund $refund,
        Capture $capture,
        Card $card,
        Query $query,
        Tds $tds
    ) {
        $this->helper = $helper;
        $this->token = $token;
        $this->create = $create;
        $this->createPix = $createPix;
        $this->createWallet = $createWallet;
        $this->refund = $refund;
        $this->capture = $capture;
        $this->card = $card;
        $this->query = $query;
        $this->tds = $tds;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function loadBearerToken(): void
    {
        $authData = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->helper->getApiKey(),
            'client_secret' => $this->helper->getApiSecret()
        ];
        $this->logRequest($authData, 'token');
        $response = $this->token->execute($authData);
        $this->logRequest($response, 'token');


        if ($response['status'] !== 200) {
            $this->saveRequest($authData, $response, $response['status'], 'token');
            throw new \Exception(__('Error on get token'));
        }

        $this->bearerToken = $response['response']['access_token'];
    }

    public function token(): Token
    {
        return $this->token;
    }

    /**
     * @throws \Exception
     */
    public function create(): ClientInterface
    {
        return $this->getClient($this->create);
    }

    /**
     * @throws \Exception
     */
    public function createPix(): ClientInterface
    {
        return $this->getClient($this->createPix);
    }

    /**
     * @throws \Exception
     */
    public function createWallet(): ClientInterface
    {
        return $this->getClient($this->createWallet);
    }

    /**
     * @throws \Exception
     */
    public function query(): ClientInterface
    {
        return $this->getClient($this->query);
    }

    /**
     * @throws \Exception
     */
    public function refund(): ClientInterface
    {
        return $this->getClient($this->refund);
    }

    /**
     * @throws \Exception
     */
    public function capture(): ClientInterface
    {
        return $this->getClient($this->capture);
    }

    /**
     * @throws \Exception
     */
    public function card(): ClientInterface
    {
        return $this->getClient($this->card);
    }

    /**
     * @throws \Exception
     */
    public function tds(): ClientInterface
    {
        return $this->getClient($this->tds);
    }

    /**
     * @throws \Exception
     */
    protected function getClient(ClientInterface $client): ClientInterface
    {
        $this->loadBearerToken();
        $client->setBearerToken($this->bearerToken);
        return $client;
    }

    /**
     * @param $request
     * @param string $name
     */
    public function logRequest($request, string $name = 'picpay-checkout'): void
    {
        $this->helper->log('Request', $name);
        $this->helper->log($request, $name);
    }

    /**
     * @param $response
     * @param string $name
     */
    public function logResponse($response, $name = 'picpay-checkout'): void
    {
        $this->helper->log('RESPONSE', $name);
        $this->helper->log($response, $name);
    }

    /**
     * @param $request
     * @param $response
     * @param $statusCode
     * @return void
     */
    public function saveRequest(
        $request,
        $response,
        $statusCode,
        $method = \PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider::CODE
    ): void {
        $this->helper->saveRequest($request, $response, $statusCode, $method);
    }
}
