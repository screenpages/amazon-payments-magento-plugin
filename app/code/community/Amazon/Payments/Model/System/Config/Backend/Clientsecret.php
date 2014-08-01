<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Model_System_Config_Backend_Clientsecret extends Mage_Core_Model_Config_Data
{
    private $_path = 'amazon_login/settings/client_secret';

    /**
     * Use Amazon_Login Client ID
     */
    public function save()
    {
        Mage::getConfig()->saveConfig($this->_path, $this->value);
        return parent::save();
    }

    public function afterLoad()
    {
        $this->value = Mage::getStoreConfig($this->_path);
        $this->_afterLoad();
    }

}