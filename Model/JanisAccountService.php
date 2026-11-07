<?php

namespace JanisCommerce\JanisConnector\Model;

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
 *
 * Rest is used directly instead of extending JanisConnector because that base
 * class ends a failed request with a redirect and exit(), which would abort
 * the configuration save it is running inside. Here every failure is an
 * exception the observer can swallow.
 */
class JanisAccountService
{
    /**
     * Account fields Janis accepts as they are. All of them travel on every
     * save: `server` and `status` because the schema makes them mandatory, and
     * the rest because the account is sent whole.
     *
     * `accessToken` is deliberately out. The schema accepts it and the read
     * returns it, but it is a credential and Janis keeps the stored one when
     * the request leaves it out, so there is no reason to move it around.
     */
    const ACCOUNT_FIELDS = [
        'name',
        'server',
        'status',
        'linkedAccounts'
    ];

    /**
     * Value sent for an account field Janis did not return, so the key travels
     * anyway instead of being dropped from the request.
     */
    const ACCOUNT_FIELD_DEFAULTS = [
        'linkedAccounts' => []
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
     * @var Rest
     */
    private $rest;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var JanisConnectorLogger
     */
    private $logger;

    /**
     * JanisAccountService constructor.
     * @param Rest $rest
     * @param Data $helper
     * @param JanisConnectorLogger $logger
     */
    public function __construct(
        Rest $rest,
        Data $helper,
        JanisConnectorLogger $logger
    ) {
        $this->rest = $rest;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Sends the Magento settings to the Janis account with the given name.
     *
     * @param string $accountName
     * @return string Id of the updated account
     * @throws \RuntimeException When Janis is unreachable, answers an error or has no such account
     */
    public function pushSettings($accountName)
    {
        $account = $this->findByName($accountName);

        $payload = $this->buildPayload($account);

        $endpoint = $this->helper->getJanisCommerceAccountEndpoint();

        $this->logger->info('[JanisConnector] Sending the account to ' . $endpoint . ': ' . json_encode($payload));

        $response = $this->request($endpoint, 'POST', json_encode($payload));

        $this->logger->info('[JanisConnector] Janis answered: ' . json_encode($response));

        return $account['id'];
    }

    /**
     * Reads the account Janis holds for the configured name.
     *
     * @param string $accountName
     * @return array
     * @throws \RuntimeException
     */
    public function findByName($accountName)
    {
        $response = $this->request($this->helper->getJanisCommerceAccountEndpoint(null, $accountName), 'GET');

        $account = is_array($response) && isset($response[0]) ? $response[0] : null;

        if (!is_array($account) || empty($account['id'])) {
            throw new \RuntimeException(sprintf('Janis has no account named "%s"', $accountName));
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
     * @return array
     */
    private function buildPayload(array $account)
    {
        $payload = ['id' => $account['id']];

        foreach (self::ACCOUNT_FIELDS as $field) {
            if (array_key_exists($field, $account)) {
                $payload[$field] = $account[$field];
            } elseif (array_key_exists($field, self::ACCOUNT_FIELD_DEFAULTS)) {
                $payload[$field] = self::ACCOUNT_FIELD_DEFAULTS[$field];
            }
        }

        foreach (self::ACCOUNT_FEATURES as $feature) {
            $payload[$feature] = !empty($account[$feature]);
        }

        // The block always travels, even empty: Janis rebuilds `behaviors` from
        // the request, so leaving it out is not "do not touch it", it is "reset
        // it". The same goes for `oms`.
        $payload['behaviors'] = $this->pick($account['behaviors'] ?? [], self::ACCOUNT_BEHAVIORS);

        $payload['oms'] = $this->buildOms($account['oms'] ?? []);

        return $payload;
    }

    /**
     * OMS block of the account, with the order statuses taken from Magento.
     *
     * Magento owns both statuses, so both are sent on every save and the ones
     * it no longer has travel as null. Keeping the value Janis already held
     * when the administrator clears a status would leave Janis waiting for a
     * notification Magento stopped sending, and the configuration screen
     * showing something different from what Janis actually has.
     *
     * The invoiced status follows the invoice notification switch for the same
     * reason: with the notification off nothing will ever reach that status.
     *
     * @param array $currentOms
     * @return array
     */
    private function buildOms(array $currentOms)
    {
        $oms = $this->pick($currentOms, self::OMS_FIELDS);

        $createdStatus = $this->helper->getOrderCreatedStatus();

        $invoicedStatus = $this->helper->isInvoiceNotificationEnabled()
            ? $this->helper->getOrderInvoicedStatus()
            : null;

        $oms['orderImportStatuses'] = [
            'onImport' => !empty($createdStatus) ? [$createdStatus] : null,
            'onInvoice' => !empty($invoicedStatus) ? [$invoicedStatus] : null
        ];

        return $oms;
    }

    /**
     * Performs the request, turning both a transport failure and a non 2xx
     * answer into an exception.
     *
     * @param string $endpoint
     * @param string $method
     * @param array|string $params
     * @return array|null
     * @throws \RuntimeException
     */
    private function request($endpoint, $method, $params = [])
    {
        try {
            $response = $this->rest->request($endpoint, $method, $params);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('the %s request to %s could not be completed: %s', $method, $endpoint, $e->getMessage()),
                0,
                $e
            );
        }

        $status = (int)$this->rest->getStatus();

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(sprintf('Janis answered status %s for %s', $status, $endpoint));
        }

        return $response;
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
