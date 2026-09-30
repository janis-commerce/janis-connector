<?php
namespace JanisCommerce\JanisConnector\Block\Adminhtml\System\Config;

use JanisCommerce\JanisConnector\Model\SystemInformation as SystemInformationModel;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Read-only panel with the module version, the PHP and Magento runtime, and
 * the last runs of the connector's own cron jobs.
 */
class SystemInformation extends Field
{
    /**
     * @var SystemInformationModel
     */
    private $systemInformation;

    /**
     * SystemInformation constructor.
     * @param Context $context
     * @param SystemInformationModel $systemInformation
     * @param array $data
     */
    public function __construct(
        Context $context,
        SystemInformationModel $systemInformation,
        array $data = []
    ) {
        $this->systemInformation = $systemInformation;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $data = $this->systemInformation->getData();

        $html = $this->renderSection((string)__('Module'), [
            (string)__('Name') => $data['module']['name'],
            (string)__('Installed version') => $data['module']['version'] !== ''
                ? $data['module']['version']
                : (string)__('not reported — check that composer.json declares a version')
        ]);

        $html .= $this->renderSection((string)__('PHP'), [
            (string)__('PHP version') => $data['php']['version'],
            (string)__('Memory limit') => $data['php']['memory_limit'],
            (string)__('Max execution time') => $data['php']['max_execution_time'],
            (string)__('Post max size') => $data['php']['post_max_size'],
            (string)__('Max input vars') => $data['php']['max_input_vars'],
            (string)__('Path to php.ini') => $data['php']['php_ini_path']
        ]);

        $html .= $this->renderSection((string)__('Magento'), [
            (string)__('Magento mode') => $data['magento']['mode'],
            (string)__('Current Magento time') => $data['magento']['current_time'],
            (string)__('Current database time') => $data['magento']['database_time']
        ]);

        $html .= $this->renderCron($data['cron']);

        return sprintf(
            '<tr id="row_%s"><td colspan="2"><div style="max-width:100%%;overflow:auto;">%s</div></td></tr>',
            $element->getHtmlId(),
            $html
        );
    }

    /**
     * @param string $title
     * @param array $rows
     * @return string
     */
    private function renderSection($title, array $rows)
    {
        $body = '';

        foreach ($rows as $label => $value) {
            $body .= sprintf(
                '<tr><th style="text-align:left;padding:4px 16px 4px 0;font-weight:600;white-space:nowrap;">%s</th><td style="padding:4px 0;">%s</td></tr>',
                htmlspecialchars((string)$label),
                htmlspecialchars((string)$value)
            );
        }

        return sprintf(
            '<h3 style="margin:16px 0 6px;">%s</h3><table style="border-collapse:collapse;">%s</table>',
            htmlspecialchars($title),
            $body
        );
    }

    /**
     * @param array $entries
     * @return string
     */
    private function renderCron(array $entries)
    {
        $title = sprintf('<h3 style="margin:16px 0 6px;">%s</h3>', htmlspecialchars((string)__('Janis cron (last %1)', SystemInformationModel::CRON_HISTORY_SIZE)));

        if (!$entries) {
            return $title . sprintf(
                '<p style="margin:0;color:#a94442;">%s</p>',
                htmlspecialchars((string)__('No runs recorded yet. If this stays empty, the Janis cron group is not running.'))
            );
        }

        $headers = [
            __('Job code'),
            __('Status'),
            __('Scheduled at'),
            __('Executed at'),
            __('Finished at'),
            __('Messages')
        ];

        $head = '';

        foreach ($headers as $header) {
            $head .= sprintf(
                '<th style="text-align:left;padding:4px 12px 4px 0;border-bottom:1px solid #ccc;white-space:nowrap;">%s</th>',
                htmlspecialchars((string)$header)
            );
        }

        $body = '';

        foreach ($entries as $entry) {
            $body .= sprintf(
                '<tr>
                    <td style="padding:4px 12px 4px 0;">%s</td>
                    <td style="padding:4px 12px 4px 0;">%s</td>
                    <td style="padding:4px 12px 4px 0;white-space:nowrap;">%s</td>
                    <td style="padding:4px 12px 4px 0;white-space:nowrap;">%s</td>
                    <td style="padding:4px 12px 4px 0;white-space:nowrap;">%s</td>
                    <td style="padding:4px 0;">%s</td>
                </tr>',
                htmlspecialchars((string)$entry['job_code']),
                htmlspecialchars((string)$entry['status']),
                htmlspecialchars((string)$entry['scheduled_at']),
                htmlspecialchars((string)($entry['executed_at'] ?? '')),
                htmlspecialchars((string)($entry['finished_at'] ?? '')),
                htmlspecialchars((string)($entry['messages'] ?? ''))
            );
        }

        return $title . sprintf(
            '<table style="border-collapse:collapse;"><thead><tr>%s</tr></thead><tbody>%s</tbody></table>',
            $head,
            $body
        );
    }
}
