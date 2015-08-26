<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Simplepath extends Mage_Core_Model_Config_Data
{
    /**
     * Validate data
     */
    public function save()
    {
        $value = trim($this->getValue());

        if ($value) {
            $value = str_replace('&quot;', '"', $value);
            $_simplePath = Mage::getModel('amazon_payments/simplePath');

            $json = $_simplePath->decryptPayload($value);
            Mage::getSingleton('adminhtml/session')->addSuccess("Import from clipboard decrypted: $json");
        }

        // Don't save
        //return parent::save();
    }


}
