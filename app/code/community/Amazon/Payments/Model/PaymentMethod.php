<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{

    // Unique internal payment method identifier
    protected $_code                    = 'amazon_payments';

    protected $_formBlockType           = 'amazon_payments/form';
    //protected $_infoBlockType           = 'amazon_payments/info';

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
        return substr($this->_getApi()->getConfig()->getStoreName(), 0, 16); // 16 chars max
    }

    /**
     * Set session error checking for declined orders
     */
    protected function _setErrorCheck()
    {
        Mage::getSingleton('checkout/session')->setAmazonErrorCheck(true);
    }

    /**
     * Get session error checking
     */
    protected function _getErrorCheck()
    {
        return Mage::getSingleton('checkout/session')->getAmazonErrorCheck();
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

        if ($this->getConfigData('payment_action') == self::ACTION_AUTHORIZE_CAPTURE) {
            $stateObject->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
        }

        // Asynchronous Mode always returns Pending
        if ($this->getConfigData('is_async')) {
            // "Pending Payment" indicates async for internal use
            $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus('pending');
        }
        else {
            $stateObject->setStatus($this->getConfigData('order_status'));
        }

        $stateObject->setIsNotified(Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE);
    }

    /**
     * Pre-order (no authorize or capture)
     */
    protected function _preorder(Varien_Object $payment, $amount, $message)
    {
        $order = $payment->getOrder();
        $message = str_ireplace('order', 'Pre-order', $message);

        $payment->setTransactionId($payment->getAdditionalInformation('order_reference'));
        $payment->setIsTransactionClosed(false);

        $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER;
        $payment->addTransaction($transactionType, null, false, $message);
    }

    /**
     * Authorize, with option to Capture
     */
    protected function _authorize(Varien_Object $payment, $amount, $captureNow = false)
    {
        $order = $payment->getOrder();
        $orderReference = $payment->getAdditionalInformation('order_reference');

        $sellerAuthorizationNote = null;

        // Sandbox simulation testing for Stand Alone Checkout
        if ($payment->getAdditionalInformation('sandbox') && $this->_getApi()->getConfig()->isSandbox()) {
            $sellerAuthorizationNote = $payment->getAdditionalInformation('sandbox');
        }

        // For core and third-party checkouts, may test credit card decline by uncommenting:
        //$sellerAuthorizationNote = '{"SandboxSimulation": {"State":"Declined", "ReasonCode":"InvalidPaymentMethod", "PaymentMethodUpdateTimeInMins":5}}';

        // Token payment (i.e. authorize against billing agreement id)
        if ($amazonBillingAgreementId = $payment->getAdditionalInformation('billing_agreement_id')) {
            $forceSync = $this->_isPreorder($payment) && $this->getConfig('is_async'); // Manual Sync

            $result = $this->_getApi()->authorizeOnBillingAgreement(
                $amazonBillingAgreementId,
                $this->_getMagentoReferenceId($payment) . '-bill',
                $amount,
                $order->getBaseCurrencyCode(),
                $captureNow,
                ($captureNow) ? $this->_getSoftDescriptor() : null,
                $sellerAuthorizationNote,
                $forceSync
            );

            $authorizationDetails = $result->getAuthorizationDetails();

            $orderReference = $result->getAmazonOrderReferenceId();
            $payment->setAdditionalInformation('order_reference', $orderReference);
        }
        // Normal payment
        else {
            $authorizationDetails = $this->_getApi()->authorize(
                $orderReference,
                $this->_getMagentoReferenceId($payment) . '-auth',
                $amount,
                $order->getBaseCurrencyCode(),
                $captureNow,
                ($captureNow) ? $this->_getSoftDescriptor() : null,
                $sellerAuthorizationNote
            );
        }


        $status = $authorizationDetails->getAuthorizationStatus();

        switch ($status->getState()) {
            case Amazon_Payments_Model_Api::AUTH_STATUS_PENDING:
            case Amazon_Payments_Model_Api::AUTH_STATUS_OPEN:
            case Amazon_Payments_Model_Api::AUTH_STATUS_CLOSED:

                $payment->setTransactionId($authorizationDetails->getAmazonAuthorizationId());
                $payment->setParentTransactionId($payment->getAdditionalInformation('order_reference'));
                $payment->setIsTransactionClosed(false);

                // Add transaction
                if ($captureNow) {

                    if (!$this->getConfigData('is_async')) {
                        $transactionSave = Mage::getModel('core/resource_transaction');

                        $captureReferenceIds = $authorizationDetails->getIdList()->getmember();

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
                    }

                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                    $message = Mage::helper('payment')->__('Authorize and capture request for %s sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
                }
                else {
                    $transactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                    $message = Mage::helper('payment')->__('Authorize request for %s sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
                }

                $payment->addTransaction($transactionType, null, false, $message);

                break;

            case Amazon_Payments_Model_Api::AUTH_STATUS_DECLINED:
                // Cancel order reference
                if ($status->getReasonCode() == 'TransactionTimedOut') {
                    $this->_getApi()->cancelOrderReference($orderReference);
                }

                $this->_setErrorCheck();
                Mage::throwException("Amazon could not process your order.\n\n" . $status->getReasonCode() . " (" . $status->getState() . ")\n" . $status->getReasonDescription());
                break;
            default:
                $this->_setErrorCheck();
                Mage::throwException('Amazon could not process your order.');
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
        if (!$amount) {
            return $this;
        }

        // Admin order using token (Amazon Billing Agreement id)
        if ($adminBillingAgreementId = Mage::getSingleton('adminhtml/session_quote')->getAmazonBillingAgreementId()) {
            $payment->setAdditionalInformation('order_reference', $adminBillingAgreementId);
            $payment->setAdditionalInformation('billing_agreement_id', $adminBillingAgreementId);
        }

        $orderReferenceId   = $payment->getAdditionalInformation('order_reference');
        $billingAgreementId = $payment->getAdditionalInformation('billing_agreement_id'); // token payment. orderReferenceId will be the same.

        $orderConfirmed = false;

        // User did not agree to billing agreement; create order reference ID from billing agreement ID
        if ($billingAgreementId && !$adminBillingAgreementId && !$payment->getAdditionalInformation('billing_agreement_consent')) {
            $orderAttributes = array(
                'PlatformId' => Amazon_Payments_Model_Api::ORDER_PLATFORM_ID,
                'OrderTotal' => array(
                    'CurrencyCode' => $payment->getOrder()->getBaseCurrencyCode(),
                    'Amount' => $amount,
                )
             );

            $orderReferenceDetails = $this->_getApi()->createOrderReferenceForId($billingAgreementId, 'BillingAgreement', true, true, $orderAttributes);
            $orderReferenceId = $orderReferenceDetails->getAmazonOrderReferenceId();
            $billingAgreementId = false;
            $orderConfirmed = true;

            $payment->setAdditionalInformation('order_reference', $orderReferenceId);
            $payment->unsAdditionalInformation('billing_agreement_id');
            $payment->unsAdditionalInformation('billing_agreement_consent');
        }

        if (!$orderReferenceId) {
            $orderReferenceId = Mage::getSingleton('checkout/session')->getAmazonOrderReferenceId();

            if (!$orderReferenceId) {
                Mage::throwException('Please log in to your Amazon account by clicking the Amazon pay button.');
            }

            $payment->setAdditionalInformation('order_reference', $orderReferenceId);
        }

        $payment->setTransactionId($orderReferenceId);
        $order = $payment->getOrder();

        // Normal order (no token)
        if (!$billingAgreementId && !$orderConfirmed) {
            // If previous order submission failed (e.g. bad credit card), must validate order status to prevent multiple setOrderReferenceDetails()
            if ($this->_getErrorCheck()) {
                $orderReferenceDetails = $this->_getApi()->getOrderReferenceDetails($orderReferenceId);
            }

            if (!$this->_getErrorCheck() || $orderReferenceDetails->getOrderReferenceStatus()->getState() == 'Draft') {
                $apiResult = $this->_getApi()->setOrderReferenceDetails(
                    $orderReferenceId,
                    $order->getBaseGrandTotal(),
                    $order->getBaseCurrencyCode(),
                    $order->getIncrementId(),
                    $this->_getApi()->getConfig()->getStoreName()
                );
            }

            try {
                $apiResult = $this->_getApi()->confirmOrderReference($orderReferenceId);
            }
            catch (Exception $e) {
                Mage::throwException("Please try another Amazon payment method." . "\n\n" . substr($e->getMessage(), 0, strpos($e->getMessage(), 'Stack trace')));
                $this->_setErrorCheck();
                return;
            }
        }
        // Token payment
        else if ($billingAgreementId) {
            $apiResult = $this->_getApi()->confirmBillingAgreement($billingAgreementId);
            // Save token
            if ($apiResult && $payment->getAdditionalInformation('billing_agreement_consent')) {
                Mage::getModel('amazon_payments/token')->saveBillingAgreementId($billingAgreementId, $order->getQuote()->getShippingAddress()->getShippingMethod());
            }
        }

        $payment->setIsTransactionClosed(false);
        $payment->setSkipOrderProcessing(true);

        $comment  = '';
        $comment .= $this->getConfigData('is_async') ? 'Asynchronous ' : '';
        $comment .= $billingAgreementId ? 'Tokenized ' : '';
        $comment .= $this->_getApi()->getConfig()->isSandbox() ? 'Sandbox ' : '';
        $comment .= 'Order of %s sent to Amazon Payments.';
        $message = Mage::helper('payment')->__($comment, $order->getStore()->convertPrice($amount, true, false));

        // Pre-order (delayed tokenized payment)
        if ($this->getConfigData('token_delayed') && $billingAgreementId) {
            $this->_preorder($payment, $amount, $message);
            return $this;
        }

        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $message);

        switch ($this->getConfigData('payment_action')) {
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
    public function authorize(Varien_Object $payment, $amount, $captureNow = false)
    {
        $this->_authorize($payment, $amount, $captureNow);
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
        // Pre-order (delayed tokenized) payment must authorize and capture
        if ($this->_isPreorder($payment)) {
            return $this->_authorize($payment, $amount, true);
        }

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
                case Amazon_Payments_Model_Api::AUTH_STATUS_PENDING:
                case Amazon_Payments_Model_Api::AUTH_STATUS_DECLINED:
                case Amazon_Payments_Model_Api::AUTH_STATUS_CLOSED:
                    $this->_setErrorCheck();
                    Mage::throwException('Amazon Payments capture error: ' . $status->getReasonCode() . ' - ' . $status->getReasonDescription());
                    break;
                case Amazon_Payments_Model_Api::AUTH_STATUS_COMPLETED:
                    // Already captured.
                    break;
                default:
                    $this->_setErrorCheck();
                    Mage::throwException('Amazon Payments capture error.');
                    break;
            }

            $payment->setTransactionId($result->getAmazonCaptureId());
            $payment->setParentTransactionId($authReferenceId);
            $payment->setIsTransactionClosed(false);

        }
        else {
            $this->_setErrorCheck();
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
            $this->_getMagentoReferenceId($payment) . substr(md5($this->_getMagentoReferenceId($payment) . microtime() ),-4) . '-refund',
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
     * Is pre-order?
     *
     * @return bool
     */
    function _isPreorder($payment)
    {
        // Pre-order (delayed tokenized) payment must authorize and capture
        if ($payment->getAdditionalInformation('billing_agreement_id')) {
            $lastTrans = $payment->lookupTransaction($payment->getLastTransId());
            if ($lastTrans->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
                return true;
            }
        }
        return false;
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

            // Pre-order (delayed tokenized) payment?
            if ($this->_isPreorder($payment)) {
                return true;
            }

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


    /**
     * Allow payment method in checkout?
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        return (Mage::getSingleton('amazon_payments/config')->isEnabled() && Mage::helper('amazon_payments')->isEnableProductPayments() && ((Mage::helper('amazon_payments')->isCheckoutAmazonSession() && $this->getConfigData('checkout_page') == 'onepage') || $this->getConfigData('use_in_checkout')));
    }

    /**
     * Using internal pages for input payment data
     * Can be used in admin
     *
     * @return bool
     */
    public function canUseInternal()
    {
        // Allow admin to create order using token (Amazon billing agreement id)
        if ($this->getConfigData('token_enabled')) {
            // Admin session
            $session = Mage::getSingleton('adminhtml/session_quote');
            if ($session) {
                if (($quote = $session->getQuote()) && $quote->getCustomerId() != null) {
                    $row = Mage::getSingleton('amazon_payments/token')->getBillingAgreement($quote->getCustomerId());

                    if ($token = $row->getAmazonBillingAgreementId()) {
                        $session->setAmazonBillingAgreementId($token);
                        return true;
                    }
                }
                else {
                    $session->unsAmazonBillingAgreementId();
                }
            }
        }
        return $this->_canUseInternal;
    }
}
