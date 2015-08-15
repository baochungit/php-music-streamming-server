/*
Navicat MySQL Data Transfer

Source Server         : # localhost
Source Server Version : 50532
Source Host           : localhost:3306
Source Database       : db

Target Server Type    : MYSQL
Target Server Version : 50532
File Encoding         : 65001

Date: 2015-08-15 23:56:09
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for songs
-- ----------------------------
DROP TABLE IF EXISTS `songs`;
CREATE TABLE `songs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `filesize` varchar(255) DEFAULT NULL,
  `playtime` varchar(255) DEFAULT NULL,
  `audiostart` varchar(255) DEFAULT NULL,
  `audioend` varchar(255) DEFAULT NULL,
  `audiolength` double DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of songs
-- ----------------------------
INSERT INTO `songs` VALUES ('1', 'Boston Pops-John Williams - Super Mario Brothers Theme.mp3', '361071', '30.04075', '582', '361071', '360489', 'Boston Pops-John Williams', 'Super Mario Brothers Theme', '2014-11-11 10:19:39', null);
INSERT INTO `songs` VALUES ('2', 'Debussy - Clair de Lune.mp3', '361009', '30.04075', '520', '361009', '360489', 'Debussy', 'Clair de Lune', '2014-11-11 10:20:25', null);
INSERT INTO `songs` VALUES ('3', 'Dido - White Flag.mp3', '360997', '30.04075', '508', '360997', '360489', 'Dido', 'White Flag', '2014-11-11 10:20:59', null);

-- ----------------------------
-- Table structure for songs_stations
-- ----------------------------
DROP TABLE IF EXISTS `songs_stations`;
CREATE TABLE `songs_stations` (
  `song_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `played` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of songs_stations
-- ----------------------------
INSERT INTO `songs_stations` VALUES ('2', '1', null, '2014-11-11 10:21:59');
INSERT INTO `songs_stations` VALUES ('3', '1', null, '2014-11-11 10:22:08');
INSERT INTO `songs_stations` VALUES ('4', '1', null, null);

-- ----------------------------
-- Table structure for stations
-- ----------------------------
DROP TABLE IF EXISTS `stations`;
CREATE TABLE `stations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `genre` varchar(255) DEFAULT NULL,
  `bitrate` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of stations
-- ----------------------------
INSERT INTO `stations` VALUES ('1', 'Radio Liefde', 'Romance', '96', '2014-11-11 10:16:58', null);
