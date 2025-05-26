<?php

namespace JanisCommerce\JanisConnector\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use JanisCommerce\JanisConnector\Model\JanisOrderService;

class OrdersToJanisSender
{
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;
    /**
     * @var JanisOrderService
     */
    private $janisOrderService;

    /**
     * OrdersToJanisSender constructor.
     * @param JanisOrderService $janisOrderService
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        JanisOrderService $janisOrderService,
        CollectionFactory $orderCollectionFactory
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->janisOrderService = $janisOrderService;
    }

    public function execute()
    {
        /** @var Magento\Sales\Model\Order\Interceptor[] $orders */
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('is_sync_with_janis', '0');

        foreach ($orders as $order)
        {
            $invoices = $order->getInvoiceCollection();

            // Sending order notification to Janis
            if(  count($invoices) > 0 )
                $this->janisOrderService->sendOrderCreationNotification( $order );
        }

        return $this;
    }
}
