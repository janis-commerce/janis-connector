<?php
namespace JanisCommerce\JanisConnector\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class LogViewer extends Field
{
    // protected function _getElementHtml(AbstractElement $element)
    // {
    //     $url = $this->getUrl('janisconnector/log/ajax'); // route al controller
    //     $html = '<pre id="janis-log-content" style="height:400px;overflow:auto;background:#000;color:#0f0;padding:10px;">Loading log...</pre>';
    //     $html .= "<script>
    //         function loadJanisLog(){
    //             fetch('{$url}')
    //                 .then(r => r.text())
    //                 .then(txt => {
    //                     let pre = document.getElementById('janis-log-content');
    //                     pre.textContent = txt;
    //                     pre.scrollTop = pre.scrollHeight;
    //                 });
    //         }
    //         loadJanisLog();
    //         setInterval(loadJanisLog, 5000);
    //     </script>";
    //     return $html;
    // }

    // protected function _getHeaderHtml($element)
    // {
    //     return '<h3>' . __('Logs') . '</h3>';
    // }

    // protected function _getElementHtml(AbstractElement $element)
    // {
    //     $logFile = BP . '/var/log/janis_connector.log'; // ⚡ cámbialo por el log que quieras
    //     $lines = [];

    //     if (file_exists($logFile)) {
    //         $content = file($logFile); // Lee como array de líneas
    //         $lines = array_slice($content, -50); // Solo últimas 50 líneas
    //     }

    //     return '<pre style="
    //         height:500px;
    //         overflow:auto;
    //         background:#000;
    //         color:#0f0;
    //         padding:10px;
    //         white-space:pre-wrap;      /* permite que no se vaya de ancho */
    //         word-wrap:break-word;      /* corta líneas largas */
    //         font-size:12px;
    //         width:100%;                /* ocupa todo el ancho */
    //         box-sizing:border-box;
    //     ">'
    //         . htmlspecialchars(implode("", $lines))
    //         . '</pre>';

    //     // return '<pre style="height:400px;overflow:auto;background:#000;color:#0f0;padding:10px">'
    //     //     . htmlspecialchars(implode("", $lines))
    //     //     . '</pre>';
    // }

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
