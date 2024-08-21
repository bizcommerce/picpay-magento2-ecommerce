<?php

namespace PicPay\Checkout\Block\Info;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Config;

class AbstractInfo extends ConfigurableInfo
{
    /**
     * @var Config
     */
    protected $paymentConfig;

    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(
        Context $context,
        Config $paymentConfig,
        ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);
        $this->config = $config;
        $this->paymentConfig = $paymentConfig;

        if (isset($data['methodCode'])) {
            $this->config->setMethodCode($data['methodCode']);
        }

        if (isset($data['pathPattern'])) {
            $this->config->setPathPattern($data['pathPattern']);
        }
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->setTemplate($this->_template);
    }

    /**
     * Prepare payment information
     *
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = \Magento\Payment\Block\Info::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $storedFields = explode(',', (string) $this->config->getValue('paymentInfoKeys'));

        if (!$this->isAdminBackend()) {
            $storedFields = $this->getStoredFields($storedFields);
        }

        $this->prepareData($storedFields, $payment, $transport);

        return $transport;
    }

    /**
     * @param array $storedFields
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \Magento\Framework\DataObject|array|null $transport
     * @return void
     */
    public function prepareData(array $storedFields, $payment, $transport): void
    {
        foreach ($storedFields as $field) {
            if ($payment->getAdditionalInformation($field) !== null) {
                $this->setDataToTransfer(
                    $transport,
                    $field,
                    $payment->getAdditionalInformation($field)
                );
            }
        }
    }

    /**
     * @param array $storedFields
     * @return array
     */
    public function getStoredFields(array $storedFields): array
    {
        return array_diff(
            $storedFields,
            explode(',', (string)$this->config->getValue('privateInfoKeys'))
        );
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return \Magento\Framework\Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string|array $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getValueView($field, $value)
    {
        if (is_array($value)) {
            $value = $this->toJson($value);
        }
        return __($value);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAdminBackend()
    {
        return ($this->_appState->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML);
    }

}
