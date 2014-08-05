<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{

    // Unique internal payment method identifier
    protected $_code                    = 'amazon_payments';

    protected $_canAuthorize            = true;  // Can authorize online?
    protected $_canCapture              = true;  // Can capture funds online?
    protected $_canCapturePartial       = false; // Can capture partial amounts online?
    protected $_canRefund               = true;  // Can refund online?
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;  // Can void transactions online?
    protected $_canUseInternal          = false; // Can use this payment method in administration panel?
    protected $_canUseCheckout          = true;  // Can show this payment method as an option on checkout payment page?
    protected $_canUseForMultishipping  = false; // Is this payment method suitable for multi-shipping checkout?
    protected $_isInitializeNeeded      = true;
    protected $_canFetchTransactionInfo = true;
    protected $_canReviewPayment        = false;

    // Amazon Authorization States
    const AUTH_STATUS_PENDING   = 'Pending';
    const AUTH_STATUS_OPEN      = 'Open';
    const AUTH_STATUS_DECLINED  = 'Declined';
    const AUTH_STATUS_CLOSED    = 'Closed';
    const AUTH_STATUS_COMPLETED = 'Completed';


    /**
     * Return Amazon API
     */
    protected function _getApi()
    {
        return Mage::getSingleton('amazon_payments/api');
    }

    /**
     * Return an internal reference ID for Amazon API transactions
     */
    protected function _getMagentoReferenceId(Varien_Object $payment)
    {
        $order = $payment->getOrder();
        return $order->getIncrementId() . '-' . strtotime($payment->getCreatedAt());
    }

    /**
     * Return Soft Descriptor (description to be shown on the buyer's payment instrument statement)
     */
    protected function _getSoftDescriptor()
    {
        return substr(Mage::app()->getStore()->getName(), 0, 16); // 16 chars max
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param object $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        if ($payment = $this->getInfoInstance()) {
            $order = $payment->getOrder();
            $this->setStore($order->getStoreId())->order($payment, $order->getBaseTotalDue());
        }

        $stateObject->setState(($paymentAction == self::ACTION_AUTHORIZE_CAPTURE) ? Mage_Sales_Model_Order::STATE_PROCESSING : Mage_Sales_Model_Order::STATE_NEW);
        $stateObject->setStatus(true);
        $stateObject->setIsNotified(Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE);
    }


    /**
     * Authorize, with option to Capture
     */
    protected function _authorize(Varien_Object $payment, $amount, $captureNow = false)
    {
        $order = $payment->getOrder();

        $sellerAuthorizationNote = null;

        if ($payment->getAdditionalInformation('sandbox') && $this->_getApi()->getConfig()->isSandbox()) {
            $sellerAuthorizationNote = $payment->getAdditionalInformation('sandbox');
        }

        $result = $this->_getApi()->authorize(
            $payment->getTransactionId(),
            $this->_getMagentoReferenceId($payment) . '-authorize',
            $amount,
            $order->getBaseCurrencyCode(),
            $captureNow,
            ($captureNow) ? $this->_getSoftDescriptor() : null,
            $sellerAuthorizationNote
        );

        $status = $result->getAuthorizationStatus();

        switch ($status->getState()) {
            case self::AUTH_STATUS_PENDING:
            case self::AUTH_STATUS_OPEN:
            case self::AUTH_STATUS_CLOSED:

                $payment->setTransactionId($result->getAmazonAuthorizationId());
                $payment->setParentTransactionId($payment->getAdditionalInformation('order_reference'));
                $payment->setIsTransactionClosed(false);

                // Add transaction
                if ($captureNow) {

                    $transactionSave = Mage::getModel('core/resource_transaction');

                    $captureReferenceIds = $result->getIdList()->getmember();

                    if ($order->canInvoice()) {
                        // Create invoice
                        $invoice = $order
                            ->prepareInvoice()
                            ->register();
                        $invoice->setTransactionId(current($captureReferenceIds));

                        $transactionSave
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder());

                    }

                    $transactionSave->save();

                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                    $message = Mage::helper('payment')->__('Authorize and capture request for %s sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
                }
                else {
                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                    $message = Mage::helper('payment')->__('Authorize request for %s sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
                }

                $payment->addTransaction($transactionType, null, false, $message);

                break;

            case self::AUTH_STATUS_DECLINED:
                Mage::throwException('Amazon Payments authorization error: ' . $status->getState() . ' - ' . $status->getReasonCode() . '  ' . $status->getReasonDescription());
                break;
            default:
                Mage::throwException('Amazon Payments authorization error.');
                break;
        }

    }

    /**
     * Order payment method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Amazon_Payments_Model_PaymentMethod
     */
    public function order(Varien_Object $payment, $amount)
    {

        $orderReferenceId = $payment->getAdditionalInformation('order_reference');

        $payment->setTransactionId($orderReferenceId);
        $order = $payment->getOrder();

        $apiResult = $this->_getApi()->setOrderReferenceDetails(
            $orderReferenceId,
            $order->getBaseGrandTotal(),
            $order->getBaseCurrencyCode(),
            $order->getIncrementId(),
            Mage::app()->getStore()->getName()
        );

        $apiResult = $this->_getApi()->confirmOrderReference($orderReferenceId);

        $payment->setIsTransactionClosed(false);
        $payment->setSkipOrderProcessing(true);
        $message = Mage::helper('payment')->__('Order of %s sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $message);


        switch (Mage::getStoreConfig('payment/amazon_payments/payment_action')) {
            case self::ACTION_AUTHORIZE:
                $this->_authorize($payment, $amount, false);
                break;

            case self::ACTION_AUTHORIZE_CAPTURE:
                $this->_authorize($payment, $amount, true);
                break;
            default:
                break;
        }


        return $this;
    }

    /**
     * Authorize
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Amazon_Payments_Model_PaymentMethod
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_authorize($payment, $amount, false);
        return $this;
    }

    /**
     * Authorize and Capture
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Amazon_Payments_Model_PaymentMethod
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $authReferenceId = $transactionAuth->getTxnId();

        $order = $payment->getOrder();

        $result = $this->_getApi()->capture(
            $authReferenceId,
            $this->_getMagentoReferenceId($payment) . '-capture',
            $amount,
            $order->getBaseCurrencyCode(),
            $this->_getSoftDescriptor()
        );

        if ($result) {
            $status = $result->getCaptureStatus();

            // Error handling
            switch ($status->getState()) {
                case self::AUTH_STATUS_PENDING:
                case self::AUTH_STATUS_DECLINED:
                case self::AUTH_STATUS_CLOSED:
                    Mage::throwException('Amazon Payments capture error: ' . $status->getReasonCode() . ' - ' . $status->getReasonDescription());
                    break;
                case self::AUTH_STATUS_COMPLETED:
                    // Already captured.
                    break;
                default:
                    Mage::throwException('Amazon Payments capture error.');
                    break;
            }

            $payment->setTransactionId($result->getAmazonCaptureId());
            $payment->setParentTransactionId($authReferenceId);
            $payment->setIsTransactionClosed(false);

        }
        else {
            //$error = error_get_last();
            //Mage::log($error);
            Mage::throwException('Unable to capture payment at this time. Please try again later.');
        }

        return $this;
    }

    /**
     * Refund
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if (!$this->canRefund()) {
            Mage::throwException('Unable to refund.');
        }

        $order = $payment->getOrder();

        $result = $this->_getApi()->refund(
            $payment->getRefundTransactionId(),
            $this->_getMagentoReferenceId($payment) . '-refund',
            $amount,
            $order->getBaseCurrencyCode(),
            null,
            $this->_getSoftDescriptor()
        );

        $payment->setTransactionId($result->getAmazonRefundId());
        $payment->setParentTransactionId($payment->getRefundTransactionId());

        $message = Mage::helper('payment')->__('A refund request for %s sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, null, false, $message);

        return $this;
    }

    /**
     * Cancel
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->_void($payment);
    }

    /**
     * Void
     */
    public function void(Varien_Object $payment)
    {
        return $this->_void($payment);
    }

    /**
     * Void/Cancel
     */
    protected function _void(Varien_Object $payment)
    {
        $orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);

        if (!$orderTransaction) {
            $orderTransactionId = $payment->getAdditionalInformation('order_reference');
        }
        else {
            $orderTransactionId = $orderTransaction->getTxnId();
        }

        if ($orderTransaction) {
            $this->_getApi()->cancelOrderReference($orderTransactionId);
        }
        return $this;
    }

    /**
     * Can capture?
     *
     * @return bool
     */
    public function canCapture()
    {
        $payment = $this->getInfoInstance();
        if ($payment) {
            $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

            if (!$transactionAuth || $transactionAuth->getIsClosed()) {
                return false;
            }
        }

        return parent::canCapture();
    }


    /**
     * Check create invoice?
     *
     * @return bool
     */
    public function canInvoice()
    {
        $payment = $this->getInfoInstance();
        if ($payment) {
            $transactionAuth = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
            if (!$transactionAuth || $transactionAuth->getIsClosed()) {
                return false;
            }
        }
        return parent::canInvoice();
    }




}
