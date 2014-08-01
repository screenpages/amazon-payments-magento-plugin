<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Model_System_Config_Source_region
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'us', 'label'=>Mage::helper('adminhtml')->__('United States')),
            array('value'=>'eu', 'label'=>Mage::helper('adminhtml')->__('Europe')),
        );
    }
}

