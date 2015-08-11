<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Login_Block_Button extends Mage_Core_Block_Template
{
    public function getButtonImage()
    {
        $button = '';

        switch (Mage::getStoreConfig('amazon_login/settings/button_color')) {
            case 'DarkGray':
                $button .= '_drkgry_';
                break;
            case 'LightGray':
                $button .= '_gry_';
                break;
            default:
                $button .= '_gold_';
                break;
        }

        $isLarge = (Mage::getStoreConfig('amazon_login/settings/button_size') == 'large');

        switch (Mage::getStoreConfig('amazon_login/settings/button_type')) {
            case 'Login':
                $button .= $isLarge ? '152x64' : '76x32';
                break;
            case 'A':
                $button .= $isLarge ? '64x64' : '32x32';
                break;
            default:
                $button .= $isLarge ? '312x64' : '156x32';
                break;
        }

        return 'https://images-na.ssl-images-amazon.com/images/G/01/lwa/btnLWA' . $button . '.png';
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
     * Get additional login scope
     */
    public function getAdditionalScope()
    {
         return $this->helper('amazon_login')->getAdditionalScope();
    }

    /**
     * Get button type
     */
    public function getButtonType()
    {
         return Mage::getStoreConfig('amazon_login/settings/button_type');
    }

    /**
     * Get button size
     */
    public function getButtonSize()
    {
         return Mage::getStoreConfig('amazon_login/settings/button_size');
    }

    /**
     * Get button color
     */
    public function getButtonColor()
    {
         return Mage::getStoreConfig('amazon_login/settings/button_color');
    }

    /**
     * Return seller ID
     */
    public function getSellerId()
    {
        return $this->helper('amazon_payments')->getSellerId();
    }

    /**
     * Get login auth URL
     */
    public function getLoginAuthUrl()
    {
         return $this->getUrl('amazon_payments/checkout/authorize', array('_forced_secure'=>true));
    }

    /**
     * Return URL to use for checkout
     */
    public function getCheckoutUrl()
    {
        return $this->helper('amazon_payments')->getCheckoutUrl() . 'account/redirect';
    }

}
