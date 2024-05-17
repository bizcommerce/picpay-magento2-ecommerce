<?php
/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 *
 *
 */

declare(strict_types=1);

namespace PicPay\Checkout\Api\Data;

interface CallbackSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get transaction list.
     * @return \PicPay\Checkout\Api\Data\CallbackInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \PicPay\Checkout\Api\Data\CallbackInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

