<?php

/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_SimplepathController extends Mage_Core_Controller_Front_Action
{
    /**
     * Simplepath callback
     */
    public function indexAction()
    {
        header('Access-Control-Allow-Origin: https://payments.amazon.com');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $payloadJson = Mage::app()->getRequest()->getParam('payload');
        //$payloadJson = '{"encryptedKey":"mOrTIP1U%2FR%2BQJn5iJ8%2BsBGXsR19wXq8bSSTUiV6zEk5w5DCqmlzC0XTV8SeyBKKR%2B6c0g4FDXITC0okoY7eJyf%2BwjzLrOgQwVJ56KzGKHzF3FAEboXsfxQ5vC6FYyNgms433MKu%2F4BgvdDr9UgzAcgkpTM4Bj740i4MQiKv77%2BSFgHWniGcltiQy6wydIIQuxJeumZ1rEOY6e7jKw10KIaLLIenEoBVhsn%2Bleiec7CzFTTWOj%2Fic2mXxcomHqImMkqaHl8bLw9ggcpNI9s5NfAMgugPFKNb35l0n8TZYpBMMkr5N38pkxJjlmvkRMFzWzckTI54bTDOFuu5QlEByDg%3D%3D","encryptedPayload":"gmDc8Xl%2Bd%2FJQL2N4%2FDbDd0MNlNQ%2FV2NamF6B5IbW70kgbVFaG6%2F1YDslEYzCF8hmwzqWhncWf%2F0y8bTQZns1HEdyBjozfKlwa0kv%2BN9muOr6azycx1juKzD6msl%2FPlij5JRbV3cmEQ1reOzHFvomixiBGIdqbDUd5D66ZfpZksP2TKvrB4YRhx52d%2FBREyNhmtT2yj8%2F0o89ZAlwyDKHh8kaNOc2tN1FKFBqMHVq1FcFnY222BMKxGOtHBKOaBmD","iv":"hY0MqefXJJuwM9R8nBE6zg%3D%3D","sigKeyID":"sigKey82015","signature":"GyxnV2Chmy06jscgmS3Gk2mQMxi%2BFK2ytR%2F6qoIUWgxupOYSz2gtEEXJ0%2BVKQSFQW39StiZtWfqQw%2BSLINB6dbD3NSS5Z5uwI2ZZqU5erosEq7pPtdMwGIOZRChZj3ni%2BtMm2PGflokfvrsrW7HohQX%2FGOJMuMLoIA4Wv7L1FMIlcaIqTBtDZCyXXtFDz8m33%2B%2F3M3oFjgfLk7ppnoRKU%2Bg8oOM16Xoz5eV8JkYlwmhL7E9EP%2B09r7ZlPRQOEFtQA%2B59yV8FgDFWZpT%2F58usjvR3Tup3EIuV86UEFYvY1a5mX0BRE291OTk2A5%2FuW8r6Rf%2FwrJ2qJl5pnfSYn5qMbA%3D%3D"}';
        //$redirectUrl = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/payment', array('_store' => 0));
        $redirectUrl = Mage::getModel('amazon_payments/simplePath')->getSimplepathUrl();

        $this->getResponse()->setHeader('Content-type', 'application/json');

        try {
            if ($payloadJson) {
                $_simplePath = Mage::getModel('amazon_payments/simplePath');
                $json = $_simplePath->decryptPayload($payloadJson);

                if ($json) {
                    if ($adminSession = $this->_getAdminSession()) {
                        $adminSession->addSuccess($json);
                    }

                    $this->_redirectUrl($redirectUrl);
                }
            } else {
                $this->getResponse()->setBody(Zend_Json::encode(array('result' => 'error', 'message' => 'payload parameter not found.')));
            }

        } catch (Exception $e) {
            $this->getResponse()->setBody(Zend_Json::encode(array('result' => 'error', 'message' => $e->getMessage())));
        }
    }

    /**
     * Return adminhtml session
     */
    private function _getAdminSession()
    {
        // Check if adminhtml cookie is set
        if (isset($_COOKIE['adminhtml'])) {
          session_write_close();
          $GLOBALS['_SESSION'] = null;
          $session = Mage::getSingleton('core/session');
          $session->setSessionId($_COOKIE['adminhtml']);
          $session->start('adminhtml');
          return Mage::getSingleton('adminhtml/session');
        }

        return false;
    }

}
