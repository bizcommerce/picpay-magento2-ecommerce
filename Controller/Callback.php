<?php
/**
 *
 *
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 */

namespace PicPay\Checkout\Controller;

use PicPay\Checkout\Helper\Data as HelperData;
use PicPay\Checkout\Helper\Order as HelperOrder;
use PicPay\Checkout\Model\CallbackFactory;
use PicPay\Checkout\Model\ResourceModel\Callback as CallbackResourceModel;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

abstract class Callback extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    const LOG_NAME = 'picpay-callback';

    /**
     * @var string
     */
    protected $eventName = 'callback';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * @var CallbackResourceModel
     */
    protected $callbackResourceModel;

    /**
     * @var CallbackFactory
     */
    protected $callbackFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var string
     */
    protected $requestContent;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * Event manager
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * PostBack constructor.
     * @param Context $context
     * @param Json $json
     * @param ResultFactory $resultFactory
     * @param HelperData $helperData
     * @param HelperOrder $helperOrder
     * @param CallbackResourceModel $callbackResourceModel
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Context $context,
        Json $json,
        ResultFactory $resultFactory,
        HelperData $helperData,
        HelperOrder $helperOrder,
        CallbackResourceModel $callbackResourceModel,
        CallbackFactory $callbackFactory,
        ManagerInterface $eventManager
    ) {
        $this->json = $json;
        $this->resultFactory = $resultFactory;
        $this->helperData = $helperData;
        $this->helperOrder = $helperOrder;
        $this->callbackResourceModel = $callbackResourceModel;
        $this->callbackFactory = $callbackFactory;
        $this->eventManager = $eventManager;

        parent::__construct($context);
    }

    /**
     * https://api-docs.picpay.com.br/reference/webhook
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    abstract public function execute();

    /**
     * @param $result
     * @param $content
     * @param $params
     * @return mixed
     */
    public function dispatchEvent($result, $content, $params)
    {
        $this->eventManager->dispatch(
            'picpay_checkout_callback_' . $this->eventName,
            [
                'result' => $result,
                'content' => $content,
                'params' => $params
            ]
        );

        return $result;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $hash = $request->getParam('hash');
        $storeHash = sha1($this->helperData->getGeneralConfig('app_key'));
        return ($hash == $storeHash);
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    protected function getContent(RequestInterface $request): array
    {
        $this->requestContent = [];
        if (!$this->requestContent) {
            try {
                $content = $request->getContent();
                $this->requestContent = $this->helperData->jsonDecode($content);
            } catch (\Exception $e) {
                $this->helperData->getLogger()->critical($e->getMessage());
            }
        }
        return $this->requestContent;
    }

    /**
     * @param $content
     */
    protected function logParams(array $content): void
    {
        $this->helperData->log(__('Content'), self::LOG_NAME);
        $this->helperData->log($content, self::LOG_NAME);
    }
}
