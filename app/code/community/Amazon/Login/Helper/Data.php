<?php
/**
 * Login with Amazon Helper
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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

}
