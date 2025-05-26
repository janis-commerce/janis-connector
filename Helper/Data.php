<?php

namespace JanisCommerce\JanisConnector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const JANIS_ATTR_CODE = 'janis';
    const DEFAULT_SHIPPING_TYPE = 'delivery';
    const DEFAULT_SLA_NAME = 'express_delivery';
    const DEFAULT_LATITUDE = 0;
    const DEFAULT_LONGITUDE = 0;

    const API_TEST_MODE_ENABLE = "janis_connection_section/janis_connection_group/api_test_mode";

    const API_CLIENT = "janis_connection_section/janis_connection_group/api_client";
    const API_KEY = "janis_connection_section/janis_connection_group/api_key";
    const API_SECRET = "janis_connection_section/janis_connection_group/api_secret";
    const API_URL = "janis_connection_section/janis_connection_group/api_url";

    const API_CLIENT_TEST_MODE = "janis_connection_section/janis_connection_group/api_client_test_mode";
    const API_KEY_TEST_MODE = "janis_connection_section/janis_connection_group/api_key_test_mode";
    const API_SECRET_TEST_MODE = "janis_connection_section/janis_connection_group/api_secret_test_mode";
    const API_URL_TEST_MODE = "janis_connection_section/janis_connection_group/api_url_test_mode";

    const JANIS_ACCOUNT_NAME = "janis_connection_section/janis_orders_group/janis_account_name";
    const JANIS_ENDPOINT_TO_NOTIFY_NEW_ORDER = "janis_connection_section/janis_orders_group/janis_endpoint_create_order";
    const JANIS_ENDPOINT_TO_SPLIT_CARTS = "janis_connection_section/janis_orders_group/janis_endpoint_splitcart";

    const JANIS_SALES_CHANNEL_ID = "janis_connection_section/janis_orders_group/janis_sales_channel_id";

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }


    /**
     * Retrieves a TRUE when Test Mode was enabled, and FALSE when Production Mode was enabled
     *
     * @return bool
     */
    public function getApiTestModeEnable()
    {
        return (bool)$this->scopeConfig->getValue(
            self::API_TEST_MODE_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Api Client to be able to connect on Production Mode
     *
     * @return string
     */
    public function getApiClient()
    {
        return $this->scopeConfig->getValue(
            self::API_CLIENT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Api Client to be able to connect on Test Mode
     *
     * @return string
     */
    public function getApiClientTestMode()
    {
        return $this->scopeConfig->getValue(
            self::API_CLIENT_TEST_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Api Key to be able to connect on Production Mode
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue(
            self::API_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Api Key to be able to connect on Test Mode
     *
     * @return string
     */
    public function getApiKeyTestMode()
    {
        return $this->scopeConfig->getValue(
            self::API_KEY_TEST_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Secret Key to be able to connect on Production Mode
     *
     * @return string
     */
    public function getApiSecret()
    {
        return $this->scopeConfig->getValue(
            self::API_SECRET,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Secret Key to be able to connect on Test Mode
     *
     * @return string
     */
    public function getApiSecretTestMode()
    {
        return $this->scopeConfig->getValue(
            self::API_SECRET_TEST_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis url to be able to connect on Production Mode
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->scopeConfig->getValue(
            self::API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis url to be able to connect on Test Mode
     *
     * @return string
     */
    public function getApiUrlTestMode()
    {
        return $this->scopeConfig->getValue(
            self::API_URL_TEST_MODE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis user name account
     *
     * @return mixed
     */
    public function getJanisAccountName()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_ACCOUNT_NAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup url to target to a Janis EP to sent notifications when a new order with invoice was created
     *
     * @return string
     */
    public function getJanisEndpointToNotifyNewOrder()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_ENDPOINT_TO_NOTIFY_NEW_ORDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup url to target to a Janis EP to splitcarts order
     *
     * @return string
     */
    public function getJanisEndpointToSplitCarts()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_ENDPOINT_TO_SPLIT_CARTS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup value Sale Channel Id
     *
     * @return string
     */
    public function getJanisSalesChannelId()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_SALES_CHANNEL_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return a janis string custom code to be used as key when splitcart by janis is activated
     *
     * @return string janis splitcart custom code
     */
    public function getJanisCode()
    {
        return self::JANIS_ATTR_CODE;
    }

    /**
     * Set a session shipping type to be used as default
     *
     * @param $shippingType
     * @return string
     */
    public function setShippingType($shippingType)
    {
        return $this->checkoutSession->setShippingType($shippingType);
    }

    /**
     * Get a session shipping type to be used when a splitcart body payload to sent Janis EP needs to be built
     *
     * @return string
     */
    public function getShippingType()
    {
        $shippingType = $this->checkoutSession->getShippingType();
        return ($shippingType) ? $shippingType : (self::DEFAULT_SHIPPING_TYPE);
    }
    /**
     * Set a session shipping type to be used as default
     *
     * @param $shippingType
     * @return string
     */
    public function setSlaName($slaName)
    {
        return $this->checkoutSession->setSlaName($slaName);
    }

    /**
     * Get a session shipping type to be used when a splitcart body payload to sent Janis EP needs to be built
     *
     * @return string
     */
    public function getSlaName()
    {
        $slaName = $this->checkoutSession->getSlaName();
        return ($slaName) ? $slaName : (self::DEFAULT_SLA_NAME);
    }

    /**
     * Set a session latitude coordinate to be used as default
     *
     * @param $latitude
     * @return string
     */
    public function setLatitude($latitude)
    {
        (!empty($latitude)) ?: 0;
        return $this->checkoutSession->setCustomerLatitude($latitude);
    }

    /**
     * Get a session latitude coordinate to be used when a splitcart body payload to sent Janis EP needs to be built
     *
     * @return string
     */
    public function getLatitude()
    {
        $latitude = $this->checkoutSession->getCustomerLatitude();
        return ($latitude) ?: (self::DEFAULT_LATITUDE);
    }

    /**
     * Set a session longitude coordinate to be used as default
     *
     * @param $longitude
     * @return string
     */
    public function setLongitude($longitude)
    {
        (!empty($longitude)) ?: 0;
        return $this->checkoutSession->setCustomerLongitude($longitude);
    }

    /**
     * Get a session longitude coordinate to be used when a splitcart body payload to sent Janis EP needs to be built
     *
     * @return string
     */
    public function getLongitude()
    {
        $longitude = $this->checkoutSession->getCustomerLongitude();
        return ($longitude) ?: (self::DEFAULT_LONGITUDE);
    }
}
