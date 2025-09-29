<?php

namespace JanisCommerce\JanisConnector\Model\DataMappers;

abstract class AbstractAttributeMapper
{
    protected $obj;

    const IS_STRING = 1;
    const IS_NUMERIC = 2;
    const IS_BOOLEAN = 3;


    abstract public function builtOrderNotificationPayload($jsonEncoded = false);

    /**
     * Set a current object to be used as default
     *
     * @param $obj
     */
    public function setObj($obj)
    {
        $this->obj = $obj;
    }

    /**
     * Adds an array attribute to payload array
     *
     * @param string $index Name of DB parameter to use as
     * @param string $value Value to set
     * @param array $payloadArray Current payload array
     * @param int $type of value to be used to built the payload
     * @return array
     */
    protected function addToPayload($index, $value, $payloadArray, $type = self::IS_STRING)
    {
        switch ( $type )
        {
            case self::IS_NUMERIC:
                return array_merge($payloadArray, [$index => (float)$value]);

            case self::IS_BOOLEAN:
                return array_merge($payloadArray, [$index => (bool)$value]);

            default:
                return array_merge($payloadArray, [$index => $value]);
        }
    }
}
