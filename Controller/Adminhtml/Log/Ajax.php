<?php
namespace JanisCommerce\JanisConnector\Controller\Adminhtml\Log;

use JanisCommerce\JanisConnector\Model\Log\Reader;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\RawFactory;

class Ajax extends Action
{
    const ADMIN_RESOURCE = 'JanisCommerce_JanisConnector::config_JanisCommerce_JanisConnector';

    private $resultRawFactory;

    /**
     * @var Reader
     */
    private $reader;

    public function __construct(
        Action\Context $context,
        RawFactory $resultRawFactory,
        Reader $reader
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->reader = $reader;
    }

    public function execute()
    {
        $lines = $this->reader->tail($this->getRequestedLines(), true);

        $output = $lines
            ? implode("\n", $lines)
            : (string)__('Log file not found.');

        return $this->resultRawFactory->create()->setContents($output);
    }

    /**
     * @return int
     */
    private function getRequestedLines()
    {
        $requested = (int)$this->getRequest()->getParam('lines');

        return $requested > 0 ? min($requested, 2000) : Reader::DEFAULT_LINES;
    }
}
