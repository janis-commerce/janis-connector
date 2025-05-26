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
     * @param $saleOrder
     * @return array|bool|float|int|mixed|string|null
     */
    public function sendOrderCreationNotification($saleOrder)
    {
        $this->orderNotification->setObj($saleOrder);

        // Getting create order payload
        $payload = $this->orderNotification->builtPayload(true);

        $this->JanisConnectorLogger->info('*************** Order Notification ***************');
        $this->JanisConnectorLogger->info('Order id: ' . $saleOrder->getId() .' sended.');

        $response = $this->post(
            $this->helper->getJanisEndpointToNotifyNewOrder(),
            $payload
        );

        // Saving order comment
        if ( isset($response['SendMessageResponse']['ResponseMetadata']['RequestId']) )
        {
            $this->orderCommentManager->saveComment($saleOrder, print_r($response['SendMessageResponse']['ResponseMetadata']['RequestId'], true));
        } else {
            $this->orderCommentManager->saveComment($saleOrder, print_r($response, true));
        }

        $saleOrder->setIsSyncWithJanis(1);
        $saleOrder->save();

        return $response;
    }
}
