<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Model\JanisCartService;
use JanisCommerce\JanisConnector\Api\ReloadPdpCalendarDataInterface;

class ReloadPdpCalendarData implements ReloadPdpCalendarDataInterface
{

    const KEY_SHIPPING_TYPE = 'shippingType';

    const KEY_SLA_NAME = 'slaName';
    /**
     * @var JanisCartService
     */
    private $janisCartService;

    private $request;


    public function __construct(
        JanisCartService $janisCartService,
        \Magento\Framework\Webapi\Rest\Request $request
    )
    {
        $this->janisCartService = $janisCartService;
        $this->request = $request;
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $body = $this->request->getBodyParams();

        $strShippingType = '';

        if(!isset($body['shippingType']) && isset($body['slaName'])){
            $strShippingType = $body['slaName'];
            $indexShipping = self::KEY_SLA_NAME;
        }else{
            $strShippingType = $body['shippingType'];
            $indexShipping = self::KEY_SHIPPING_TYPE;
        }

        if(!isset($body['dropoff']) ){
            return ['message' => 'Error: Falta un parametro'];
        }

        if( isset($body['skus']) )
            return $this->janisCartService->getSplitCarts($indexShipping, $strShippingType, $body['dropoff'], $body['skus']);

        return $this->janisCartService->getSplitCarts($indexShipping, $strShippingType, $body['dropoff']);
    }
}
