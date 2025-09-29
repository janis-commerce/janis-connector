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

    const API_ENVIRONMENT = "janis_configuration_section/janis_credentials_group/api_environment";
    const JANIS_CLIENT = "janis_configuration_section/janis_credentials_group/janis_client";
    const JANIS_API_KEY = "janis_configuration_section/janis_credentials_group/janis_api_key";
    const JANIS_API_SECRET = "janis_configuration_section/janis_credentials_group/janis_api_secret";

    const JANIS_SALES_CHANNEL_ID = "janis_configuration_section/janis_simulate_group/janis_sales_channel_id";

    const JANIS_ACCOUNT_NAME = "janis_configuration_section/janis_orders_group/janis_account_name";
    const NOTIFY_INVOICE = "janis_configuration_section/janis_orders_group/notify_invoice";
    const ORDER_CREATED_STATUS = "janis_configuration_section/janis_orders_group/order_created_status";
    const ORDER_INVOICED_STATUS = "janis_configuration_section/janis_orders_group/order_invoiced_status";

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
     * Retrieves the selected environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->scopeConfig->getValue(
            self::API_ENVIRONMENT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves a setup Janis Api Client
     *
     * @return string
     */
    public function getJanisClient()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_CLIENT,
            ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Retrieves a setup Janis Api Key
     *
     * @return string
     */
    public function getJanisApiKey()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_API_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Retrieves a setup Janis Secret Key
     *
     * @return string
     */
    public function getJanisApiSecret()
    {
        return $this->scopeConfig->getValue(
            self::JANIS_API_SECRET,
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
     * Retrieves the Janis endpoint URL for order notifications based on environment
     *
     * @return string
     */
    public function getJanisEndpointToNotifyOrder()
    {
        $environment = $this->getEnvironment();

        switch ($environment) {
            case 'production':
                return 'https://magento.oms.janis.in/api/order-status-change-hook';
            case 'qa':
                return 'https://magento.oms.janisqa.in/api/order-status-change-hook';
            case 'beta':
                return 'https://magento.oms.janisdev.in/api/order-status-change-hook';
            default:
                return 'https://magento.oms.janis.in/api/order-status-change-hook'; // Default to production
        }
    }

    /**
     * Retrieves the Janis endpoint URL for split carts based on environment
     *
     * @return string
     */
    public function getJanisEndpointToSplitCarts()
    {
        $environment = $this->getEnvironment();

        switch ($environment) {
            case 'production':
                return 'https://public.delivery.janis.in/api/simulate-by-shipping-type';
            case 'qa':
                return 'https://public.delivery.janisqa.in/api/simulate-by-shipping-type';
            case 'beta':
                return 'https://public.delivery.janisdev.in/api/simulate-by-shipping-type';
            default:
                return 'https://public.delivery.janis.in/api/simulate-by-shipping-type'; // Default to production
        }
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

    /**
     * Retrieves the order created status setting
     *
     * @return string
     */
    public function getOrderCreatedStatus()
    {
        return $this->scopeConfig->getValue(
            self::ORDER_CREATED_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves the order invoiced status setting
     *
     * @return string
     */
    public function getOrderInvoicedStatus()
    {
        return $this->scopeConfig->getValue(
            self::ORDER_INVOICED_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieves the invoice notification enabled setting
     *
     * @return bool
     */
    public function isInvoiceNotificationEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::NOTIFY_INVOICE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
