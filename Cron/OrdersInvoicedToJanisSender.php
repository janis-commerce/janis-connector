<?php

namespace JanisCommerce\JanisConnector\Cron;

use Magento\Framework\DB\Select;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Model\JanisOrderService;
use JanisCommerce\JanisConnector\Helper\Data;

class OrdersInvoicedToJanisSender
{
    /**
     * Orders handled per run. The rest stay pending and are picked up by the
     * next run, which keeps memory flat regardless of how big the backlog is.
     */
    const BATCH_SIZE = 100;

    /**
     * @var JanisConnectorLogger
     */
    private $JanisConnectorLogger;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var JanisOrderService
     */
    private $janisOrderService;

    /**
     * @var Data
     */
    private $helper;

    /**
     * OrdersInvoicedToJanisSender constructor.
     */
    public function __construct(
        JanisConnectorLogger $JanisConnectorLogger,
        JanisOrderService $janisOrderService,
        CollectionFactory $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        Data $helper
    ) {
        $this->JanisConnectorLogger = $JanisConnectorLogger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->janisOrderService = $janisOrderService;
        $this->helper = $helper;
    }

    public function execute()
    {
        $this->JanisConnectorLogger->info('*************** OrdersInvoicedToJanisSender cron job started ***************');

        $orderInvoicedStatus = $this->helper->getOrderInvoicedStatus();

        if(!$this->helper->isInvoiceNotificationEnabled() || !$orderInvoicedStatus) {
            $this->JanisConnectorLogger->warning('Skipped: Invoice notification not enabled or order invoiced status not configured.');
            return true;
        }

        $orderIds = $this->fetchPendingOrderIds($orderInvoicedStatus);

        $this->JanisConnectorLogger->info('Total orders to process: ' . count($orderIds));

        foreach ($orderIds as $orderId) {

            try {

                // Loaded through the repository so the entity is complete before saving it.
                $order = $this->orderRepository->get($orderId);

                if (!$order->getInvoiceCollection()->getSize()) {
                    $this->JanisConnectorLogger->info(sprintf('[Order #%s] No invoices found.', $order->getIncrementId()));
                    continue;
                }

                $this->janisOrderService->sendOrderNotification($order, "is_order_invoice_notified");

                $this->JanisConnectorLogger->info(sprintf('[Order #%s] Invoice notification sent.', $order->getIncrementId()));

            } catch (\Throwable $e) {
                // One order failing must not stop the rest of the batch.
                $this->JanisConnectorLogger->error(sprintf(
                    '[Order %s] Error sending notification: %s',
                    $orderId,
                    $e->getMessage()
                ));
            }
        }

        $this->JanisConnectorLogger->info('*************** OrdersInvoicedToJanisSender cron job completed ***************');

        return true;
    }

    /**
     * Only the ids are read here: selecting every column of every pending order
     * is what used to make this job grow with the backlog.
     *
     * @param string $orderInvoicedStatus
     * @return string[]
     */
    private function fetchPendingOrderIds($orderInvoicedStatus)
    {
        $orders = $this->orderCollectionFactory->create();

        // INNER JOIN con historial de estados
        $orders->getSelect()->joinInner(
            ['h' => $orders->getTable('sales_order_status_history')],
            'main_table.entity_id = h.parent_id',
            []
        );

        $select = $orders->getSelect();

        $select->where('main_table.is_order_invoice_notified = 0');
        $select->where('h.status = (?)', $orderInvoicedStatus);

        // Evitar duplicados (por múltiples registros de historial)
        $select->group('main_table.entity_id');

        $select->reset(Select::COLUMNS);
        $select->columns('main_table.entity_id');
        $select->limit(self::BATCH_SIZE);

        $this->JanisConnectorLogger->debug('SQL Final: ' . $select->__toString());

        return $orders->getConnection()->fetchCol($select);
    }
}
