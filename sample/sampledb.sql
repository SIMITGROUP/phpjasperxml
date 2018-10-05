-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 21, 2013 at 10:45 PM
-- Server version: 5.1.44
-- PHP Version: 5.3.1

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `phpjasperxml`
--

-- --------------------------------------------------------

--
-- Table structure for table `sample1`
--

CREATE TABLE IF NOT EXISTS `sample1` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `itemname` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL,
  `uom` varchar(10) NOT NULL,
  PRIMARY KEY (`no`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `sample1`
--

INSERT INTO `sample1` (`no`, `date`, `itemname`, `qty`, `uom`) VALUES
(1, '2009-08-11', 'Sample 1', 10, 'PCS'),
(2, '2009-08-26', '滑鼠', 2, 'PCS'),
(3, '2009-08-15', 'LCD Monitor', 1, 'PCS'),
(4, '2009-08-11', 'test item 3', 3, 'PCS'),
(6, '2009-08-11', 'Again, sample data', 8, 'day'),
(7, '2013-03-13', 'Dell Computer With Keyboard Mouse', 20, 'PCS');

-- --------------------------------------------------------

--
-- Table structure for table `sample2`
--

CREATE TABLE IF NOT EXISTS `sample2` (
  `date` date NOT NULL,
  `docno` varchar(20) NOT NULL,
  `companyname` varchar(30) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `terms` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `sample2`
--

INSERT INTO `sample2` (`date`, `docno`, `companyname`, `amount`, `terms`, `address`, `id`) VALUES
('2009-08-12', 'PO1001', 'Company 1', 100.00, 'C.O.D', '222, Street XXX,\r\nXXXX, XXXX,\r\nMalaysia', 1),
('2009-08-22', 'PO1002', 'Company 2', 300.00, '30 Days', '11, Street YYYY,\r\nYYYYY YYYYY\r\nSingapore', 2);

-- --------------------------------------------------------

--
-- Table structure for table `sample2line`
--

CREATE TABLE IF NOT EXISTS `sample2line` (
  `no` int(11) NOT NULL,
  `itemname` varchar(40) NOT NULL,
  `qty` int(11) NOT NULL,
  `unitprice` decimal(12,2) NOT NULL,
  `uom` varchar(10) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `headerid` int(11) NOT NULL,
  `lineid` int(11) NOT NULL AUTO_INCREMENT,
  `linedesc` text NOT NULL,
  PRIMARY KEY (`lineid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `sample2line`
--

INSERT INTO `sample2line` (`no`, `itemname`, `qty`, `unitprice`, `uom`, `amount`, `headerid`, `lineid`, `linedesc`) VALUES
(1, 'LCD Monitor', 3, 300.00, 'PCS', 900.00, 1, 1, '* Samsung (SN:12345)\r\n* HP (SN: 2323434)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* Samsung (SN:12345)\r\n* HP (SN: 2323434)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* Samsung (SN:12345)\r\n* HP (SN: 2323434)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* Samsung (SN:12345)\r\n* HP (SN: 2323434)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* Samsung (SN:12345)\r\n* HP (SN: 2323434)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* Samsung (SN:12345)\r\n* HP (SN: 2323434)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)\r\n* ACER (SN:xxxxx)'),
(2, 'Optical Mouse', 4, 1.00, 'PCS', 4.00, 1, 2, '* 2nd hand'),
(1, 'Notebook', 1, 1000.00, 'PCS', 1000.00, 2, 3, '');
SET FOREIGN_KEY_CHECKS=1;
