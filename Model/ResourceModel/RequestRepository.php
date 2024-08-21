<?php

namespace PicPay\Checkout\Model\ResourceModel;

use PicPay\Checkout\Model\RequestFactory;
use PicPay\Checkout\Api\Data\RequestInterfaceFactory;
use PicPay\Checkout\Api\Data\RequestSearchResultsInterfaceFactory;
use PicPay\Checkout\Api\RequestRepositoryInterface;
use PicPay\Checkout\Model\ResourceModel\Request as ResourceRequest;
use PicPay\Checkout\Model\ResourceModel\Request\CollectionFactory as RequestCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class RequestRepository implements RequestRepositoryInterface
{
    /** @var ResourceRequest  */
    protected $resource;

    /** @var RequestCollectionFactory  */
    protected $requestCollectionFactory;

    /** @var RequestFactory  */
    protected $requestFactory;

    /** @var RequestSearchResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var RequestInterfaceFactory  */
    protected $dataRequestFactory;

    /** @var CollectionProcessorInterface  */
    protected $collectionProcessor;

    /** @var JoinProcessorInterface  */
    protected $extensionAttributesJoinProcessor;

    public function __construct(
        ResourceRequest $resource,
        RequestCollectionFactory $requestCollectionFactory,
        RequestFactory $requestFactory,
        RequestInterfaceFactory $dataRequestFactory,
        RequestSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->resource = $resource;
        $this->requestCollectionFactory = $requestCollectionFactory;
        $this->requestFactory = $requestFactory;
        $this->dataRequestFactory = $dataRequestFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        /** @var \PicPay\Checkout\Model\Request $request */
        $request = $this->requestFactory->create();
        $this->resource->load($request, $id);
        if (!$request->getId()) {
            throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $id));
        }
        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \PicPay\Checkout\Api\Data\RequestInterface $callback
    ) {
        try {
            $request = $this->resource->save($callback);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the request info: %1',
                $exception->getMessage()
            ));
        }

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) {
        $collection = $this->requestCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \PicPay\Checkout\Api\Data\RequestInterface::class
        );

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
