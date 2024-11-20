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
 */

namespace PicPay\Checkout\Helper;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Session\SessionManager;
use PicPay\Checkout\Logger\Logger;
use PicPay\Checkout\Api\RequestRepositoryInterface;
use PicPay\Checkout\Model\RequestFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Payment\Helper\Data
{
    public const DEFAULT_COUTRY_CODE = '55';

    protected array $methodNames = [
        'MC' => 'Mastercard',
        'AU' => 'Aura',
        'VI' => 'Visa',
        'ELO' => 'Elo',
        'AE' => 'American Express',
        'JCB' => 'JCB',
        'HC' => 'Hipercard',
        'HI' => 'Hiper'
    ];

    protected array $transactionStatus = [
        '4' => 'waiting_payment',
        '6' => 'approved',
        '7' => 'canceled',
        '24' => 'contestation',
        '87' => 'monitoring',
        '89' => 'failed'
    ];

    /** @var \PicPay\Checkout\Logger\Logger */
    protected $logger;

    /** @var OrderInterface  */
    protected $order;

    /** @var RequestRepositoryInterface  */
    protected $requestRepository;

    /** @var RequestFactory  */
    protected $requestFactory;

    /** @var WriterInterface */
    private $configWriter;

    /** @var Json */
    private $json;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var RemoteAddress */
    private $remoteAddress;

    /** @var CategoryRepositoryInterface  */
    protected $categoryRepository;

    /** @var CustomerSession  */
    protected $customerSession;

    /** @var DirectoryData */
    protected $helperDirectory;

    /** @var ComponentRegistrar */
    protected $componentRegistrar;

    /** @var DateTime */
    protected $dateTime;

    /** @var EncryptorInterface */
    protected $encryptor;

    /** @var File */
    protected $file;

    /** @var Header  */
    protected $httpHeader;

    /** @var SessionManager */
    protected $sessionManager;

    /** @var ResourceConnection */
    protected $resourceConnection;

    public function __construct(
        Context $context,
        LayoutFactory $layoutFactory,
        Factory $paymentMethodFactory,
        Emulation $appEmulation,
        Config $paymentConfig,
        Initial $initialConfig,
        Logger $logger,
        WriterInterface $configWriter,
        Json $json,
        StoreManagerInterface $storeManager,
        RemoteAddress $remoteAddress,
        CustomerSession $customerSession,
        CategoryRepositoryInterface $categoryRepository,
        RequestRepositoryInterface $requestRepository,
        RequestFactory $requestFactory,
        OrderInterface $order,
        Header $httpHeader,
        SessionManager $sessionManager,
        ResourceConnection $resourceConnection,
        ComponentRegistrar $componentRegistrar,
        DateTime $dateTime,
        DirectoryData $helperDirectory,
        EncryptorInterface $encryptor,
        File $file
    ) {
        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );

        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->remoteAddress = $remoteAddress;
        $this->customerSession = $customerSession;
        $this->categoryRepository = $categoryRepository;
        $this->requestRepository = $requestRepository;
        $this->requestFactory = $requestFactory;
        $this->order = $order;
        $this->httpHeader = $httpHeader;
        $this->sessionManager = $sessionManager;
        $this->resourceConnection = $resourceConnection;
        $this->componentRegistrar = $componentRegistrar;
        $this->dateTime = $dateTime;
        $this->helperDirectory = $helperDirectory;
        $this->encryptor = $encryptor;
        $this->file = $file;
    }

    public function getAllowedMethods(): array
    {
        return [
            \PicPay\Checkout\Model\Ui\CreditCard\ConfigProvider::CODE
        ];
    }

    /**
     * Log custom message using PicPay logger instance
     *
     * @param $message
     * @param string $name
     * @param void
     */
    public function log($message, string $name = 'picpay_checkout'): void
    {
        if ($this->getGeneralConfig('debug')) {
            try {
                if (!is_string($message)) {
                    $message = $this->jsonEncode($message);
                }

                $this->logger->setName($name);
                $this->logger->debug($this->mask($message));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function getApiKey($storeId = null): string
    {
        $key = $this->getGeneralConfig('api_key', $storeId);
        if (empty($key)) {
            $this->log('Api key is empty');
        }
        return $key;
    }

    public function getWebhookToken($storeId = null): string
    {
        $secret = $this->encryptor->decrypt($this->getGeneralConfig('webhook_token', $storeId));
        if (empty($secret)) {
            $this->log('Webhook Token is empty');
        }
        return $secret;
    }

    public function getApiSecret($storeId = null): string
    {
        $secret = $this->encryptor->decrypt($this->getGeneralConfig('api_secret', $storeId));
        if (empty($secret)) {
            $this->log('Api Secret is empty');
        }
        return $secret;
    }

    /**
     * @param string $message
     * @return string
     */
    public function mask(string $message): string
    {
        $message = preg_replace('/"cardNumber":\s?"([^"]+)"/', '"cardNumber":"*********"', $message);
        $message = preg_replace('/"cvv":\s?"([^"]+)"/', '"cvv":"***"', $message);
        $message = preg_replace('/"client_secret":\s?"([^"]+)"/', '"client_secret":"*********"', $message);
        return preg_replace('/"access_token":\s?"([^"]+)"/', '"access_token":"*********"', $message);
    }

    /**
     * @param $message
     * @return bool|string
     */
    public function jsonEncode($message): string
    {
        try {
            return $this->json->serialize($message);
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
        return $message;
    }

    public function jsonDecode(string $message): array
    {
        try {
            return $this->json->unserialize($message);
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
        return [];
    }

    public function getConfig(
        string $config,
        string $group = 'picpay_checkout_cc',
        string $section = 'payment',
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig->getValue(
            $section . '/' . $group . '/' . $config,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function saveConfig(
        string $value,
        string $config,
        string $group = 'general',
        string $section = 'picpay_checkout'
    ): void {
        $this->configWriter->save(
            $section . '/' . $group . '/' . $config,
            $value
        );
    }

    /**
     * @param $request
     * @param $response
     * @param $statusCode
     * @param $method
     * @return void
     */
    public function saveRequest(
        $request,
        $response,
        $statusCode,
        string $method = 'picpay_checkout'
    ): void {
        if ((bool) $this->getGeneralConfig('debug')) {
            try {
                $request = $this->serializeAndMask($request);
                $response = $this->serializeAndMask($response);

                $connection = $this->resourceConnection->getConnection();
                $requestModel = $this->requestFactory->create();
                $requestModel->setRequest($request);
                $requestModel->setResponse($response);
                $requestModel->setMethod($method);
                $requestModel->setStatusCode($statusCode);

                $this->requestRepository->save($requestModel);
                $connection->commit();
            } catch (\Exception $e) {
                $this->log($e->getMessage());
            }
        }
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function serializeAndMask($data): string|bool
    {
        if (!is_string($data)) {
            $data = $this->json->serialize($data);
        }
        return $this->mask($data);
    }

    public function isLateCapture(): bool
    {
        return ! ((bool) $this->getConfig('auto_capture'));
    }

    public function getGeneralConfig(string $config, $scopeCode = null): string
    {
        return $this->getConfig($config, 'general', 'picpay_checkout', $scopeCode);
    }

    public function getEndpointConfig(string $config, string $scopeCode = null): string
    {
        return $this->getConfig($config, 'endpoints', 'picpay_checkout', $scopeCode);
    }

    public function getInfoUrl(): string
    {
        return $this->getGeneralConfig('info_url');
    }

    public function getWebhookUrl(): string
    {
        $url = $this->_getUrl('picpay_checkout/callback/payments');
        return rtrim($url, '/');
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getMediaUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getStoreName(): string
    {
        return $this->getConfig('name', 'store_information', 'general');
    }

    public function getUrl(string $route, array $params = []): string
    {
        return $this->_getUrl($route, $params);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->_logger;
    }

    public function digits(string $string): string
    {
        return preg_replace('/\D/', '', (string) $string);
    }

    public function formatPhoneNumber(string $phoneNumber): array
    {
        $phoneNumber = $this->clearNumber($phoneNumber);
        $areaCode = substr($phoneNumber, 0, 2);
        $number = substr($phoneNumber, 2);
        return [
            'type' => 'MOBILE',
            'countryCode' => self::DEFAULT_COUTRY_CODE,
            'areaCode' => $areaCode,
            'number' => $number
        ];
    }

    public function clearNumber(string $string): string
    {
        return preg_replace('/\D/', '', (string) $string);
    }

    public function loadOrder(string $incrementId): OrderInterface
    {
        return $this->order->loadByIncrementId($incrementId);
    }

    public function getMethodName(string $ccType): string
    {
        $brandName = 'Outro';
        if (isset($this->methodNames[$ccType])) {
            $brandName = $this->methodNames[$ccType];
        }
        return $brandName;
    }

    public function getModuleVersion(): string
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'PicPay_Checkout');
        $composerJsonPath = $modulePath . '/composer.json';

        if ($this->file->fileExists($composerJsonPath)) {
            $composerJsonContent = $this->file->read($composerJsonPath);
            $composerData = json_decode($composerJsonContent, true);

            if (isset($composerData['version'])) {
                return $composerData['version'];
            }
        }

        return '*.*.*';
    }

    public function getCurrentIpAddress(): string
    {
        return (string) $this->remoteAddress->getRemoteAddress();
    }

    public function isUrl(string $trackNumber): bool
    {
        return filter_var($trackNumber, FILTER_VALIDATE_URL);
    }

    public function formatDate(string $date): string
    {
        return date('d/m/Y', strtotime($date));
    }

    public function getUserAgent(): string
    {
        return $this->httpHeader->getHttpUserAgent();
    }

    public function getSessionId(): string
    {
        return $this->sessionManager->getSessionId();
    }

    public function getSoftDescriptor(): string
    {
        $softDescriptor = $this->getGeneralConfig('soft_descriptor') ?: $this->getStoreName();
        return substr($softDescriptor, 0, 13);
    }

    public function getConvertedDate($date, int $additionalSeconds = 0, bool $setTimeZone = true): string
    {
        $date = new \DateTime($date, new \DateTimeZone('UTC'));

        if ($setTimeZone) {
            $timezone = new \DateTimeZone('America/Sao_Paulo');
            $date->setTimezone($timezone);
        }

        $interval = new \DateInterval("PT{$additionalSeconds}S");
        $date->add($interval);

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return float|int
     * @throws \Exception
     */
    public function getDiffBetweenDates($startDate, $endDate)
    {
        $start = new \DateTime($startDate, new \DateTimeZone('UTC'));

        if ($start->format('Y-m-d H:i:s') > $endDate) {
            return 0;
        }

        $end = new \DateTime($endDate, new \DateTimeZone('UTC'));

        $interval = $start->diff($end);
        $seconds = ($interval->days * 24 * 60 * 60) +
            ($interval->h * 60 * 60) +
            ($interval->i * 60) +
            $interval->s;

        return $seconds;
    }
}
