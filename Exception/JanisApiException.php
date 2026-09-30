<?php

namespace JanisCommerce\JanisConnector\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Thrown when a request to a Janis endpoint cannot be completed or comes back
 * with an unexpected status. Replaces the previous behaviour of redirecting and
 * calling exit(), which killed the whole cron run on the first failure.
 */
class JanisApiException extends LocalizedException
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var mixed
     */
    private $responseBody;

    /**
     * @param Phrase $phrase
     * @param int $statusCode HTTP status returned by Janis, 0 when the request never completed
     * @param mixed $responseBody Decoded response body, when there is one
     * @param \Exception|null $cause
     */
    public function __construct(Phrase $phrase, $statusCode = 0, $responseBody = null, \Exception $cause = null)
    {
        $this->statusCode = (int)$statusCode;
        $this->responseBody = $responseBody;

        parent::__construct($phrase, $cause);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
