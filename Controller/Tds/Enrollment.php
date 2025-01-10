<?php

namespace PicPay\Checkout\Controller\Tds;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Model\CheckoutTds;

class Enrollment extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var CheckoutTds
     */
    protected $tds;

    /**
     * @param Context $context
     * @param CheckoutTds $tds
     * @param JsonFactory $resultJsonFactory
     * @param Json $json
     */
    public function __construct(
        Context                 $context,
        CheckoutTds                     $tds,
        JsonFactory             $resultJsonFactory,
        Json                    $json
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->tds = $tds;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $content = $this->getRequest()->getContent();
            $bodyParams = ($content) ? $this->json->unserialize($content) : [];
            $response = $this->tds->runTdsRequest($bodyParams);

            if ($response['response']['chargeId']) {
                $result->setJsonData($this->json->serialize($response['response']['transactions'][0]));
            }

            $responseCode = 200;
        } catch (\Exception $e) {
            $responseCode = 500;
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $result->setHttpResponseCode($responseCode);
        return $result;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(403);
        return new InvalidRequestException(
            $result
        );
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
