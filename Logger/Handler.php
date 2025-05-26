<?php

namespace JanisCommerce\JanisConnector\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * Logging level.
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @var int
     */
    protected $loggerType = JanisConnectorLogger::INFO;

    /**
     * File name.
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @var string
     */
    protected $fileName = '/var/log/janis_connector.log';
}
