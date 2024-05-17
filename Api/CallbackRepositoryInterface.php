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

declare(strict_types=1);

namespace PicPay\Checkout\Api;

interface CallbackRepositoryInterface
{
    /**
     * Save Queue
     * @param \PicPay\Checkout\Api\Data\CallbackInterface $callback
     * @return \PicPay\Checkout\Api\Data\CallbackInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        Data\CallbackInterface $callback
    );

    /**
     * Retrieve CallbackInterface
     * @param string $id
     * @return \PicPay\Checkout\Api\Data\CallbackInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve Queue matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \PicPay\Checkout\Api\Data\CallbackSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
