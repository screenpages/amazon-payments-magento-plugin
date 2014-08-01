<?php
/**
 * Validate Client ID and Client Secret
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Model_System_Config_Backend_Enabled extends Mage_Core_Model_Config_Data
{
    /**
     * Perform API call to Amazon to validate Client ID/Secret
     *
     */
    public function save()
    {
        //return Mage::getModel('amazon_login/system_config_backend_enabled')->save();
        return parent::save();
    }
}
?>