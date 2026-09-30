<?php
namespace JanisCommerce\JanisConnector\Block\Adminhtml\System\Config;

use JanisCommerce\JanisConnector\Model\Log\Reader;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class LogViewer extends Field
{
    const LINES_TO_DISPLAY = 'janis_logs_section/janis_logs_group/lines_to_display';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * LogViewer constructor.
     * @param Context $context
     * @param Reader $reader
     * @param array $data
     */
    public function __construct(
        Context $context,
        Reader $reader,
        array $data = []
    ) {
        $this->reader = $reader;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        // Most recent entry first, so the interesting part is visible without scrolling.
        $lines = $this->reader->tail($this->getLinesToDisplay(), true);

        $output = $lines
            ? implode("\n", $lines)
            : (string)__('No log entries yet.');

        $file = $this->reader->resolveCurrentFile();

        $caption = $file
            ? sprintf('%s — %s', basename($file), __('newest first'))
            : (string)__('no log file yet');

        return sprintf(
            '<tr id="row_%s"><td colspan="2">
                <div style="max-width:100%%;overflow:auto;">
                    <p style="margin:0 0 6px;font-size:12px;color:#666;">%s</p>
                    <pre style="
                        background:#000;
                        color:#0f0;
                        font-family:monospace;
                        font-size:13px;
                        line-height:1.4;
                        white-space:pre-wrap;       /* respeta saltos */
                        word-break:break-all;        /* corta palabras largas */
                        overflow-wrap:break-word;    /* corta URLs/JSON */
                        max-height:600px;
                        padding:10px;
                        margin:0;
                        box-sizing:border-box;
                    ">%s</pre>
                </div>
            </td></tr>',
            $element->getHtmlId(),
            htmlspecialchars($caption),
            htmlspecialchars($output)
        );
    }

    /**
     * @return int
     */
    private function getLinesToDisplay()
    {
        $configured = (int)$this->_scopeConfig->getValue(self::LINES_TO_DISPLAY);

        return $configured > 0 ? $configured : Reader::DEFAULT_LINES;
    }
}
