<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Exception\JanisApiException;
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
     * @var JanisConnectorLogger
     */
    private $JanisConnectorLogger;


    /**
     * JanisConnector constructor.
     * @param Rest $rest
     * @param Data $helperData
     * @param JanisConnectorLogger $JanisConnectorLogger
     */
    public function __construct(
        Rest $rest,
        Data $helperData,
        JanisConnectorLogger $JanisConnectorLogger
    )
    {
        $this->rest = $rest;
        $this->helperData = $helperData;
        $this->JanisConnectorLogger = $JanisConnectorLogger;
    }

    /**
     * Rest request to be able to connect with Janis EPs
     *
     * @param string $endpoint Url endpoint to request a GET petition
     * @return array|null Response
     * @throws JanisApiException When the request fails or Janis answers a non 2xx status
     */
    public function get($endpoint)
    {
        $response = $this->send($endpoint, 'GET');

        $this->checkResponseStatus($this->rest->getStatus(), $endpoint, $response);

        return $response;
    }

    /**
     * Rest request to be able to connect with Janis EPs
     *
     * @param string $endpoint Url endpoint to request a POST petition
     * @param array|string $params Request custom params
     * @return array|null Response
     * @throws JanisApiException When the request fails or Janis answers a non 2xx status
     */
    public function post($endpoint, $params)
    {
        $this->JanisConnectorLogger->info('Endpoint URL: ' . $endpoint);
        $this->JanisConnectorLogger->info('Body Payload sended: ' . (is_string($params) ? $params : json_encode($params)));

        $response = $this->send($endpoint, 'POST', $params);

        $this->checkResponseStatus($this->rest->getStatus(), $endpoint, $response);

        $this->JanisConnectorLogger->info('Response payload: ' . json_encode($response));

        return $response;
    }

    /**
     * Performs the request and wraps any transport level failure (DNS, timeout,
     * malformed body) into a JanisApiException, so every caller sees a single
     * exception type regardless of where the failure happened.
     *
     * @param string $endpoint
     * @param string $httpMethod
     * @param array|string $params
     * @return array|null
     * @throws JanisApiException
     */
    private function send($endpoint, $httpMethod, $params = [])
    {
        try {
            return $this->rest->request($endpoint, $httpMethod, $params);
        } catch (\Exception $e) {
            $this->JanisConnectorLogger->error(sprintf(
                'Janis %s request to %s could not be completed: %s',
                $httpMethod,
                $endpoint,
                $e->getMessage()
            ));

            throw new JanisApiException(
                __('The %1 request to Janis could not be completed: %2', $httpMethod, $e->getMessage()),
                0,
                null,
                $e
            );
        }
    }

    /**
     * Any 2xx is a success. Anything else throws, so the caller decides whether
     * to retry, skip the record or surface the error.
     *
     * @param int $statusCode
     * @param string $endpoint
     * @param mixed $response
     * @throws JanisApiException
     */
    private function checkResponseStatus($statusCode, $endpoint, $response = null)
    {
        $statusCode = (int)$statusCode;

        if ($statusCode >= 200 && $statusCode < 300) {
            $this->JanisConnectorLogger->info('Connection status: ' . $statusCode);
            return;
        }

        $this->JanisConnectorLogger->error(sprintf(
            'Janis answered status %s for %s',
            $statusCode,
            $endpoint
        ));

        throw new JanisApiException(
            __('Janis answered status %1.', $statusCode),
            $statusCode,
            $response
        );
    }
}
