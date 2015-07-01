<?php
/**
 * Login with Amazon Helper
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Login_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieve customer verify url
     *
     * @return string
     */
    public function getVerifyUrl()
    {
        return $this->_getUrl('amazon_login/customer/verify');
    }

    /**
     * Retrieve Amazon Profile in session
     */
    public function getAmazonProfileSession()
    {
        return Mage::getSingleton('customer/session')->getAmazonProfile();
    }

    /**
     * Retrieve additional login access scope
     */
    public function getAdditionalScope()
    {
        $scope = trim(Mage::getStoreConfig('amazon_login/settings/additional_scope'));
        return ($scope) ? ' ' . $scope : '';
    }

    /**
     * Return API region
     */
    public function getRegion()
    {
        $region = Mage::getStoreConfig('amazon_login/settings/region');
        return ($region) ? $region : 'us';
    }

    /**
     * Return language for Amazon frontend
     */
    public function getLanguage()
    {

        if ($language = Mage::getStoreConfig('amazon_login/settings/language')) {
            return $language;
        }

        $code = Mage::getStoreConfig('general/locale/code');

        if ($code == 'en_GB') {
            return 'en-GB';
        }

        switch (substr($code, 0, 2)) {
            case 'de': return 'de-DE'; break;
            case 'fr': return 'fr-FR'; break;
            case 'it': return 'it-IT'; break;
            case 'es': return 'es-ES'; break;
            default: return false;
        }
    }

    /**
     * Return login authorize URL
     *
     * @return string
     */
    public function getLoginAuthUrl()
    {
        return $this->_getUrl('amazon_login/customer/authorize', array('_forced_secure' => true));
    }

    /**
     * Is login a popup or full-page redirect?
     */
    public function isPopup()
    {
        return (Mage::getStoreConfig('amazon_login/settings/popup'));
    }

    /**
     * Is Amazon_Login enabled in config?
     */
    public function isEnabled()
    {
        return (Mage::getStoreConfig('amazon_login/settings/enabled'));
    }

    /**
     * Get admin region for localizing URLs to Amazon
     */
    public function getAdminRegion()
    {

        if (in_array($this->getAdminConfig('amazon_login/settings/region'), array('uk', 'de'))) {
            return 'eu';
        }

        $countryCode = $this->getAdminConfig('general/country/default');

        // Is EU country?
        $euCountries = explode(',', Mage::getStoreConfig('general/country/eu_countries'));
        if (in_array($countryCode, $euCountries)) {
            return 'eu';
        }
    }

    /**
     * Get config by website or store admin scope
     */
    public function getAdminConfig($path)
    {
        if ($storeCode = Mage::app()->getRequest()->getParam('store')) {
            return Mage::getStoreConfig($path, $storeCode);
        }
        else if ($websiteCode = Mage::app()->getRequest()->getParam('website')) {
            return Mage::app()->getWebsite($websiteCode)->getConfig($path);
        }
        else {
            return Mage::getStoreConfig($path);
        }
    }


}
