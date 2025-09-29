<?php
namespace JanisCommerce\JanisConnector\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class LogViewer extends Field
{

    public function render(AbstractElement $element): string
    {
        $logFile = BP . '/var/log/janis_connector.log';
        $output = '';
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -200);
            $output = implode("", $lines);
        }

        return sprintf(
            '<tr id="row_%s"><td colspan="2">
                <div style="max-width:100%%;overflow:auto;">
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
            htmlspecialchars($output)
        );
    }
}
