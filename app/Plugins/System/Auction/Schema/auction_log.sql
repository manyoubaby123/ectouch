/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50719
Source Host           : localhost:3306
Source Database       : ectouch

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2018-11-23 13:22:30
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for auction_log
-- ----------------------------
DROP TABLE IF EXISTS `auction_log`;
CREATE TABLE `auction_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `act_id` int(10) unsigned NOT NULL,
  `bid_user` int(10) unsigned NOT NULL,
  `bid_price` decimal(10,2) unsigned NOT NULL,
  `bid_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `act_id` (`act_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
