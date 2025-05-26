<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Util\Rest;


abstract class JanisConnector
{
    /**
     * @var Rest
     */
    private $rest;
    /**
     * @var Data
     */
    private $helperData;

    const URL_PROTOCOL = 'https';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;
    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    private $responseFactory;
    /**
     * @var JanisConnectorLogger
     */
    private $JanisConnectorLogger;


    /**
     * JanisConnector constructor.
     * @param Rest $rest
     * @param Data $helperData
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param JanisConnectorLogger $JanisConnectorLogger
     */
    public function __construct(
        Rest $rest,
        Data $helperData,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        JanisConnectorLogger $JanisConnectorLogger
    )
    {
        $this->rest = $rest;
        $this->helperData = $helperData;
        $this->url = $url;
        $this->responseFactory = $responseFactory;
        $this->JanisConnectorLogger = $JanisConnectorLogger;
    }

    /**
     * Rest request to be able to connect with Janis EPs
     *
     * @param string $endpoint Url endpoint to request a GET petition
     * @return array|null Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($endpoint)
    {
        try{
            $response = $this->rest->request($endpoint, 'GET');
            $this->checkResponseStatus($this->rest->getStatus());
            return $response;
        }
        catch (\Exception $e)
        {
            $this->redirectAfterError($e->getMessage());
        }

        return [];
    }

    /**
     * Rest request to be able to connect with Janis EPs
     *
     * @param string $endpoint Url endpoint to request a POST petition
     * @param array $params Request custom params
     * @return array|null Response
     */
    public function post($endpoint, $params)
    {
        try{
            $response = $this->rest->request($endpoint, 'POST', $params);
            $this->JanisConnectorLogger->info('Endpoint URL: ' . $endpoint);
            //$this->JanisConnectorLogger->info('Body Payload sended: ' . print_r(json_decode($params), true));
            $this->JanisConnectorLogger->info('Body Payload sended: ' . $params);
            $this->checkResponseStatus($this->rest->getStatus());
            $this->JanisConnectorLogger->info("Response payload: " . json_encode($response));

            return $response;
        }
        catch (\Exception $e)
        {
            $this->redirectAfterError($e->getMessage());
        }

        return [];
    }

    private function checkResponseStatus($statusCode)
    {
        // Some EP error message
        if( (int)$statusCode !== 200 )
        {
            $this->redirectAfterError($statusCode);
        }
        else
        {
            $this->JanisConnectorLogger->info('Connection status: '. $statusCode);
        }
    }

    /**
     * Triggers redirect to error message page
     *
     * @param string $message Error message
     */
    private function redirectAfterError($statusCode)
    {
        $CustomRedirectionUrl = $this->url->getUrl('janis/error/index');
        $this->responseFactory->create()->setRedirect($CustomRedirectionUrl)->sendResponse();
        $this->JanisConnectorLogger->info('Connection Error: '. $statusCode);
        exit();
    }
}
