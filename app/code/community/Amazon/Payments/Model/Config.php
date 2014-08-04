<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Model_Config
{
    /**#@+
     * Paths to Payment Method config
     */

    const CONFIG_XML_PATH_ENABLED       = 'payment/amazon_payments/enabled';
    const CONFIG_XML_PATH_CLIENT_ID     = 'payment/amazon_payments/client_id';
    const CONFIG_XML_PATH_CLIENT_SECRET = 'payment/amazon_payments/client_secret';
    const CONFIG_XML_PATH_SELLER_ID     = 'payment/amazon_payments/seller_id';
    const CONFIG_XML_PATH_ACCESS_KEY    = 'payment/amazon_payments/access_key';
    const CONFIG_XML_PATH_ACCESS_SECRET = 'payment/amazon_payments/access_secret';
    const CONFIG_XML_PATH_REGION        = 'payment/amazon_payments/region';
    const CONFIG_XML_PATH_SANDBOX       = 'payment/amazon_payments/sandbox';
    const CONFIG_XML_PATH_DEBUG         = 'payment/amazon_payments/debug';
    const CONFIG_XML_PATH_CHECKOUT_PAGE = 'payment/amazon_payments/checkout_page';

    const CONFIG_XML_PATH_LOGIN_ENABLED = 'amazon_login/settings/enabled';



    /**
     * Retrieve config value for store by path
     *
     * @param string $path
     * @param mixed $store
     * @return mixed
     */
    protected function _getStoreConfig($path, $store)
    {
        return Mage::getStoreConfig($path, $store);
    }

    /**
     * Is sandbox?
     *
     * @param   store $store
     * @return  string
     */
    public function isSandbox($store = null)
    {
        return (bool) $this->_getStoreConfig(self::CONFIG_XML_PATH_SANDBOX, $store);
    }

    /**
     * Is module enabled?
     *
     * @param   store $store
     * @return  string
     */
    public function isEnabled($store = null)
    {
        return (bool) $this->_getStoreConfig(self::CONFIG_XML_PATH_ENABLED, $store);
    }

    /**
     * Is guest checkout/pay only? (does not create customer account)
     *
     * @param   store $store
     * @return  string
     */
    public function isGuestCheckout($store = null)
    {
        return ! (bool) $this->_getStoreConfig(self::CONFIG_XML_PATH_LOGIN_ENABLED, $store);
    }

    /**
     * Is debug mode enabled?
     *
     * @param   store $store
     * @return  string
     */
    public function isDebugMode($store = null)
    {
        return (bool) $this->_getStoreConfig(self::CONFIG_XML_PATH_DEBUG, $store);
    }

    /**
     * Get client ID
     *
     * @param   store $store
     * @return  string
     */
    public function getClientId($store = null)
    {
        return trim($this->_getStoreConfig(self::CONFIG_XML_PATH_CLIENT_ID, $store));
    }

    /**
     * Get client secret
     *
     * @param   store $store
     * @return  string
     */
    public function getClientSecret($store = null)
    {
        return trim($this->_getStoreConfig(self::CONFIG_XML_PATH_CLIENT_SECRET, $store));
    }

    /**
     * Get seller/merchant ID
     *
     * @param   store $store
     * @return  string
     */
    public function getSellerId($store = null)
    {
        return trim($this->_getStoreConfig(self::CONFIG_XML_PATH_SELLER_ID, $store));
    }

    /**
     * Get API access key
     *
     * @param   store $store
     * @return  string
     */
    public function getAccessKey($store = null)
    {
        return trim($this->_getStoreConfig(self::CONFIG_XML_PATH_ACCESS_KEY, $store));
    }

    /**
     * Get API secret access key
     *
     * @param   store $store
     * @return  string
     */
    public function getAccessSecret($store = null)
    {
        return trim($this->_getStoreConfig(self::CONFIG_XML_PATH_ACCESS_SECRET, $store));
    }

    /**
     * Get API region
     *
     * @param   store $store
     * @return  string
     */
    public function getRegion($store = null)
    {
        $region = $this->_getStoreConfig(self::CONFIG_XML_PATH_REGION, $store);
        if (!$region) {
            $region = 'us';
        }
        return $region;
    }

    /**
     * Get Checkout Page type
     *
     * @param   store $store
     * @return  string
     */
    public function getCheckoutPage($store = null)
    {
        return $this->_getStoreConfig(self::CONFIG_XML_PATH_CHECKOUT_PAGE, $store);
    }

    /**
     * Is Checkout using OnePage?
     *
     * @param   store $store
     * @return  string
     */
    public function isCheckoutOnepage($store = null)
    {
        return ($this->_getStoreConfig(self::CONFIG_XML_PATH_CHECKOUT_PAGE, $store) == 'onepage');
    }
}
