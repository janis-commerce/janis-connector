<?php

namespace JanisCommerce\JanisConnector\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Collects the runtime facts needed to diagnose the connector without asking
 * for server access: which version is installed, what PHP it runs on, what
 * time each side thinks it is, and whether its own cron is actually running.
 */
class SystemInformation
{
    const MODULE_NAME = 'JanisCommerce_JanisConnector';

    /**
     * Only this module's jobs, not Magento's global cron.
     */
    const CRON_JOB_CODES = [
        'janis_connector_send_created_orders',
        'janis_connector_send_invoiced_orders'
    ];

    const CRON_HISTORY_SIZE = 5;

    /**
     * @var PackageInfo
     */
    private $packageInfo;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * SystemInformation constructor.
     */
    public function __construct(
        PackageInfo $packageInfo,
        ComponentRegistrar $componentRegistrar,
        ResourceConnection $resourceConnection,
        State $appState,
        TimezoneInterface $timezone,
        Json $serializer
    ) {
        $this->packageInfo = $packageInfo;
        $this->componentRegistrar = $componentRegistrar;
        $this->resourceConnection = $resourceConnection;
        $this->appState = $appState;
        $this->timezone = $timezone;
        $this->serializer = $serializer;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'module' => [
                'name' => self::MODULE_NAME,
                'version' => $this->getModuleVersion()
            ],
            'php' => $this->getPhpInformation(),
            'magento' => $this->getMagentoInformation(),
            'cron' => $this->getCronHistory()
        ];
    }

    /**
     * Installed version of the module.
     *
     * PackageInfo reads the "version" key of the module's composer.json, which
     * is why it has to be declared there. Instances installed by copying the
     * module into app/code have no composer.lock to fall back on, so the file
     * is read directly as a second attempt.
     *
     * @return string
     */
    public function getModuleVersion()
    {
        $version = (string)$this->packageInfo->getVersion(self::MODULE_NAME);

        if ($version !== '') {
            return $version;
        }

        return $this->readVersionFromComposerJson();
    }

    /**
     * @return array
     */
    private function getPhpInformation()
    {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'php_ini_path' => php_ini_loaded_file() ?: 'n/a'
        ];
    }

    /**
     * @return array
     */
    private function getMagentoInformation()
    {
        return [
            'mode' => $this->getApplicationMode(),
            'current_time' => $this->timezone->date()->format('Y-m-d H:i:s T'),
            'database_time' => $this->getDatabaseTime()
        ];
    }

    /**
     * @return string
     */
    private function getApplicationMode()
    {
        try {
            return $this->appState->getMode();
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Reading the database clock separately makes a drift between both sides
     * visible, which is a common reason for cron jobs not firing when expected.
     *
     * @return string
     */
    private function getDatabaseTime()
    {
        try {
            return (string)$this->resourceConnection->getConnection()->fetchOne('SELECT NOW()');
        } catch (\Exception $e) {
            return 'n/a';
        }
    }

    /**
     * Last runs of this module's cron jobs.
     *
     * @return array
     */
    public function getCronHistory()
    {
        try {

            $connection = $this->resourceConnection->getConnection();

            $select = $connection->select()
                ->from(
                    $this->resourceConnection->getTableName('cron_schedule'),
                    ['job_code', 'status', 'created_at', 'scheduled_at', 'executed_at', 'finished_at', 'messages']
                )
                ->where('job_code IN (?)', self::CRON_JOB_CODES)
                ->order('scheduled_at DESC')
                ->limit(self::CRON_HISTORY_SIZE);

            return $connection->fetchAll($select);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return string
     */
    private function readVersionFromComposerJson()
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);

        if (!$path) {
            return '';
        }

        $composerJson = $path . '/composer.json';

        if (!is_readable($composerJson)) {
            return '';
        }

        try {
            $contents = $this->serializer->unserialize(file_get_contents($composerJson));
        } catch (\Exception $e) {
            return '';
        }

        return isset($contents['version']) ? (string)$contents['version'] : '';
    }
}
