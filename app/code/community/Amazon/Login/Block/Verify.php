<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Login_Block_Verify extends Mage_Core_Block_Template
{
    public function getEmail()
    {
        $profile = $this->helper('amazon_login')->getAmazonProfileSession();
        return $profile['email'];
    }

    public function getPostActionUrl()
    {
        return $this->helper('amazon_login')->getVerifyUrl() . '?redirect=' . $this->getRequest()->getParam('redirect');
    }

    public function getForgotPasswordUrl()
    {
         return $this->helper('customer')->getForgotPasswordUrl();
    }

}
