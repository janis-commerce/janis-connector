<?php
declare(strict_types=1);

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Api\SystemInformationInterface;

class SystemInformationProvider implements SystemInformationInterface
{
    /**
     * @var SystemInformation
     */
    private $systemInformation;

    /**
     * SystemInformationProvider constructor.
     * @param SystemInformation $systemInformation
     */
    public function __construct(
        SystemInformation $systemInformation
    ) {
        $this->systemInformation = $systemInformation;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->systemInformation->getData();
    }
}
