<?php

namespace JanisCommerce\JanisConnector\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Model\JanisOrderService;
use JanisCommerce\JanisConnector\Helper\Data;

class OrdersCreatedToJanisSender
{
    /**
     * @var JanisConnectorLogger
     */
    private $JanisConnectorLogger;

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
     * OrdersCreatedToJanisSender constructor.
     */
    public function __construct(
        JanisConnectorLogger $JanisConnectorLogger,
        JanisOrderService $janisOrderService,
        CollectionFactory $orderCollectionFactory,
        Data $helper
    ) {
        $this->JanisConnectorLogger = $JanisConnectorLogger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->janisOrderService = $janisOrderService;
        $this->helper = $helper;
    }

    public function execute()
    {
        $this->JanisConnectorLogger->info('*************** OrdersCreatedToJanisSender cron job started ***************');

        $orderCreatedStatus = $this->helper->getOrderCreatedStatus();
        // Validación inicial
        if (!$orderCreatedStatus) {
            $this->JanisConnectorLogger->warning('Skipped: No status configured for order import.');
            return true;
        }

        // Crear colección base de pedidos
        $orders = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*');

        // 🔸 INNER JOIN con historial de estados
        $orders->getSelect()->joinInner(
            ['h' => $orders->getTable('sales_order_status_history')],
            'main_table.entity_id = h.parent_id',
            []
        );

        // 🔸 Condición OR de notificación
        $connection = $orders->getConnection();

        $orders->getSelect()->where('main_table.is_order_created_notified = 0');
        $orders->getSelect()->where('h.status = (?)', $orderCreatedStatus);

        // 🔸 Evitar duplicados (por múltiples registros de historial)
        $orders->getSelect()->group('main_table.entity_id');

        // Logs
        $this->JanisConnectorLogger->info('SQL Final: ' . $orders->getSelect()->__toString());
        $this->JanisConnectorLogger->info('Janis Config: isInvoiceNotificationEnabled: ' . var_export($this->helper->isInvoiceNotificationEnabled(), true));
        $this->JanisConnectorLogger->info('Total orders to process: ' . $orders->count());

        // 🔸 Procesar pedidos encontrados
        foreach ($orders as $order) {
            try {
                // Notificación de creación
                if ($order->getData('is_order_created_notified') == 0) {
                    $this->janisOrderService->sendOrderNotification($order, "is_order_created_notified");
                    $this->JanisConnectorLogger->info(sprintf('[Order #%s] Pending creation notification.', $order->getIncrementId()));
                }

            } catch (\Throwable $e) {
                $this->JanisConnectorLogger->error(sprintf(
                    '[Order #%s] Error sending notification: %s',
                    $order->getIncrementId(),
                    $e->getMessage()
                ));
            }
        }

        $this->JanisConnectorLogger->info('*************** OrdersCreatedToJanisSender cron job completed ***************');

        return true;
    }
}
