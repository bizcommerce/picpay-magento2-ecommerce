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

namespace PicPay\Checkout\Model\ResourceModel;

use PicPay\Checkout\Model\CallbackFactory;
use PicPay\Checkout\Api\Data\CallbackInterfaceFactory;
use PicPay\Checkout\Api\Data\CallbackSearchResultsInterfaceFactory;
use PicPay\Checkout\Api\CallbackRepositoryInterface;
use PicPay\Checkout\Model\ResourceModel\Callback as ResourceCallback;
use PicPay\Checkout\Model\ResourceModel\Callback\CollectionFactory as CallbackCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CallbackRepository implements CallbackRepositoryInterface
{
    /** @var CallbackFactory  */
    protected $callbackFactory;

    /** @var CallbackCollectionFactory  */
    protected $callbackCollectionFactory;

    /** @var ResourceCallback  */
    protected $resource;

    /** @var CallbackSearchResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var CallbackInterfaceFactory  */
    protected $dataCallbackFactory;

    /** @var JoinProcessorInterface  */
    protected $extensionAttributesJoinProcessor;

    /** @var CollectionProcessorInterface  */
    private $collectionProcessor;

    public function __construct(
        CallbackFactory $callbackFactory,
        CallbackInterfaceFactory $dataCallbackFactory,
        ResourceCallback $resource,
        CallbackCollectionFactory $callbackCollectionFactory,
        CallbackSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->callbackFactory = $callbackFactory;
        $this->callbackCollectionFactory = $callbackCollectionFactory;
        $this->resource = $resource;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataCallbackFactory = $dataCallbackFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ) {
        $collection = $this->callbackCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \PicPay\Checkout\Api\Data\CallbackInterface::class
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

    /**
     * {@inheritdoc}
     */
    public function save(
        \PicPay\Checkout\Api\Data\CallbackInterface $callback
    ) {
        try {
            $callback = $this->resource->save($callback);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the callback info: %1',
                $exception->getMessage()
            ));
        }
        return $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id) {
        /** @var \PicPay\Checkout\Model\Callback $callback */
        $callback = $this->callbackFactory->create();
        $this->resource->load($callback, $id);
        if (!$callback->getId()) {
            throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $id));
        }
        return $callback;
    }
}
