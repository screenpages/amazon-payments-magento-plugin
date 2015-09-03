<?php
/**
 * Amazon Payments SimplePath
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Adminhtml_Amazon_SimplepathController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Return SimplePath URL with regenerated key-pair
     */
    public function spurlAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::getSingleton('amazon_payments/simplePath')->getSimplepathUrl());
    }

    /**
     * Detect whether Amazon credentials are set (polled by Ajax)
     */
    public function pollAction()
    {
        $hasKeys = Mage::getSingleton('amazon_payments/config')->getSellerId() ? 1 : 0;
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($hasKeys);
    }

}
