<?php

declare(strict_types=1);

namespace PicPay\Checkout\Api;

interface RequestRepositoryInterface
{
    /**
     * Retrieve Queue matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \PicPay\Checkout\Api\Data\RequestSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Save Queue
     * @param \PicPay\Checkout\Api\Data\RequestInterface $callback
     * @return \PicPay\Checkout\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        Data\RequestInterface $callback
    );

    /**
     * Retrieve RequestInterface
     * @param string $id
     * @return \PicPay\Checkout\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);
}
