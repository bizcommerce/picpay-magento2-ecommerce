<?php

/**
 * PicPay
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 */

namespace PicPay\Checkout\Helper;

use Magento\Framework\Exception\LocalizedException;
use PicPay\Checkout\Helper\Data as HelperData;
use PicPay\Checkout\Gateway\Http\Client;
use PicPay\Checkout\Gateway\Http\Client\Api;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Payment as ResourcePayment;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\App\Emulation;

class Order extends \Magento\Payment\Helper\Data
{
    //CANCELED, DENIED, ERROR, PAID, PARTIAL, PRE_AUTHORIZED, REFUNDED, CHARGEBACK
    public const STATUS_PAID = 'PAID';

    public const STATUS_PRE_AUTHORIZED = 'PRE_AUTHORIZED';

    public const STATUS_CHARGE_PRE_AUTHORIZED = 'PreAuthorized';

    public const STATUS_PARTIAL = 'PARTIAL';

    public const STATUS_ERROR = 'ERROR';

    public const STATUS_DENIED = 'DENIED';

    public const STATUS_CCANCELED = 'CANCELED';

    public const STATUS_CHARGEBACK = 'CHARGEBACK';

    public const STATUS_REFUNDED = 'REFUNDED';

    public const DEFAULT_QRCODE_WIDTH = 400;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var CollectionFactory
     */
    protected $orderStatusCollectionFactory;

    /**
     * @var ResourcePayment
     */
    protected $resourcePayment;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /** @var Client */
    protected $client;

