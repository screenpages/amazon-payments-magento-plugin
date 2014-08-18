<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Block_Button extends Mage_Core_Block_Template
{
    public function getCheckoutUrl()
    {
        $_helper = Mage::helper('amazon_payments/data');
        $_config = Mage::getSingleton('amazon_payments/config');

        if ($_config->isCheckoutOnepage()) {
            return $this->getOnepageCheckoutUrl();
        }
        else if ($_config->isCheckoutModal()) {
            return $_helper->getModalUrl();
        }
        else {
            return $_helper->getStandaloneUrl();
        }

    }

    public function getOnepageCheckoutUrl()
    {
        return $this->getUrl('amazon_payments/onepage', array('_forced_secure'=>true));
    }

    public function getAmazonPayButtonId() {
        return $this->getNameInLayout();
    }

    public function getSellerId()
    {
        return $this->helper('amazon_payments')->getSellerId();
    }

    public function isDisabled()
    {
        return !Mage::getSingleton('checkout/session')->getQuote()->validateMinimumAmount();
    }

    public function isAmazonPayButtonEnabled()
    {
        return (!Mage::getSingleton('amazon_payments/config')->isCheckoutOnepage() || Mage::getSingleton('amazon_payments/config')->showPayOnCart());
    }

}
