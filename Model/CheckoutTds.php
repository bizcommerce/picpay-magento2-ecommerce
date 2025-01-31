<?php

namespace PicPay\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\CartRepositoryInterface;
use PicPay\Checkout\Gateway\Http\Client\Api;
use PicPay\Checkout\Gateway\Request\Tds\SetupRequest;
use PicPay\Checkout\Gateway\Request\Tds\EnrollmentRequest;
use PicPay\Checkout\Helper\Data as HelperData;

class CheckoutTds
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

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
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param Session $checkoutSession
     * @param HelperData $helperData
     * @param SetupRequest $setupRequest
     * @param EventManagerInterface $eventManager
     * @param EnrollmentRequest $enrollmentRequest
     * @param Api $api
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Session                 $checkoutSession,
        HelperData              $helperData,
        SetupRequest            $setupRequest,
        EventManagerInterface   $eventManager,
        EnrollmentRequest       $enrollmentRequest,
        Api                     $api,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helperData = $helperData;
        $this->enrollmentRequest = $enrollmentRequest;
        $this->setupRequest = $setupRequest;
        $this->eventManager = $eventManager;
        $this->api = $api;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param $data
     * @return mixed|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function runTdsRequest($data)
    {
        $enrollment = [];
        $paymentData = $this->getPaymentData($data);
        $setup = $this->createSetup($paymentData);

        if ($setup['response']['chargeId']) {
            $enrollment = $this->runEnrollment($setup['response']['chargeId'], $paymentData);
            $this->processEnrollment($enrollment, $setup['response']['transactions'][0]['cardholderAuthenticationId']);
        }

        return $enrollment;
    }

    /**
     * @param $params
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPaymentData($params)
    {
        $paymentData = new \Magento\Framework\DataObject();
        $paymentData->setData($params);

        $this->eventManager->dispatch(
            'payment_method_assign_data_picpay_checkout_cc',
            [
                AbstractDataAssignObserver::METHOD_CODE => 'picpay_checkout_cc',
                AbstractDataAssignObserver::MODEL_CODE => $this->checkoutSession->getQuote()->getPayment(),
                AbstractDataAssignObserver::DATA_CODE => $paymentData
            ]
        );
        return $paymentData;
    }

    /**
     * @param $paymentData
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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
            $this->checkoutSession->setPicPayTdsSetupTransaction($result['response']['transactions']);
            return $result;
        }

        throw new \Exception('Error trying to create 3DS setup on PicPay');
    }

    /**
     * @param $chargeId
     * @param $paymentData
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function runEnrollment($chargeId, $paymentData)
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
            $this->checkoutSession->setPicPayTdsChargeStatus($result['response']['chargeStatus']);
            $this->checkoutSession->setPicPayTdsEnrollmentTransaction($result['response']['transactions']);
            return $result;
        }

        throw new \Exception('Error trying to create 3DS setup on PicPay');
    }

    /**
     * @param mixed $enrollment
     * @param $cardholderAuthenticationId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processEnrollment($enrollment, $cardholderAuthenticationId): void
    {
        if (isset($enrollment['response']['chargeId']) && isset($enrollment['response']['transactions'][0])) {
            $transaction = $enrollment['response']['transactions'][0];
            $this->checkoutSession->setPicPayTdsChallengeStatus($transaction['cardholderAuthenticationStatus']);

            $quote = $this->checkoutSession->getQuote();
            $quote->setPicpayChargeId($enrollment['response']['chargeId']);
            $quote->setPicpayChallengeStatus($transaction['cardholderAuthenticationStatus']);
            $quote->setPicpayMerchantId($cardholderAuthenticationId);
            $quote->setPicpayCardholderAuthId($cardholderAuthenticationId);
            $this->quoteRepository->save($quote);
        }
    }
}
