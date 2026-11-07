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
     * Account keys that never travel.
     *
     * Everything else does, including a field added to the account after this
     * class was written. Janis validates the request against a closed schema
     * that silently drops every key it does not declare -- at the top level and
     * inside `behaviors` and `oms` alike -- so forwarding the account whole
     * costs nothing and keeping a list of accepted fields here would only mean
     * that each new field is dropped until somebody remembers to add it.
     *
     * `accessToken` is the one exception: the read returns it and the schema
     * accepts it, but it is a credential, and Janis keeps the stored one when
     * the request leaves it out, so there is no reason to move it around.
     */
    const NOT_SENT = [
        'accessToken'
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
     * The account as Janis returned it, with the Magento settings written on
     * top and the keys of NOT_SENT removed.
     *
     * Nothing else is filtered. The request is validated against a closed
     * schema that keeps what it declares and discards the rest, so the read
     * only fields of the account (`dateCreated`, the `import*` flags, the
     * computed `behaviors.publishStoreViewId`) cost nothing by travelling, and
     * a field added to the account tomorrow reaches Janis without anyone
     * having to come back and declare it here.
     *
     * @param array $account Account as Janis returned it
     * @return array
     */
    private function buildPayload(array $account)
    {
        $payload = array_diff_key($account, array_flip(self::NOT_SENT));

        // Both blocks travel even when the account has none, because Janis
        // rebuilds them from the request: leaving one out is not "do not touch
        // it", it is "reset it".
        $payload['behaviors'] = $account['behaviors'] ?? [];

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
        $oms = $currentOms;

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

}
