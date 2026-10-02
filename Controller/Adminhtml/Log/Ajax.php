<?php
namespace JanisCommerce\JanisConnector\Controller\Adminhtml\Log;

use JanisCommerce\JanisConnector\Model\Log\Reader;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Feeds the log viewer of the configuration screen.
 *
 * The first call returns the tail of the current file together with its size.
 * Every following call sends that size back as an offset and only the bytes
 * appended since then travel to the browser, so polling stays cheap no matter
 * how large the file grows.
 */
class Ajax extends Action
{
    const ADMIN_RESOURCE = 'JanisCommerce_JanisConnector::config_JanisCommerce_JanisConnector';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Reader
     */
    private $reader;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        Reader $reader
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->reader = $reader;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $file = $this->reader->resolveCurrentFile();

        if (!$file) {
            return $result->setData([
                'file' => null,
                'offset' => 0,
                'lines' => [],
                'reset' => true,
                'message' => (string)__('No log entries yet.')
            ]);
        }

        $name = basename($file);

        if ($this->shouldReload($name)) {
            return $result->setData([
                'file' => $name,
                'offset' => $this->reader->getFileSize($file),
                'lines' => $this->reader->tail($this->getRequestedLines(), false),
                'reset' => true,
                'message' => null
            ]);
        }

        $appended = $this->reader->readSince($file, (int)$this->getRequest()->getParam('offset'));

        return $result->setData([
            'file' => $name,
            'offset' => $appended['offset'],
            'lines' => $appended['lines'],
            'reset' => false,
            'message' => null
        ]);
    }

    /**
     * Whether the whole tail has to be sent again instead of the new bytes.
     *
     * Happens on the first call, when the viewer is still showing yesterday's
     * file after the daily rotation, and when the file shrank -- the offset the
     * browser holds no longer points at the same content.
     *
     * @param string $name Base name of the file currently being written
     * @return bool
     */
    private function shouldReload($name)
    {
        $request = $this->getRequest();

        $offset = $request->getParam('offset');

        if ($offset === null || $offset === '' || $request->getParam('file') !== $name) {
            return true;
        }

        return (int)$offset > $this->reader->getFileSize($this->reader->resolveCurrentFile());
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
