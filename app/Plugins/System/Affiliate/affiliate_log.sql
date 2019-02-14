/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50719
Source Host           : localhost:3306
Source Database       : ectouch

Target Server Type    : MYSQL
Target Server Version : 50719
File Encoding         : 65001

Date: 2018-11-23 13:24:48
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for affiliate_log
-- ----------------------------
DROP TABLE IF EXISTS `affiliate_log`;
CREATE TABLE `affiliate_log` (
  `log_id` int(10) NOT NULL AUTO_INCREMENT,
  `order_id` int(10) NOT NULL,
  `time` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `user_name` varchar(60) DEFAULT NULL,
  `money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `point` int(10) NOT NULL DEFAULT '0',
  `separate_type` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
