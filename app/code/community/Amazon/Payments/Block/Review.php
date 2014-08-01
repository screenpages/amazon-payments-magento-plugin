<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Payments_Block_Review extends Mage_Checkout_Block_Onepage_Abstract
{
    protected function _construct()
    {
        $this->getCheckout()->setStepData('review', array(
            'label'     => Mage::helper('checkout')->__('Order Review'),
            'is_show'   => $this->isShow()
        ));
        parent::_construct();

        $this->getQuote()->collectTotals()->save();
    }
}
