<?php

namespace JanisCommerce\JanisConnector\Observer;

use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Model\JanisAccountService;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Pushes the configuration to Janis every time the section is saved.
 */
class ConfigSaveObserver implements ObserverInterface
{
    const SECTION = 'janis_configuration_section';

    const LAST_UPDATE_FORMAT = 'Y-m-d H:i:s T';

    /**
     * @var JanisAccountService
     */
    private $accountService;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var JanisConnectorLogger
     */
    private $logger;

    public function __construct(
        JanisAccountService $accountService,
        Data $helper,
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig,
        TimezoneInterface $timezone,
        JanisConnectorLogger $logger
    ) {
        $this->accountService = $accountService;
        $this->helper = $helper;
        $this->configWriter = $configWriter;
        $this->reinitableConfig = $reinitableConfig;
        $this->timezone = $timezone;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        if ($observer->getEvent()->getSection() !== self::SECTION) {
            return;
        }

        $accountName = $this->helper->getJanisAccountName();

        if (!$this->isReadyToSync($accountName)) {
            return;
        }

        try {
            $accountId = $this->accountService->pushSettings($accountName);
        } catch (\Throwable $e) {
            $this->logger->error('[JanisConnector] Settings were not sent to Janis: ' . $e->getMessage());
            return;
        }

        $this->registerLastUpdate();

        $this->logger->info('[JanisConnector] Settings sent to Janis.', ['accountId' => $accountId]);
    }

    /**
     * Nothing is sent until Janis can be called and the account is known.
     *
     * @param string|null $accountName
     * @return bool
     */
    private function isReadyToSync($accountName)
    {
        if (empty($this->helper->getJanisClient())
            || empty($this->helper->getJanisApiKey())
            || empty($this->helper->getJanisApiSecret())) {
            $this->logger->warning('[JanisConnector] Settings were not sent to Janis: credentials are missing.');
            return false;
        }

        if (empty($accountName)) {
            $this->logger->warning('[JanisConnector] Settings were not sent to Janis: the account name is missing.');
            return false;
        }

        if (empty($this->helper->getOrderCreatedStatus()) && empty($this->helper->getOrderInvoicedStatus())) {
            $this->logger->info('[JanisConnector] Settings were not sent to Janis: no order status is configured.');
            return false;
        }

        return true;
    }

    /**
     * Leaves the moment of the last successful sync on the configuration
     * screen. The scope config is reloaded so the field already shows it when
     * the page comes back.
     */
    private function registerLastUpdate()
    {
        $this->configWriter->save(
            Data::LAST_UPDATE,
            $this->timezone->date()->format(self::LAST_UPDATE_FORMAT)
        );

        $this->reinitableConfig->reinit();
    }
}
