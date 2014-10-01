<?php
/**
 * Validate Client ID and Client Secret
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_System_Config_Backend_Enabled extends Mage_Core_Model_Config_Data
{
    /**
     * Validate data
     */
    public function save()
    {

        $data = $this->getFieldsetData();
        $isEnabled = $this->getValue();

        if ($isEnabled) {
            if ($data['seller_id'] && !ctype_alnum($data['seller_id'])) {
                Mage::getSingleton('core/session')->addError('Error: Please verify your Seller ID (alphanumeric characters only).');
            }
        }
        return parent::save();
    }


    /**
     * Perform API call to Amazon to validate keys
     *
     */
    /*
    public function _afterSaveCommit()
    {

        $data = $this->getFieldsetData();
        $isEnabled = $this->getValue();
        $idTest = 'S23-1234567-1234567';

        if ($isEnabled) {
            $_api = Mage::getSingleton('amazon_payments/api');

            try {
                $result = $_api->getOrderReferenceDetails($idTest);
            }
            catch (Exception $e) {
                if (strpos($e->getMessage(), $idTest) === FALSE) {
                    $matches = array();
                    preg_match("/'(.*)'/", $e->getMessage(), $matches);
                    if ($matches[0]) {
                        Mage::getSingleton('core/session')->addError('Error: ' . $matches[0]);
                    }

                }
            }
        }

        return parent::_afterSaveCommit();
    }
    */
}