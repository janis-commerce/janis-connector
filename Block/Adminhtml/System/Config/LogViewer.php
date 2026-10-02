<?php
namespace JanisCommerce\JanisConnector\Block\Adminhtml\System\Config;

use JanisCommerce\JanisConnector\Model\Log\Reader;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Live view of the connector log, inside the configuration screen.
 *
 * The block only renders the shell: the content is fetched from
 * janis_connector/log/ajax and kept up to date by the log-viewer component,
 * so the screen behaves like a tail -f without reloading the page.
 */
class LogViewer extends Field
{
    const LINES_TO_DISPLAY = 'janis_logs_section/janis_logs_group/lines_to_display';

    /**
     * How many lines the browser keeps before dropping the oldest ones.
     */
    const BUFFER_LINES = 2000;

    /**
     * Milliseconds between polls.
     */
    const DEFAULT_INTERVAL = 5000;

    /**
     * @var string
     */
    protected $_template = 'JanisCommerce_JanisConnector::system/config/log-viewer.phtml';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Json
     */
    private $json;

    /**
     * LogViewer constructor.
     * @param Context $context
     * @param Reader $reader
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        Reader $reader,
        Json $json,
        array $data = []
    ) {
        $this->reader = $reader;
        $this->json = $json;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $this->setElementId($element->getHtmlId());

        return sprintf(
            '<tr id="row_%s"><td colspan="2">%s</td></tr>',
            $element->getHtmlId(),
            $this->toHtml()
        );
    }

    /**
     * Configuration handed over to the javascript component.
     *
     * @return string
     */
    public function getViewerConfig()
    {
        return $this->json->serialize([
            'url' => $this->getUrl('janis_connector/log/ajax'),
            'lines' => $this->getLinesToDisplay(),
            'bufferLines' => self::BUFFER_LINES,
            'interval' => self::DEFAULT_INTERVAL
        ]);
    }

    /**
     * Name of the file the viewer starts with, for the caption.
     *
     * @return string
     */
    public function getCurrentFileName()
    {
        $file = $this->reader->resolveCurrentFile();

        return $file ? basename($file) : (string)__('no log file yet');
    }

    /**
     * @return int
     */
    public function getLinesToDisplay()
    {
        $configured = (int)$this->_scopeConfig->getValue(self::LINES_TO_DISPLAY);

        return $configured > 0 ? $configured : Reader::DEFAULT_LINES;
    }

    /**
     * Polling intervals offered in the toolbar.
     *
     * @return array
     */
    public function getIntervalOptions()
    {
        return [
            2000 => (string)__('2 seconds'),
            5000 => (string)__('5 seconds'),
            10000 => (string)__('10 seconds'),
            30000 => (string)__('30 seconds')
        ];
    }
}
