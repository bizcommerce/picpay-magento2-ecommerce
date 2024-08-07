<?php

namespace PicPay\Checkout\Api\Data;

interface CallbackInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const ENTITY_ID = 'entity_id';
    const INCREMENT_ID = 'increment_id';
    const STATUS = 'status';
    const METHOD = 'method';
    const PAYLOAD = 'payload';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @return \PicPay\Checkout\Api\Data\CallbackExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \PicPay\Checkout\Api\Data\CallbackExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(CallbackExtensionInterface $extensionAttributes);

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set EntityId.
     * @param $entityId
     */
    public function setEntityId($entityId);

    /**
     * Get Status.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set Status.
     * @param $status
     */
    public function setStatus($status);

    /**
     * Get IncrementID.
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set IncrementId.
     * @param $orderId
     */
    public function setIncrementId($incrementId);

    /**
     * Get Payload.
     *
     * @return string
     */
    public function getPayload();

    /**
     * Set Payload.
     * @param $payload
     */
    public function setPayload($payload);

    /**
     * Get Method.
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set Method.
     * @param $method
     */
    public function setMethod($method);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt.
     * @param $createdAt
     */
    public function setCreatedAt($createdAt);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set Updated At.
     * @param $updatedAt
     */
    public function setUpdatedAt($updatedAt);
}
