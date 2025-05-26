<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Util\Rest;

class JanisCartService extends JanisConnector
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
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $product;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    private $productRepository;
    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    private $quoteItemFactory;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cartModel;

    /**
     * JanisCartService constructor.
     * @param Rest $rest
     * @param Data $helper
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Backend\Model\View\Result\Redirect $redirect
     * @param JanisConnectorLogger $JanisConnectorLogger
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Checkout\Model\Cart $cartModel
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     */
    public function __construct(
        Rest $rest,
        Data $helper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Backend\Model\View\Result\Redirect $redirect,
        JanisConnectorLogger $JanisConnectorLogger,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Checkout\Model\Cart $cartModel,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseFactory $responseFactory
    )
    {
        parent::__construct($rest, $helper, $url, $responseFactory, $JanisConnectorLogger);
        $this->helper = $helper;
        $this->JanisConnectorLogger = $JanisConnectorLogger;
        $this->product = $product;
        $this->productRepository = $productRepository;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->cartModel = $cartModel;
    }

    /**
     * Creates a body payload to be able to sent it to Janis EP (setup in admin), and receive a payload response
     *
     * @param string $shippingType Receives type of shipping
     * @param float[] $dropoff Receives an array with two values: [latitude, longitude]
     * @param array $customSkus Receives an array of product skus exam. [ ['sku' => '1234', 'qty' => '1'], ['sku' => '5678', 'qty' => '3'], ......]
     * @return array Structured Janis response, with all splitted carts
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSplitCarts(
        $indexShipping = 'shippingType',
        $shippingType = 'delivery',
        $dropoff = ['lat' => '', 'long' => ''],
        $customSkus = []
    )
    {
        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = $this->cartModel->getQuote();

        if(empty($shippingType)){
            $shippingType = $this->helper->getShippingType();
        }

        if (isset($dropoff['lat']))
        {
            if (empty($dropoff['lat']))
            {
                $dropoff['lat'] = $this->helper->getLatitude();
            }
        }
        else
        {
            $dropoff['lat'] = $this->helper->getLatitude();
        }

        if (isset($dropoff['long']))
        {
            if (empty($dropoff['long']))
            {
                $dropoff['long'] = $this->helper->getLongitude();
            }
        }
        else
            $dropoff['long'] = $this->helper->getLongitude();

        $dropoff['lat'] = (float) $dropoff['lat'];
        $dropoff['long'] = (float) $dropoff['long'];


        $this->JanisConnectorLogger->info('*************** Splitcart Resquest ***************');
        $body = $this->buildQuoteBodySplitCarts($cart, $shippingType, $dropoff, $customSkus, $indexShipping);

        $response = $this->post(
            $this->helper->getJanisEndpointToSplitCarts(),
            $body
        );

        //TODO: If response empty or with error, must return null
        return $response;
    }


    /**
     * @param $cart
     * @param string $shippingType Receives type of shipping
     * @param float[] $dropoff Receives an array with two values, [longitude, latitude]
     * @param array $customSkus Receives an array of skus ex. [ ['sku' => '1234', 'qty' => '1'], ['sku' => '5678', 'qty' => '3'], ......]
     * @param string $indexShipping Defines key to be used for the new API (slaName new API, shippingType old API)
     * @return array Json structured body to be used in Janis to split a cart
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildQuoteBodySplitCarts($cart, $shippingType, $dropoff, $customSkus, $indexShipping)
    {
        $skus = [];
        if (count($customSkus) == 0)
        {
            foreach ($cart->getAllItems() as $item)
            {
                $TypeId = $item->getProduct()->getTypeId();

                if (!$item->hasParentItemId())
                {
                    if ($TypeId == 'configurable')
                    {
                        $ItemId = $item->getItemId();

                        $quoteId = $item->getQuoteId();

                        $quoteItem = $this->quoteItemFactory->create()->getCollection()
                            ->addFieldToFilter('quote_id', ['eq' => $quoteId])
                            ->addFieldToFilter('parent_item_id', ['eq' => $ItemId])
                            ->getFirstItem();

                        $productSku = $this->product->create()->load($quoteItem->getProductId())->getSku();
                        $product = $this->productRepository->get($productSku);
                    } else
                    {
                        $productId = $item->getProduct()->getEntityId();
                        $productSku = $this->product->create()->load($productId)->getSku();
                        $product = $this->productRepository->get($productSku);
                    }

                    $skus[] = array(
                        'referenceId' => $product->getSku(), // sku
                        'quantity' => (int)$item->getQty(),
                        'externalId' => $item->getId() // item id
                    );
                }
            }
        } else
        {
            foreach ($customSkus as $onlySku)
            {
                $skus[] = array(
                    'referenceId' => $onlySku['sku'], // sku
                    'quantity' => ($onlySku['qty']) ? $onlySku['qty'] : 1,
                    'externalId' => '' // item id
                );
            }
        }

        $body = [
            $indexShipping => $shippingType,
            'dropoff' => array('coordinates' => [$dropoff['lat'], $dropoff['long']]),
            'salesChannel' => array('referenceId' => $this->helper->getJanisSalesChannelId()),
            'skus' => $skus
        ];

        return json_encode($body);
    }
}

