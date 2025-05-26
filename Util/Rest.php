<?php

namespace JanisCommerce\JanisConnector\Util;


use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Client\Curl;
use JanisCommerce\JanisConnector\Helper\Data;

class Rest extends Api
{
    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;
    /**
     * @var Magento\Framework\HTTP\Client\Curl
     */
    private $curl;
    /**
     * @var Json
     */
    private $serializeJson;
    /**
     * @var Data
     */
    private $helperData;

    private $status;

    public function __construct(
        ZendClientFactory $httpClientFactory,
        Json $serializeJson,
        Curl $curl,
        Data $helperData
    )
    {
        parent::__construct($serializeJson);
        $this->httpClientFactory = $httpClientFactory;
        $this->curl = $curl;
        $this->serializeJson = $serializeJson;
        $this->helperData = $helperData;
    }

    /**
     * @inheritDoc
     */
    public function request(
        $apiUrl,
        $httpMethod = "POST",
        $params = array()
    )
    {
        $userData = [
            'janis-client' => $this->helperData->getApiClient(),
            'janis-api-key' => $this->helperData->getApiKey(),
            'janis-api-secret' => $this->helperData->getApiSecret()
        ];

        // If Test mode was enabled
        if ($this->helperData->getApiClientTestMode())
        {
            $userData = [
                'janis-client' => $this->helperData->getApiClientTestMode(),
                'janis-api-key' => $this->helperData->getApiKeyTestMode(),
                'janis-api-secret' => $this->helperData->getApiSecretTestMode()
            ];
        }

        $this->curl->setHeaders($userData);
        $this->curl->addHeader("Content-Type", "application/json");

        if ($httpMethod === 'GET')
            $this->curl->get($apiUrl);
        elseif ($httpMethod === 'POST')
            $this->curl->post($apiUrl, $params);

        $this->status = $this->curl->getStatus();

        return $this->unSerialize($this->curl->getBody());
    }

    /**
     * Returns latest status response
     *
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }
}
