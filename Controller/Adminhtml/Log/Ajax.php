<?php
namespace JanisCommerce\JanisConnector\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\RawFactory;

class Ajax extends Action
{
    const ADMIN_RESOURCE = 'JanisCommerce_JanisConnector::config_JanisCommerce_JanisConnector';

    private $resultRawFactory;

    public function __construct(
        Action\Context $context,
        RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
    }

    public function execute()
    {
        $logFile = BP . '/var/log/janis_connector.log';
        $output = __('Log file not found.');

        if (file_exists($logFile)) {
            $lines = explode("\n", file_get_contents($logFile));
            $output = implode("\n", array_slice($lines, -200)); // últimas 200 líneas
        }

        return $this->resultRawFactory->create()->setContents($output);
    }
}
