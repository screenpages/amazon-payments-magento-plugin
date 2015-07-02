<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Login_Block_Script extends Mage_Core_Block_Template
{

    /**
     * Return Widgets.js URL
     */
    public function getWidgetsUrl()
    {
        switch (Mage::getStoreConfig('amazon_login/settings/region')) {
          case 'uk':
              $staticRegion = 'eu';
              $widgetRegion = 'uk';
              $lpa = 'lpa/';
              break;

          case 'de':
              $staticRegion = 'eu';
              $widgetRegion = 'de';
              $lpa = 'lpa/';
              break;

          // US
          default:
              $staticRegion = 'na';
              $widgetRegion = 'us';
              $lpa = '';
              break;
        }

        $sandbox = $this->isSandboxEnabled() ? 'sandbox/' : '';

        return "https://static-$staticRegion.payments-amazon.com/OffAmazonPayments/$widgetRegion/{$sandbox}{$lpa}js/Widgets.js?sellerId=" . $this->getSellerId();
    }

    /**
     * Is popup window?
     *
     * @return bool
     */
    public function isPopup()
    {
        return ($this->helper('amazon_login')->isPopup());
    }

    /**
     * Is sandbox mode?
     */
    public function isSandboxEnabled()
    {
        return (Mage::getStoreConfig('payment/amazon_payments/sandbox'));
    }

    /**
     * Get client ID
     */
    public function getClientId()
    {
        return Mage::getModel('amazon_login/api')->getClientId();
    }

    /**
     * Return seller ID
     */
    public function getSellerId()
    {
        return $this->helper('amazon_payments')->getSellerId();
    }

    /**
     * Get additional scope
     */
    public function getAdditionalScope()
    {
         return $this->helper('amazon_login')->getAdditionalScope();
    }

    /**
     * Get login auth URL
     */
    public function getLoginAuthUrl()
    {
         return $this->helper('amazon_login')->getLoginAuthUrl();
    }

    /**
     * Is Amazon Payments enabled?
     *
     * @return bool
     */
    public function isAmazonPaymentsEnabled()
    {
        return $this->helper('amazon_login')->isAmazonPaymentsEnabled();
    }

}
