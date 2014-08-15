<?php
/**
 * Login with Amazon Customer Controller
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Login_CustomerController extends Mage_Core_Controller_Front_Action
{

    /**
     * Authorize Login Token
     *
     * Create account and/or log user in.
     */
    public function authorizeAction()
    {
        if ($token = $this->getRequest()->getParam('token')) {
            $customer = Mage::getModel('amazon_login/customer')->loginWithToken($token);

            if ($customer->getId()) {
                $this->_redirectUrl(Mage::helper('customer')->getDashboardUrl());
            }
            // Login failed
            else {
                Mage::getSingleton('customer/session')->addError('Unable to log in with Amazon.');

                if ($referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME)) {
                    $referer = Mage::helper('core')->urlDecode($referer);
                    $this->_redirectUrl($referer);
                }
            }

        }
    }

    /**
     * Verify Customer account
     */
    public function verifyAction()
    {
        if ($login = $this->getRequest()->getParam('login')) {
            $profile = Mage::helper('amazon_login')->getAmazonProfileSession();

            try {
                if (Mage::getSingleton('customer/session')->login($profile['email'], $login['password'])) {
                    Mage::getSingleton('amazon_login/customer')->createAssociation($profile, Mage::getSingleton('customer/session')->getCustomer()->getId());

                    if ($token = Mage::getSingleton('checkout/session')->getAmazonAccessTokenVerify()) {
                        Mage::getSingleton('checkout/session')->setAmazonAccessToken($token);
                        Mage::getSingleton('checkout/session')->unsAmazonAccessTokenVerify();
                    }

                    $redirect = $this->getRequest()->getParam('redirect');
                    if (!$redirect) {
                        $this->_redirectUrl(Mage::helper('customer')->getDashboardUrl());
                    }
                    else {
                        $this->_redirect($redirect);
                    }

                }

            } catch (Exception $e) {
                Mage::getSingleton('customer/session')->addError($e->getMessage());
            }

        }


        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

}