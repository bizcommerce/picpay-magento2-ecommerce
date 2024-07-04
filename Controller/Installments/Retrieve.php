<?php

namespace PicPay\Checkout\Controller\Installments;

use PicPay\Checkout\Helper\Data as HelperData;
use PicPay\Checkout\Helper\Installments;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Retrieve extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var Json */
    protected $json;

    /** @var Session */
    protected $checkoutSession;

    /** @var SessionManagerInterface */
    protected $session;

    /** @var HelperData */
    protected $helperData;

    /** @var Installments */
    private $helperInstallments;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        SessionManagerInterface $session,
        JsonFactory $resultJsonFactory,
        Json $json,
        HelperData $helperData,
        Installments $helperInstallments
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->helperData = $helperData;
        $this->helperInstallments = $helperInstallments;

        parent::__construct($context);
    }

    public function execute()
    {
        $responseCode = 401;
        $result = $this->resultJsonFactory->create();

        try {
            $content = $this->getRequest()->getContent();
            $bodyParams = ($content) ? $this->json->unserialize($content) : [];
            $ccType = $bodyParams['cc_type'] ?? '';

            $result->setJsonData($this->json->serialize($this->getInstallments($ccType)));
            $responseCode = 200;
        } catch (\Exception $e) {
            $responseCode = 500;
        }

        $result->setHttpResponseCode($responseCode);
        return $result;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getInstallments(string $ccType): array
    {
        $this->session->setPicPayCcType($ccType);
        return $this->helperInstallments->getAllInstallments($this->checkoutSession->getQuote()->getGrandTotal());
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(403);
        return new InvalidRequestException(
            $result
        );
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
