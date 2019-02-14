/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50719
Source Host           : localhost:3306
Source Database       : ectouch

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2018-11-23 13:20:14
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for wholesale
-- ----------------------------
DROP TABLE IF EXISTS `wholesale`;
CREATE TABLE `wholesale` (
  `act_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL,
  `goods_name` varchar(255) NOT NULL,
  `rank_ids` varchar(255) NOT NULL,
  `prices` text NOT NULL,
  `enabled` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`act_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
