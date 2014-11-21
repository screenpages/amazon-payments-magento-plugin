<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Async extends Mage_Core_Model_Abstract
{

    /**
     * Return Amazon API
     */
    protected function _getApi()
    {
        return Mage::getSingleton('amazon_payments/api');
    }

    /**
     * Create invoice
     */
    protected function _createInvoice(Mage_Sales_Model_Order $order, $captureReferenceIds)
    {
        if ($order->canInvoice()) {
            $transactionSave = Mage::getModel('core/resource_transaction');

            // Create invoice
            $invoice = $order
                ->prepareInvoice()
                ->register();
            $invoice->setTransactionId(current($captureReferenceIds));

            $transactionSave
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            return $transactionSave->save();
        }

        return false;
    }

    /**
     * Poll Amazon API to receive order status and update Magento order.
     */
    public function syncOrderStatus(Mage_Sales_Model_Order $order, $isManualSync = false)
    {
        $amazonOrderReference = $order->getPayment()->getAdditionalInformation('order_reference');

        $_api = $this->_getApi();
        $message = '';

        $result = $this->_getApi()->getOrderReferenceDetails($amazonOrderReference);

        if ($result) {
            $status = $result->getOrderReferenceStatus()->getState();

            $message = Mage::helper('payment')->__('Sync with Amazon: Amazon order status is %s.',  $status);

            if ($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {

                $order->setStatus($_api->getConfig()->getNewOrderStatus());

                // Payment accepted...
                if ($status == Amazon_Payments_Model_Api::AUTH_STATUS_OPEN) {

                    // Authorize & Capture -- create invoice
                    if ($_api->getConfig()->getPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);

                        if ($this->_createInvoice($order, $result->getIdList()->getmember())) {
                            $message .= ' ' . Mage::helper('payment')->__('Invoice created.');
                        }
                    }
                    // Capture only
                    else {
                        $order->setState(Mage_Sales_Model_Order::STATE_NEW);
                    }
                }
                // Declined
                elseif ($status == Amazon_Payments_Model_Api::AUTH_STATUS_DECLINED) {
                    $order->setState(Mage_Sales_Model_Order::STATE_HOLDED);
                    $order->setStatus(Mage_Sales_Model_Order::ACTION_FLAG_HOLD);
                    $message .= ' Order placed on hold. Please direct customer to Amazon Payments site to update their payment method.';
                }


                $order->addStatusToHistory($order->getStatus(), $message, false);

                $order->save();
            }

            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }
    }

    /**
     * Magento cron to sync Amazon orders
     */
    public function cron()
    {
        if ($this->_getApi()->getConfig()->isAsync()) {

            $orderCollection = Mage::getModel('sales/order_payment')
                ->getCollection()
                ->join(array('order'=>'sales/order'), 'main_table.parent_id=order.entity_id', 'state')
                ->addFieldToFilter('method', 'amazon_payments')
                ->addFieldToFilter('state', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) // Async
                ;

            foreach ($orderCollection as $orderRow) {
                $order = Mage::getModel('sales/order')->load($orderRow->getId());
                $this->syncOrderStatus($order);
            }
        }
    }
}