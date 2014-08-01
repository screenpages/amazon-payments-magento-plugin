<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Model_Observer_Onepage
{
    /**
     * Event: controller_action_layout_load_before
     */
    public function beforeLoadLayout(Varien_Event_Observer $observer)
    {
        $fullActionName = $observer->getEvent()->getAction()->getFullActionName();

        if ($fullActionName == 'checkout_onepage_index' && Mage::helper('amazon_payments/data')->isCheckoutAmazonSession()) {
            $observer->getEvent()->getLayout()->getUpdate()->addHandle('checkout_onepage_index_amazon_payments');
        }

    }

    /**
     * Event: payment_method_is_active
     */
    public function paymentMethodIsActive(Varien_Event_Observer $observer) {
        $event           = $observer->getEvent();
        $method          = $event->getMethodInstance();
        $result          = $event->getResult();

        if ($method->getCode() == 'amazon_payments') {
            // Disable Payment Option if no session found
            if (!Mage::helper('amazon_payments/data')->isCheckoutAmazonSession()) {
                $result->isAvailable = false;
            }
        }
    }
}