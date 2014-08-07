<?php
/**
 * Amazon Payments Helper
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Get config
     */
    public function getConfig()
    {
        return Mage::getSingleton('amazon_payments/config');
    }

    /**
     * Retrieve seller ID
     *
     * @return string
     */
    public function getSellerId()
    {
        return $this->getConfig()->getSellerId();
    }

    /**
     * Retrieve client ID
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->getConfig()->getClientId();
    }

    /**
     * Retrieve client secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->getConfig()->getClientSecret();
    }

    /**
     * Retrieve stand alone URL
     *
     * @return string
     */
    public function getStandaloneUrl()
    {
        return Mage::getUrl('checkout/amazon_payments', array('_secure'=>true));
    }

    /**
     * Does user have Amazon order reference for checkout?
     *
     * @return string
     */
    public function isCheckoutAmazonSession()
    {
        return (Mage::getSingleton('checkout/session')->getAmazonAccessToken());
    }


    /**
     * Is sandbox mode?
     *
     * @return bool
     */
    public function isAmazonSandbox()
    {
        return $this->getConfig()->isSandbox();
    }

    /**
     * Clear session data
     */
    public function clearSession()
    {
        Mage::getSingleton('checkout/session')->unsAmazonAccessToken();

    }

    /**
     * Change OnePage login block?
     *
     * amazon_payments.xml template helper
     */
    public function switchOnepageLoginTemplateIf($amazonTemplate, $defaultTemplate)
    {
        if ($this->getConfig()->isCheckoutOnepage()) {
            return $amazonTemplate;
        } else {
            return $defaultTemplate;
        }
    }



}