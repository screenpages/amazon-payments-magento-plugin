<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Login_Model_System_Config_Source_Buttonsize
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'small', 'label'=>Mage::helper('adminhtml')->__('Small (148px x 30px)')),
            array('value'=>'medium', 'label'=>Mage::helper('adminhtml')->__('Medium (200px x 45px)')),
            array('value'=>'large', 'label'=>Mage::helper('adminhtml')->__('Large (296px x 60px)')),
            array('value'=>'x-large', 'label'=>Mage::helper('adminhtml')->__('X-large (400px x 90px)')),
        );
    }
}