-- Additional Categories for K2 table SQL script
-- This will install the table need to run Additional Categories for K2


--
-- Table structure for table `#__k2_additional_categories`
--

CREATE TABLE IF NOT EXISTS `#__k2_additional_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `catid` int(11) NOT NULL DEFAULT '0',
  `itemID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;