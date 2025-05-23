-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 23, 2025 at 10:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecom_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(10) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `admin_pass` varchar(255) NOT NULL,
  `admin_image` text NOT NULL,
  `admin_contact` varchar(255) NOT NULL,
  `admin_country` text NOT NULL,
  `admin_job` varchar(255) NOT NULL,
  `admin_about` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_name`, `admin_email`, `admin_pass`, `admin_image`, `admin_contact`, `admin_country`, `admin_job`, `admin_about`) VALUES
(3, 'Dje', 'anis@gmail.com', '1234', 'phone.png', '123456789', 'Algeria ', 'front', 'none'),
(101, 'chou', 'chou@gmail.com', '1234', 'admin_1745942450.png', '0777777777', 'Algeria ', 'ee', 'ee');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `p_id` int(10) NOT NULL,
  `qty` int(10) NOT NULL,
  `p_price` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'WAITING',
  `added_date` datetime DEFAULT current_timestamp(),
  `checked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `customer_id`, `p_id`, `qty`, `p_price`, `status`, `added_date`, `checked`) VALUES
(2, 11, 63, 1, '88000', 'Not Confirmed', '2025-03-01 12:00:00', 0),
(3, 11, 64, 1, '88000', 'Not Confirmed', '2025-04-01 12:00:00', 0),
(4, 11, 85, 3, '0', 'ORDERED', '2025-05-02 18:15:59', 0),
(5, 11, 66, 1, '88000', 'ORDERED', '2025-05-02 18:25:53', 0),
(6, 11, 64, 1, '59000', 'ORDERED', '2025-05-02 19:06:29', 0),
(7, 11, 60, 1, '80000', 'ORDERED', '2025-05-02 19:47:07', 0),
(8, 11, 19, 2, '42250', 'ORDERED', '2025-05-02 19:50:09', 0),
(9, 12, 66, 1, '88000', 'ORDERED', '2025-05-02 20:00:32', 0),
(10, 12, 60, 1, '80000', 'ORDERED', '2025-05-02 20:13:01', 0),
(11, 12, 86, 1, '0', 'ORDERED', '2025-05-02 20:13:33', 0),
(12, 12, 83, 2, '0', 'ORDERED', '2025-05-02 20:24:36', 0),
(13, 12, 64, 1, '59000', 'ORDERED', '2025-05-02 21:13:08', 0),
(14, 12, 64, 1, '59000', 'WAITING', '2025-05-02 22:27:37', 0),
(28, 11, 95, 1, '2000', 'ORDERED', '2025-05-04 10:19:39', 0),
(30, 11, 82, 1, '5900', 'ORDERED', '2025-05-04 10:40:46', 0),
(31, 11, 82, 1, '5900', 'ORDERED', '2025-05-05 14:44:10', 1),
(32, 11, 19, 1, '42250', 'ORDERED', '2025-05-05 15:04:01', 1),
(33, 11, 18, 1, '49000', 'ORDERED', '2025-05-05 15:04:56', 1),
(34, 11, 70, 1, '165000', 'ORDERED', '2025-05-05 15:22:20', 0),
(41, 11, 60, 1, '80000', 'ORDERED', '2025-05-22 12:36:23', 0),
(44, 11, 60, 2, '80000', 'ORDERED', '2025-05-23 15:26:13', 0),
(46, 11, 19, 1, '42250', 'ORDERED', '2025-05-23 16:13:24', 0),
(47, 15, 105, 1, '120000', 'ORDERED', '2025-05-23 17:32:32', 0),
(48, 15, 19, 1, '42250', 'ORDERED', '2025-05-23 19:37:12', 0),
(49, 15, 83, 1, '0', 'ORDERED', '2025-05-23 19:41:48', 0),
(50, 15, 81, 2, '0', 'ORDERED', '2025-05-23 19:41:50', 0),
(51, 15, 60, 1, '80000', 'ORDERED', '2025-05-23 19:53:31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(10) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `customer_country` text NOT NULL,
  `customer_city` text NOT NULL,
  `customer_address` text NOT NULL,
  `customer_contact` varchar(255) NOT NULL DEFAULT '',
  `customer_image` text NOT NULL,
  `customer_ip` varchar(255) NOT NULL,
  `customer_confirm_code` text NOT NULL,
  `customer_points` int(10) DEFAULT NULL,
  `fidelity_discount` int(11) NOT NULL DEFAULT 0,
  `customer_postal_code` varchar(20) DEFAULT '',
  `customer_phone` varchar(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`, `email`, `password`, `customer_country`, `customer_city`, `customer_address`, `customer_contact`, `customer_image`, `customer_ip`, `customer_confirm_code`, `customer_points`, `fidelity_discount`, `customer_postal_code`, `customer_phone`) VALUES
(11, 'ramoul', 'ramoul@gmail.com', '$2y$10$BuVaZVN3PF3fyzgWVgZ1w.Tz5Dodhk3lBWV4U11c71P0pko0z9g5.', 'Algérie', 'Alger centre', 'Street 5 - bd Mustapha Ben Boulaïd', '', 'customer_11_1745505702.jpg', '', '', 38555, 0, '', ''),
(12, 'test', 'test@gmail.com', '$2y$10$ua558RoVPU.7vpispBi6IuEAcCPU3aEx8H4ApQCpR0DWIBdf9OSYO', 'Algérie', 'Constantine', 'Street 5 - bd Mustapha Ben Boulaïd', '', '', '', '', 7800, 0, '', ''),
(15, 'Final', 'final@gmail.com', '$2y$10$pX5PX0TES69feTlh2iVToOk05IgvDEbdSg9VrFaanBtBG2BmWdmcK', 'Algérie', 'Constantine', 'Street 5 - bd Mustapha Ben Boulaïd', '', '', '', '', 60000, 0, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `customer_orders`
--

CREATE TABLE `customer_orders` (
  `order_id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `due_amount` int(100) NOT NULL,
  `invoice_no` int(100) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_status` text NOT NULL,
  `has_discount` tinyint(1) NOT NULL DEFAULT 0,
  `discount_id` int(11) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `city` text NOT NULL,
  `postal_code` text NOT NULL,
  `phone` varchar(255) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `checked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_orders`
--

INSERT INTO `customer_orders` (`order_id`, `customer_id`, `due_amount`, `invoice_no`, `order_date`, `order_status`, `has_discount`, `discount_id`, `shipping_address`, `city`, `postal_code`, `phone`, `subtotal`, `shipping_cost`, `total_amount`, `checked`) VALUES
(49, 11, 88000, 123458, '2025-05-01 13:19:05', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(51, 11, 88000, 123460, '2025-03-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(52, 11, 88000, 123461, '2025-04-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(55, 11, 88000, 123458, '2025-04-01 11:00:00', 'completed', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(57, 11, 88000, 123460, '2025-03-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(58, 11, 88000, 123461, '2025-04-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(61, 11, 88000, 123458, '2025-04-01 11:00:00', 'completed', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(63, 11, 88000, 123460, '2025-03-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(64, 11, 88000, 123461, '2025-04-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(67, 11, 88000, 123458, '2025-04-01 11:00:00', 'completed', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(69, 11, 88000, 123460, '2025-03-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(70, 11, 88000, 123461, '2025-04-01 11:00:00', 'pending', 0, NULL, '', '', '', '', 0.00, 0.00, 0.00, 0),
(83, 11, 76550, 0, '2025-05-23 15:26:24', 'pending', 0, 1, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(86, 12, 57500, 0, '2025-05-23 15:26:24', 'pending', 0, 2, 'Street 5 - bd Mustapha Ben Boulaïd', 'Constantine', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(94, 11, 6370, 0, '2025-05-04 09:59:05', 'pending', 0, NULL, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '16100', '0562332803', 0.00, 0.00, 0.00, 0),
(95, 11, 5810, 0, '2025-05-23 15:26:24', 'pending', 0, 1, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(96, 11, 38525, 0, '2025-05-23 15:26:24', 'pending', 0, 1, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(97, 11, 47500, 0, '2025-05-23 15:26:24', 'pending', 0, 2, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(99, 11, 124250, 0, '2025-05-23 15:26:24', 'pending', 0, 2, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(100, 11, 135500, 0, '2025-05-23 15:26:24', 'pending', 0, 2, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(106, 11, 120500, 0, '2025-05-23 15:26:24', 'pending', 0, 2, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(107, 11, 38525, 0, '2025-05-23 15:26:24', 'pending', 0, 1, 'Street 5 - bd Mustapha Ben Boulaïd', 'Alger centre', '16100', '0562332803', 0.00, 0.00, 0.00, 0),
(108, 15, 108500, 0, '2025-05-23 16:56:19', 'pending', 0, NULL, 'Street 5 - bd Mustapha Ben Boulaïd', 'Constantine', '16100', '0562332803', 0.00, 0.00, 0.00, 0),
(109, 15, 38525, 0, '2025-05-23 18:52:48', 'completed', 0, NULL, 'cite sidiabdellah bt 10788', 'Constantine', '25000', '0554997155', 0.00, 0.00, 0.00, 0),
(110, 15, 72500, 0, '2025-05-23 19:05:17', 'pending', 0, NULL, 'cite sakiet sidi youcef bt 1002 n1488', 'Constantine', '25000', '0554997155', 0.00, 0.00, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `usage_limit`, `usage_count`, `start_date`, `expiry_date`, `status`, `created_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 0.00, 0.00, NULL, 0, NULL, '2025-06-01', 'active', '2025-05-02 17:37:01'),
(2, 'SUMMER25', 'percentage', 25.00, 5000.00, 0.00, 100, 0, NULL, '2025-07-01', 'active', '2025-05-02 17:37:01'),
(3, 'FREESHIP', 'fixed', 500.00, 10000.00, 500.00, 50, 0, NULL, '2025-05-17', 'active', '2025-05-02 17:37:01');

-- --------------------------------------------------------

--
-- Table structure for table `fidelity_gifts`
--

CREATE TABLE `fidelity_gifts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('discount','accessory') NOT NULL,
  `value` int(11) NOT NULL,
  `required_points` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `min_product_price` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fidelity_gifts`
--

INSERT INTO `fidelity_gifts` (`id`, `name`, `description`, `type`, `value`, `required_points`, `created_at`, `updated_at`, `min_product_price`) VALUES
(5, '5% Discount', 'Get 5% off on your next purchase', 'discount', 500, 500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(6, '10% Discount', 'Get 10% off on your next purchase', 'discount', 1000, 1000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(7, '15% Discount', 'Get 15% off on your next purchase', 'discount', 1500, 1500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(8, '20% Discount', 'Get 20% off on your next purchase', 'discount', 2000, 2000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(9, '25% Discount', 'Get 25% off on your next purchase (not applicable for products under 10,000 D.A)', 'discount', 2500, 2500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 10000),
(10, '30% Discount', 'Get 30% off on your next purchase (not applicable for products under 15,000 D.A)', 'discount', 3000, 3000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 15000),
(11, '40% Discount', 'Get 40% off on your next purchase (not applicable for products under 20,000 D.A)', 'discount', 4000, 4000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 20000),
(12, '50% Discount', 'Get 50% off on your next purchase (not applicable for products under 30,000 D.A)', 'discount', 5000, 5000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 30000),
(13, 'Premium Phone Case', 'A high-quality protective case for your smartphone', 'accessory', 1000, 1000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(14, 'Tempered Glass Screen Protector', 'Protect your phone screen from scratches and cracks', 'accessory', 800, 800, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(15, 'Phone Grip Stand', 'Comfortable grip and stand for your smartphone', 'accessory', 500, 500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(16, 'Car Phone Mount', 'Secure your phone while driving', 'accessory', 1200, 1200, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(17, 'Wireless Charging Pad', 'Charge your compatible devices without cables', 'accessory', 1500, 1500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(18, 'Wireless Earbuds', 'High-quality wireless earbuds for music and calls', 'accessory', 3000, 3000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(19, 'Bluetooth Speaker', 'Portable speaker with excellent sound quality', 'accessory', 2500, 2500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(20, 'Wired Headphones', 'Comfortable over-ear headphones', 'accessory', 1800, 1800, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(21, 'Laptop Sleeve', 'Protective sleeve for laptops up to 15.6\"', 'accessory', 1200, 1200, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(22, 'Wireless Mouse', 'Ergonomic wireless mouse for comfortable use', 'accessory', 1500, 1500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(23, 'USB Hub', 'Expand your connectivity options with multiple ports', 'accessory', 1000, 1000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(24, 'Laptop Cooling Pad', 'Keep your laptop cool during intensive tasks', 'accessory', 2000, 2000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(25, 'Power Bank', '10,000mAh portable charger for your devices', 'accessory', 2000, 2000, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(26, 'USB-C Cable Pack', 'Set of 3 durable USB-C cables of different lengths', 'accessory', 1200, 1200, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(27, 'Cable Organizer', 'Keep your cables neat and tangle-free', 'accessory', 800, 800, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0),
(28, 'Smartphone Tripod', 'Flexible tripod for taking photos and videos', 'accessory', 1500, 1500, '2025-05-02 16:36:29', '2025-05-02 16:36:29', 0);

-- --------------------------------------------------------

--
-- Table structure for table `fidelity_redemptions`
--

CREATE TABLE `fidelity_redemptions` (
  `id` int(11) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `gift_id` int(11) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fidelity_redemptions`
--

INSERT INTO `fidelity_redemptions` (`id`, `customer_id`, `gift_id`, `redeemed_at`) VALUES
(3, 11, 15, '2025-05-02 17:15:59'),
(4, 11, 15, '2025-05-02 17:16:06'),
(6, 11, 6, '2025-05-02 17:16:54'),
(8, 12, 15, '2025-05-02 19:24:36'),
(18, 11, 6, '2025-05-04 09:47:17'),
(22, 15, 15, '2025-05-23 18:41:48'),
(23, 15, 13, '2025-05-23 18:41:50'),
(24, 15, 13, '2025-05-23 18:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `manufacturers`
--

CREATE TABLE `manufacturers` (
  `manufacturer_id` int(10) NOT NULL,
  `manufacturer_title` varchar(255) NOT NULL,
  `manufacturer_top` text NOT NULL,
  `manufacturer_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manufacturers`
--

INSERT INTO `manufacturers` (`manufacturer_id`, `manufacturer_title`, `manufacturer_top`, `manufacturer_image`) VALUES
(1, 'Apple', 'yes', 'apple.jpg'),
(2, 'Samsung', 'yes', '680a0c6c2b0f9.png'),
(3, 'Realme', 'yes', 'realme.jpg'),
(4, 'Dell', 'yes', 'dell.jpg'),
(5, 'Soundcore', 'no', 'Anker.jpg'),
(6, 'Xiaomi', 'yes', 'xiaomi.jpg'),
(7, 'Asus', 'yes', 'asus.jpg'),
(8, 'Logitech', 'yes', '680a4a60c4843.png'),
(9, 'Kingston', 'yes', 'manufacturer_1746451470.png');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(2, 106, 60, 2, 80000.00),
(3, 107, 19, 1, 42250.00),
(4, 108, 105, 1, 120000.00),
(5, 109, 19, 1, 42250.00),
(6, 110, 83, 1, 0.00),
(7, 110, 81, 2, 0.00),
(8, 110, 60, 1, 80000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(10) NOT NULL,
  `order_id` int(10) NOT NULL,
  `amount` int(10) NOT NULL,
  `payment_mode` text NOT NULL,
  `ref_no` int(10) NOT NULL,
  `code` int(10) NOT NULL,
  `payment_date` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `amount`, `payment_mode`, `ref_no`, `code`, `payment_date`) VALUES
(4, 49, 400, 'Western Union', 101025780, 696950, 'January 1'),
(6, 51, 100, 'Bank Code', 1010101022, 88669, '09/14/2021'),
(7, 52, 480, 'Western Union', 1785002101, 66990, '09-04-2021'),
(10, 55, 480, 'Bank Code', 2147483647, 66580, '09-14-2021'),
(21, 57, 120, 'Bank Code', 1455000020, 202020, '09-13-2021'),
(22, 58, 120, 'Bank Code', 1450000020, 202020, '09-15-2021'),
(25, 61, 245, 'Western Union', 1200002588, 88850, '09-15-2021'),
(26, 110, 72500, 'Credit Card', 2147483647, 969733, '2025-05-23 21:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) NOT NULL,
  `p_cat_id` int(10) NOT NULL,
  `cat_id` int(10) NOT NULL,
  `manufacturer_id` int(10) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `product_title` text NOT NULL,
  `product_url` text NOT NULL,
  `product_price` int(10) NOT NULL,
  `product_psp_price` int(100) DEFAULT NULL,
  `stock` int(10) NOT NULL DEFAULT 0,
  `product_desc` text NOT NULL,
  `product_features` text NOT NULL,
  `product_video` text NOT NULL,
  `product_keywords` text NOT NULL,
  `product_label` text NOT NULL,
  `related_products` text NOT NULL DEFAULT '',
  `status` varchar(255) NOT NULL,
  `fidelity_percentage` int(11) DEFAULT 0,
  `fidelity_score` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `p_cat_id`, `cat_id`, `manufacturer_id`, `date`, `product_title`, `product_url`, `product_price`, `product_psp_price`, `stock`, `product_desc`, `product_features`, `product_video`, `product_keywords`, `product_label`, `related_products`, `status`, `fidelity_percentage`, `fidelity_score`) VALUES
(18, 4, 0, 3, '2025-05-05 14:05:00', 'Realme Gt6 (12 Gb) (256 Gb)', 'realme-gt6', 49000, 0, 7, 'The Realme GT6 is a powerful smartphone featuring a 6.78-inch AMOLED display with 120Hz refresh rate, Snapdragon 8s Gen 3 processor, and a 50MP triple camera system. With 12GB RAM and 256GB storage, it delivers exceptional performance for gaming and multitasking.', 'Display: 6.78&quot; AMOLED, 2780 x 1264 pixels, 120Hz refresh rate|Processor: Qualcomm Snapdragon 8s Gen 3|Memory: 12GB RAM|Storage: 256GB (non-expandable)|Rear Camera: Triple Camera: 50MP Main (f/1.8), 8MP Ultra-Wide (f/2.2), 2MP Macro (f/2.4)|Front Camera: 16MP (f/2.5)|Battery: 5500mAh with 120W SuperVOOC charging|Operating System: Android 14 with Realme UI 5.0|Connectivity: 5G, Wi-Fi 6, Bluetooth 5.3, NFC, USB Type-C|Dimensions: 162.9 x 75.8 x 8.7 mm|Weight: 199g|Colors: Silver, Green, Purple|Water Resistance: IP65 (splash resistant)', '', 'realme, gt6, smartphone, 256gb', 'sale', '17,19,18', 'product', 10, 4225),
(19, 4, 0, 6, '2025-05-23 18:38:48', 'Xiaomi Note 13 Pro 4G (6 Gb) (128Gb)', 'xiaomi-note-13-pro', 49000, 42250, 5, 'The Xiaomi Note 13 Pro 4G features a stunning 6.67-inch AMOLED display, Snapdragon 7s Gen 2 processor, and an impressive 200MP main camera. With 6GB RAM and 128GB storage, it offers great performance and value.', 'Display: 6.67&quot; AMOLED, 2400 x 1080 pixels, 120Hz refresh rate|Processor: Qualcomm Snapdragon 7s Gen 2|Memory: 6GB RAM|Storage: 128GB (expandable via microSD)|Rear Camera: Triple Camera: 200MP Main (f/1.7) with OIS, 8MP Ultra-Wide (f/2.2), 2MP Macro (f/2.4)|Front Camera: 16MP (f/2.4)|Battery: 5000mAh with 67W fast charging|Operating System: Android 13 with MIUI 14|Connectivity: 4G LTE, Wi-Fi 5, Bluetooth 5.2, NFC, USB Type-C|Dimensions: 161.2 x 74.3 x 8.0 mm|Weight: 187g|Colors: Black, Blue, Purple|Water Resistance: IP54 (splash resistant)', '', 'xiaomi, note, 13, pro, smartphone, 128gb', '', '17,18,19', 'product', 10, 4225),
(60, 4, 0, 2, '2025-05-23 19:05:17', 'Samsung Galaxy S23 (8 Gb) (256 Gb)', 'samsung-galaxy-s23-8-gb-256-gb', 96000, 80000, 7, 'The Samsung Galaxy S23 features a sleek design with a 6.1-inch Dynamic AMOLED 2X display, Snapdragon 8 Gen 2 processor, and a triple camera system. With 8GB RAM and 256GB storage, it offers powerful performance for all your needs.', 'Display: 6.1&quot; FHD+ Dynamic AMOLED 2X, 2340 x 1080 pixels, 120Hz refresh rate|Processor: Qualcomm Snapdragon 8 Gen 2 (4nm)|Memory: 8GB RAM|Storage: 256GB (non-expandable)|Rear Camera: Triple Camera: 50MP Wide (f/1.8), 12MP Ultra-Wide (f/2.2), 10MP Telephoto (f/2.4) with 3x optical zoom|Front Camera: 12MP (f/2.2)|Battery: 3900mAh with 25W wired charging, 15W wireless charging|Operating System: Android 13 with One UI 5.1|Connectivity: 5G, Wi-Fi 6E, Bluetooth 5.3, NFC, USB Type-C|Dimensions: 146.3 x 70.9 x 7.6 mm|Weight: 168g|Colors: Phantom Black, Cream, Green, Lavender|Water Resistance: IP68 (water and dust resistant)\r\nrelated : 18,19', '', 'samsung, galaxy, s23, smartphone, 256gb', 'hot', '19 , 18', 'product', 6, 4800),
(63, 4, 0, 1, '2025-05-01 14:02:01', 'iPhone 14 (6 Gb) (128 Gb)', 'iphone-14', 110000, 100000, 6, 'The iPhone 14 features a 6.1-inch Super Retina XDR display, A15 Bionic chip, and a 12MP dual-camera system. With 6GB RAM and 128GB storage, it’s a reliable choice for iOS users.', 'Display: 6.1&quot; Super Retina XDR, 2532 x 1170 pixels, 60Hz|Processor: A15 Bionic (5nm)|Memory: 6GB RAM|Storage: 128GB (non-expandable)|Rear Camera: Dual Camera: 12MP Main (f/1.5), 12MP Ultra-Wide (f/2.4)|Front Camera: 12MP (f/1.9)|Battery: 3279mAh with 20W wired charging, 15W MagSafe wireless charging|Operating System: iOS 16|Connectivity: 5G, Wi-Fi 6, Bluetooth 5.3, NFC, Lightning|Dimensions: 146.7 x 71.5 x 7.8 mm|Weight: 172g|Colors: Midnight, Starlight, Blue|Water Resistance: IP68', '', 'iphone, 14, apple, smartphone, 128gb', 'sale', '18,19,61', 'product', 5, 5000),
(64, 4, 0, 2, '2025-05-02 20:26:47', 'Samsung Galaxy A54 (8 Gb) (128 Gb)', 'samsung-galaxy-a54', 65000, 59000, 8, 'The Samsung Galaxy A54 offers a 6.4-inch Super AMOLED display, Exynos 1380 processor, and a 50MP quad-camera system. With 8GB RAM and 128GB storage, it’s a great mid-range option.', 'Display: 6.4&quot; Super AMOLED, 2340 x 1080 pixels, 120Hz|Processor: Exynos 1380 (5nm)|Memory: 8GB RAM|Storage: 128GB (expandable via microSD)|Rear Camera: Quad Camera: 50MP Main (f/1.8), 12MP Ultra-Wide (f/2.2), 5MP Macro (f/2.4), 5MP Depth (f/2.4)|Front Camera: 32MP (f/2.2)|Battery: 5000mAh with 25W fast charging|Operating System: Android 13 with One UI 5.1|Connectivity: 5G, Wi-Fi 6, Bluetooth 5.3, NFC, USB-C|Dimensions: 158.2 x 76.7 x 8.2 mm|Weight: 202g|Colors: Awesome Black, Awesome White, Awesome Violet|Water Resistance: IP67', '', 'samsung, galaxy, a54, smartphone, 128gb', '', '61,19', 'product', 5, 2950),
(65, 4, 0, 3, '2025-05-01 14:00:34', 'Realme 9 Pro (8 Gb) (128 Gb)', 'realme-9-pro', 55000, 50000, 8, 'The Realme 9 Pro features a 6.6-inch IPS LCD display, Snapdragon 695 processor, and a 64MP triple-camera system. With 8GB RAM and 128GB storage, it’s a solid mid-range phone.', 'Display: 6.6&quot; IPS LCD, 2400 x 1080 pixels, 120Hz|Processor: Qualcomm Snapdragon 695 (6nm)|Memory: 8GB RAM|Storage: 128GB (expandable via microSD)|Rear Camera: Triple Camera: 64MP Main (f/1.8), 8MP Ultra-Wide (f/2.2), 2MP Macro (f/2.4)|Front Camera: 16MP (f/2.5)|Battery: 5000mAh with 33W fast charging|Operating System: Android 12 with Realme UI 3.0|Connectivity: 5G, Wi-Fi 5, Bluetooth 5.1, NFC, USB-C|Dimensions: 164.3 x 75.6 x 8.5 mm|Weight: 195g|Colors: Midnight Black, Aurora Green|Water Resistance: None', '', 'realme, 9, pro, smartphone, 128gb', 'hot', '18,19', 'product', 5, 2500),
(66, 4, 0, 6, '2025-05-02 21:45:44', 'Xiaomi 14 (12 Gb) (256 Gb)', 'xiaomi-14', 95000, 88000, 0, 'The Xiaomi 14 boasts a 6.36-inch AMOLED display, Snapdragon 8 Gen 3 processor, and a 50MP Leica-tuned camera system. With 12GB RAM and 256GB storage, it’s a flagship device.', 'Display: 6.36&quot; AMOLED, 2670 x 1200 pixels, 120Hz|Processor: Qualcomm Snapdragon 8 Gen 3|Memory: 12GB RAM|Storage: 256GB (non-expandable)|Rear Camera: Triple Camera: 50MP Main (f/1.6, Leica), 50MP Ultra-Wide (f/2.2), 50MP Telephoto (f/2.0) with 3.2x optical zoom|Front Camera: 32MP (f/2.0)|Battery: 4610mAh with 90W fast charging, 50W wireless charging|Operating System: Android 14 with HyperOS|Connectivity: 5G, Wi-Fi 7, Bluetooth 5.4, NFC, USB-C|Dimensions: 152.8 x 71.5 x 8.2 mm|Weight: 193g|Colors: Black, White, Jade Green|Water Resistance: IP68', '', 'xiaomi, 14, smartphone, 256gb', 'new', '19,18', 'product', 5, 4400),
(67, 8, 0, 4, '2025-05-01 13:59:10', 'Dell Inspiron 15 (16 Gb) (1 Tb)', 'dell-inspiron-15', 90000, 85000, 5, 'The Dell Inspiron 15 features a 15.6-inch FHD display, Intel Core i5-1235U processor, and a 1TB SSD. With 16GB RAM, it’s great for multitasking.', 'Display: 15.6&quot; FHD, 1920 x 1080 pixels, 120Hz|Processor: Intel Core i5-1235U (12th Gen)|Memory: 16GB DDR4 RAM|Storage: 1TB SSD|Graphics: Intel Iris Xe|Ports: 2x USB 3.2, 1x USB-C, HDMI|Battery: 54Wh with 65W charging|Operating System: Windows 11 Home|Connectivity: Wi-Fi 6, Bluetooth 5.2|Dimensions: 358.5 x 235.6 x 18.9 mm|Weight: 1.65kg|Colors: Platinum Silver', '', 'dell, inspiron, 15, laptop, 1tb', '', '', 'product', 5, 4250),
(68, 8, 0, 4, '2025-05-01 13:59:05', 'Dell Latitude 5440 (8 Gb) (512 Gb)', 'dell-latitude-5440', 95000, 90000, 3, 'The Dell Latitude 5440 offers a 14-inch FHD display, Intel Core i5-1335U processor, and a 512GB SSD. With 8GB RAM, it’s ideal for business users.', 'Display: 14&quot; FHD, 1920 x 1080 pixels, 60Hz|Processor: Intel Core i5-1335U (13th Gen)|Memory: 8GB DDR4 RAM|Storage: 512GB SSD|Graphics: Intel Iris Xe|Ports: 2x USB 3.2, 2x USB-C, HDMI|Battery: 42Wh with 65W charging|Operating System: Windows 11 Pro|Connectivity: Wi-Fi 6E, Bluetooth 5.3|Dimensions: 321.4 x 212 x 19.1 mm|Weight: 1.4kg|Colors: Grey', '', 'dell, latitude, 5440, laptop, 512gb', '', '', 'product', 5, 4500),
(69, 8, 0, 4, '2025-05-02 21:36:20', 'Dell G15 Gaming (16 Gb) (1 Tb)', 'dell-g15-gaming', 135000, 125000, 1, 'The Dell G15 Gaming laptop features a 15.6-inch FHD 165Hz display, Intel Core i7-12700H processor, and NVIDIA RTX 3060 graphics. With 16GB RAM and 1TB SSD, it’s built for gaming.', 'Display: 15.6&quot; FHD, 1920 x 1080 pixels, 165Hz|Processor: Intel Core i7-12700H (12th Gen)|Memory: 16GB DDR5 RAM|Storage: 1TB SSD|Graphics: NVIDIA RTX 3060 6GB|Ports: 3x USB 3.2, 1x USB-C, HDMI|Battery: 86Wh with 180W charging|Operating System: Windows 11 Home|Connectivity: Wi-Fi 6, Bluetooth 5.2|Dimensions: 357.3 x 272.1 x 26.9 mm|Weight: 2.5kg|Colors: Dark Shadow Grey', '', 'dell, g15, gaming, laptop, 1tb', 'hot', '', 'product', 4, 5000),
(70, 8, 0, 4, '2025-05-22 08:42:30', 'Dell Precision 5570 (32 Gb) (1 Tb)', 'dell-precision-5570', 180000, 165000, 6, 'The Dell Precision 5570 is a workstation with a 15.6-inch UHD display, Intel Core i9-12950HX processor, and NVIDIA RTX A2000 graphics. With 32GB RAM and 1TB SSD, it’s for professionals.', 'Display: 15.6&quot; UHD, 3840 x 2400 pixels, 60Hz|Processor: Intel Core i9-12950HX (12th Gen)|Memory: 32GB DDR5 RAM|Storage: 1TB SSD|Graphics: NVIDIA RTX A2000 8GB|Ports: 2x USB-C Thunderbolt 4, 1x USB 3.2, HDMI|Battery: 86Wh with 130W charging|Operating System: Windows 11 Pro|Connectivity: Wi-Fi 6E, Bluetooth 5.3|Dimensions: 344.4 x 230.1 x 18.5 mm|Weight: 1.8kg|Colors: Silver', '', 'dell, precision, 5570, laptop, 1tb', 'new', '', 'product', 4, 6600),
(71, 8, 0, 7, '2025-05-01 13:57:55', 'Asus ROG Strix G16 (16 Gb) (1 Tb)', 'asus-rog-strix-g16', 145000, 135000, 3, 'The Asus ROG Strix G16 features a 16-inch QHD 240Hz display, Intel Core i7-13650HX processor, and NVIDIA RTX 4070 graphics. With 16GB RAM and 1TB SSD, it’s a gaming powerhouse.', 'Display: 16&quot; QHD, 2560 x 1600 pixels, 240Hz|Processor: Intel Core i7-13650HX (13th Gen)|Memory: 16GB DDR5 RAM|Storage: 1TB SSD|Graphics: NVIDIA RTX 4070 8GB|Ports: 2x USB 3.2, 2x USB-C, HDMI|Battery: 90Wh with 280W charging|Operating System: Windows 11 Home|Connectivity: Wi-Fi 6E, Bluetooth 5.3|Dimensions: 354 x 264 x 22.6 mm|Weight: 2.5kg|Colors: Eclipse Grey', '', 'asus, rog, strix, g16, laptop, 1tb', 'hot', '', 'product', 4, 5400),
(72, 8, 0, 7, '2025-05-05 13:32:21', 'Asus TUF A15 (8 Gb) (512 Gb)', 'asus-tuf-a15', 95000, 88000, 5, 'The Asus TUF A15 offers a 15.6-inch FHD 144Hz display, AMD Ryzen 7 7735HS processor, and NVIDIA RTX 3050 graphics. With 8GB RAM and 512GB SSD, it’s a budget gaming laptop.', 'Display: 15.6&quot; FHD, 1920 x 1080 pixels, 144Hz|Processor: AMD Ryzen 7 7735HS|Memory: 8GB DDR5 RAM|Storage: 512GB SSD|Graphics: NVIDIA RTX 3050 4GB|Ports: 2x USB 3.2, 1x USB-C, HDMI|Battery: 48Wh with 150W charging|Operating System: Windows 11 Home|Connectivity: Wi-Fi 6, Bluetooth 5.2|Dimensions: 359.8 x 256 x 22.8 mm|Weight: 2.2kg|Colors: Mecha Grey', '', 'asus, tuf, a15, laptop, 512gb', 'new', '', 'product', 5, 4400),
(73, 8, 0, 7, '2025-05-01 13:57:46', 'Asus VivoBook 15 (8 Gb) (256 Gb)', 'asus-vivobook-15', 75000, 70000, 7, 'The Asus VivoBook 15 features a 15.6-inch FHD OLED display, Intel Core i3-1215U processor, and 256GB SSD. With 8GB RAM, it’s perfect for everyday use.', 'Display: 15.6&quot; FHD OLED, 1920 x 1080 pixels, 60Hz|Processor: Intel Core i3-1215U (12th Gen)|Memory: 8GB DDR4 RAM|Storage: 256GB SSD|Graphics: Intel UHD Graphics|Ports: 2x USB 3.2, 1x USB-C, HDMI|Battery: 42Wh with 65W charging|Operating System: Windows 11 Home|Connectivity: Wi-Fi 6, Bluetooth 5.2|Dimensions: 359.8 x 232.9 x 18.9 mm|Weight: 1.7kg|Colors: Quiet Blue', '', 'asus, vivobook, 15, laptop, 256gb', '', '', 'product', 6, 4200),
(74, 8, 0, 7, '2025-05-01 13:57:05', 'Asus ProArt P16 (32 Gb) (2 Tb)', 'asus-proart-p16', 200000, 185000, 2, 'The Asus ProArt P16 features a 16-inch OLED 4K display, AMD Ryzen 9 7945HX processor, and NVIDIA RTX 4080 graphics. With 32GB RAM and 2TB SSD, it’s for creative professionals.', 'Display: 16&quot; OLED 4K, 3840 x 2400 pixels, 120Hz|Processor: AMD Ryzen 9 7945HX|Memory: 32GB DDR5 RAM|Storage: 2TB SSD|Graphics: NVIDIA RTX 4080 12GB|Ports: 2x USB 3.2, 2x USB-C, HDMI|Battery: 96Wh with 240W charging|Operating System: Windows 11 Pro|Connectivity: Wi-Fi 6E, Bluetooth 5.3|Dimensions: 355 x 252 x 18.9 mm|Weight: 2.0kg|Colors: Black', '', 'asus, proart, p16, laptop, 2tb', 'new', '', 'product', 5, 9250),
(75, 9, 0, 5, '2025-05-01 13:55:56', 'Soundcore Motion+ Speaker', 'soundcore-motion-plus', 10000, 9000, 12, 'The Soundcore Motion+ Speaker delivers powerful sound with BassUp technology, 30W audio output, and 12 hours of playtime.', 'Audio: 30W with BassUp technology|Playtime: 12 hours|Charging: USB-C|Connectivity: Bluetooth 5.0|Features: IPX7 waterproof, stereo pairing|Weight: 1.05kg|Colors: Black, Blue', '', 'soundcore, motion, plus, speaker, accessories', '', '', 'product', 10, 900),
(76, 9, 0, 5, '2025-05-01 13:52:27', 'Soundcore Space A40 Earbuds', 'soundcore-space-a40', 9000, 8000, 15, 'The Soundcore Space A40 earbuds offer active noise cancellation, 10 hours of playtime per charge, and a compact design.', 'Drivers: 10mm dynamic drivers|ANC: Active Noise Cancellation|Playtime: 10 hours (50 hours with case)|Charging: USB-C, wireless charging|Connectivity: Bluetooth 5.2|Features: IPX4 water resistance, 6-mic call clarity|Weight: 4.9g per earbud|Colors: Black, White', '', 'soundcore, space, a40, earbuds, accessories', 'new', '', 'product', 10, 800),
(77, 9, 0, 5, '2025-05-01 13:51:38', 'Soundcore Q30 Headphones', 'soundcore-q30', 8000, 7000, 10, 'The Soundcore Q30 headphones feature hybrid active noise cancellation, 40 hours of playtime, and memory foam ear cups.', 'Drivers: 40mm dynamic drivers|ANC: Hybrid Active Noise Cancellation|Playtime: 40 hours|Charging: USB-C|Connectivity: Bluetooth 5.0|Features: 3 ANC modes, foldable design|Weight: 260g|Colors: Black, Pink', '', 'soundcore, q30, headphones, accessories', '', '', 'product', 10, 700),
(78, 9, 0, 5, '2025-05-01 13:51:32', 'Soundcore PowerConf C300 Webcam', 'soundcore-powerconf-c300', 12000, 11000, 8, 'The Soundcore PowerConf C300 webcam offers 1080p video at 60fps, AI-powered auto-framing, and dual microphones for clear calls.', 'Resolution: 1080p at 60fps|Features: AI auto-framing, adjustable FOV|Microphones: Dual stereo mics|Connectivity: USB-C|Compatibility: Windows, macOS|Weight: 200g|Colors: Black', '', 'soundcore, powerconf, c300, webcam, accessories', 'new', '', 'product', 15, 1650),
(79, 9, 0, 8, '2025-05-01 13:51:26', 'Logitech G Pro X Keyboard', 'logitech-g-pro-x', 14000, 12000, 6, 'The Logitech G Pro X is a tenkeyless mechanical keyboard with swappable switches, RGB lighting, and a compact design.', 'Switches: GX Blue Clicky (swappable)|Layout: Tenkeyless|Lighting: RGB per-key|Connectivity: USB|Features: Detachable cable, programmable keys|Weight: 980g|Colors: Black', '', 'logitech, g, pro, x, keyboard, accessories', 'hot', '', 'product', 15, 1800),
(80, 9, 0, 8, '2025-05-01 13:51:20', 'Logitech Brio 4K Webcam', 'logitech-brio-4k', 18000, 16000, 5, 'The Logitech Brio 4K webcam delivers 4K video at 30fps, HDR support, and 5x digital zoom for professional video calls.', 'Resolution: 4K at 30fps, 1080p at 60fps|Features: HDR, 5x digital zoom|Microphones: Dual omni-directional|Connectivity: USB-C|Compatibility: Windows, macOS|Weight: 340g|Colors: Black', '', 'logitech, brio, 4k, webcam, accessories', '', '', 'product', 15, 2400),
(81, 9, 0, 8, '2025-05-23 19:05:17', 'Logitech G502 Hero Mouse', 'logitech-g502-hero', 9000, 8000, 7, 'The Logitech G502 Hero mouse features a 25,600 DPI sensor, 11 programmable buttons, and adjustable weights for gaming precision.', 'Sensor: 25,600 DPI HERO|Buttons: 11 programmable|Connectivity: USB|Features: Adjustable weights, RGB lighting|Weight: 121g (adjustable)|Colors: Black', '', 'logitech, g502, hero, mouse, accessories', '', '', 'product', 10, 800),
(82, 9, 0, 8, '2025-05-05 14:02:31', 'Logitech K380 Keyboard', 'logitech-k380', 6000, 5900, 7, 'The Logitech K380 is a compact Bluetooth keyboard that connects to up to 3 devices, with a minimalist design.', 'Layout: Compact with round keys|Connectivity: Bluetooth|Features: Multi-device pairing (up to 3)|Battery: 2 years (2x AAA)|Compatibility: Windows, macOS, iOS, Android|Weight: 423g|Colors: White, Rose', '', 'logitech, k380, keyboard, accessories', 'new', '', 'product', 10, 590),
(83, 9, 0, 8, '2025-05-23 19:05:17', 'Premium Phone Case', 'premium-phone-case', 1000, NULL, 46, 'A high-quality protective case for your smartphone. Shock-absorbent material with precise cutouts for buttons and ports. Available for various phone models.', 'Material: Premium silicone and polycarbonate|Protection: Military-grade drop protection|Compatibility: Universal fit for most smartphones|Features: Raised edges for screen protection, precise cutouts|Colors: Black, Clear, Blue', '', 'phone case, protective case, smartphone accessory', '', '', 'product', 10, 100),
(84, 9, 0, 8, '2025-05-02 21:38:26', 'Tempered Glass Screen Protector', 'tempered-glass-screen-protector', 800, NULL, 99, 'Protect your phone screen from scratches and cracks with this premium tempered glass screen protector. 9H hardness, oleophobic coating, and easy installation.', 'Hardness: 9H tempered glass|Thickness: 0.33mm|Coating: Oleophobic anti-fingerprint|Installation: Easy bubble-free application|Compatibility: Universal fit for most smartphones', '', 'screen protector, tempered glass, phone protection', '', '', 'product', 10, 80),
(85, 9, 0, 8, '2025-05-04 09:59:05', 'Phone Grip Stand', 'phone-grip-stand', 500, NULL, 147, 'Comfortable grip and stand for your smartphone. Prevents drops, improves selfies, and provides a convenient stand for watching videos.', 'Material: Durable plastic and silicone|Adhesive: Reusable gel adhesive|Features: Collapsible stand, secure grip|Compatibility: Works with any smartphone|Colors: Black, White, Blue', '', 'phone grip, phone stand, pop socket', '', '', 'product', 10, 50),
(86, 9, 0, 8, '2025-05-02 20:26:47', 'Car Phone Mount', 'car-phone-mount', 1200, NULL, 74, 'Secure your phone while driving with this adjustable car phone mount. Compatible with all smartphones, strong suction cup, and 360-degree rotation.', 'Mounting: Dashboard or windshield suction cup|Adjustment: 360° rotation|Compatibility: Universal fit for phones 4-7 inches|Features: One-touch locking mechanism|Material: Durable plastic with silicone grip', '', 'car mount, phone holder, car accessory', '', '', 'product', 10, 120),
(87, 9, 0, 8, '2025-05-02 17:15:43', 'Wireless Charging Pad', 'wireless-charging-pad', 1500, NULL, 60, 'Charge your compatible devices without cables. Fast charging, sleek design, and LED indicator. Compatible with all Qi-enabled devices.', 'Charging: 15W fast wireless charging|Compatibility: All Qi-enabled devices|Features: LED indicator, anti-slip surface|Input: USB-C|Size: 100mm diameter|Colors: Black, White', '', 'wireless charger, charging pad, Qi charger', '', '', 'product', 10, 150),
(88, 9, 0, 5, '2025-05-02 17:15:43', 'Wireless Earbuds', 'wireless-earbuds', 3000, NULL, 40, 'High-quality wireless earbuds for music and calls. Bluetooth 5.0, touch controls, and up to 20 hours of battery life with the charging case.', 'Connectivity: Bluetooth 5.0|Battery: 5 hours (20 hours with case)|Charging: USB-C|Features: Touch controls, voice assistant|Water resistance: IPX5|Colors: Black, White', '', 'wireless earbuds, bluetooth earphones, true wireless', '', '', 'product', 10, 300),
(89, 9, 0, 5, '2025-05-02 21:45:44', 'Bluetooth Speaker', 'bluetooth-speaker', 2500, NULL, 34, 'Portable speaker with excellent sound quality. Waterproof, 12-hour battery life, and built-in microphone for calls. Perfect for outdoor activities.', 'Audio: 20W stereo sound|Battery: 12 hours playtime|Connectivity: Bluetooth 5.0|Features: IPX7 waterproof, built-in mic|Size: Compact and portable|Colors: Black, Blue', '', 'bluetooth speaker, portable speaker, wireless speaker', '', '', 'product', 10, 250),
(90, 9, 0, 5, '2025-05-02 21:45:44', 'Wired Headphones', 'wired-headphones', 1800, NULL, 44, 'Comfortable over-ear headphones with excellent sound quality. Padded ear cups, adjustable headband, and in-line microphone and controls.', 'Design: Over-ear with memory foam padding|Drivers: 40mm dynamic drivers|Cable: 1.5m with 3.5mm jack|Features: In-line mic and controls|Foldable: Yes, for easy storage|Colors: Black, Silver', '', 'wired headphones, over-ear headphones, stereo headphones', '', '', 'product', 10, 180),
(91, 9, 0, 8, '2025-05-23 19:26:01', 'Laptop Sleeve', 'laptop-sleeve', 1200, NULL, 55, 'Protective sleeve for laptops up to 15.6&quot;. Water-resistant neoprene material, soft interior lining, and additional pocket for accessories.', 'Size: Fits laptops up to 15.6&quot;|Material: Water-resistant neoprene|Interior: Soft plush lining|Pockets: Additional front pocket for accessories|Closure: Zipper|Colors: Black, Grey, Blue', '', 'laptop sleeve, laptop case, notebook protection', '', '', 'product', 10, 120),
(92, 9, 0, 8, '2025-05-02 17:15:43', 'Wireless Mouse', 'wireless-mouse', 1500, NULL, 70, 'Ergonomic wireless mouse for comfortable use. 1600 DPI optical sensor, silent clicks, and long battery life. Compatible with Windows, Mac, and Linux.', 'Sensor: 1600 DPI optical sensor|Buttons: 3 buttons with silent clicks|Connectivity: 2.4GHz wireless with USB receiver|Battery: Up to 12 months battery life|Compatibility: Windows, macOS, Linux|Colors: Black, Grey', '', 'wireless mouse, computer mouse, ergonomic mouse', '', '', 'product', 10, 150),
(93, 9, 0, 8, '2025-05-02 17:15:43', 'USB Hub', 'usb-hub', 1000, NULL, 80, 'Expand your connectivity options with multiple ports. 4 USB 3.0 ports, slim design, and plug-and-play functionality. Compatible with laptops and desktops.', 'Ports: 4x USB 3.0 ports|Speed: Up to 5Gbps data transfer|Power: Bus-powered, no external power needed|Compatibility: Windows, macOS, Linux|Size: Compact and portable|Colors: Black, Silver', '', 'USB hub, port expander, USB adapter', '', '', 'product', 10, 100),
(94, 9, 0, 8, '2025-05-23 19:25:08', 'Laptop Cooling Pad', 'laptop-cooling-pad', 2000, NULL, 40, 'Keep your laptop cool during intensive tasks. Five quiet fans, adjustable height settings, and blue LED lighting. Compatible with laptops up to 17&quot;.', 'Fans: 5 quiet fans with blue LED lighting|Size: Fits laptops up to 17&quot;|Adjustment: 2 height settings|Power: USB-powered|Features: Metal mesh surface for optimal airflow|Colors: Black', '', 'cooling pad, laptop cooler, notebook fan', '', '', 'product', 10, 200),
(95, 9, 0, 8, '2025-05-23 19:24:48', 'Power Bank', 'power-bank', 2000, NULL, 64, '10,000mAh portable charger for your devices. Fast charging, dual USB outputs, and LED power indicator. Charge your smartphone up to 3 times on a single charge.', 'Capacity: 10,000mAh|Outputs: 2x USB-A (5V/2.4A)|Input: USB-C and Micro-USB|Features: LED power indicator, fast charging|Size: Pocket-sized and lightweight|Colors: Black, White', '', 'power bank, portable charger, battery pack', '', '', 'product', 10, 200),
(96, 9, 9, 8, '2025-05-02 21:42:30', 'USB-C Cable Pack', 'usb-c-cable-pack', 1200, 0, 90, 'Set of 3 durable USB-C cables of different lengths (0.5m, 1m, 2m). Nylon braided for durability, fast charging and data transfer. Compatible with all USB-C devices.', 'Contents: 3 cables (0.5m, 1m, 2m)|Material: Nylon braided for durability|Speed: USB 3.1 (10Gbps data transfer)|Power: Fast charging support (up to 100W)|Compatibility: All USB-C devices|Colors: Black', '', 'USB-C cable, charging cable, type-c cable', '', '', 'product', 10, 120),
(97, 9, 9, 8, '2025-05-02 20:38:38', 'Cable Organizer', 'cable-organizer', 800, 0, 100, 'Keep your cables neat and tangle-free. Silicone material, multiple slots for different cables, and adhesive backing for secure placement.', 'Material: Durable silicone|Slots: 5 cable slots of different sizes|Mounting: Adhesive backing for secure placement|Features: Keeps cables organized and tangle-free|Size: Compact desk design|Colors: Black, Grey, White', '', 'cable organizer, cable management, desk organizer', '', '', 'product', 10, 80),
(98, 9, 0, 8, '2025-05-05 13:07:46', 'Smartphone Tripod', 'smartphone-tripod', 1500, 0, 59, 'Flexible tripod for taking photos and videos. Bendable legs for secure placement on any surface, Bluetooth remote control included, and universal phone mount.', 'Height: 12 inches (30cm)|Legs: Flexible and bendable for any surface|Mount: Universal smartphone mount (up to 3.5 inches wide)|Remote: Bluetooth remote control included|Features: 360° rotation, lightweight design|Colors: Black', '', 'phone tripod, camera stand, selfie stick', '', '', 'product', 10, 150),
(99, 9, 0, 9, '2025-05-23 19:23:57', 'Kensington Extra Large Monitor Stand for Desks', 'kensington-extra-large-monitor-stand-for-desks-', 5400, NULL, 10, 'The ergonomic design promotes healthy posture and optimizes the comfort of the eyes, neck and shoulders by raising the monitor to an optimal viewing height\r\nThe extra large platform supports larger screens (up to 32 inches) and provides ample space (up to 508 mm) to store a full-size keyboard, accessories and/or papers underneath for a tidy desk\r\nSolid steel base provides a sleek, durable stand for monitors, iMacs and all-in-one PCs weighing up to 20kg\r\nSimple setup provides a one-step process, from box to use, with no additional tools required', 'Colour	Black,\r\nMaterial Alloy Steel,\r\nBrand Kensington,\r\nItem Weight  2.03 kg,\r\nItem Dimensions	60 x 12 x 26 centimetres,\r\nFinish Type Painted,\r\nItem Shape rectangular prism,\r\nBase Type	Alloy Steel,\r\nIs Assembly Required?	No,\r\nLoad Capacity	20 kg,', '', 'stand,pc stand', 'new', '', 'product', 0, 0),
(105, 4, 0, 2, '2025-05-23 16:56:19', 'Samsung Galaxy S25 Edge (12 Gb) (512 Gb)', 'samsung-galaxy-s25-edge-12-gb-512-gb', 123000, 120000, 2, 'Samsung dévoile lors de l&#039;évènement Galaxy Unpacked 2025 son Galaxy S25 Edge, un smartphone ultra haut de gamme au design en titane ultra-fin (5,8 mm), se positionnant comme une alternative élégante et performante dans la gamme Galaxy S25, ainsi qu&#039;un Galaxy AI revu et boosté pour plus de fonctionnalités gérées par l&#039;IA. Son écran QHD+ AMOLED 120 Hz de 6,7 pouces affiche une luminosité de 2600 cd/m² et propulsé par un SoC Qualcomm Snapdragon 8 Elite, associé à 12 Go de RAM et jusqu&#039;à 512 Go de stockage. Côté photo on retrouve deux capteurs à l&#039;arrière : un grand-angle de 200 mégapixels avec stabilisation optique et une promesse d&#039;une qualité de zoom optique x2, ainsi qu&#039;un ultra grand-angle de 12 mégapixels. Sa batterie de 3900 mAh compatible charge rapide 25 W en filaire et 15 W sans fil promet 24 heures d&#039;autonomie en lecture vidéo.', 'Display: 6.1&amp;quot; FHD+ Dynamic AMOLED 2X, 2340 x 1080 pixels, 120Hz refresh rate|Processor: Qualcomm Snapdragon 8 Gen 2 (4nm)|Memory: 8GB RAM|Storage: 256GB (non-expandable)|Rear Camera: Triple Camera: 50MP Wide (f/1.8), 12MP Ultra-Wide (f/2.2), 10MP Telephoto (f/2.4) with 3x optical zoom|Front Camera: 12MP (f/2.2)|Battery: 3900mAh with 25W wired charging, 15W wireless charging|Operating System: Android 13 with One UI 5.1|Connectivity: 5G, Wi-Fi 6E, Bluetooth 5.3, NFC, USB Type-C|Dimensions: 146.3 x 70.9 x 7.6 mm|Weight: 168g|Colors: Phantom Black, Cream, Green, Lavender|Water Resistance: IP68 (water and dust resistant)\r\nrelated : 18,19', '', 'S25 , Edge , S25 Edge', 'new', '', 'product', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `p_cat_id` int(10) NOT NULL,
  `p_cat_title` text NOT NULL,
  `p_cat_top` text NOT NULL,
  `p_cat_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`p_cat_id`, `p_cat_title`, `p_cat_top`, `p_cat_image`) VALUES
(4, 'Phone', 'no', 'phone.png'),
(8, 'Laptops', 'yes', 'laptop.png'),
(9, 'Accessories', 'no', 'accessories.png');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `image_path` text NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`, `is_primary`) VALUES
(1, 18, '6804dbee255ea.jpg', 0),
(2, 19, '6804dbcae13bb.jpg', 0),
(3, 60, '6804daed51dbe.jpg', 0),
(4, 63, '680a369b12d77.jpg', 0),
(5, 64, '680a368f59d59.jpg', 0),
(6, 65, '680a3683c6c9a.jpg', 0),
(7, 66, '680a3672dac55.jpg', 0),
(8, 67, '680a3664bc1eb.jpg', 0),
(9, 68, '680a365131e68.jpg', 0),
(10, 69, '680a363eab88a.jpg', 0),
(11, 70, '680a35e40feba.jpg', 0),
(12, 71, '680a3308caee0.jpg', 0),
(13, 72, '680a32f5dc308.jpg', 0),
(14, 73, '680a348bb223d.jpg', 0),
(15, 74, '680a348012baa.jpg', 0),
(16, 75, '680a346f87154.jpg', 0),
(17, 76, '680a346563ca7.jpg', 0),
(18, 77, '680a345c2904c.jpg', 0),
(19, 78, '680a34521f216.jpg', 0),
(20, 79, '680a344673c82.png', 0),
(21, 80, '680a343b34002.jpg', 0),
(22, 81, '680a341134292.jpg', 0),
(23, 82, '680a343073a3c.png', 0),
(24, 83, 'phone_case_1.jpg', 0),
(25, 84, 'screen_protector_1.jpg', 0),
(26, 85, 'phone_grip_1.jpg', 0),
(27, 86, 'car_mount_1.jpg', 0),
(28, 87, 'wireless_charger_1.jpg', 0),
(29, 88, 'earbuds_1.jpg', 0),
(30, 89, 'speaker_1.jpg', 0),
(31, 90, 'headphones_1.jpg', 0),
(32, 91, 'laptop_sleeve_1.jpg', 0),
(33, 92, 'mouse_1.jpg', 0),
(34, 93, 'usb_hub_1.jpg', 0),
(35, 94, 'cooling_pad_1.jpg', 0),
(36, 95, 'power_bank_1.jpg', 0),
(37, 96, '68153c46eba16.png', 0),
(38, 97, '68152d4e0ec1e.png', 0),
(39, 98, '68152d1a34d41.png', 0),
(40, 99, '6818bd0bec451.png', 0),
(64, 18, 'realme_gt6_img2.jpg', 0),
(65, 19, 'xiaomi_note13pro_img2.jpg', 0),
(66, 60, '6804daed55d3e.jpg', 0),
(67, 63, 'iphone14_img2.jpg', 0),
(68, 64, 'galaxya54_img2.jpg', 0),
(69, 65, 'realme9pro_img2.jpg', 0),
(70, 66, 'xiaomi14_img2.jpg', 0),
(71, 67, 'dellinspiron15_img2.jpg', 0),
(72, 69, 'dellg15gaming_img2.jpg', 0),
(73, 70, 'dellprecision5570_img2.jpg', 0),
(74, 71, 'asusrogstrixg16_img2.jpg', 0),
(75, 72, 'asustufa15_img2.jpg', 0),
(76, 74, 'asusproartp16_img2.jpg', 0),
(77, 75, 'soundcoremotionplus_img2.jpg', 0),
(78, 77, 'soundcoreq30_img2.jpg', 0),
(79, 79, 'logitechgprox_img2.jpg', 0),
(80, 81, 'logitechg502hero_img2.jpg', 0),
(81, 83, 'phone_case_2.jpg', 0),
(82, 84, 'screen_protector_2.jpg', 0),
(83, 85, 'phone_grip_2.jpg', 0),
(84, 86, 'car_mount_2.jpg', 0),
(85, 87, 'wireless_charger_2.jpg', 0),
(86, 88, 'earbuds_2.jpg', 0),
(87, 89, 'speaker_2.jpg', 0),
(88, 90, 'headphones_2.jpg', 0),
(89, 91, 'laptop_sleeve_2.jpg', 0),
(90, 92, 'mouse_2.jpg', 0),
(91, 93, 'usb_hub_2.jpg', 0),
(92, 94, 'cooling_pad_2.jpg', 0),
(93, 95, 'power_bank_2.jpg', 0),
(94, 96, 'usb_c_cable_2.jpg', 0),
(95, 97, 'cable_organizer_2.jpg', 0),
(96, 98, 'tripod_2.jpg', 0),
(97, 99, '6818bd0bedded.png', 0),
(127, 18, 'realme_gt6_img3.jpg', 0),
(128, 19, 'xiaomi_note13pro_img3.jpg', 0),
(129, 60, '6804daed56841.jpg', 0),
(130, 63, 'iphone14_img3.jpg', 0),
(131, 65, 'realme9pro_img3.jpg', 0),
(132, 66, 'xiaomi14_img3.jpg', 0),
(133, 69, 'dellg15gaming_img3.jpg', 0),
(134, 72, 'asustufa15_img3.jpg', 0),
(135, 83, 'phone_case_3.jpg', 0),
(136, 84, 'screen_protector_3.jpg', 0),
(137, 85, 'phone_grip_3.jpg', 0),
(138, 86, 'car_mount_3.jpg', 0),
(139, 87, 'wireless_charger_3.jpg', 0),
(140, 88, 'earbuds_3.jpg', 0),
(141, 89, 'speaker_3.jpg', 0),
(142, 90, 'headphones_3.jpg', 0),
(143, 91, 'laptop_sleeve_3.jpg', 0),
(144, 92, 'mouse_3.jpg', 0),
(145, 93, 'usb_hub_3.jpg', 0),
(146, 94, 'cooling_pad_3.jpg', 0),
(147, 95, 'power_bank_3.jpg', 0),
(148, 96, 'usb_c_cable_3.jpg', 0),
(149, 97, 'cable_organizer_3.jpg', 0),
(150, 98, 'tripod_3.jpg', 0),
(151, 99, '6818bd0bee236.png', 0),
(158, 18, 'realme_gt6_img4.jpg', 0),
(159, 19, 'xiaomi_note13pro_img4.jpg', 0),
(160, 65, 'realme9pro_img4.jpg', 0),
(169, 105, '6830a2e102671.png', 1),
(170, 105, '6830a2e1041c5.png', 0),
(171, 105, '6830a2e104934.png', 0),
(172, 99, '6830cb4d9cd31.jpg', 1),
(173, 95, '6830cb809382a.jpg', 1),
(174, 94, '6830cb942f4e0.jpg', 1),
(175, 93, '6830cbb03a2f7.jpg', 1),
(176, 92, '6830cbbdebdf7.jpg', 1),
(177, 91, '6830cbc91bc1d.jpg', 1),
(178, 90, '6830cbd53b846.jpg', 1),
(179, 89, '6830cbee9d05b.jpg', 1),
(180, 88, '6830cbfb5e84c.png', 1),
(181, 87, '6830cc0c4648c.jpg', 1),
(182, 86, '6830cc833a4fa.jpg', 1),
(183, 85, '6830cc913965a.jpg', 1),
(184, 84, '6830cc9dc1314.jpg', 1),
(185, 83, '6830ccaa5f7a3.png', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_customer` (`customer_id`),
  ADD KEY `fk_cart_product` (`p_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_orders`
--
ALTER TABLE `customer_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_customer` (`customer_id`),
  ADD KEY `fk_orders_discount` (`discount_id`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `fidelity_gifts`
--
ALTER TABLE `fidelity_gifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fidelity_redemptions`
--
ALTER TABLE `fidelity_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `gift_id` (`gift_id`);

--
-- Indexes for table `manufacturers`
--
ALTER TABLE `manufacturers`
  ADD PRIMARY KEY (`manufacturer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payments_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_products_p_cat` (`p_cat_id`),
  ADD KEY `fk_products_manufacturer` (`manufacturer_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`p_cat_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `customer_orders`
--
ALTER TABLE `customer_orders`
  MODIFY `order_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `fidelity_gifts`
--
ALTER TABLE `fidelity_gifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `fidelity_redemptions`
--
ALTER TABLE `fidelity_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `manufacturers`
--
ALTER TABLE `manufacturers`
  MODIFY `manufacturer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `p_cat_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`p_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_orders`
--
ALTER TABLE `customer_orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_discount` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `fidelity_redemptions`
--
ALTER TABLE `fidelity_redemptions`
  ADD CONSTRAINT `fidelity_redemptions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fidelity_redemptions_ibfk_2` FOREIGN KEY (`gift_id`) REFERENCES `fidelity_gifts` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `customer_orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitems_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `customer_orders` (`order_id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`manufacturer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_p_cat` FOREIGN KEY (`p_cat_id`) REFERENCES `product_categories` (`p_cat_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_productimages_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
