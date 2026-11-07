<?php

namespace JanisCommerce\JanisConnector\Model\DataMappers\OrderCreationNotification;


use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Model\DataMappers\AbstractAttributeMapper;

class OrderNotification extends AbstractAttributeMapper
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * OrderNotification constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * Body payload builder to sent notification to janis of new order created
     *
     * @param boolean $jsonEncoded
     * @return array|false|string
     */
    public function builtOrderNotificationPayload($jsonEncoded = false)
    {
        $payload = [];

        $payload = $this->addToPayload('accountName', $this->helper->getJanisAccountName(), $payload);
        $payload = $this->addToPayload('orderId', $this->obj->getIncrementId(), $payload);
        $payload = $this->addToPayload('externalRef', $this->obj->getId(), $payload);
        $payload = $this->addToPayload('status', $this->obj->getStatus(), $payload);
        $payload = $this->addToPayload('statusCode', $this->obj->getState(), $payload);

        // Magento's `sales_order.store_id` is a store VIEW, not what the admin calls a Store: it points
        // to the `store` table, which holds the views, while the admin's Stores live in `store_group`.
        // The key is named after the entity it really carries, because the scope Janis needs to reach
        // this order over REST is the store view's and not the group's.
        //
        // It is sent as Magento returns it -- the hook accepts a string or a number -- and omitted when
        // there is none: the hook rejects a null and resolves the scope on its own when the key is absent.
        $storeViewId = $this->obj->getStoreId();

        if ($storeViewId !== null)
        {
            $payload = $this->addToPayload('storeViewId', $storeViewId, $payload);
        }

        if ($jsonEncoded)
        {
            return json_encode($payload);
        }

        return $payload;
    }
}