    /** @var Api */
    protected $api;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Factory $paymentMethodFactory
     * @param Emulation $appEmulation
     * @param Config $paymentConfig
     * @param Initial $initialConfig
     * @param OrderFactory $orderFactory
     * @param CreditmemoFactory $creditmemoFactory
     * @param OrderRepository $orderRepository
     * @param InvoiceRepository $invoiceRepository
     * @param CreditmemoService $creditmemoService
     * @param ResourcePayment $resourcePayment
     * @param CollectionFactory $orderStatusCollectionFactory
     * @param Filesystem $filesystem
     * @param Client $client
     * @param Api $api
     * @param DateTime $dateTime
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        OrderFactory $orderFactory,
        CreditmemoFactory $creditmemoFactory,
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        CreditmemoService $creditmemoService,
        ResourcePayment $resourcePayment,
        CollectionFactory $orderStatusCollectionFactory,
        Filesystem $filesystem,
        Client $client,
        Api $api,
        DateTime $dateTime,
        HelperData $helperData
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);

        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->resourcePayment = $resourcePayment;
        $this->creditmemoService = $creditmemoService;
        $this->filesystem = $filesystem;
        $this->dateTime = $dateTime;
        $this->client = $client;
        $this->api = $api;
        $this->helperData = $helperData;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
    }

    /**
     * @param Payment $payment
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function savePayment($payment): void
    {
        $this->resourcePayment->save($payment);
    }

    /**
     *  Update Order Status
     *
     * @param SalesOrder $order
     * @param string $picpayStatus
     * @param array $content
     * @param float $amount
     * @param string $method
     * @param bool $callback
     * @param float $refundedAmount
     * @return bool
     */
    public function updateOrder(
        SalesOrder $order,
        string $picpayStatus,
        array $content,
        float $amount,
        string $method,
        bool $callback = false,
        float $refundedAmount = 0
    ): bool {
        try {
            /** @var Payment $payment */
            $payment = $order->getPayment();
            $orderStatus = $payment->getAdditionalInformation('status');
            $order->addCommentToStatusHistory(__('Callback received %1 -> %2', $orderStatus, $picpayStatus));

            if ($picpayStatus != $orderStatus) {
                switch ($picpayStatus) {
                    case self::STATUS_PAID:
                        if ($order->canInvoice()) {
                            $this->invoiceOrder($order, $amount);
                        }

                        $updateStatus = $order->getIsVirtual()
                            ? $this->helperData->getConfig('paid_virtual_order_status', $method)
                            : $this->helperData->getConfig('paid_order_status', $method);

                        $message = __('Your payment for the order %1 was confirmed', $order->getIncrementId());
                        $order->addCommentToStatusHistory($message, $updateStatus, true);
                        break;
                    case self::STATUS_CCANCELED:
                    case self::STATUS_DENIED:
                        $order = $this->cancelOrder($order, $refundedAmount, $callback);
                        break;
                    case self::STATUS_CHARGEBACK:
                    case self::STATUS_REFUNDED:
                        $refundedAmount = $refundedAmount ?: $amount;
                        $order = $this->refundOrder($order, $refundedAmount, $callback);
                        break;
                }

                $payment->setAdditionalInformation('status', $picpayStatus);
            }

            $this->orderRepository->save($order);
            $this->savePayment($payment);

            return true;
        } catch (\Exception $e) {
            $this->helperData->log($e->getMessage());
        }

        return false;
    }

    /**
     * @param SalesOrder $order
     * @param float $amount
     * @param boolean $callback
     * @return SalesOrder $order
     *@throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelOrder(SalesOrder $order, float $amount, bool $callback = false): SalesOrder
    {
        if ($order->canCreditmemo()) {
            $creditMemo = $this->creditmemoFactory->createByOrder($order);
            $this->creditmemoService->refund($creditMemo, true);
        } elseif ($order->canCancel()) {
            $order->cancel();
        }

        $cancelledStatus = $this->helperData->getConfig(
            'cancelled_order_status',
            $order->getPayment()->getMethod(),
            'payment',
            $order->getStoreId()
        ) ?: false;

        $order->addCommentToStatusHistory(__('The order %1 was cancelled. Amount of %2', $cancelledStatus, $amount));

        return $order;
    }

    /**
     * @param SalesOrder $order
     * @param float $amount
     */
    protected function invoiceOrder(SalesOrder $order, $amount): void
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->setParentTransactionId($payment->getLastTransId());
        $payment->registerCaptureNotification($amount);
    }

    /**
     * @param $order
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function credimemoOrder(SalesOrder $order): void
    {
        $creditMemo = $this->creditmemoFactory->createByOrder($order);
        $this->creditmemoService->refund($creditMemo);
    }

    /**
     * @param SalesOrder $order
     * @param $captureCase
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function captureOrder(SalesOrder $order, $captureCase = 'online'): void
    {
        if ($order->canInvoice()) {
            /** @var Invoice $invoice */
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase($captureCase);
            $invoice->register();
            $invoice->pay();

            $this->invoiceRepository->save($invoice);

            // Update the order
            $order->getPayment()->setAdditionalInformation('captured', true);
            $this->orderRepository->save($order);
        }
    }

    /**
     * @param SalesOrder $order
     * @param float $amount
     * @param bool $callback
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refundOrder(SalesOrder $order, float $amount, bool $callback = false): SalesOrder
    {
        if ($order->getBaseGrandTotal() == $amount) {
            return $this->cancelOrder($order, $amount, $callback);
        }

        $totalRefunded = (float) $order->getTotalRefunded() + $amount;
        $order->setTotalRefunded($totalRefunded);
        $order->addCommentToStatusHistory(__('The order had the amount refunded by PicPay. Amount of %1', $amount));

        return $order;
    }

    /**
     * @throws LocalizedException
     */
    protected function setTransactionInformation(Payment $payment, array $content, string $prefix = ''): Payment
    {
        foreach ($content as $key => $value) {
            if (!is_array($value)) {
                $payment->setAdditionalInformation($prefix . $key, $value);
            }
        }
        return $payment;
    }

    public function updateDefaultAdditionalInfo(Payment $payment, array $content): Payment
    {
        try {
            //merchantChargeId, id, chargeStatus, amount
            $payment = $this->setTransactionInformation($payment, $content);
            $transactions = $content['transactions'];

            $tid = $content['id'] ?? $content['merchantChargeId'];
            if (is_array($transactions)) {
                foreach ($transactions as $i => $transaction) {
                    $prefix = 't' . (string) ($i + 1) . '-';
                    $this->setTransactionInformation($payment, $transaction, $prefix);
                }
            }

            $payment->setTransactionId($tid);
            $payment->setLastTransId($tid);
            $payment->setAdditionalInformation('tid', $tid);
            $payment->setAdditionalInformation('order_id', $content['merchantChargeId'] ?? '');
            $payment->setAdditionalInformation('status', $content['chargeStatus'] ?? '');
            $payment->setIsTransactionClosed(false);
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $payment;
    }

    public function updateRefundedAdditionalInformation(Payment $payment, $transaction): Payment
    {
        if (isset($transaction['refunds'])) {
            foreach ($transaction['refunds'] as $i => $refund) {
                $this->setTransactionInformation($payment, $refund, 'refund-' . $i . '-');
            }
        }
        return $payment;
    }

    public function updatePaymentAdditionalInfo(Payment $payment, array $transactions, $method)
    {
        try {
            foreach ($transactions as $i => $transaction) {
                if (isset($transaction[$method])) {
                    $prefix = $method . (string) ($i + 1) . '-';
                    $this->setTransactionInformation($payment, $transaction[$method], $prefix);
                }
            }
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $payment;
    }

    public function getStatusState(string $status): string
    {
        if ($status) {
            $statuses = $this->orderStatusCollectionFactory
                ->create()
                ->joinStates()
                ->addFieldToFilter('main_table.status', $status);

            if ($statuses->getSize()) {
                return $statuses->getFirstItem()->getState();
            }
        }

        return '';
    }

    /**
     * @param string $chargeId
     * @return SalesOrder|false
     */
    public function loadOrderByMerchantChargeId(string $chargeId): SalesOrder|false
    {
        $order = $this->orderFactory->create();
        if ($chargeId) {
            $order->loadByAttribute('picpay_charge_id', $chargeId);
        }
        return $order->getId() ? $order : false;
    }

    /**
     * @param $payment
     * @return string
     */
    public function getPaymentStatusState($payment): string
    {
        $defaultState = $payment->getOrder()->getState();
        $status = $payment->getMethodInstance()->getConfigData('order_status');
        return $status ? $this->getStatusState($status) : $defaultState;
    }
}
