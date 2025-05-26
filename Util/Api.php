<?php

namespace JanisCommerce\JanisConnector\Util;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

abstract class Api
{
    /**
     * @var Json
     */
    private $serializeJson;

    /**
     * Api constructor.
     * @param Json $serializeJson
     */
    public function __construct(
        Json $serializeJson
    ) {
        $this->serializeJson = $serializeJson;
    }

    /**
     * @param array $data
     * @return bool|false|string
     */
    public function serialize(array $data) {
        return $this->serializeJson->serialize($data);
    }

    /**
     * @param string $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function unSerialize($data) {

        if( $data )
            return $this->serializeJson->unserialize($data);

        return null;
    }

    /**
     * Interact with external APIs, sent REST requests and receives API responses
     *
     * @param $apiUrl
     * @param string $httpMethod
     * @param array $params
     * @return bool|string
     * @throws LocalizedException
     */
    public abstract function request(
        $apiUrl,
        $httpMethod = "POST",
        $params = array()
    );
}
