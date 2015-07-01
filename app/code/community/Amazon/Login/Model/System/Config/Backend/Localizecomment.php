<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */

class Amazon_Login_Model_System_Config_Backend_Localizecomment extends Mage_Core_Model_Config_Data
{
    /**
     * Return dynamic help/comment text
     *
     */
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        switch (Mage::helper('amazon_login')->getAdminRegion()) {
            case 'eu':
                $domain = 'sellercentral-europe.amazon.com';
                break;
            default:
                $domain = null;
                break;
        }


        // Use JS as the settings/header comment doesn't support getCommentText
        if ($domain) {
            return '<script>
                $$(".amzn-link").each(function(el, i) {
                    if (el.href) {
                        el.href = el.href.replace("sellercentral.amazon.com", "' . $domain . '");
                    }

                    var onclick = el.readAttribute("onclick");

                    if (onclick) {
                        el.writeAttribute("onclick", "javascript:window.open(\'https://' . $domain . '\')");
                        //el.writeAttribute("onclick", onclick.toString().replace("sellercentral.amazon.com", "' . $domain . '"));
                    }

                });
            </script>';
        }
    }
}