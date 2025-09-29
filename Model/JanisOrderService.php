<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Model\DataMappers\OrderCreationNotification\OrderNotification;
use JanisCommerce\JanisConnector\Util\Rest;

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
     * JanisOrderService constructor.
     * @param Rest $rest
     * @param Data $helper
     * @param OrderNotification $orderNotification
     * @param OrderCommentManager $orderCommentManager
     * @param JanisConnectorLogger $JanisConnectorLogger
     */
    public function __construct(
        Rest $rest,
        Data $helper,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        OrderNotification $orderNotification,
        OrderCommentManager $orderCommentManager,
        JanisConnectorLogger $JanisConnectorLogger
    )
    {
        $this->helper = $helper;
        $this->JanisConnectorLogger = $JanisConnectorLogger;
        $this->orderNotification = $orderNotification;
        $this->orderCommentManager = $orderCommentManager;
        parent::__construct($rest, $helper, $url, $responseFactory, $JanisConnectorLogger);
    }

    /**
     * Send order notification to Janis and update the corresponding notification flag
     *
     * @param \Magento\Sales\Model\Order $saleOrder The order to send notification for
     * @param string $notificationType The type of notification being sent. Possible values:
     *   - "is_order_created_notified": Sets is_order_created_notified field to 1
     *   - "is_order_invoice_notified": Sets is_order_invoice_notified field to 1
     * @return array|bool|float|int|mixed|string|null Response from Janis API
     */
    public function sendOrderNotification($saleOrder, $notificationType)
    {
        $this->orderNotification->setObj($saleOrder);

        $notificationTypeString = $notificationType === 'is_order_created_notified' ? 'created' : 'invoiced';

        // Getting create order payload
        $payload = $this->orderNotification->builtOrderNotificationPayload(true);

        $this->JanisConnectorLogger->info('*************** Order Notification ***************');
        $this->JanisConnectorLogger->info('Start sending notification type: ' . $notificationTypeString . ' order id: ' . $saleOrder->getId());

        $response = $this->post($this->helper->getJanisEndpointToNotifyOrder(), $payload);

        // Saving order comment
        if ( isset($response['SendMessageResponse']['ResponseMetadata']['RequestId']) )
        {
            $this->orderCommentManager->saveComment($saleOrder, print_r($response['SendMessageResponse']['ResponseMetadata']['RequestId'], true));
            $this->JanisConnectorLogger->info('Notification type: ' . $notificationTypeString . ' order id: ' . $saleOrder->getId() .' sended.');
        } else {
            $this->orderCommentManager->saveComment($saleOrder, print_r($response, true));
            $this->JanisConnectorLogger->info('Notification type: ' . $notificationTypeString . ' order id: ' . $saleOrder->getId() .' not sended.');
        }

        // Set the appropriate notification flag based on notification type
        if ($notificationType === 'is_order_created_notified') {
            $saleOrder->setOrderCreatedNotificated(1);
        } elseif ($notificationType === 'is_order_invoice_notified') {
            $saleOrder->setOrderInvoiceNotified(1);
        }

        $saleOrder->save();

        return $response;
    }
}
