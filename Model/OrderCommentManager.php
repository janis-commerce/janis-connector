<?php


namespace JanisCommerce\JanisConnector\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Psr\Log\LoggerInterface;

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
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * OrderCommentManager constructor.
     * @param HistoryFactory $orderHistoryFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        HistoryFactory $orderHistoryFactory,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    )
    {
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }

    public function saveComment($saleOrder, $message)
    {
        $comment = "The order has been notified to Janis. This RequestId has been  responded by Janis services: ". $message;
        $this->logger->info("Janis Order Service response: ". $message);

        $history = $this->orderHistoryFactory->create()
            ->setStatus($saleOrder->getStatus())
            ->setEntityName(\Magento\Sales\Model\Order::ENTITY) // Set the entity name for order
            ->setComment($comment);

        $saleOrder->addStatusHistory($history); // Add your comment to order
        $this->orderRepository->save($saleOrder);
    }
}
