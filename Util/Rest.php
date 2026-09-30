<?php

namespace JanisCommerce\JanisConnector\Util;


use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Client\Curl;
use JanisCommerce\JanisConnector\Helper\Data;

class Rest extends Api
{
    /**
     * Seconds to wait for Janis before giving up. Without it a slow endpoint
     * holds the cron process until PHP's own execution limit kicks in.
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * @var Curl
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
        Json $serializeJson,
        Curl $curl,
        Data $helperData
    )
    {
        parent::__construct($serializeJson);
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
            'janis-client' => $this->helperData->getJanisClient(),
            'janis-api-key' => $this->helperData->getJanisApiKey(),
            'janis-api-secret' => $this->helperData->getJanisApiSecret()
        ];

        $this->curl->setHeaders($userData);
        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->setTimeout(self::REQUEST_TIMEOUT);

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
