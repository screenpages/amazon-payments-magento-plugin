<?php
/**
 * Amazon Payments Checkout Controller
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_IndexController extends Mage_Core_Controller_Front_Action
{

    /**
     * Index
     */
    public function indexAction()
    {

    }

    /**
     * Token checkout (test)
     */
    public function tokencheckoutAction()
    {
        $token = Mage::getModel('amazon_payments/token')->getBillingAgreement();

        if ($amazonBillingAgreementId = $token->getAmazonBillingAgreementId()) {
            $_api = Mage::getModel('amazon_payments/api');

            $quote = Mage::getSingleton('checkout/session')->getQuote();

            $quote->setSendCconfirmation(1);


            //var_dump($quote->debug());

            ///*
            $orderDetails = $_api->getBillingAgreementDetails($amazonBillingAgreementId, Mage::getSingleton('checkout/session')->getAmazonAccessToken());

            $address = $orderDetails->getDestination()->getPhysicalDestination();

            // Split name into first/last
            $name      = $address->getName();
            $firstName = substr($name, 0, strrpos($name, ' '));
            $lastName  = substr($name, strlen($firstName) + 1);

            // Find Mage state/region ID
            $regionModel = Mage::getModel('directory/region')->loadByCode($address->getStateOrRegion(), $address->getCountryCode());
            $regionId    = $regionModel->getId();

            $data = array(
                'customer_address_id' => '',
                'firstname'   => $firstName,
                'lastname'    => $lastName,
                //'street'      => array($address->getAddressLine1(), $address->getAddressLine2()),
                'street'      => $address->getAddressLine1(),
                'city'        => $address->getCity(),
                'region_id'   => $regionId,
                'postcode'    => $address->getPostalCode(),
                'country_id'  => $address->getCountryCode(),
                'telephone'   => ($address->getPhone()) ? $address->getPhone() : '-', // Mage requires phone number
                'use_for_shipping' => true,
            );

            if ($email = Mage::getSingleton('checkout/session')->getCustomerEmail()) {
                $data['email'] = $email;
            }


            $quote->getBillingAddress()->addData($data);
            $shippingAddress = $quote->getShippingAddress()->addData($data);

            // Collect Rates and Set Shipping & Payment Method
            $shippingAddress->setCollectShippingRates(true)
                           ->collectShippingRates()
                           ->setShippingMethod($token->getShippingMethod())
                           ->setPaymentMethod('amazon_payments');

            // Set Sales Order Payment
            $quote->getPayment()->importData(array(
                'method' => 'amazon_payments',
                'additional_information' => array(
                    'order_reference' => $amazonBillingAgreementId,
                    'billing_agreement_id' => $amazonBillingAgreementId,
                ),
            ));

            //var_dump($quote->getPayment()->debug());
            //exit;

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();

            // Create Order From Quote
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $increment_id = $service->getOrder()->getRealOrderId();
            $id = $service->getOrder()->getId();

            // Clear cart
            //$quote->setIsActive(false);
            //$quote->delete();

            Mage::getSingleton('core/session')->addSuccess('Thank you for your order.');

            $this->_redirect("sales/order/view/order_id/$id");

        }


    }

}
