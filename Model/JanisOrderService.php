<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Exception\JanisApiException;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Model\DataMappers\OrderCreationNotification\OrderNotification;
use JanisCommerce\JanisConnector\Util\Rest;
use Magento\Sales\Api\OrderRepositoryInterface;

class JanisOrderService extends JanisConnector
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var JanisConnectorLogger
     */
    private $JanisConnectorLogger;
    /**
     * @var OrderNotification
     */
    private $orderNotification;
    /**
     * @var OrderCommentManager
     */
    private $orderCommentManager;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * JanisOrderService constructor.
     * @param Rest $rest
     * @param Data $helper
     * @param OrderNotification $orderNotification
     * @param OrderCommentManager $orderCommentManager
     * @param OrderRepositoryInterface $orderRepository
     * @param JanisConnectorLogger $JanisConnectorLogger
     */
    public function __construct(
        Rest $rest,
        Data $helper,
        OrderNotification $orderNotification,
        OrderCommentManager $orderCommentManager,
        OrderRepositoryInterface $orderRepository,
        JanisConnectorLogger $JanisConnectorLogger
    )
    {
        $this->helper = $helper;
        $this->JanisConnectorLogger = $JanisConnectorLogger;
        $this->orderNotification = $orderNotification;
        $this->orderCommentManager = $orderCommentManager;
        $this->orderRepository = $orderRepository;
        parent::__construct($rest, $helper, $JanisConnectorLogger);
    }

    /**
     * Send order notification to Janis and update the corresponding notification flag.
     *
     * The flag is only written once Janis confirmed the notification with a
     * RequestId. If the call fails, or comes back without one, the order keeps
     * its flag at 0 and the next cron run retries it.
     *
     * @param \Magento\Sales\Model\Order $saleOrder The order to send notification for
     * @param string $notificationType The type of notification being sent. Possible values:
     *   - "is_order_created_notified": Sets is_order_created_notified field to 1
     *   - "is_order_invoice_notified": Sets is_order_invoice_notified field to 1
     * @return array|bool|float|int|mixed|string|null Response from Janis API
     * @throws JanisApiException When Janis does not confirm the notification
     */
    public function sendOrderNotification($saleOrder, $notificationType)
    {
        $this->orderNotification->setObj($saleOrder);

        $notificationTypeString = $notificationType === 'is_order_created_notified' ? 'created' : 'invoiced';

        // Getting create order payload
        $payload = $this->orderNotification->builtOrderNotificationPayload(true);

        $this->JanisConnectorLogger->info('*************** Order Notification ***************');
        $this->JanisConnectorLogger->info('Start sending notification type: ' . $notificationTypeString . ' | OrderId: ' . $saleOrder->getId() . ' | IncrementId: ' . $saleOrder->getIncrementId());

        try {
            $response = $this->post($this->helper->getJanisEndpointToNotifyOrder(), $payload);
        } catch (JanisApiException $e) {
            $this->failNotification($saleOrder, $notificationTypeString, $e->getMessage());
            throw $e;
        }

        $requestId = isset($response['SendMessageResponse']['ResponseMetadata']['RequestId'])
            ? $response['SendMessageResponse']['ResponseMetadata']['RequestId']
            : null;

        // A 2xx without a RequestId is not a confirmation: leave the flag untouched so it retries.
        if (!$requestId) {

            $reason = 'Janis answered without a RequestId.';

            $this->failNotification($saleOrder, $notificationTypeString, $reason);

            throw new JanisApiException(
                __('Janis did not confirm the notification for order %1.', $saleOrder->getIncrementId())
            );
        }

        $this->orderCommentManager->addSuccessComment($saleOrder, $requestId);

        // Set the appropriate notification flag based on notification type
        if ($notificationType === 'is_order_created_notified') {
            $saleOrder->setIsOrderCreatedNotified(1);
        } elseif ($notificationType === 'is_order_invoice_notified') {
            $saleOrder->setIsOrderInvoiceNotified(1);
        }

        $this->orderRepository->save($saleOrder);

        $this->JanisConnectorLogger->info('Notification type: ' . $notificationTypeString . ' | OrderId: ' . $saleOrder->getId() . ' | IncrementId: ' . $saleOrder->getIncrementId() . ' sended.');

        return $response;
    }

    /**
     * Leaves a trace of the failed attempt on the order without marking it as notified.
     *
     * @param \Magento\Sales\Model\Order $saleOrder
     * @param string $notificationTypeString
     * @param string $reason
     */
    private function failNotification($saleOrder, $notificationTypeString, $reason)
    {
        $this->JanisConnectorLogger->error('Notification type: ' . $notificationTypeString . ' | OrderId: ' . $saleOrder->getId() . ' | IncrementId: ' . $saleOrder->getIncrementId() . ' not sended. ' . $reason);

        $this->orderCommentManager->addFailureComment($saleOrder, $reason);

        try {
            $this->orderRepository->save($saleOrder);
        } catch (\Exception $e) {
            // Losing the history note must not hide the original failure.
            $this->JanisConnectorLogger->error('Could not store the failure comment for order ' . $saleOrder->getIncrementId() . ': ' . $e->getMessage());
        }
    }
}
