<?php


namespace JanisCommerce\JanisConnector\Model;

use Magento\Sales\Model\Order\Status\HistoryFactory;
use Psr\Log\LoggerInterface;

/**
 * Builds the order history entries left behind by a Janis notification.
 *
 * These methods only attach the history record to the order; persisting it is
 * the caller's job, so a notification results in a single write instead of the
 * order being saved once here and again by the service.
 */
class OrderCommentManager
{
    /**
     * @var HistoryFactory
     */
    private $orderHistoryFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OrderCommentManager constructor.
     * @param HistoryFactory $orderHistoryFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        HistoryFactory $orderHistoryFactory,
        LoggerInterface $logger
    )
    {
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->logger = $logger;
    }

    /**
     * Records a confirmed notification, identified by the RequestId Janis returned.
     *
     * @param \Magento\Sales\Model\Order $saleOrder
     * @param string $requestId
     */
    public function addSuccessComment($saleOrder, $requestId)
    {
        $this->logger->info('Janis Order Service response: ' . $requestId);

        $this->addComment(
            $saleOrder,
            'The order has been notified to Janis. RequestId returned by Janis: ' . $requestId
        );
    }

    /**
     * Records a failed notification. The order keeps its notification flag at 0
     * so the cron picks it up again on the next run.
     *
     * @param \Magento\Sales\Model\Order $saleOrder
     * @param string $reason
     */
    public function addFailureComment($saleOrder, $reason)
    {
        $this->logger->error('Janis Order Service could not notify the order: ' . $reason);

        $this->addComment(
            $saleOrder,
            'The order could not be notified to Janis and will be retried. Reason: ' . $reason
        );
    }

    /**
     * @param \Magento\Sales\Model\Order $saleOrder
     * @param string $comment
     */
    private function addComment($saleOrder, $comment)
    {
        $history = $this->orderHistoryFactory->create()
            ->setStatus($saleOrder->getStatus())
            ->setEntityName(\Magento\Sales\Model\Order::ENTITY) // Set the entity name for order
            ->setComment($comment);

        $saleOrder->addStatusHistory($history);
    }
}
