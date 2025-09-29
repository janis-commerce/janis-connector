<?php

namespace JanisCommerce\JanisConnector\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use JanisCommerce\JanisConnector\Model\JanisOrderService;
use JanisCommerce\JanisConnector\Helper\Data;

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
     * @var Data
     */
    private $helper;

    /**
     * OrdersToJanisSender constructor.
     * @param JanisOrderService $janisOrderService
     * @param CollectionFactory $orderCollectionFactory
     * @param Data $helper
     */
    public function __construct(
        JanisOrderService $janisOrderService,
        CollectionFactory $orderCollectionFactory,
        Data $helper
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->janisOrderService = $janisOrderService;
        $this->helper = $helper;
    }

    public function execute()
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*');

        // Join con historial de estados
        $orders->getSelect()->join(
            ['h' => $orders->getTable('sales_order_status_history')],
            'main_table.entity_id = h.parent_id',
            []
        );

        // Condiciones de notificación
        $conditions = [
            ['attribute' => 'is_order_created_notified', 'eq' => '0']
        ];

        if ($this->helper->isInvoiceNotificationEnabled()) {
            $conditions[] = ['attribute' => 'is_order_invoice_notified', 'eq' => '0'];
        }

        if (count($conditions) > 1) {
            $orders->addFieldToFilter($conditions); // OR
        } else {
            $onlyCondition = $conditions[0];
            $orders->addFieldToFilter($onlyCondition['attribute'], ['eq' => $onlyCondition['eq']]);
        }

        // Filtrar por los statuses configurados en admin
        $statuses = [];

        // Siempre se considera el status de "pedido creado"
        if ($this->helper->getOrderCreatedStatus()) {
            $statuses[] = $this->helper->getOrderCreatedStatus();
        }

        // Solo agregamos el de facturación si está habilitado y configurado
        if ($this->helper->isInvoiceNotificationEnabled()
            && $this->helper->getOrderInvoicedStatus()
        ) {
            $statuses[] = $this->helper->getOrderInvoicedStatus();
        }

        if (!empty($statuses)) {
            $orders->getSelect()->where('h.status IN (?)', $statuses);
        }

        // Evitar duplicados de pedidos por múltiples registros en historial
        $orders->getSelect()->group('main_table.entity_id');

        // Procesar pedidos
        foreach ($orders as $order) {
            // Enviar notificación de creación solo si no se ha notificado antes
            if ($order->getData('is_order_created_notified') == 0) {
                $this->janisOrderService->sendOrderNotification($order, "is_order_created_notified");
            }

            // Enviar notificación de facturación solo si no se ha notificado antes
            if ($this->helper->isInvoiceNotificationEnabled()
                && $order->getData('is_order_invoice_notified') == 0
            ) {
                $invoices = $order->getInvoiceCollection();

                if (count($invoices) > 0) {
                    $this->janisOrderService->sendOrderNotification($order, "is_order_invoice_notified");
                }
            }
        }

        return $this;
    }
}
