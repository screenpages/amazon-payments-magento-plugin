<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Login_Block_Script extends Mage_Core_Block_Template
{
    /**
     * Is popup window?
     *
     * @return string
     */
    public function getIsPopup()
    {
        return ($this->isPopup()) ? 'true' : 'false';
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
     * Get additional scope
     */
    public function getAdditionalScope()
    {
         return $this->helper('amazon_login')->getAdditionalScope();
    }

}
