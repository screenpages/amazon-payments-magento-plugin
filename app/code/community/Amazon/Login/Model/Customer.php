<?php
/**
 * Login with Amazon Customer Model
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Amazon_Login_Model_Customer extends Mage_Customer_Model_Customer
{
    /**
     * Log user in via Amazon token
     *
     * @param string $token
     *   Amazon Access Token
     * @return object $customer
     */
    public function loginWithToken($token)
    {
        $amazonProfile = $this->getAmazonProfile($token);

        if ($amazonProfile && isset($amazonProfile['email'])) {
            $this->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($amazonProfile['email']);

            // Log user in?
            if ($this->getId()) {
                Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($this);
            }
            else {
                $this->createCustomer($amazonProfile);
            }
        }

        return $this;
    }

    /**
     * Get Amazon Profile
     */
    public function getAmazonProfile($token)
    {
        return Mage::getModel('amazon_login/api')->request('user/profile?access_token=' . urlencode($token));
    }

    /**
     * Get Amazon Name
     *
     * @return array
     */
    public function getAmazonName($name)
    {
        $firstName = substr($name, 0, strrpos($name, ' '));
        $lastName  = substr($name, strlen($firstName) + 1);
        return array($firstName, $lastName);
    }

    /**
     * Create a new customer
     *
     * @param array $amazonProfile
     *   Associative array containing email and name
     * @return object $customer
     */
    public function createCustomer($amazonProfile)
    {
        // Verify customer does not exist
        $this->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($amazonProfile['email']);
        if ($this->getId()) {
            return $this;
        }

        $firstName = substr($amazonProfile['name'], 0, strrpos($amazonProfile['name'], ' '));
        $lastName  = substr($amazonProfile['name'], strlen($firstName) + 1);

        try {
            $this
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->setEmail($amazonProfile['email'])
                ->setPassword($this->generatePassword(8))
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setConfirmation(null)
                ->setIsActive(1)
                ->save()
                ->sendNewAccountEmail('registered', '', Mage::app()->getStore()->getId());

            Mage::getSingleton('customer/session')->loginById($this->getId());
        }
        catch (Exception $ex) {
            Zend_Debug::dump($ex->getMessage());
        }

        return $this;
    }

}


?>