<?php
declare(strict_types=1);

namespace JanisCommerce\JanisConnector\Api;

interface SystemInformationInterface
{
    /**
     * Installed module version plus PHP, Magento and Janis cron status.
     *
     * Lets the running version be audited remotely: /V1/modules only returns
     * module names, with no version attached.
     *
     * @return mixed
     */
    public function execute();
}
