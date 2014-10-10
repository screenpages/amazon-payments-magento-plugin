<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Observer_Onepage
{
    /**
     * Event: controller_action_layout_load_before
     */
    public function beforeLoadLayout(Varien_Event_Observer $observer)
    {
        $_helper = Mage::helper('amazon_payments/data');
        $fullActionName = $observer->getEvent()->getAction()->getFullActionName();


        if ($fullActionName == 'checkout_onepage_index' && $_helper->getConfig()->isEnabled() && $_helper->isCheckoutAmazonSession()) {
            // If One Page is disable and user has active Amazon Session, redirect to standalone checkout
            if (!$_helper->getConfig()->isCheckoutOnepage()) {
                Mage::app()->getFrontController()->getResponse()->setRedirect($_helper->getStandaloneUrl());
            }

            $observer->getEvent()->getLayout()->getUpdate()->addHandle('checkout_onepage_index_amazon_payments');
        }

    }
}