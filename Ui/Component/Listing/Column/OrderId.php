<?php

namespace PicPay\Checkout\Ui\Component\Listing\Column;

use PicPay\Checkout\Helper\Data;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Backend\Model\UrlInterface;

class OrderId extends Column
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Data $helper,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param $entityId
     * @return string
     */
    protected function getOrderViewLink($entityId): string
    {
        return $this->urlBuilder->getUrl(
            'sales/order/view',
            ['order_id' => $entityId]
        );
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item['increment_id']) {
                    $order = $this->helper->loadOrder($item['increment_id']);
                    $orderId = $order->getId();
                    $item[$fieldName] = $this->formatFieldName($orderId, $item['increment_id']);
                } else {
                    $item[$fieldName] = __('Not Available');
                }
            }
        }

        return $dataSource;
    }

    protected function formatFieldName($orderId, $incrementId): string
    {
        return sprintf(
            '<a href="%s">%s</a>',
            $this->getOrderViewLink($orderId),
            $incrementId
        );
    }
}
