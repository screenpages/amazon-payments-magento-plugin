<?php
/**
 * Amazon Payments
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Payments_Model_SimplePath
{
    const API_ENDPOINT_DOWNLOAD_KEYS = 'https://payments.amazon.com/817ee4262/downloadkeyspage';
    const API_ENDPOINT_GET_PUBLICKEY = 'https://payments.amazon.com/817ee4262/getpublickey';

    const CONFIG_XML_PATH_PRIVATE_KEY = 'payment/amazon_payments/simplepath/privatekey';
    const CONFIG_XML_PATH_PUBLIC_KEY  = 'payment/amazon_payments/simplepath/publickey';

    /**
     * Generate and save RSA keys
     */
    public function generateKeys()
    {
        $rsa = new Zend_Crypt_Rsa;
        $keys = $rsa->generateKeys(array('private_key_bits' => 2048, 'hashAlgorithm' => 'sha1'));

        $config = Mage::getModel('core/config');
        $config->saveConfig(self::CONFIG_XML_PATH_PUBLIC_KEY, $keys['publicKey'], 'default', 0);
        $config->saveConfig(self::CONFIG_XML_PATH_PRIVATE_KEY, Mage::helper('core')->encrypt($keys['privateKey']), 'default', 0);

        return $keys;
    }

    /**
     * Return RSA public key
     */
    public function getPublicKey($pemformat = false)
    {
        $publickey = Mage::getStoreConfig(self::CONFIG_XML_PATH_PUBLIC_KEY, 0);
        if (!$publickey) {
          $keys = $this->generateKeys();
          $publickey = $keys['publicKey'];
        }

        if (!$pemformat) {
            $publickey = str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"), array('','',''), $publickey);
        }
        return $publickey;
    }

    /**
     * Return RSA private key
     */

    public function getPrivateKey()
    {
        return Mage::helper('core')->decrypt(Mage::getStoreConfig(self::CONFIG_XML_PATH_PRIVATE_KEY, 0));
    }

    /**
     * Convert key to PEM format for openssl functions
     */
    public function key2pem($key)
    {
        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split($key, 64, "\n") .
               "-----END PUBLIC KEY-----\n";
    }

    /**
     * Verify and decrypt JSON payload
     */
    public function decryptPayload($payloadJson)
    {
        $payload = Zend_Json::decode($payloadJson, Zend_Json::TYPE_OBJECT);
        $payloadVerify = clone $payload;

        // Validate JSON
        if (!isset($payload->encryptedKey, $payload->encryptedPayload, $payload->iv, $payload->sigKeyID, $payload->signature)) {
            Mage::throwException("Unable to import Amazon keys. Please verify your JSON format and values.");
        }

        // URL decode values
        foreach ($payload as $key => $value) {
            $payload->$key = urldecode($value);
        }

        // Retrieve Amazon public key for signature verification
        $client = new Zend_Http_Client(self::API_ENDPOINT_GET_PUBLICKEY, array(
            'maxredirects' => 2,
            'timeout'      => 30));

        $client->setParameterGet(array('sigkey_id' => $payload->sigKeyID));

        $response = $client->request();
        $amazonPublickey = urldecode($response->getBody());

        // Use raw JSON (without signature or URL decode) as the data to verify signature
        unset($payloadVerify->signature);
        $payloadVerifyJson = Zend_Json::encode($payloadVerify);

        // Verify signature using Amazon publickey and JSON paylaod
        if (openssl_verify($payloadVerifyJson, base64_decode($payload->signature), $this->key2pem($amazonPublickey), OPENSSL_ALGO_SHA256)) {

            // Decrypt Amazon key using private key
            $decryptedKey = null;
            openssl_private_decrypt(base64_decode($payload->encryptedKey), $decryptedKey, $this->getPrivateKey(), OPENSSL_PKCS1_OAEP_PADDING);

            // Decrypt final payload (AES 128-bit)
            $finalPayload = mcrypt_cbc(MCRYPT_RIJNDAEL_128, $decryptedKey, base64_decode($payload->encryptedPayload), MCRYPT_DECRYPT, base64_decode($payload->iv));

            if (Zend_Json::decode($finalPayload)) {
                return $finalPayload;
            }
            else {
                return false;
            }

        } else {
            Mage::throwException("Unable to verify signature for JSON payload.");
        }
    }

}
