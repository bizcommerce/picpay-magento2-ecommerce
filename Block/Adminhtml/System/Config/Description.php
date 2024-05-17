<?php

namespace PicPay\Checkout\Block\Adminhtml\System\Config;

use PicPay\Checkout\Helper\Data;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Description extends Template implements RendererInterface
{
    protected $helper;

    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $description = '<div style="margin-left: 4rem; color: #696969" class="info-picpay-checkout">';
        $description .='<p>' . __('Don\'t forget to configure the Webhook URL in your PicPay account.') . '</p>';
        $description .='<p>' . __('Webhook URL: %1', $this->helper->getWebhookUrl()) . '</p>';
        $description .='<p>' . __('More info <a href="%1" target="_blank">here</a>', $this->helper->getInfoUrl()) . '</p>';
        $description .='</div>';
        $description .= '<div style="margin-left: 4rem; color: #696969" class="info-picpay-checkout-extra">';
        $description .= '<small>' . __('Module Version: v%1', $this->helper->getModuleVersion()) . '</small>';
        $description .='</div>';
        return $description;
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->_decorateRowHtml($element, $this->_renderValue($element));
    }

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element)
    {
        $html = '<td colspan="4" class="value">';
        $html .= $this->_getElementHtml($element);
        $html .= '</td>';
        return $html;
    }

    /**
     * Decorate field row html
     *
     * @param AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(AbstractElement $element, $html)
    {
        return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
    }
}
