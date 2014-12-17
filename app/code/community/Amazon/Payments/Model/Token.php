<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_Token extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('amazon_payments/token');
    }

    /**
     * Save token
     */
    public function saveBillingAgreementId($amazonBillingAgreementId, $amazonUid)
    {
        //$model = Mage::getModel('amazon_payments/token');

        //var_dump(self::debug());
        //exit;

        $row = $this->load($amazonUid, 'amazon_uid');

        if ($row)  {
            $row->setAmazonBillingAgreementId($amazonBillingAgreementId)->save();
        }
        else {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
              $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            }
            else {
              $customer_id = null;
            }

            $this->setAmazonBillingAgreementId($amazonBillingAgreementId)
                 ->setAmazonUid($amazonUid)
                 ->setCustomerId($customer_id)
                 ->save();
        }
    }

}