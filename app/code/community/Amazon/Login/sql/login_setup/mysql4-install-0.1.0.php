<?php
/**
 * Amazon Login
 *
 * @category    Amazon
 * @package     Amazon_Payments
 * @copyright   Copyright (c) 2014 Amazon.com
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */


$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('amazon_login')}` (
  `login_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `amazon_uid` varchar(255) NOT NULL,
  PRIMARY KEY (`login_id`),
  KEY `amazon_uid` (`amazon_uid`),
  UNIQUE KEY `customer_id` (`customer_id`),
  CONSTRAINT `amazon_login_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();