<?php

namespace PicPay\Checkout\Controller\Tds;

use Magento\Backend\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Gateway\Request\Tds\SetupRequest;
use PicPay\Checkout\Gateway\Request\Tds\EnrollmentRequest;
use PicPay\Checkout\Helper\Data as HelperData;

class Enrollment extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
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

    /**
     * @var SetupRequest
     */
    protected $setupRequest;

    /**
     * @var EnrollmentRequest
     */
    protected $enrollmentRequest;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Copy
     */
    protected $copy;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    public function __construct(
        Context                 $context,
        Session                 $checkoutSession,
        SessionManagerInterface $session,
        JsonFactory             $resultJsonFactory,
        Json                    $json,
        HelperData              $helperData,
        SetupRequest            $setupRequest,
        EnrollmentRequest       $enrollmentRequest,
        Api                     $api,
        Copy                    $copy,
        OrderFactory            $orderFactory
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->json = $json;
        $this->helperData = $helperData;
        $this->enrollmentRequest = $enrollmentRequest;
        $this->setupRequest = $setupRequest;
        $this->api = $api;
        $this->copy = $copy;
        $this->orderFactory = $orderFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $content = $this->getRequest()->getContent();
            $bodyParams = ($content) ? $this->json->unserialize($content) : [];
            $paymentData = $this->getPaymentData($bodyParams);

            $setup = $this->createSetup($paymentData);
            if ($setup['response']['chargeId']) {
                $enrollment = $this->startEnrollment($setup['response']['chargeId'], $paymentData);
            }

            $result->setJsonData($this->json->serialize($setup));
            $responseCode = 200;
        } catch (\Exception $e) {
            $responseCode = 500;
        }

        $result->setHttpResponseCode($responseCode);
        return $result;
    }

    protected function getPaymentData($params)
    {
        $paymentData = new \Magento\Framework\DataObject();
        $paymentData->setData($params);

        $this->_eventManager->dispatch(
            'payment_method_assign_data_picpay_checkout_cc',
            [
                AbstractDataAssignObserver::METHOD_CODE => 'picpay_checkout_cc',
                AbstractDataAssignObserver::MODEL_CODE => $this->checkoutSession->getQuote()->getPayment(),
                AbstractDataAssignObserver::DATA_CODE => $paymentData
            ]
        );
        return $paymentData;
    }

    public function createSetup($paymentData)
    {
        $quote = $this->checkoutSession->getQuote();

        $transaction = $this->setupRequest->build([
            'payment' => $paymentData,
            'quote' => $quote,
            'amount' => $quote->getGrandTotal()
        ]);

        $result = $this->api->tds()->setup($transaction['request']);

        if ($result['status'] == 200) {
            $this->checkoutSession->setPicPayTdsChargeId($result['response']['chargeId']);
            $this->checkoutSession->setPicPayTdsSetupTransaction($result['response']['transaction']);
            return $result;
        }

        throw new \Exception('Error trying to create 3DS setup on PicPay');
    }

    public function startEnrollment($chargeId, $paymentData)
    {
        $quote = $this->checkoutSession->getQuote();
        $transaction = $this->enrollmentRequest->build([
            'payment' => $paymentData,
            'quote' => $quote,
            'amount' => $quote->getGrandTotal(),
            'chargeId' => $chargeId
        ]);

        $result = $this->api->tds()->enrollment($transaction['request']);

        if ($result['status'] == 200) {
            $this->checkoutSession->setPicPayTdsChargeId($result['response']['chargeId']);
            $this->checkoutSession->setPicPayTdsSetupTransaction($result['response']['transaction']);
            return $result;
        }

        throw new \Exception('Error trying to create 3DS setup on PicPay');
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
