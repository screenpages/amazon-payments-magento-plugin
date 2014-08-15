<?php
/**
 * Amazon Payments Checkout Controller
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_OnepageController extends Amazon_Payments_Controller_Checkout
{
    protected $_checkoutUrl = 'checkout/onepage';

    /**
     * Index action
     */
    public function indexAction()
    {
        // placeholder required
    }

    /**
     * Save widget (address/payment info)
     */
    public function saveWidgetAction()
    {
        $result = array();

        if ($this->_expireAjax()) {
            return;
        }

        try {
            $this->_saveShipping();
            $this->_getOnepage()->getCheckout()->setStepData('widget', 'complete', true);

            $this->_getOnepage()->savePayment(array(
                'method' => 'amazon_payments',
                'additional_information' => array(
                    'order_reference' => $this->getAmazonOrderReferenceId(),
                )
            ));

            if ($this->_getOnepage()->getQuote()->isVirtual()) {
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            } else {
                $result['goto_section'] = 'shipping_method';
                $result['update_section'] = array(
                    'name' => 'shipping-method',
                    'html' => $this->_getShippingMethodsHtml()
                );
            }

        }
        // Catch any API errors like invalid keys
        catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->_getOnepage()->saveShippingMethod($data);
            // $result will contain error data if shipping method is empty
            if (!$result) {
                Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->_getOnepage()->getQuote()));
                $this->_getOnepage()->getQuote()->collectTotals();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));


                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            }

            $this->_getOnepage()->getQuote()->collectTotals()->save();
            //$this->_getOnepage()->getQuote()->save();

            /*
            $this->_getOnepage()->saveOrder();
            $this->_getOnepage()->getQuote()->save();
            */


            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Get shipping method step html
     *
     * @return string
     */
    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * Get order review step html
     *
     * @return string
     */
    protected function _getReviewHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_review');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;

        //return $this->getLayout()->getBlock('root')->toHtml();
    }


}

