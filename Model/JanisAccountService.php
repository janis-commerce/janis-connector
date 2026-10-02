<?php

namespace JanisCommerce\JanisConnector\Model;

use JanisCommerce\JanisConnector\Exception\JanisApiException;
use JanisCommerce\JanisConnector\Helper\Data;
use JanisCommerce\JanisConnector\Logger\JanisConnectorLogger;
use JanisCommerce\JanisConnector\Util\Rest;

/**
 * Keeps the Janis account aligned with the settings configured in Magento.
 *
 * Janis replaces the whole account on every save: the fields the request
 * leaves out are reset -- booleans become false and behaviours become null --
 * so sending only the two order statuses would silently turn off everything
 * else. The account is read first and sent back complete, with just the
 * settings owned by Magento overwritten on top.
 */
class JanisAccountService extends JanisConnector
{
    /**
     * Account fields Janis accepts as they are.
     */
    const ACCOUNT_FIELDS = [
        'name',
        'server',
        'status',
        'linkedAccounts'
    ];

    /**
     * Flags Janis rebuilds from the request on every save. An absent flag is
     * persisted as false, which is why all of them always travel.
     */
    const ACCOUNT_FEATURES = [
        'publishCategories',
        'publishBrands',
        'publishAttributes',
        'publishProducts',
        'publishPrices',
        'publishStock',
        'importOrders'
    ];

    /**
     * Behaviours Janis accepts. Same reasoning as the features: the ones left
     * out of the request are persisted as null.
     */
    const ACCOUNT_BEHAVIORS = [
        'publishProductsBehavior',
        'publishAttributesBehavior',
        'publishPricesBehavior',
        'publishProductsScopeBehavior',
        'publishAttributesScopeBehavior',
        'publishCategoriesScopeBehavior',
        'publishBrandsScopeBehavior',
        'publishRootCategory',
        'publishBrandCode'
    ];

    /**
     * OMS fields Janis accepts inside the account.
     */
    const OMS_FIELDS = [
        'statusToImportOrders',
        'orderImportStatuses',
        'orderImportStatusOnInvoice',
        'customOrderChangesProcessor',
        'orderChangesProcessor',
        'storeCode'
    ];

    /**
     * @var Data
     */
    private $helper;

    /**
     * JanisAccountService constructor.
     * @param Rest $rest
     * @param Data $helper
     * @param JanisConnectorLogger $JanisConnectorLogger
     */
    public function __construct(
        Rest $rest,
        Data $helper,
        JanisConnectorLogger $JanisConnectorLogger
    ) {
        $this->helper = $helper;
        parent::__construct($rest, $helper, $JanisConnectorLogger);
    }

    /**
     * Sends the Magento settings to the Janis account with the given name.
     *
     * @param string $accountName
     * @param string|null $store
     * @return string Id of the updated account
     * @throws JanisApiException When Janis is unreachable or answers an error
     * @throws \RuntimeException When no account matches the configured name
     */
    public function pushSettings($accountName, $store = null)
    {
        $account = $this->findByName($accountName, $store);

        $payload = $this->buildPayload($account, $store);

        $this->post($this->helper->getJanisCommerceAccountEndpoint(null, null, $store), json_encode($payload));

        return $account['id'];
    }

    /**
     * Reads the account Janis holds for the configured name.
     *
     * @param string $accountName
     * @param string|null $store
     * @return array
     * @throws JanisApiException
     * @throws \RuntimeException
     */
    public function findByName($accountName, $store = null)
    {
        $endpoint = $this->helper->getJanisCommerceAccountEndpoint(null, $accountName, $store);

        $response = $this->get($endpoint);

        $account = is_array($response) && isset($response[0]) ? $response[0] : null;

        if (!is_array($account) || empty($account['id'])) {
            throw new \RuntimeException(
                sprintf('Janis has no account named "%s".', $accountName)
            );
        }

        return $account;
    }

    /**
     * Rebuilds the account exactly as Janis expects it, with the Magento
     * settings applied over the values Janis already holds.
     *
     * Only the fields Janis declares are sent: the request is validated
     * against a closed schema and an unknown key makes the whole save fail.
     *
     * @param array $account Account as Janis returned it
     * @param string|null $store
     * @return array
     */
    private function buildPayload(array $account, $store = null)
    {
        $payload = ['id' => $account['id']];

        foreach (self::ACCOUNT_FIELDS as $field) {
            if (array_key_exists($field, $account)) {
                $payload[$field] = $account[$field];
            }
        }

        foreach (self::ACCOUNT_FEATURES as $feature) {
            $payload[$feature] = !empty($account[$feature]);
        }

        $behaviors = $this->pick($account['behaviors'] ?? [], self::ACCOUNT_BEHAVIORS);

        if ($behaviors) {
            $payload['behaviors'] = $behaviors;
        }

        $payload['oms'] = $this->buildOms($account['oms'] ?? [], $store);

        return $payload;
    }

    /**
     * OMS block of the account, with the order statuses taken from Magento.
     *
     * @param array $currentOms
     * @param string|null $store
     * @return array
     */
    private function buildOms(array $currentOms, $store = null)
    {
        $oms = $this->pick($currentOms, self::OMS_FIELDS);

        $statuses = isset($oms['orderImportStatuses']) && is_array($oms['orderImportStatuses'])
            ? $oms['orderImportStatuses']
            : [];

        $createdStatus = $this->helper->getOrderCreatedStatus($store);
        $invoicedStatus = $this->helper->getOrderInvoicedStatus($store);

        if (!empty($createdStatus)) {
            $statuses['onImport'] = [$createdStatus];
        }

        if (!empty($invoicedStatus)) {
            $statuses['onInvoice'] = [$invoicedStatus];
        }

        if ($statuses) {
            $oms['orderImportStatuses'] = $statuses;
        }

        return $oms;
    }

    /**
     * @param array $source
     * @param string[] $keys
     * @return array
     */
    private function pick(array $source, array $keys)
    {
        $picked = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $source)) {
                $picked[$key] = $source[$key];
            }
        }

        return $picked;
    }
}
