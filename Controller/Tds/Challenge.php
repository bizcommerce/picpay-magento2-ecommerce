<?php

namespace PicPay\Checkout\Controller\Tds;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;
use Magento\Quote\Model\QuoteRepository;

class Challenge extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /** @var Json */
    protected $json;

    /** @var Session */
    protected $checkoutSession;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    public function __construct(
        Context         $context,
        Session         $checkoutSession,
        Json            $json,
        JsonFactory $resultJsonFactory,
        QuoteRepository $quoteRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->json = $json;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $result = $this->resultJsonFactory->create();

            $quoteId = $this->checkoutSession->getQuoteId();
            $quote = $this->quoteRepository->get($quoteId);

            if ($quote->getPicpayChargeId()) {
                $tdsChallengeStatus = $quote->getPicpayChallengeStatus();
                return $result->setData([
                    'challenge_status' => $tdsChallengeStatus,
                    'charge_id' => $quote->getPicpayChargeId()
                ]);
            }
        } catch (\Exception $e) {
            return $result->setData(['error' => true, 'message' => $e->getMessage()]);
        }

        return $result->setData(['error' => true, 'message' => __('No orders found for this user.')]);
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
