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
    protected $_quote;
    /**
     * Prepare quote item info about gift wrapping
     *
     * @param mixed $entity
     * @param array $data
     * @return Enterprise_GiftWrapping_Model_Observer
     */
    protected function _saveItemInfo($entity, $data)
    {
        if (is_array($data)) {
            $wrapping = Mage::getModel('enterprise_giftwrapping/wrapping')->load($data['design']);
            $entity->setGwId($wrapping->getId())
                ->save();
        }
        return $this;
    }

    /**
     * Prepare entire order info about gift wrapping
     *
     * @param mixed $entity
     * @param array $data
     * @return Enterprise_GiftWrapping_Model_Observer
     */
    protected function _saveOrderInfo($entity, $data)
    {
        if (is_array($data)) {
            $wrappingInfo = array();
            if (isset($data['design'])) {
                $wrapping = Mage::getModel('enterprise_giftwrapping/wrapping')->load($data['design']);
                $wrappingInfo['gw_id'] = $wrapping->getId();
            }
            $wrappingInfo['gw_allow_gift_receipt'] = isset($data['allow_gift_receipt']);
            $wrappingInfo['gw_add_card'] = isset($data['add_printed_card']);
            if ($entity->getShippingAddress()) {
                $entity->getShippingAddress()->addData($wrappingInfo);
            }
            $entity->addData($wrappingInfo)->save();
        }
        return $this;
    }

    /**
     * Process gift wrapping options on checkout proccess
     *
     * @param Varien_Object $observer
     * @return Enterprise_GiftWrapping_Model_Observer
     */
    public function checkoutProcessWrappingInfo($observer)
    {
        $request = $observer->getEvent()->getRequest();
        $giftWrappingInfo = $request->getParam('giftwrapping');
        if (is_array($giftWrappingInfo)) {
            $quote = $observer->getEvent()->getQuote();
            $giftOptionsInfo = $request->getParam('giftoptions');
            foreach ($giftWrappingInfo as $entityId => $data) {
                $info = array();
                if (!is_array($giftOptionsInfo) || empty($giftOptionsInfo[$entityId]['type'])) {
                    continue;
                }
                switch ($giftOptionsInfo[$entityId]['type']) {
                    case 'quote':
                        $entity = $quote;
                        $this->_saveOrderInfo($entity, $data);
                        break;
                    case 'quote_item':
                        $entity = $quote->getItemById($entityId);
                        $this->_saveItemInfo($entity, $data);
                        break;
                    case 'quote_address':
                        $entity = $quote->getAddressById($entityId);
                        $this->_saveOrderInfo($entity, $data);
                        break;
                    case 'quote_address_item':
                        $entity = $quote
                            ->getAddressById($giftOptionsInfo[$entityId]['address'])
                            ->getItemById($entityId);
                        $this->_saveItemInfo($entity, $data);
                        break;
                }
            }
        }
        return $this;
    }

    /**
     * Event: controller_action_layout_load_before
     *
     * Load layout handle override for OnePage
     */
    public function beforeLoadLayout(Varien_Event_Observer $observer)
    {
        $_helper = Mage::helper('amazon_payments/data');
        $fullActionName = $observer->getEvent()->getAction()->getFullActionName();


        if ($fullActionName == 'checkout_onepage_index' && $_helper->getConfig()->isEnabled() && $_helper->isCheckoutAmazonSession() && $_helper->isEnableProductPayments()) {
            // If One Page is disable and user has active Amazon Session, redirect to standalone checkout
            if (!$_helper->getConfig()->isCheckoutOnepage()) {
                Mage::app()->getFrontController()->getResponse()->setRedirect($_helper->getStandaloneUrl());
            }

            // Use custom checkout layout
            $observer->getEvent()->getLayout()->getUpdate()->addHandle('checkout_onepage_index_amazon_payments');
        }
    }


    /**
     * Event: custom_quote_process
     *
     * Clear address if user switches from Amazon Checkout to third-party checkout
     */
    public function clearShippingAddress(Varien_Event_Observer $observer)
    {
        $_helper = Mage::helper('amazon_payments/data');
        $session = $observer->getEvent()->getCheckoutSession();

        $action = Mage::app()->getFrontController()->getAction()->getFullActionName();
        $action_reset = array('opc_index_index', 'firecheckout_index_index');

        if (in_array($action, $action_reset) && $session && $session->getCheckoutState() == 'begin' && $session->getAmazonAddressId() && $session->getQuoteId() && $this->_quote === null) {

            $quote = $this->_quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore()->getId())->load($session->getQuoteId());
            $address = $quote->getShippingAddress();

            if ($address->getId() == $session->getAmazonAddressId()) {

                $reset = array(
                    'firstname'   => '',
                    'lastname'    => '',
                    'street'      => '',
                    'city'        => '',
                    'region_id'   => '',
                    'postcode'    => '',
                    'country_id'  => '',
                    'telephone'   => '',
                );

                $address->setData($reset);
                $quote->setShippingAddress($address);
                $quote->setBillingAddress($address);

                $quote->collectTotals()->save();

                $session->unsAmazonAddressId();
            }

        }
    }

}