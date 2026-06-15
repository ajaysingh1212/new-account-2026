-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2026 at 10:06 AM
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
-- Database: `new_account`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `company_id`, `action`, `model`, `model_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `description`, `created_at`) VALUES
(1, 21, NULL, 'created', 'App\\Models\\Company', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Company created: Eemotrack India', '2026-05-22 06:58:54'),
(2, 21, NULL, 'created', 'App\\Models\\Company', 2, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Company created: Maruti Suzuki Venture', '2026-05-22 07:02:48'),
(3, 22, 1, 'created', 'App\\Models\\Role', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Role created: Accountent', '2026-05-22 07:42:18'),
(4, 22, 1, 'created', 'App\\Models\\User', 24, NULL, '{\"name\":\"Simaran\",\"email\":\"simran@eemot.com\",\"user_type\":\"user\",\"phone\":null,\"current_company_id\":\"1\",\"is_active\":true,\"updated_at\":\"2026-05-22T07:42:55.000000Z\",\"created_at\":\"2026-05-22T07:42:55.000000Z\",\"id\":24}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User created: Simaran', '2026-05-22 07:42:55'),
(5, 22, 1, 'created', 'App\\Models\\User', 25, NULL, '{\"name\":\"Sanket Kumar\",\"email\":\"sanket@eemot.com\",\"user_type\":\"user\",\"phone\":null,\"current_company_id\":\"1\",\"is_active\":true,\"updated_at\":\"2026-05-22T07:43:22.000000Z\",\"created_at\":\"2026-05-22T07:43:22.000000Z\",\"id\":25}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User created: Sanket Kumar', '2026-05-22 07:43:22'),
(6, 22, 1, 'updated', 'App\\Models\\Role', 3, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Role updated: Accountent', '2026-05-22 07:45:45'),
(7, 25, 1, 'created', 'App\\Models\\Party', 1, NULL, '{\"party_code\":\"PTY-00001\",\"party_type\":\"both\",\"display_name\":\"Ajay Mehta\",\"legal_name\":\"Ajay Traders\",\"contact_person\":\"Ajay Kumar\",\"email\":\"ajayfilliptect@gmail.com\",\"phone\":\"8863897163\",\"alternate_phone\":\"8294169540\",\"whatsapp_number\":\"8294169540\",\"gstin\":\"121212121212121\",\"pan_number\":\"KIIPK7404N\",\"tan_number\":null,\"cin_number\":null,\"tax_type\":\"registered\",\"place_of_supply\":\"india\",\"billing_address\":\"B.C. Road, Patna, Bihar, 800001\",\"shipping_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"city\":\"Patna HQ\",\"state\":\"Bihar\",\"pincode\":\"800001\",\"country\":\"India\",\"opening_balance\":\"0.00\",\"opening_balance_type\":\"payable\",\"opening_balance_date\":\"2026-05-22T00:00:00.000000Z\",\"credit_limit\":null,\"credit_days\":null,\"payment_terms\":null,\"bank_name\":\"HDFC Bank\",\"account_holder_name\":\"Ajay Mehta\",\"account_number\":\"50100713877621\",\"ifsc_code\":\"HDFC0006215\",\"branch_name\":\"EEMOTRACK INDIA\",\"upi_id\":\"50100713877621@ybl\",\"status\":\"active\",\"notes\":null,\"company_id\":1,\"current_balance\":\"0.00\",\"created_by\":25,\"updated_by\":25,\"updated_at\":\"2026-05-22T07:46:49.000000Z\",\"created_at\":\"2026-05-22T07:46:49.000000Z\",\"id\":1}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Party created: Ajay Mehta', '2026-05-22 07:46:49'),
(8, 25, 1, 'created', 'App\\Models\\BankAccount', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Bank account created: HDFC', '2026-05-22 09:06:00'),
(9, 22, 1, 'updated', 'App\\Models\\User', 22, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Profile updated', '2026-05-22 11:52:25'),
(10, 25, 1, 'updated', 'App\\Models\\ProductionBatch', 1, '{\"id\":1,\"company_id\":1,\"finished_item_id\":4,\"batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22T00:00:00.000000Z\",\"quantity\":\"5.000\",\"raw_material_cost\":\"2000.00\",\"cost_per_unit\":\"400.00\",\"notes\":null,\"units_data\":[{\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":null,\"notes\":null},{\"serial_no\":null,\"batch_no\":\"MAY2026-D2CQ\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":null,\"notes\":null},{\"serial_no\":null,\"batch_no\":\"MAY2026-YJ5U\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":null,\"notes\":null},{\"serial_no\":null,\"batch_no\":\"MAY2026-ID9I\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":null,\"notes\":null},{\"serial_no\":null,\"batch_no\":\"MAY2026-YYDR\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":null,\"notes\":null}],\"created_at\":\"2026-05-22T09:03:46.000000Z\",\"updated_at\":\"2026-05-22T09:03:46.000000Z\",\"created_by\":25,\"finished_item\":{\"id\":4,\"company_id\":1,\"product_type_id\":2,\"item_type\":\"product\",\"item_code\":\"ITM-00003\",\"hsn_code\":\"HSN000003\",\"barcode\":\"ITM-00003\",\"qr_code\":\"QR-ITM-00003\",\"name\":\"GPS 3x PRO\",\"sku\":\"ET-UQHU9821\",\"unit\":\"PCS\",\"brand\":null,\"model\":null,\"size\":null,\"color\":null,\"description\":null,\"purchase_price\":\"600.00\",\"purchase_tax_inclusive\":true,\"purchase_gst_percent\":\"10.00\",\"sale_price\":\"2600.00\",\"sale_tax_inclusive\":true,\"sale_gst_percent\":\"10.00\",\"opening_stock\":\"0.000\",\"current_stock\":\"20.000\",\"stock_value\":\"11800.00\",\"low_stock_qty\":\"10.000\",\"track_stock\":true,\"is_bom_enabled\":false,\"status\":\"active\",\"created_by\":25,\"created_at\":\"2026-05-22T08:55:04.000000Z\",\"updated_at\":\"2026-05-25T08:37:25.000000Z\",\"deleted_at\":null,\"bom_materials\":[{\"id\":1,\"company_id\":1,\"finished_item_id\":4,\"raw_item_id\":3,\"qty_per_unit\":\"4.000\",\"created_at\":\"2026-05-22T08:55:04.000000Z\",\"updated_at\":\"2026-05-22T08:55:04.000000Z\",\"raw_item\":{\"id\":3,\"company_id\":1,\"product_type_id\":1,\"item_type\":\"product\",\"item_code\":\"ITM-00002\",\"hsn_code\":\"HSN000002\",\"barcode\":\"ITM-00002\",\"qr_code\":\"QR-ITM-00002\",\"name\":\"gps-box\",\"sku\":\"ET-UCLV1470\",\"unit\":\"PCS\",\"brand\":null,\"model\":null,\"size\":null,\"color\":\"White\",\"description\":null,\"purchase_price\":\"100.00\",\"purchase_tax_inclusive\":true,\"purchase_gst_percent\":\"10.00\",\"sale_price\":\"150.00\",\"sale_tax_inclusive\":true,\"sale_gst_percent\":\"10.00\",\"opening_stock\":\"0.000\",\"current_stock\":\"95.998\",\"stock_value\":\"11489.78\",\"low_stock_qty\":\"100.000\",\"track_stock\":true,\"is_bom_enabled\":false,\"status\":\"active\",\"created_by\":25,\"created_at\":\"2026-05-22T08:23:20.000000Z\",\"updated_at\":\"2026-05-25T08:35:42.000000Z\",\"deleted_at\":null}}]}}', '{\"id\":1,\"company_id\":1,\"finished_item_id\":4,\"batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22T00:00:00.000000Z\",\"quantity\":\"5.000\",\"raw_material_cost\":\"2000.00\",\"cost_per_unit\":\"400.00\",\"notes\":null,\"units_data\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-002\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-D2CQ\",\"vts_sim\":\"43534543546546\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-003\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-YJ5U\",\"vts_sim\":\"543647567\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-004\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-ID9I\",\"vts_sim\":\"547567575\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-005\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-YYDR\",\"vts_sim\":\"435234214\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null}],\"created_at\":\"2026-05-22T09:03:46.000000Z\",\"updated_at\":\"2026-05-28T08:18:17.000000Z\",\"created_by\":25}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Production batch PB-00001 updated by Sanket Kumar (Accountent) for company Eemotrack India.', '2026-05-28 08:18:17'),
(11, 25, 1, 'updated', 'App\\Models\\SalesInvoice', 6, '{\"company_id\":1,\"party_id\":null,\"cost_center_id\":null,\"sub_cost_center_id\":null,\"sale_type\":\"credit\",\"invoice_no\":\"00000004\",\"billing_date\":\"2026-05-28T00:00:00.000000Z\",\"reference_no\":null,\"phone\":null,\"billing_address\":null,\"shipping_address\":null,\"subtotal\":\"2600.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"260.00\",\"grand_total\":\"2860.00\",\"notes\":null,\"terms\":null,\"attachment\":null,\"status\":\"posted\",\"inter_company_transfer\":true,\"inter_company_target_company_ids\":[2],\"created_by\":25,\"deleted_at\":null,\"items\":[{\"id\":5,\"sales_invoice_id\":6,\"item_id\":4,\"description\":null,\"quantity\":\"1.000\",\"unit\":\"PCS\",\"unit_price\":\"2600.00\",\"discount_type\":\"percent\",\"discount_value\":\"0.00\",\"discount_amount\":\"0.00\",\"tax_percent\":\"10.00\",\"tax_amount\":\"260.00\",\"line_total\":\"2860.00\",\"selected_units\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}],\"created_at\":\"2026-05-28T08:18:50.000000Z\",\"updated_at\":\"2026-05-28T08:18:50.000000Z\"}]}', '{\"id\":6,\"company_id\":1,\"party_id\":1,\"cost_center_id\":null,\"sub_cost_center_id\":null,\"sale_type\":\"credit\",\"invoice_no\":\"00000004\",\"billing_date\":\"2026-05-28T00:00:00.000000Z\",\"reference_no\":null,\"phone\":null,\"billing_address\":null,\"shipping_address\":null,\"subtotal\":\"2600.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"260.00\",\"grand_total\":\"2860.00\",\"notes\":null,\"terms\":null,\"attachment\":null,\"status\":\"posted\",\"inter_company_transfer\":true,\"inter_company_target_company_ids\":[2],\"created_by\":25,\"created_at\":\"2026-05-28T08:18:50.000000Z\",\"updated_at\":\"2026-05-28T08:19:11.000000Z\",\"deleted_at\":null,\"items\":[{\"id\":6,\"sales_invoice_id\":6,\"item_id\":4,\"description\":null,\"quantity\":\"1.000\",\"unit\":\"PCS\",\"unit_price\":\"2600.00\",\"discount_type\":\"percent\",\"discount_value\":\"0.00\",\"discount_amount\":\"0.00\",\"tax_percent\":\"10.00\",\"tax_amount\":\"260.00\",\"line_total\":\"2860.00\",\"selected_units\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}],\"created_at\":\"2026-05-28T08:19:11.000000Z\",\"updated_at\":\"2026-05-28T08:19:11.000000Z\"}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Sales invoice 00000004 updated by Sanket Kumar (Accountent) for company Eemotrack India.', '2026-05-28 08:19:12'),
(12, 23, 2, 'created', 'App\\Models\\Role', 4, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Role created: Accountent', '2026-05-28 10:13:25'),
(13, 23, 2, 'created', 'App\\Models\\User', 26, NULL, '{\"name\":\"Sanket Kumar\",\"email\":\"sanket@msv.com\",\"user_type\":\"user\",\"phone\":null,\"current_company_id\":\"2\",\"is_active\":true,\"updated_at\":\"2026-05-28T10:14:02.000000Z\",\"created_at\":\"2026-05-28T10:14:02.000000Z\",\"id\":26}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User created: Sanket Kumar', '2026-05-28 10:14:02'),
(14, 25, 2, 'updated', 'App\\Models\\PurchaseBill', 4, '{\"company_id\":2,\"party_id\":2,\"cost_center_id\":null,\"sub_cost_center_id\":null,\"purchase_type\":\"credit\",\"invoice_no\":\"IC-00000004\",\"supplier_bill_no\":\"00000004\",\"billing_date\":\"2026-05-28T00:00:00.000000Z\",\"purchase_bill_date\":\"2026-05-28T00:00:00.000000Z\",\"reference_no\":\"Auto purchase from sale 00000004\",\"docket_no\":null,\"e_bill_no\":null,\"phone\":null,\"billing_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\\r\\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"shipping_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\\r\\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"subtotal\":\"2600.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"260.00\",\"grand_total\":\"2860.00\",\"notes\":\"Inter-company purchase auto-created from Eemotrack India sale 00000004.\",\"terms\":null,\"attachment\":null,\"status\":\"posted\",\"source_sales_invoice_id\":6,\"inter_company_source_company_id\":1,\"created_by\":25,\"deleted_at\":null,\"items\":[{\"id\":4,\"purchase_bill_id\":4,\"item_id\":5,\"description\":null,\"quantity\":\"1.000\",\"unit\":\"PCS\",\"unit_price\":\"2600.00\",\"discount_type\":\"percent\",\"discount_value\":\"0.00\",\"discount_amount\":\"0.00\",\"tax_percent\":\"10.00\",\"tax_amount\":\"260.00\",\"line_total\":\"2860.00\",\"selected_units\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}],\"created_at\":\"2026-05-28T08:18:50.000000Z\",\"updated_at\":\"2026-05-28T08:18:50.000000Z\",\"item\":{\"id\":5,\"company_id\":2,\"product_type_id\":3,\"item_type\":\"product\",\"item_code\":\"ITM-00003\",\"hsn_code\":\"HSN000003\",\"barcode\":\"ITM-00003\",\"qr_code\":\"QR-ITM-00003\",\"name\":\"GPS 3x PRO\",\"sku\":\"ET-UQHU9821\",\"unit\":\"PCS\",\"brand\":null,\"model\":null,\"size\":null,\"color\":null,\"description\":null,\"purchase_price\":\"600.00\",\"purchase_tax_inclusive\":true,\"purchase_gst_percent\":\"10.00\",\"sale_price\":\"2600.00\",\"sale_tax_inclusive\":true,\"sale_gst_percent\":\"10.00\",\"opening_stock\":\"0.000\",\"current_stock\":\"2.000\",\"stock_value\":\"5460.00\",\"low_stock_qty\":\"10.000\",\"track_stock\":true,\"is_bom_enabled\":false,\"status\":\"active\",\"created_by\":23,\"created_at\":\"2026-05-22T11:01:41.000000Z\",\"updated_at\":\"2026-05-28T08:18:50.000000Z\",\"deleted_at\":null}}],\"party\":{\"id\":2,\"company_id\":2,\"party_code\":\"CO-1\",\"party_type\":\"supplier\",\"display_name\":\"Eemotrack India\",\"legal_name\":\"Eemotrack India\",\"contact_person\":null,\"email\":\"info@eemotrack.com\",\"phone\":null,\"alternate_phone\":null,\"whatsapp_number\":null,\"gstin\":\"10AQFPK9218D1ZA\",\"pan_number\":\"KIIPK7404N\",\"tan_number\":null,\"cin_number\":null,\"tax_type\":\"registered\",\"place_of_supply\":null,\"billing_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\\r\\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"shipping_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\\r\\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"city\":null,\"state\":null,\"pincode\":null,\"country\":\"India\",\"opening_balance\":\"0.00\",\"opening_balance_type\":\"payable\",\"opening_balance_date\":null,\"current_balance\":\"2860.00\",\"credit_limit\":null,\"credit_days\":null,\"payment_terms\":null,\"bank_name\":null,\"account_holder_name\":null,\"account_number\":null,\"ifsc_code\":null,\"branch_name\":null,\"upi_id\":null,\"status\":\"active\",\"notes\":null,\"created_by\":25,\"updated_by\":null,\"created_at\":\"2026-05-28T08:18:50.000000Z\",\"updated_at\":\"2026-05-28T08:18:50.000000Z\",\"deleted_at\":null}}', '{\"id\":4,\"company_id\":2,\"party_id\":2,\"cost_center_id\":null,\"sub_cost_center_id\":null,\"purchase_type\":\"credit\",\"invoice_no\":\"IC-00000004\",\"supplier_bill_no\":\"00000004\",\"billing_date\":\"2026-05-28T00:00:00.000000Z\",\"purchase_bill_date\":\"2026-05-28T00:00:00.000000Z\",\"reference_no\":\"Auto purchase from sale 00000004\",\"docket_no\":null,\"e_bill_no\":null,\"phone\":null,\"billing_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\\r\\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"shipping_address\":\"Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\\r\\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\",\"subtotal\":\"2600.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"260.00\",\"grand_total\":\"2860.00\",\"notes\":\"Inter-company purchase auto-created from Eemotrack India sale 00000004.\",\"terms\":null,\"attachment\":null,\"status\":\"posted\",\"source_sales_invoice_id\":6,\"inter_company_source_company_id\":1,\"created_by\":25,\"created_at\":\"2026-05-28T08:18:50.000000Z\",\"updated_at\":\"2026-05-28T08:18:50.000000Z\",\"deleted_at\":null,\"items\":[{\"id\":5,\"purchase_bill_id\":4,\"item_id\":5,\"description\":null,\"quantity\":\"1.000\",\"unit\":\"PCS\",\"unit_price\":\"2600.00\",\"discount_type\":\"percent\",\"discount_value\":\"0.00\",\"discount_amount\":\"0.00\",\"tax_percent\":\"10.00\",\"tax_amount\":\"260.00\",\"line_total\":\"2860.00\",\"selected_units\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}],\"created_at\":\"2026-05-28T10:14:55.000000Z\",\"updated_at\":\"2026-05-28T10:14:55.000000Z\"}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Auto inter-company purchase updated from source sale edit by Sanket Kumar.', '2026-05-28 10:14:55'),
(15, 25, 1, 'updated', 'App\\Models\\SalesInvoice', 6, '{\"company_id\":1,\"party_id\":1,\"cost_center_id\":null,\"sub_cost_center_id\":null,\"sale_type\":\"credit\",\"invoice_no\":\"00000004\",\"billing_date\":\"2026-05-28T00:00:00.000000Z\",\"reference_no\":null,\"phone\":null,\"billing_address\":null,\"shipping_address\":null,\"subtotal\":\"2600.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"260.00\",\"grand_total\":\"2860.00\",\"notes\":null,\"terms\":null,\"attachment\":null,\"status\":\"posted\",\"inter_company_transfer\":true,\"inter_company_target_company_ids\":[2],\"created_by\":25,\"deleted_at\":null,\"items\":[{\"id\":6,\"sales_invoice_id\":6,\"item_id\":4,\"description\":null,\"quantity\":\"1.000\",\"unit\":\"PCS\",\"unit_price\":\"2600.00\",\"discount_type\":\"percent\",\"discount_value\":\"0.00\",\"discount_amount\":\"0.00\",\"tax_percent\":\"10.00\",\"tax_amount\":\"260.00\",\"line_total\":\"2860.00\",\"selected_units\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}],\"created_at\":\"2026-05-28T08:19:11.000000Z\",\"updated_at\":\"2026-05-28T08:19:11.000000Z\"}]}', '{\"id\":6,\"company_id\":1,\"party_id\":1,\"cost_center_id\":null,\"sub_cost_center_id\":null,\"sale_type\":\"credit\",\"invoice_no\":\"00000004\",\"billing_date\":\"2026-05-28T00:00:00.000000Z\",\"reference_no\":null,\"phone\":null,\"billing_address\":null,\"shipping_address\":null,\"subtotal\":\"2600.00\",\"discount_amount\":\"0.00\",\"tax_amount\":\"260.00\",\"grand_total\":\"2860.00\",\"notes\":null,\"terms\":null,\"attachment\":null,\"status\":\"posted\",\"inter_company_transfer\":true,\"inter_company_target_company_ids\":[2],\"created_by\":25,\"created_at\":\"2026-05-28T08:18:50.000000Z\",\"updated_at\":\"2026-05-28T10:14:55.000000Z\",\"deleted_at\":null,\"items\":[{\"id\":7,\"sales_invoice_id\":6,\"item_id\":4,\"description\":null,\"quantity\":\"1.000\",\"unit\":\"PCS\",\"unit_price\":\"2600.00\",\"discount_type\":\"percent\",\"discount_value\":\"0.00\",\"discount_amount\":\"0.00\",\"tax_percent\":\"10.00\",\"tax_amount\":\"260.00\",\"line_total\":\"2860.00\",\"selected_units\":[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}],\"created_at\":\"2026-05-28T10:14:55.000000Z\",\"updated_at\":\"2026-05-28T10:14:55.000000Z\"}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Sales invoice 00000004 updated by Sanket Kumar (Accountent) for company Eemotrack India.', '2026-05-28 10:14:55'),
(16, 22, 1, 'approved', 'App\\Models\\Expense', 1, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'Expense EXP-000001 approved and posted to bank.', '2026-05-30 11:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `account_code` varchar(30) NOT NULL,
  `account_type` varchar(20) NOT NULL DEFAULT 'bank',
  `account_name` varchar(255) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `swift_code` varchar(30) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL,
  `upi_qr_code` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_balance_date` date DEFAULT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `print_on_invoice` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `company_id`, `account_code`, `account_type`, `account_name`, `bank_name`, `branch_name`, `account_holder_name`, `account_number`, `ifsc_code`, `swift_code`, `upi_id`, `upi_qr_code`, `phone`, `email`, `address`, `opening_balance`, `opening_balance_date`, `current_balance`, `is_primary`, `print_on_invoice`, `status`, `notes`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'BA-0001', 'bank', 'HDFC', 'HDFC Bank', 'EEMOTRACK INDIA', 'Ajay Mehta', '50100713877621', 'HDFC0006215', NULL, '50100713877621@ybl', 'bank-qr-codes/yM2M5b4SHBVov8opVR60hQRo2yE7qV0Yxx4rXkoa.png', '8863897163', 'ajayfilliptect@gmail.com', 'patna', 80000.00, '2026-05-22', 75640.01, 1, 1, 'active', NULL, 25, 25, '2026-05-22 03:36:00', '2026-05-30 06:07:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `bank_account_id` bigint(20) UNSIGNED NOT NULL,
  `related_bank_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(40) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `payment_mode` varchar(40) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `transfer_group` varchar(80) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_transactions`
--

INSERT INTO `bank_transactions` (`id`, `company_id`, `bank_account_id`, `related_bank_account_id`, `party_id`, `transaction_date`, `transaction_type`, `direction`, `amount`, `balance_after`, `reference_no`, `payment_mode`, `reference_type`, `reference_id`, `description`, `attachment`, `transfer_group`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 1, NULL, 1, '2026-05-22', 'payment_out', 'out', 9890.00, 70110.00, 'uhsfjhe88', 'IMPS', 'App\\Models\\PartyPayment', 1, NULL, 'payment-attachments/OkqeUC6YEHYlhwNt6xSBB320lKTIfKSZEwpBJKUC.png', NULL, 25, '2026-05-22 03:36:44', '2026-05-22 03:36:44'),
(3, 1, 1, NULL, 1, '2026-05-22', 'payment_in', 'in', 5500.01, 75610.01, 'qwer', 'RTGS', 'App\\Models\\PartyPayment', 2, NULL, 'payment-attachments/zdUm8E96iSkdLb4ZeQxoZWpc8NS3LBUceT5Uyya8.png', NULL, 25, '2026-05-22 03:41:30', '2026-05-22 03:41:30'),
(4, 1, 1, NULL, 1, '2026-05-30', 'payment_in', 'in', 1580.00, 77190.01, NULL, 'UPI', 'App\\Models\\PartyPayment', 3, NULL, NULL, NULL, 25, '2026-05-30 03:11:51', '2026-05-30 03:11:51'),
(5, 1, 1, NULL, NULL, '2026-05-22', 'opening_balance', 'in', 80000.00, 77190.01, 'BA-0001', NULL, 'App\\Models\\BankAccount', 1, 'Opening balance updated from bank master.', NULL, NULL, 25, '2026-05-30 06:02:13', '2026-05-30 06:02:13'),
(6, 1, 1, NULL, NULL, '2026-05-30', 'expense', 'out', 1550.00, 75640.01, 'EXP-000001', 'UPI', 'App\\Models\\Expense', 1, NULL, NULL, NULL, 22, '2026-05-30 06:07:11', '2026-05-30 06:07:11');

-- --------------------------------------------------------

--
-- Table structure for table `buyers`
--

CREATE TABLE `buyers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `buyer_code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buyers`
--

INSERT INTO `buyers` (`id`, `company_id`, `buyer_code`, `name`, `phone`, `email`, `address`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'BUY-00001', 'Sonu Mehta', '08863897163', 'sonu@gmail.com', 'B.C. Road, Patna, Bihar, 800001', 'active', 22, '2026-05-25 03:04:56', '2026-05-25 03:04:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-admin@admin|127.0.0.1', 'i:1;', 1779449652),
('laravel-cache-admin@admin|127.0.0.1:timer', 'i:1779449652;', 1779449652),
('laravel-cache-admin@admincom|127.0.0.1', 'i:1;', 1779449665),
('laravel-cache-admin@admincom|127.0.0.1:timer', 'i:1779449665;', 1779449665),
('laravel-cache-superadmin@bizaccount.com|127.0.0.1', 'i:1;', 1779704219),
('laravel-cache-superadmin@bizaccount.com|127.0.0.1:timer', 'i:1779704219;', 1779704219);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `cin_number` varchar(30) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `currency_symbol` varchar(5) NOT NULL DEFAULT '₹',
  `financial_year_start` varchar(10) NOT NULL DEFAULT '04-01',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `has_crm_access` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `email`, `phone`, `address`, `logo`, `gst_number`, `pan_number`, `cin_number`, `website`, `currency`, `currency_symbol`, `financial_year_start`, `is_active`, `has_crm_access`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Eemotrack India', 'info@eemotrack.com', NULL, 'Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\r\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055', 'logos/1sf2I8ZfxeeJr9nFejRs3vPNcpCjaLCujAsD9XA6.png', '10AQFPK9218D1ZA', 'KIIPK7404N', NULL, NULL, 'INR', '₹', '04-01', 1, 1, 21, '2026-05-22 01:28:53', '2026-05-22 06:17:20', NULL),
(2, 'Maruti Suzuki Venture', 'info@marutisuzuki.online', NULL, 'C-54 & C-55 G Block Road, Bandra East, Mumbai 400051.', 'logos/rC45sA5Fnw9pEr28oowzpLoLwfYbwQYdO8DkTBNM.jpg', '10AQFPK9918D1ZA', 'KIIPK7504N', NULL, NULL, 'INR', '₹', '04-01', 1, 1, 21, '2026-05-22 01:32:47', '2026-05-22 01:32:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company_merges`
--

CREATE TABLE `company_merges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `merged_with_company_id` bigint(20) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_merges`
--

INSERT INTO `company_merges` (`id`, `company_id`, `merged_with_company_id`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 1, 2, NULL, 21, '2026-05-28 02:43:40', '2026-05-28 02:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE `cost_centers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `budget_amount` decimal(15,2) DEFAULT NULL,
  `budget_start_date` date DEFAULT NULL,
  `budget_end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_challans`
--

CREATE TABLE `delivery_challans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sub_cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `challan_no` varchar(30) NOT NULL,
  `challan_date` date NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `dispatch_through` varchar(255) DEFAULT NULL,
  `vehicle_no` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_phone` varchar(30) DEFAULT NULL,
  `lr_no` varchar(255) DEFAULT NULL,
  `lr_date` date DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'issued',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_challans`
--

INSERT INTO `delivery_challans` (`id`, `company_id`, `party_id`, `cost_center_id`, `sub_cost_center_id`, `challan_no`, `challan_date`, `reference_no`, `dispatch_through`, `vehicle_no`, `driver_name`, `driver_phone`, `lr_no`, `lr_date`, `phone`, `billing_address`, `shipping_address`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `notes`, `terms`, `attachment`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, NULL, NULL, 'DC-2026000001', '2026-05-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2600.00, 0.00, 260.00, 2860.00, NULL, NULL, NULL, 'issued', 25, '2026-05-30 06:49:29', '2026-05-30 06:49:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_challan_items`
--

CREATE TABLE `delivery_challan_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `delivery_challan_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_challan_items`
--

INSERT INTO `delivery_challan_items` (`id`, `delivery_challan_id`, `item_id`, `description`, `quantity`, `unit`, `unit_price`, `discount_type`, `discount_value`, `discount_amount`, `tax_percent`, `tax_amount`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 4, NULL, 1.000, 'PCS', 2600.00, 'percent', 0.00, 0.00, 10.00, 260.00, 2860.00, '2026-05-30 06:49:29', '2026-05-30 06:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `entry_visibilities`
--

CREATE TABLE `entry_visibilities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_type` varchar(255) NOT NULL,
  `entry_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `visible_to_all_company` tinyint(1) NOT NULL DEFAULT 0,
  `visible_to_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to_roles`)),
  `visible_to_users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to_users`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `entry_visibilities`
--

INSERT INTO `entry_visibilities` (`id`, `entry_type`, `entry_id`, `company_id`, `visible_to_all_company`, `visible_to_roles`, `visible_to_users`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\Party', 1, 1, 0, '[]', '[]', '2026-05-22 02:16:49', '2026-05-22 02:16:49'),
(2, 'App\\Models\\ProductType', 1, 1, 0, '[]', '[]', '2026-05-22 02:23:54', '2026-05-22 02:23:54'),
(3, 'App\\Models\\Item', 2, 1, 0, '[]', '[]', '2026-05-22 02:30:27', '2026-05-22 02:30:27'),
(4, 'App\\Models\\Item', 3, 1, 0, '[]', '[]', '2026-05-22 02:53:20', '2026-05-22 02:53:20'),
(5, 'App\\Models\\ProductType', 2, 1, 0, '[]', '[]', '2026-05-22 02:57:30', '2026-05-22 02:57:30'),
(6, 'App\\Models\\PurchaseBill', 2, 1, 0, '[]', '[]', '2026-05-22 03:22:45', '2026-05-22 03:22:45'),
(7, 'App\\Models\\Item', 4, 1, 0, '[]', '[]', '2026-05-22 03:25:04', '2026-05-22 03:25:04'),
(8, 'App\\Models\\ProductionBatch', 1, 1, 0, '[]', '[]', '2026-05-22 03:33:46', '2026-05-22 03:33:46'),
(9, 'App\\Models\\BankAccount', 1, 1, 0, '[]', '[]', '2026-05-22 03:36:00', '2026-05-22 03:36:00'),
(10, 'App\\Models\\SalesInvoice', 2, 1, 0, '[]', '[]', '2026-05-22 03:40:09', '2026-05-22 03:40:09'),
(11, 'App\\Models\\ProductionBatch', 2, 1, 1, '[3]', '[25]', '2026-05-22 06:35:49', '2026-05-22 06:35:49'),
(12, 'App\\Models\\SalesInvoice', 3, 1, 0, '[]', '[25]', '2026-05-23 00:55:30', '2026-05-23 00:55:30'),
(13, 'App\\Models\\PurchaseBill', 3, 1, 0, '[]', '[]', '2026-05-23 00:57:03', '2026-05-23 00:57:03'),
(14, 'App\\Models\\ProductionBatch', 3, 1, 0, '[]', '[]', '2026-05-23 01:00:49', '2026-05-23 01:00:49'),
(15, 'App\\Models\\Buyer', 1, 1, 0, '[]', '[25]', '2026-05-25 03:04:56', '2026-05-25 03:04:56'),
(16, 'App\\Models\\ProductionBatch', 4, 1, 0, '[]', '[]', '2026-05-25 03:05:42', '2026-05-25 03:05:42'),
(17, 'App\\Models\\SalesInvoice', 4, 1, 0, '[]', '[]', '2026-05-25 03:07:25', '2026-05-25 03:07:25'),
(18, 'App\\Models\\SalesInvoice', 6, 1, 0, '[]', '[]', '2026-05-28 02:48:50', '2026-05-28 02:48:50'),
(19, 'App\\Models\\PurchaseBill', 4, 2, 0, '[4]', '[26]', '2026-05-28 04:44:55', '2026-05-28 04:44:55'),
(20, 'App\\Models\\ProductionBatch', 5, 1, 0, '[]', '[]', '2026-05-28 04:57:38', '2026-05-28 04:57:38'),
(21, 'App\\Models\\ExpenseLedger', 1, 1, 0, '[]', '[]', '2026-05-30 06:03:46', '2026-05-30 06:03:46'),
(22, 'App\\Models\\Expense', 1, 1, 0, '[]', '[]', '2026-05-30 06:04:48', '2026-05-30 06:04:48'),
(23, 'App\\Models\\DeliveryChallan', 1, 1, 0, '[]', '[]', '2026-05-30 06:49:29', '2026-05-30 06:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `estimates`
--

CREATE TABLE `estimates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sub_cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estimate_no` varchar(30) NOT NULL,
  `estimate_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `converted_sales_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `estimate_items`
--

CREATE TABLE `estimate_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `estimate_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `expense_ledger_id` bigint(20) UNSIGNED NOT NULL,
  `bank_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expense_date` date NOT NULL,
  `expense_no` varchar(30) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `payment_mode` varchar(40) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending_approval',
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `company_id`, `expense_ledger_id`, `bank_account_id`, `expense_date`, `expense_no`, `reference_no`, `vendor_name`, `amount`, `tax_amount`, `total_amount`, `payment_mode`, `status`, `description`, `attachment`, `approved_at`, `approved_by`, `approval_note`, `rejected_at`, `rejected_by`, `rejection_reason`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, '2026-05-30', 'EXP-000001', NULL, 'Ajay Kumar', 1550.00, 0.00, 1550.00, 'UPI', 'approved', NULL, NULL, '2026-05-30 06:07:11', 22, 'ok acccepted', NULL, NULL, NULL, 25, 22, '2026-05-30 06:04:48', '2026-05-30 06:07:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expense_ledgers`
--

CREATE TABLE `expense_ledgers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `ledger_code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_balance_date` date DEFAULT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `attachment` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_ledgers`
--

INSERT INTO `expense_ledgers` (`id`, `company_id`, `ledger_code`, `name`, `category`, `opening_balance`, `opening_balance_date`, `current_balance`, `status`, `attachment`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'EL-0001', 'Recharge', 'Office', 0.00, '2026-05-30', 1550.00, 'active', NULL, NULL, 25, 25, '2026-05-30 06:03:46', '2026-05-30 06:07:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `product_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_type` varchar(20) NOT NULL DEFAULT 'product',
  `item_code` varchar(40) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'PCS',
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `purchase_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `purchase_tax_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `purchase_gst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sale_tax_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `sale_gst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `opening_stock` decimal(15,3) NOT NULL DEFAULT 0.000,
  `current_stock` decimal(15,3) NOT NULL DEFAULT 0.000,
  `stock_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `low_stock_qty` decimal(15,3) DEFAULT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT 1,
  `is_bom_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `company_id`, `product_type_id`, `item_type`, `item_code`, `hsn_code`, `barcode`, `qr_code`, `name`, `sku`, `unit`, `brand`, `model`, `size`, `color`, `description`, `purchase_price`, `purchase_tax_inclusive`, `purchase_gst_percent`, `sale_price`, `sale_tax_inclusive`, `sale_gst_percent`, `opening_stock`, `current_stock`, `stock_value`, `low_stock_qty`, `track_stock`, `is_bom_enabled`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 1, 1, 'product', 'ITM-00001', 'HSN000001', 'ITM-00001', 'QR-ITM-00001', 'gps-box', 'ET-UCLV1470', 'PCS', NULL, NULL, NULL, 'White', NULL, 100.00, 1, 10.00, 160.00, 1, 10.00, 100.000, 100.000, 10000.00, NULL, 1, 0, 'active', 25, '2026-05-22 02:30:27', '2026-05-22 02:52:05', '2026-05-22 02:52:05'),
(3, 1, 1, 'product', 'ITM-00002', 'HSN000002', 'ITM-00002', 'QR-ITM-00002', 'gps-box', 'ET-UCLV1470', 'PCS', NULL, NULL, NULL, 'White', NULL, 100.00, 1, 10.00, 150.00, 1, 10.00, 0.000, 91.998, 11089.78, 100.000, 1, 0, 'active', 25, '2026-05-22 02:53:20', '2026-05-28 04:57:38', NULL),
(4, 1, 2, 'product', 'ITM-00003', 'HSN000003', 'ITM-00003', 'QR-ITM-00003', 'GPS 3x PRO', 'ET-UQHU9821', 'PCS', NULL, NULL, NULL, NULL, NULL, 600.00, 1, 10.00, 2600.00, 1, 10.00, 0.000, 20.000, 11600.00, 10.000, 1, 0, 'active', 25, '2026-05-22 03:25:04', '2026-05-28 04:57:38', NULL),
(5, 2, 3, 'product', 'ITM-00003', 'HSN000003', 'ITM-00003', 'QR-ITM-00003', 'GPS 3x PRO', 'ET-UQHU9821', 'PCS', NULL, NULL, NULL, NULL, NULL, 600.00, 1, 10.00, 2600.00, 1, 10.00, 0.000, 2.000, 5460.00, 10.000, 1, 0, 'active', 23, '2026-05-22 05:31:41', '2026-05-28 04:44:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `item_boms`
--

CREATE TABLE `item_boms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `finished_item_id` bigint(20) UNSIGNED NOT NULL,
  `raw_item_id` bigint(20) UNSIGNED NOT NULL,
  `qty_per_unit` decimal(15,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_boms`
--

INSERT INTO `item_boms` (`id`, `company_id`, `finished_item_id`, `raw_item_id`, `qty_per_unit`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 3, 4.000, '2026-05-22 03:25:04', '2026-05-22 03:25:04');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_05_19_085613_create_companies_table', 1),
(5, '2026_05_19_085619_create_roles_table', 1),
(6, '2026_05_19_085625_create_permissions_table', 1),
(7, '2026_05_19_085632_create_role_permissions_table', 1),
(8, '2026_05_19_085638_create_user_roles_table', 1),
(9, '2026_05_19_085644_create_user_companies_table', 1),
(10, '2026_05_19_085650_create_audit_logs_table', 1),
(11, '2026_05_19_085658_create_entry_visibilities_table', 1),
(12, '2026_05_19_091214_add_profile_fields_to_users_table', 1),
(13, '2026_05_21_140000_create_parties_table', 1),
(14, '2026_05_21_140010_create_party_ledgers_table', 1),
(15, '2026_05_21_140020_create_cost_centers_table', 1),
(16, '2026_05_21_141500_create_bank_accounts_table', 1),
(17, '2026_05_21_141510_create_bank_transactions_table', 1),
(18, '2026_05_21_142500_create_inventory_and_trade_tables', 1),
(19, '2026_05_21_145800_add_payments_and_trade_attachments', 1),
(20, '2026_05_21_160000_create_estimates_and_delivery_challans', 1),
(21, '2026_05_21_170000_add_created_by_to_remaining_entry_tables', 1),
(22, '2026_05_22_082516_add_units_data_to_production_batches_table', 2),
(23, '2025_01_01_000001_create_company_merges_table', 3),
(24, '2025_01_01_000002_create_stock_transfers_table', 3),
(25, '2026_05_25_000001_add_selected_units_to_sales_invoice_items', 4),
(26, '2026_05_25_000002_create_buyers_and_return_tables', 5),
(27, '2026_05_28_000001_add_gps_and_inter_company_sale_fields', 6),
(28, '2026_05_30_000001_add_invoice_payment_allocations_and_user_pin', 7),
(29, '2026_05_30_000002_create_expenses_terms_and_invoice_print_fields', 8),
(30, '2026_06_03_000001_add_status_to_production_batches_table', 9),
(31, '2026_06_15_000001_add_has_crm_access_to_companies_table', 10);

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_code` varchar(30) NOT NULL,
  `party_type` varchar(20) NOT NULL DEFAULT 'both',
  `display_name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `alternate_phone` varchar(30) DEFAULT NULL,
  `whatsapp_number` varchar(30) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `tan_number` varchar(20) DEFAULT NULL,
  `cin_number` varchar(30) DEFAULT NULL,
  `tax_type` varchar(20) NOT NULL DEFAULT 'registered',
  `place_of_supply` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state` varchar(80) DEFAULT NULL,
  `pincode` varchar(15) DEFAULT NULL,
  `country` varchar(80) NOT NULL DEFAULT 'India',
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_balance_type` varchar(20) NOT NULL DEFAULT 'payable',
  `opening_balance_date` date DEFAULT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `credit_days` int(10) UNSIGNED DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `upi_id` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `company_id`, `party_code`, `party_type`, `display_name`, `legal_name`, `contact_person`, `email`, `phone`, `alternate_phone`, `whatsapp_number`, `gstin`, `pan_number`, `tan_number`, `cin_number`, `tax_type`, `place_of_supply`, `billing_address`, `shipping_address`, `city`, `state`, `pincode`, `country`, `opening_balance`, `opening_balance_type`, `opening_balance_date`, `current_balance`, `credit_limit`, `credit_days`, `payment_terms`, `bank_name`, `account_holder_name`, `account_number`, `ifsc_code`, `branch_name`, `upi_id`, `status`, `notes`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'PTY-00001', 'both', 'Ajay Mehta', 'Ajay Traders', 'Ajay Kumar', 'ajayfilliptect@gmail.com', '8863897163', '8294169540', '8294169540', '121212121212121', 'KIIPK7404N', NULL, NULL, 'registered', 'india', 'B.C. Road, Patna, Bihar, 800001', 'Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055', 'Patna HQ', 'Bihar', '800001', 'India', 0.00, 'payable', '2026-05-22', 1239.78, NULL, NULL, NULL, 'HDFC Bank', 'Ajay Mehta', '50100713877621', 'HDFC0006215', 'EEMOTRACK INDIA', '50100713877621@ybl', 'active', NULL, 25, 25, '2026-05-22 02:16:49', '2026-05-30 03:11:51', NULL),
(2, 2, 'CO-1', 'supplier', 'Eemotrack India', 'Eemotrack India', NULL, 'info@eemotrack.com', NULL, NULL, NULL, '10AQFPK9218D1ZA', 'KIIPK7404N', NULL, NULL, 'registered', NULL, 'Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\r\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055', 'Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\r\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055', NULL, NULL, NULL, 'India', 0.00, 'payable', NULL, 5720.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, 25, NULL, '2026-05-28 02:48:50', '2026-05-28 04:44:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `party_ledgers`
--

CREATE TABLE `party_ledgers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` varchar(40) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `party_ledgers`
--

INSERT INTO `party_ledgers` (`id`, `company_id`, `party_id`, `entry_date`, `entry_type`, `reference_type`, `reference_id`, `reference_no`, `debit`, `credit`, `balance_after`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 1, '2026-05-22', 'purchase', 'App\\Models\\PurchaseBill', 2, '00000001', 0.00, 9890.00, 9890.00, 'Purchase bill payable.', 25, '2026-05-22 03:22:45', '2026-05-22 03:22:45'),
(3, 1, 1, '2026-05-22', 'payment_out', 'App\\Models\\PartyPayment', 1, 'uhsfjhe88', 9890.00, 0.00, 0.00, 'Payment paid to party.', 25, '2026-05-22 03:36:44', '2026-05-22 03:36:44'),
(5, 1, 1, '2026-05-22', 'sale', 'App\\Models\\SalesInvoice', 2, '00000001', 5500.01, 0.00, -5500.01, 'Sales invoice receivable.', 25, '2026-05-22 03:40:09', '2026-05-22 03:40:09'),
(6, 1, 1, '2026-05-22', 'payment_in', 'App\\Models\\PartyPayment', 2, 'qwer', 0.00, 5500.01, 0.00, 'Payment received from party.', 25, '2026-05-22 03:41:30', '2026-05-22 03:41:30'),
(7, 1, 1, '2026-05-23', 'sale', 'App\\Models\\SalesInvoice', 3, '00000002', 2760.00, 0.00, -2760.00, 'Sales invoice receivable.', 22, '2026-05-23 00:55:30', '2026-05-23 00:55:30'),
(8, 1, 1, '2026-05-23', 'purchase', 'App\\Models\\PurchaseBill', 3, '00000002', 0.00, 10999.78, 8239.78, 'Purchase bill payable.', 22, '2026-05-23 00:57:03', '2026-05-23 00:57:03'),
(9, 1, 1, '2026-05-25', 'sale', 'App\\Models\\SalesInvoice', 4, '00000003', 5720.00, 0.00, 2519.78, 'Sales invoice receivable.', 22, '2026-05-25 03:07:25', '2026-05-25 03:07:25'),
(10, 2, 2, '2026-05-28', 'purchase', 'App\\Models\\PurchaseBill', 4, 'IC-00000004', 0.00, 2860.00, 2860.00, 'Auto inter-company purchase payable.', 25, '2026-05-28 02:48:50', '2026-05-28 02:48:50'),
(11, 1, 1, '2026-05-28', 'sale', 'App\\Models\\SalesInvoice', 6, '00000004', 2860.00, 0.00, -340.22, 'Sales invoice receivable updated.', 25, '2026-05-28 02:49:11', '2026-05-28 02:49:11'),
(12, 1, 1, '2026-05-28', 'sale_reversal', 'App\\Models\\SalesInvoice', 6, '00000004', 0.00, 2860.00, 2519.78, 'Sales ledger reversal before update.', 25, '2026-05-28 04:44:55', '2026-05-28 04:44:55'),
(13, 1, 1, '2026-05-28', 'sale', 'App\\Models\\SalesInvoice', 6, '00000004', 2860.00, 0.00, -340.22, 'Sales invoice receivable updated.', 25, '2026-05-28 04:44:55', '2026-05-28 04:44:55'),
(14, 2, 2, '2026-05-28', 'purchase_reversal', 'App\\Models\\PurchaseBill', 4, 'IC-00000004', 2860.00, 0.00, 0.00, 'Auto purchase ledger reversal before source sale update.', 25, '2026-05-28 04:44:55', '2026-05-28 04:44:55'),
(15, 2, 2, '2026-05-28', 'purchase', 'App\\Models\\PurchaseBill', 4, 'IC-00000004', 0.00, 2860.00, 5720.00, 'Auto inter-company purchase payable.', 25, '2026-05-28 04:44:55', '2026-05-28 04:44:55'),
(16, 1, 1, '2026-05-30', 'payment_in', 'App\\Models\\PartyPayment', 3, NULL, 0.00, 1580.00, 1239.78, 'Payment received from party.', 25, '2026-05-30 03:11:51', '2026-05-30 03:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `party_payments`
--

CREATE TABLE `party_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED NOT NULL,
  `bank_account_id` bigint(20) UNSIGNED NOT NULL,
  `payment_date` date NOT NULL,
  `payment_type` varchar(20) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `payment_mode` varchar(40) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `party_payments`
--

INSERT INTO `party_payments` (`id`, `company_id`, `party_id`, `bank_account_id`, `payment_date`, `payment_type`, `reference_no`, `amount`, `discount_amount`, `total_amount`, `payment_mode`, `description`, `attachment`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2026-05-22', 'payment_out', 'uhsfjhe88', 9890.00, 0.00, 9890.00, 'IMPS', NULL, 'payment-attachments/OkqeUC6YEHYlhwNt6xSBB320lKTIfKSZEwpBJKUC.png', 25, '2026-05-22 03:36:44', '2026-05-22 03:36:44'),
(2, 1, 1, 1, '2026-05-22', 'payment_in', 'qwer', 5500.01, 0.00, 5500.01, 'RTGS', NULL, 'payment-attachments/zdUm8E96iSkdLb4ZeQxoZWpc8NS3LBUceT5Uyya8.png', 25, '2026-05-22 03:41:30', '2026-05-22 03:41:30'),
(3, 1, 1, 1, '2026-05-30', 'payment_in', NULL, 1580.00, 0.00, 1580.00, 'UPI', NULL, NULL, 25, '2026-05-30 03:11:50', '2026-05-30 03:11:50');

-- --------------------------------------------------------

--
-- Table structure for table `party_payment_allocations`
--

CREATE TABLE `party_payment_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `party_payment_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED NOT NULL,
  `bill_type` varchar(20) NOT NULL,
  `bill_model` varchar(255) NOT NULL,
  `bill_id` bigint(20) UNSIGNED NOT NULL,
  `bill_no` varchar(255) DEFAULT NULL,
  `bill_date` date DEFAULT NULL,
  `bill_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `party_payment_allocations`
--

INSERT INTO `party_payment_allocations` (`id`, `party_payment_id`, `company_id`, `party_id`, `bill_type`, `bill_model`, `bill_id`, `bill_no`, `bill_date`, `bill_total`, `amount`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, 'sales', 'App\\Models\\SalesInvoice', 6, '00000004', '2026-05-28', 2860.00, 860.00, '2026-05-30 03:11:50', '2026-05-30 03:11:50'),
(2, 3, 1, 1, 'sales', 'App\\Models\\SalesInvoice', 4, '00000003', '2026-05-25', 5720.00, 720.00, '2026-05-30 03:11:51', '2026-05-30 03:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
(1, 'View Users', 'users.view', 'users', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(2, 'Create Users', 'users.create', 'users', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(3, 'Edit Users', 'users.edit', 'users', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(4, 'Delete Users', 'users.delete', 'users', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(5, 'View Roles', 'roles.view', 'roles', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(6, 'Create Roles', 'roles.create', 'roles', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(7, 'Edit Roles', 'roles.edit', 'roles', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(8, 'Delete Roles', 'roles.delete', 'roles', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(9, 'View Sales', 'sales.view', 'sales', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(10, 'Create Sales', 'sales.create', 'sales', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(11, 'Edit Sales', 'sales.edit', 'sales', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(12, 'Delete Sales', 'sales.delete', 'sales', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(13, 'Print Sales', 'sales.print', 'sales', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(14, 'View Estimates', 'estimates.view', 'estimates', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(15, 'Create Estimates', 'estimates.create', 'estimates', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(16, 'Edit Estimates', 'estimates.edit', 'estimates', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(17, 'Delete Estimates', 'estimates.delete', 'estimates', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(18, 'Convert Estimates', 'estimates.convert', 'estimates', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(19, 'Print Estimates', 'estimates.print', 'estimates', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(20, 'View Delivery Challans', 'delivery_challans.view', 'delivery_challans', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(21, 'Create Delivery Challans', 'delivery_challans.create', 'delivery_challans', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(22, 'Edit Delivery Challans', 'delivery_challans.edit', 'delivery_challans', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(23, 'Delete Delivery Challans', 'delivery_challans.delete', 'delivery_challans', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(24, 'Print Delivery Challans', 'delivery_challans.print', 'delivery_challans', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(25, 'View Purchase', 'purchase.view', 'purchase', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(26, 'Create Purchase', 'purchase.create', 'purchase', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(27, 'Edit Purchase', 'purchase.edit', 'purchase', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(28, 'Delete Purchase', 'purchase.delete', 'purchase', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(29, 'Print Purchase', 'purchase.print', 'purchase', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(30, 'View Stocks', 'stocks.view', 'stocks', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(31, 'Add Stocks', 'stocks.create', 'stocks', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(32, 'Edit Stocks', 'stocks.edit', 'stocks', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(33, 'View Items', 'items.view', 'items', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(34, 'Create Items', 'items.create', 'items', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(35, 'Edit Items', 'items.edit', 'items', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(36, 'Delete Items', 'items.delete', 'items', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(37, 'View Product Types', 'product_types.view', 'product_types', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(38, 'Manage Product Types', 'product_types.manage', 'product_types', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(39, 'View Production', 'production.view', 'production', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(40, 'Create Production', 'production.create', 'production', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(41, 'View Expenses', 'expenses.view', 'expenses', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(42, 'Create Expenses', 'expenses.create', 'expenses', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(43, 'Edit Expenses', 'expenses.edit', 'expenses', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(44, 'Delete Expenses', 'expenses.delete', 'expenses', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(45, 'View Parties', 'parties.view', 'parties', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(46, 'Create Parties', 'parties.create', 'parties', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(47, 'Edit Parties', 'parties.edit', 'parties', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(48, 'Delete Parties', 'parties.delete', 'parties', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(49, 'View Banking', 'banking.view', 'banking', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(50, 'Manage Banking', 'banking.manage', 'banking', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(51, 'View Cost Centers', 'cost_centers.view', 'cost_centers', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(52, 'Manage Cost Centers', 'cost_centers.manage', 'cost_centers', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(53, 'View Party Payments', 'party_payments.view', 'party_payments', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(54, 'Create Party Payments', 'party_payments.create', 'party_payments', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(55, 'View Party Reports', 'reports.party', 'reports', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(56, 'View Stock Reports', 'reports.stock', 'reports', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(57, 'View Expense Reports', 'reports.expense', 'reports', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(58, 'View GST Reports', 'reports.gst', 'reports', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(59, 'View Audit Logs', 'audit.view', 'audit', NULL, '2026-05-22 00:52:54', '2026-05-22 00:52:54'),
(60, 'View Transaction Reports', 'reports.transaction', 'reports', NULL, '2026-05-22 02:06:54', '2026-05-22 02:06:54'),
(61, 'Approve Expenses', 'expenses.approve', 'expenses', NULL, '2026-05-30 05:58:55', '2026-05-30 05:58:55'),
(62, 'Manage Terms', 'terms.manage', 'terms', NULL, '2026-05-30 05:58:55', '2026-05-30 05:58:55');

-- --------------------------------------------------------

--
-- Table structure for table `production_batches`
--

CREATE TABLE `production_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `finished_item_id` bigint(20) UNSIGNED NOT NULL,
  `batch_no` varchar(30) NOT NULL,
  `production_date` date NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `raw_material_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cost_per_unit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `units_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`units_data`)),
  `status` varchar(20) NOT NULL DEFAULT 'posted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_batches`
--

INSERT INTO `production_batches` (`id`, `company_id`, `finished_item_id`, `batch_no`, `production_date`, `quantity`, `raw_material_cost`, `cost_per_unit`, `notes`, `units_data`, `status`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 1, 4, 'PB-00001', '2026-05-22', 5.000, 2000.00, 400.00, NULL, '[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-002\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-D2CQ\",\"vts_sim\":\"43534543546546\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-003\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-YJ5U\",\"vts_sim\":\"543647567\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-004\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-ID9I\",\"vts_sim\":\"547567575\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null},{\"buyer_code\":\"BC-AUTO-005\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-YYDR\",\"vts_sim\":\"435234214\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null}]', 'posted', '2026-05-22 03:33:46', '2026-05-28 02:48:17', 25),
(2, 1, 4, 'PB-00002', '2026-05-22', 10.000, 4000.00, 400.00, NULL, '[{\"serial_no\":\"SN-MAY2026-0001\",\"batch_no\":\"MAY2026-QXZY\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0002\",\"batch_no\":\"MAY2026-VZ18\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0003\",\"batch_no\":\"MAY2026-TAPV\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0004\",\"batch_no\":\"MAY2026-IHXM\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0005\",\"batch_no\":\"MAY2026-JGQA\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0006\",\"batch_no\":\"MAY2026-S20T\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0007\",\"batch_no\":\"MAY2026-XAGP\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0008\",\"batch_no\":\"MAY2026-BK9Y\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0009\",\"batch_no\":\"MAY2026-QXT4\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0010\",\"batch_no\":\"MAY2026-1UUZ\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null}]', 'posted', '2026-05-22 06:35:49', '2026-05-22 06:35:49', 22),
(3, 1, 4, 'PB-00003', '2026-05-23', 10.000, 4000.00, 400.00, NULL, '[{\"serial_no\":\"SN-MAY2026-0001\",\"batch_no\":\"MAY2026-EB0G\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0002\",\"batch_no\":\"MAY2026-1L38\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0003\",\"batch_no\":\"MAY2026-ZUDF\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0004\",\"batch_no\":\"MAY2026-5U8R\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0005\",\"batch_no\":\"MAY2026-6V5D\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0006\",\"batch_no\":\"MAY2026-BTT8\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0007\",\"batch_no\":\"MAY2026-3EHC\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0008\",\"batch_no\":\"MAY2026-IVIL\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0009\",\"batch_no\":\"MAY2026-M5BG\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null},{\"serial_no\":\"SN-MAY2026-0010\",\"batch_no\":\"MAY2026-Q4TD\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null}]', 'posted', '2026-05-23 01:00:49', '2026-05-23 01:00:49', 22),
(4, 1, 4, 'PB-00004', '2026-05-25', 1.000, 400.00, 400.00, NULL, '[{\"buyer_id\":\"1\",\"buyer_code\":\"BUY-00001\",\"serial_no\":\"SN-MAY2026-0001\",\"batch_no\":\"MAY2026-3KW3\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null}]', 'posted', '2026-05-25 03:05:42', '2026-05-25 03:05:42', 22),
(5, 1, 4, 'PB-00005', '2026-05-28', 1.000, 400.00, 400.00, NULL, '[{\"buyer_id\":\"1\",\"buyer_code\":\"BUY-00001\",\"serial_no\":\"12121212121212\",\"batch_no\":\"YHGH2040\",\"vts_sim\":\"2343253453433\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":\"kamla market\",\"notes\":null}]', 'posted', '2026-05-28 04:57:38', '2026-05-28 04:57:38', 25);

-- --------------------------------------------------------

--
-- Table structure for table `product_types`
--

CREATE TABLE `product_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nature` varchar(30) NOT NULL DEFAULT 'finished_goods',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_types`
--

INSERT INTO `product_types` (`id`, `company_id`, `code`, `name`, `nature`, `status`, `description`, `created_at`, `updated_at`, `deleted_at`, `created_by`) VALUES
(1, 1, 'PT-0001', 'gps-box', 'raw_material', 'active', NULL, '2026-05-22 02:23:54', '2026-05-22 02:23:54', NULL, 25),
(2, 1, 'PT-0002', 'Finised Goods', 'finished_goods', 'active', NULL, '2026-05-22 02:57:30', '2026-05-22 02:57:30', NULL, 25),
(3, 2, 'FINISHED', 'Finished Goods', 'finished_goods', 'active', NULL, '2026-05-28 02:48:50', '2026-05-28 02:48:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_bills`
--

CREATE TABLE `purchase_bills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sub_cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_type` varchar(20) NOT NULL DEFAULT 'credit',
  `invoice_no` varchar(20) NOT NULL,
  `supplier_bill_no` varchar(255) DEFAULT NULL,
  `billing_date` date NOT NULL,
  `purchase_bill_date` date DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `docket_no` varchar(255) DEFAULT NULL,
  `e_bill_no` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'posted',
  `source_sales_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inter_company_source_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_bills`
--

INSERT INTO `purchase_bills` (`id`, `company_id`, `party_id`, `cost_center_id`, `sub_cost_center_id`, `purchase_type`, `invoice_no`, `supplier_bill_no`, `billing_date`, `purchase_bill_date`, `reference_no`, `docket_no`, `e_bill_no`, `phone`, `billing_address`, `shipping_address`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `notes`, `terms`, `attachment`, `status`, `source_sales_invoice_id`, `inter_company_source_company_id`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 1, 1, NULL, NULL, 'credit', '00000001', '874954', '2026-05-22', NULL, 'uhsfjhe88', 'Z72604639', '56546', NULL, NULL, NULL, 10000.00, 1100.00, 990.00, 9890.00, NULL, NULL, 'purchase-attachments/W7XtMqpFxF0u1W5H5GeJKbHE1MV1bPNbUz5sC3Nb.png', 'posted', NULL, NULL, 25, '2026-05-22 03:22:45', '2026-05-22 03:22:45', NULL),
(3, 1, 1, NULL, NULL, 'credit', '00000002', '8749534', '2026-05-23', NULL, 'qwert', '98765', '56546', NULL, NULL, NULL, 9999.80, 0.00, 999.98, 10999.78, NULL, NULL, NULL, 'posted', NULL, NULL, 22, '2026-05-23 00:57:03', '2026-05-23 00:57:03', NULL),
(4, 2, 2, NULL, NULL, 'credit', 'IC-00000004', '00000004', '2026-05-28', '2026-05-28', 'Auto purchase from sale 00000004', NULL, NULL, NULL, 'Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\r\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055', 'Patna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055\r\nPatna , Bihar , Rk Bhatacharya Road, Patna , Bihar , Rk Bhatacharya Road, Patna, Bihar 800001, India, Phone: 7857868055', 2600.00, 0.00, 260.00, 2860.00, 'Inter-company purchase auto-created from Eemotrack India sale 00000004.', NULL, NULL, 'posted', 6, 1, 25, '2026-05-28 02:48:50', '2026-05-28 02:48:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_bill_items`
--

CREATE TABLE `purchase_bill_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_bill_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `selected_units` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_units`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_bill_items`
--

INSERT INTO `purchase_bill_items` (`id`, `purchase_bill_id`, `item_id`, `description`, `quantity`, `unit`, `unit_price`, `discount_type`, `discount_value`, `discount_amount`, `tax_percent`, `tax_amount`, `line_total`, `selected_units`, `created_at`, `updated_at`) VALUES
(2, 2, 3, 'box', 100.000, 'PCS', 100.00, 'flat', 100.00, 100.00, 10.00, 990.00, 10890.00, NULL, '2026-05-22 03:22:45', '2026-05-22 03:22:45'),
(3, 3, 3, 'box', 99.998, 'PCS', 100.00, 'percent', 0.00, 0.00, 10.00, 999.98, 10999.78, NULL, '2026-05-23 00:57:03', '2026-05-23 00:57:03'),
(5, 4, 5, NULL, 1.000, 'PCS', 2600.00, 'percent', 0.00, 0.00, 10.00, 260.00, 2860.00, '[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}]', '2026-05-28 04:44:55', '2026-05-28 04:44:55');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_bill_id` bigint(20) UNSIGNED DEFAULT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `return_no` varchar(30) NOT NULL,
  `return_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'posted',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_return_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_bill_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `company_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Company Admin', 'company-admin', 'Default full-access admin role for this company.', 1, 1, '2026-05-22 01:28:54', '2026-05-22 01:28:54'),
(2, 'Company Admin', 'company-admin', 'Default full-access admin role for this company.', 2, 1, '2026-05-22 01:32:48', '2026-05-22 01:32:48'),
(3, 'Accountent', 'accountent', NULL, 1, 1, '2026-05-22 02:12:18', '2026-05-22 02:12:18'),
(4, 'Accountent', 'accountent', NULL, 2, 1, '2026-05-28 04:43:25', '2026-05-28 04:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permission`
--

INSERT INTO `role_permission` (`id`, `role_id`, `permission_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4),
(5, 1, 5),
(6, 1, 6),
(7, 1, 7),
(8, 1, 8),
(9, 1, 9),
(10, 1, 10),
(11, 1, 11),
(12, 1, 12),
(13, 1, 13),
(14, 1, 14),
(15, 1, 15),
(16, 1, 16),
(17, 1, 17),
(18, 1, 18),
(19, 1, 19),
(20, 1, 20),
(21, 1, 21),
(22, 1, 22),
(23, 1, 23),
(24, 1, 24),
(25, 1, 25),
(26, 1, 26),
(27, 1, 27),
(28, 1, 28),
(29, 1, 29),
(30, 1, 30),
(31, 1, 31),
(32, 1, 32),
(33, 1, 33),
(34, 1, 34),
(35, 1, 35),
(36, 1, 36),
(37, 1, 37),
(38, 1, 38),
(39, 1, 39),
(40, 1, 40),
(41, 1, 41),
(42, 1, 42),
(43, 1, 43),
(44, 1, 44),
(45, 1, 45),
(46, 1, 46),
(47, 1, 47),
(48, 1, 48),
(49, 1, 49),
(50, 1, 50),
(51, 1, 51),
(52, 1, 52),
(53, 1, 53),
(54, 1, 54),
(55, 1, 55),
(56, 1, 56),
(57, 1, 57),
(58, 1, 58),
(59, 1, 59),
(119, 1, 60),
(223, 1, 61),
(224, 1, 62),
(60, 2, 1),
(61, 2, 2),
(62, 2, 3),
(63, 2, 4),
(64, 2, 5),
(65, 2, 6),
(66, 2, 7),
(67, 2, 8),
(68, 2, 9),
(69, 2, 10),
(70, 2, 11),
(71, 2, 12),
(72, 2, 13),
(73, 2, 14),
(74, 2, 15),
(75, 2, 16),
(76, 2, 17),
(77, 2, 18),
(78, 2, 19),
(79, 2, 20),
(80, 2, 21),
(81, 2, 22),
(82, 2, 23),
(83, 2, 24),
(84, 2, 25),
(85, 2, 26),
(86, 2, 27),
(87, 2, 28),
(88, 2, 29),
(89, 2, 30),
(90, 2, 31),
(91, 2, 32),
(92, 2, 33),
(93, 2, 34),
(94, 2, 35),
(95, 2, 36),
(96, 2, 37),
(97, 2, 38),
(98, 2, 39),
(99, 2, 40),
(100, 2, 41),
(101, 2, 42),
(102, 2, 43),
(103, 2, 44),
(104, 2, 45),
(105, 2, 46),
(106, 2, 47),
(107, 2, 48),
(108, 2, 49),
(109, 2, 50),
(110, 2, 51),
(111, 2, 52),
(112, 2, 53),
(113, 2, 54),
(114, 2, 55),
(115, 2, 56),
(116, 2, 57),
(117, 2, 58),
(118, 2, 59),
(120, 2, 60),
(225, 2, 61),
(226, 2, 62),
(164, 3, 9),
(160, 3, 10),
(162, 3, 11),
(161, 3, 12),
(163, 3, 13),
(135, 3, 14),
(131, 3, 15),
(133, 3, 16),
(132, 3, 17),
(130, 3, 18),
(134, 3, 19),
(129, 3, 20),
(125, 3, 21),
(127, 3, 22),
(126, 3, 23),
(128, 3, 24),
(154, 3, 25),
(150, 3, 26),
(152, 3, 27),
(151, 3, 28),
(153, 3, 29),
(167, 3, 30),
(165, 3, 31),
(166, 3, 32),
(143, 3, 33),
(140, 3, 34),
(142, 3, 35),
(141, 3, 36),
(147, 3, 37),
(146, 3, 38),
(149, 3, 39),
(148, 3, 40),
(139, 3, 41),
(136, 3, 42),
(138, 3, 43),
(137, 3, 44),
(171, 3, 45),
(168, 3, 46),
(170, 3, 47),
(169, 3, 48),
(122, 3, 49),
(121, 3, 50),
(124, 3, 51),
(123, 3, 52),
(145, 3, 53),
(144, 3, 54),
(157, 3, 55),
(158, 3, 56),
(155, 3, 57),
(156, 3, 58),
(159, 3, 60),
(219, 4, 9),
(215, 4, 10),
(217, 4, 11),
(216, 4, 12),
(218, 4, 13),
(186, 4, 14),
(182, 4, 15),
(184, 4, 16),
(183, 4, 17),
(181, 4, 18),
(185, 4, 19),
(180, 4, 20),
(176, 4, 21),
(178, 4, 22),
(177, 4, 23),
(179, 4, 24),
(209, 4, 25),
(205, 4, 26),
(207, 4, 27),
(206, 4, 28),
(208, 4, 29),
(222, 4, 30),
(220, 4, 31),
(221, 4, 32),
(194, 4, 33),
(191, 4, 34),
(193, 4, 35),
(192, 4, 36),
(202, 4, 37),
(201, 4, 38),
(204, 4, 39),
(203, 4, 40),
(190, 4, 41),
(187, 4, 42),
(189, 4, 43),
(188, 4, 44),
(198, 4, 45),
(195, 4, 46),
(197, 4, 47),
(196, 4, 48),
(173, 4, 49),
(172, 4, 50),
(175, 4, 51),
(174, 4, 52),
(200, 4, 53),
(199, 4, 54),
(212, 4, 55),
(213, 4, 56),
(210, 4, 57),
(211, 4, 58),
(214, 4, 60);

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sub_cost_center_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sale_type` varchar(20) NOT NULL DEFAULT 'credit',
  `invoice_no` varchar(20) NOT NULL,
  `billing_date` date NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'posted',
  `inter_company_transfer` tinyint(1) NOT NULL DEFAULT 0,
  `inter_company_target_company_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inter_company_target_company_ids`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `company_id`, `party_id`, `cost_center_id`, `sub_cost_center_id`, `sale_type`, `invoice_no`, `billing_date`, `reference_no`, `phone`, `billing_address`, `shipping_address`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `notes`, `terms`, `attachment`, `status`, `inter_company_transfer`, `inter_company_target_company_ids`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 1, 1, NULL, NULL, 'credit', '00000001', '2026-05-22', 'uhsfjhe88', '9818762004', NULL, NULL, 5200.00, 219.99, 520.00, 5500.01, NULL, NULL, NULL, 'posted', 0, NULL, 25, '2026-05-22 03:40:09', '2026-05-22 03:40:09', NULL),
(3, 1, 1, NULL, NULL, 'credit', '00000002', '2026-05-23', 'uhsfjhe88', '9999900000', NULL, NULL, 2600.00, 100.00, 260.00, 2760.00, NULL, NULL, NULL, 'posted', 0, NULL, 22, '2026-05-23 00:55:30', '2026-05-23 00:55:30', NULL),
(4, 1, 1, NULL, NULL, 'credit', '00000003', '2026-05-25', NULL, NULL, NULL, NULL, 5200.00, 0.00, 520.00, 5720.00, NULL, NULL, NULL, 'posted', 0, NULL, 22, '2026-05-25 03:07:25', '2026-05-25 03:07:25', NULL),
(6, 1, 1, NULL, NULL, 'credit', '00000004', '2026-05-28', NULL, NULL, NULL, NULL, 2600.00, 0.00, 260.00, 2860.00, NULL, NULL, NULL, 'posted', 1, '[2]', 25, '2026-05-28 02:48:50', '2026-05-28 04:44:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_items`
--

CREATE TABLE `sales_invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `selected_units` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_units`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_invoice_items`
--

INSERT INTO `sales_invoice_items` (`id`, `sales_invoice_id`, `item_id`, `description`, `quantity`, `unit`, `unit_price`, `discount_type`, `discount_value`, `discount_amount`, `tax_percent`, `tax_amount`, `line_total`, `selected_units`, `created_at`, `updated_at`) VALUES
(2, 2, 4, 'sales', 2.000, 'PCS', 2600.00, 'percent', 0.00, 0.00, 10.00, 520.00, 5720.00, NULL, '2026-05-22 03:40:09', '2026-05-22 03:40:09'),
(3, 3, 4, 'sale', 1.000, 'PCS', 2600.00, 'percent', 0.00, 0.00, 10.00, 260.00, 2860.00, NULL, '2026-05-23 00:55:30', '2026-05-23 00:55:30'),
(4, 4, 4, 'sales', 2.000, 'PCS', 2600.00, 'percent', 0.00, 0.00, 10.00, 520.00, 5720.00, '[{\"buyer_id\":\"1\",\"buyer_code\":\"BUY-00001\",\"serial_no\":\"SN-MAY2026-0001\",\"batch_no\":\"MAY2026-3KW3\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"4-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00004\",\"production_date\":\"2026-05-25\",\"cost_per_unit\":400,\"sold\":false},{\"serial_no\":\"SN-MAY2026-0010\",\"batch_no\":\"MAY2026-Q4TD\",\"sale_price\":\"2600\",\"gst\":\"10\",\"warehouse\":\"kamla market\",\"notes\":null,\"key\":\"3-9\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00003\",\"production_date\":\"2026-05-23\",\"cost_per_unit\":400,\"sold\":false}]', '2026-05-25 03:07:25', '2026-05-25 03:07:25'),
(7, 6, 4, NULL, 1.000, 'PCS', 2600.00, 'percent', 0.00, 0.00, 10.00, 260.00, 2860.00, '[{\"buyer_code\":\"BC-AUTO-001\",\"buyer_id\":null,\"serial_no\":null,\"batch_no\":\"MAY2026-BGSD\",\"vts_sim\":\"234325345345\",\"sale_price\":\"2600\",\"gst\":\"10\",\"sale_mode\":\"exclusive\",\"warehouse\":null,\"notes\":null,\"key\":\"1-0\",\"item_id\":4,\"item_name\":\"GPS 3x PRO\",\"production_batch_no\":\"PB-00001\",\"production_date\":\"2026-05-22\",\"cost_per_unit\":400,\"sold\":false}]', '2026-05-28 04:44:55', '2026-05-28 04:44:55');

-- --------------------------------------------------------

--
-- Table structure for table `sales_returns`
--

CREATE TABLE `sales_returns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `sales_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `return_no` varchar(30) NOT NULL,
  `return_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'posted',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_items`
--

CREATE TABLE `sales_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_return_id` bigint(20) UNSIGNED NOT NULL,
  `sales_invoice_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `selected_units` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_units`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('5p6ehbmhbIJOfkWCZV9mQdxDtA0y9tQxproDY1Y4', 22, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibzNuekFYTGZkYU1ybVVEWlhTUkpiVVBjaFQ1akFhc0pMZ3ZtYmFkMiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9wcm9kdWN0aW9uLWJhdGNoZXMiO3M6NToicm91dGUiO3M6MzA6ImFkbWluLnByb2R1Y3Rpb24tYmF0Y2hlcy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjIyO30=', 1780455654),
('xzSO7NdczJ1lPWAxjdbZBWaAwiWyh7IBt5mw0yo8', 25, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSGdWbWRnZnJyb0xIWFphVkFpZVM3cHN4aElwR3FUQWpNM2NpSEc2WiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9zdG9ja3MiO3M6NToicm91dGUiO3M6MTg6ImFkbWluLnN0b2Nrcy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI1O30=', 1781510684);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `movement_date` date NOT NULL,
  `movement_type` varchar(40) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stock_after` decimal(15,3) NOT NULL DEFAULT 0.000,
  `value_after` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `company_id`, `item_id`, `party_id`, `movement_date`, `movement_type`, `direction`, `quantity`, `unit_price`, `total_value`, `stock_after`, `value_after`, `reference_type`, `reference_id`, `reference_no`, `description`, `created_at`, `updated_at`, `created_by`) VALUES
(2, 1, 2, NULL, '2026-05-22', 'opening_stock', 'in', 100.000, 100.00, 10000.00, 100.000, 10000.00, 'App\\Models\\Item', 2, 'ITM-00001', 'Opening stock from item master.', '2026-05-22 02:30:27', '2026-05-22 02:30:27', 25),
(4, 1, 3, 1, '2026-05-22', 'purchase', 'in', 100.000, 100.00, 10890.00, 100.000, 10890.00, 'App\\Models\\PurchaseBill', 2, '00000001', 'Purchase stock in.', '2026-05-22 03:22:45', '2026-05-22 03:22:45', 25),
(5, 1, 3, NULL, '2026-05-22', 'production_consumption', 'out', 20.000, 100.00, 2000.00, 80.000, 8890.00, NULL, NULL, 'PB-00001', 'Consumed for production of GPS 3x PRO', '2026-05-22 03:33:46', '2026-05-22 03:33:46', 25),
(6, 1, 4, NULL, '2026-05-22', 'production_output', 'in', 5.000, 400.00, 2000.00, 5.000, 2000.00, 'App\\Models\\ProductionBatch', 1, 'PB-00001', 'Finished goods produced — PB-00001', '2026-05-22 03:33:46', '2026-05-22 03:33:46', 25),
(8, 1, 4, 1, '2026-05-22', 'sale', 'out', 2.000, 600.00, 1200.00, 3.000, 800.00, 'App\\Models\\SalesInvoice', 2, '00000001', 'Sales stock out.', '2026-05-22 03:40:09', '2026-05-22 03:40:09', 25),
(9, 1, 4, NULL, '2026-05-22', 'transfer_out', 'out', 1.000, 2600.00, 2600.00, 2.000, 5200.00, 'App\\Models\\StockTransfer', 1, 'ST-000001', 'Transfer to Maruti Suzuki Venture', '2026-05-22 05:31:41', '2026-05-22 05:31:41', 23),
(10, 2, 5, NULL, '2026-05-22', 'transfer_in', 'in', 1.000, 2600.00, 2600.00, 1.000, 2600.00, 'App\\Models\\StockTransfer', 1, 'ST-000001', 'Transfer from Eemotrack India', '2026-05-22 05:31:41', '2026-05-22 05:31:41', 23),
(11, 1, 3, NULL, '2026-05-22', 'production_consumption', 'out', 40.000, 100.00, 4000.00, 40.000, 4890.00, NULL, NULL, 'PB-00002', 'Consumed for production of GPS 3x PRO', '2026-05-22 06:35:49', '2026-05-22 06:35:49', 22),
(12, 1, 4, NULL, '2026-05-22', 'production_output', 'in', 10.000, 400.00, 4000.00, 12.000, 9200.00, 'App\\Models\\ProductionBatch', 2, 'PB-00002', 'Finished goods produced — PB-00002', '2026-05-22 06:35:49', '2026-05-22 06:35:49', 22),
(13, 1, 4, 1, '2026-05-23', 'sale', 'out', 1.000, 600.00, 600.00, 11.000, 8600.00, 'App\\Models\\SalesInvoice', 3, '00000002', 'Sales stock out.', '2026-05-23 00:55:30', '2026-05-23 00:55:30', 22),
(14, 1, 3, 1, '2026-05-23', 'purchase', 'in', 99.998, 100.00, 10999.78, 139.998, 15889.78, 'App\\Models\\PurchaseBill', 3, '00000002', 'Purchase stock in.', '2026-05-23 00:57:03', '2026-05-23 00:57:03', 22),
(15, 1, 3, NULL, '2026-05-23', 'production_consumption', 'out', 40.000, 100.00, 4000.00, 99.998, 11889.78, NULL, NULL, 'PB-00003', 'Consumed for production of GPS 3x PRO', '2026-05-23 01:00:49', '2026-05-23 01:00:49', 22),
(16, 1, 4, NULL, '2026-05-23', 'production_output', 'in', 10.000, 400.00, 4000.00, 21.000, 12600.00, 'App\\Models\\ProductionBatch', 3, 'PB-00003', 'Finished goods produced — PB-00003', '2026-05-23 01:00:49', '2026-05-23 01:00:49', 22),
(17, 1, 3, NULL, '2026-05-25', 'production_consumption', 'out', 4.000, 100.00, 400.00, 95.998, 11489.78, NULL, NULL, 'PB-00004', 'Consumed for production of GPS 3x PRO', '2026-05-25 03:05:42', '2026-05-25 03:05:42', 22),
(18, 1, 4, NULL, '2026-05-25', 'production_output', 'in', 1.000, 400.00, 400.00, 22.000, 13000.00, 'App\\Models\\ProductionBatch', 4, 'PB-00004', 'Finished goods produced — PB-00004', '2026-05-25 03:05:42', '2026-05-25 03:05:42', 22),
(19, 1, 4, 1, '2026-05-25', 'sale', 'out', 2.000, 600.00, 1200.00, 20.000, 11800.00, 'App\\Models\\SalesInvoice', 4, '00000003', 'Sales stock out.', '2026-05-25 03:07:25', '2026-05-25 03:07:25', 22),
(20, 1, 4, NULL, '2026-05-28', 'production_output_reversal', 'out', 5.000, 400.00, 2000.00, 15.000, 9800.00, 'App\\Models\\ProductionBatch', 1, 'PB-00001', 'Production output reversal before update.', '2026-05-28 02:48:17', '2026-05-28 02:48:17', 25),
(21, 1, 3, NULL, '2026-05-28', 'production_consumption_reversal', 'in', 20.000, 100.00, 2000.00, 115.998, 13489.78, 'App\\Models\\ProductionBatch', 1, 'PB-00001', 'Production raw material reversal before update.', '2026-05-28 02:48:17', '2026-05-28 02:48:17', 25),
(22, 1, 3, NULL, '2026-05-22', 'production_consumption', 'out', 20.000, 100.00, 2000.00, 95.998, 11489.78, 'App\\Models\\ProductionBatch', 1, 'PB-00001', 'Consumed for updated production of GPS 3x PRO', '2026-05-28 02:48:17', '2026-05-28 02:48:17', 25),
(23, 1, 4, NULL, '2026-05-22', 'production_output', 'in', 5.000, 400.00, 2000.00, 20.000, 11800.00, 'App\\Models\\ProductionBatch', 1, 'PB-00001', 'Finished goods updated - PB-00001', '2026-05-28 02:48:17', '2026-05-28 02:48:17', 25),
(24, 1, 4, NULL, '2026-05-28', 'sale', 'out', 1.000, 600.00, 600.00, 19.000, 11200.00, 'App\\Models\\SalesInvoice', 6, '00000004', 'Sales stock out.', '2026-05-28 02:48:50', '2026-05-28 02:48:50', 25),
(25, 2, 5, 2, '2026-05-28', 'inter_company_purchase', 'in', 1.000, 2600.00, 2860.00, 2.000, 5460.00, 'App\\Models\\PurchaseBill', 4, 'IC-00000004', 'Auto purchase stock in from inter-company sale.', '2026-05-28 02:48:50', '2026-05-28 02:48:50', 25),
(26, 1, 4, NULL, '2026-05-28', 'sale_reversal', 'in', 1.000, 600.00, 600.00, 20.000, 11800.00, 'App\\Models\\SalesInvoice', 6, '00000004', 'Sales stock reversal before update.', '2026-05-28 02:49:11', '2026-05-28 02:49:11', 25),
(27, 1, 4, 1, '2026-05-28', 'sale', 'out', 1.000, 600.00, 600.00, 19.000, 11200.00, 'App\\Models\\SalesInvoice', 6, '00000004', 'Sales stock out.', '2026-05-28 02:49:11', '2026-05-28 02:49:11', 25),
(28, 1, 4, 1, '2026-05-28', 'sale_reversal', 'in', 1.000, 600.00, 600.00, 20.000, 11800.00, 'App\\Models\\SalesInvoice', 6, '00000004', 'Sales stock reversal before update.', '2026-05-28 04:44:55', '2026-05-28 04:44:55', 25),
(29, 1, 4, 1, '2026-05-28', 'sale', 'out', 1.000, 600.00, 600.00, 19.000, 11200.00, 'App\\Models\\SalesInvoice', 6, '00000004', 'Sales stock out.', '2026-05-28 04:44:55', '2026-05-28 04:44:55', 25),
(30, 2, 5, 2, '2026-05-28', 'inter_company_purchase_reversal', 'out', 1.000, 2600.00, 2860.00, 1.000, 2600.00, 'App\\Models\\PurchaseBill', 4, 'IC-00000004', 'Auto purchase reversal before source sale update.', '2026-05-28 04:44:55', '2026-05-28 04:44:55', 25),
(31, 2, 5, 2, '2026-05-28', 'inter_company_purchase', 'in', 1.000, 2600.00, 2860.00, 2.000, 5460.00, 'App\\Models\\PurchaseBill', 4, 'IC-00000004', 'Auto purchase stock in from inter-company sale.', '2026-05-28 04:44:55', '2026-05-28 04:44:55', 25),
(32, 1, 3, NULL, '2026-05-28', 'production_consumption', 'out', 4.000, 100.00, 400.00, 91.998, 11089.78, NULL, NULL, 'PB-00005', 'Consumed for production of GPS 3x PRO', '2026-05-28 04:57:38', '2026-05-28 04:57:38', 25),
(33, 1, 4, NULL, '2026-05-28', 'production_output', 'in', 1.000, 400.00, 400.00, 20.000, 11600.00, 'App\\Models\\ProductionBatch', 5, 'PB-00005', 'Finished goods produced — PB-00005', '2026-05-28 04:57:38', '2026-05-28 04:57:38', 25);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_company_id` bigint(20) UNSIGNED NOT NULL,
  `to_company_id` bigint(20) UNSIGNED NOT NULL,
  `transfer_no` varchar(255) NOT NULL,
  `transfer_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transfers`
--

INSERT INTO `stock_transfers` (`id`, `from_company_id`, `to_company_id`, `transfer_no`, `transfer_date`, `notes`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 2, 'ST-000001', '2026-05-22', NULL, 'approved', 23, '2026-05-22 05:31:41', NULL, 25, '2026-05-22 05:21:37', '2026-05-22 05:31:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stock_transfer_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `stock_before` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transfer_items`
--

INSERT INTO `stock_transfer_items` (`id`, `stock_transfer_id`, `item_id`, `quantity`, `stock_before`, `unit_price`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 1.000, 3.000, 2600.00, '2026-05-22 05:21:37', '2026-05-22 05:21:37');

-- --------------------------------------------------------

--
-- Table structure for table `sub_cost_centers`
--

CREATE TABLE `sub_cost_centers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `cost_center_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `budget_amount` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terms_templates`
--

CREATE TABLE `terms_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `document_type` varchar(30) NOT NULL DEFAULT 'all',
  `content` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('super_admin','admin','user') NOT NULL DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT NULL,
  `background_pic` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `current_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `screen_pin` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `user_type`, `profile_pic`, `background_pic`, `phone`, `address`, `facebook`, `twitter`, `linkedin`, `instagram`, `website`, `current_company_id`, `is_active`, `email_verified_at`, `password`, `screen_pin`, `remember_token`, `created_at`, `updated_at`) VALUES
(21, 'Super Administrator', 'superadmin@gmail.com', 'super_admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '$2y$12$SmuHc8Ef9ogt7tEYUpCJHegM4CAw1TG1oMGfyMvO6XKhcrr1jYL6i', NULL, NULL, '2026-05-22 00:52:55', '2026-05-22 00:52:55'),
(22, 'Ravi Kumar', 'admin@admin.com', 'admin', 'profiles/lZ5XkUaZ3YIaPHBzX6Blp74V2elSk9FisAC8L6li.png', 'backgrounds/IkmnicenIgqRGPxDHA3SCXVhZYlWHxjJ9CBKGcJD.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, '$2y$12$T3zRWlVEXbfKtYiuaX3BdeklAsmWGl6y5OY2z6VgnSLnb8yHeL7nS', NULL, NULL, '2026-05-22 01:28:54', '2026-05-22 06:22:25'),
(23, 'Maruti Suzuki', 'admin@suzuki.com', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, NULL, '$2y$12$XegUmDvb2//oXarU1rHiiefkwWyRowRtQMgcSQskZAE.7J.PjN8Ri', NULL, NULL, '2026-05-22 01:32:48', '2026-05-22 01:32:48'),
(24, 'Simaran', 'simran@eemot.com', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, '$2y$12$c6WjgOSdbsKLovYneGWIkOCF6ubOCAdEWx9XhRkR2DpPGbKj6dLT6', NULL, NULL, '2026-05-22 02:12:55', '2026-05-22 02:12:55'),
(25, 'Sanket Kumar', 'sanket@eemot.com', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, '$2y$12$Z5YF4MmZv.p.WLRzkNH9q.LTns9tJTE6rTXVPyWJrzj.8LdRFVJPy', '$2y$12$fzXbZsJDlzsLNiQi6Sl9JeB1Y1laPu/x.LgUxqkhQM0pOcHtfi2Zq', NULL, '2026-05-22 02:13:22', '2026-05-30 02:25:08'),
(26, 'Sanket Kumar', 'sanket@msv.com', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, NULL, '$2y$12$Or2Uy9mSInkxXEC.YADunuLlDo2aqH.xTW4VhfPammM2Qmi1anlPi', NULL, NULL, '2026-05-28 04:44:02', '2026-05-28 04:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_companies`
--

CREATE TABLE `user_companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_companies`
--

INSERT INTO `user_companies` (`id`, `user_id`, `company_id`, `created_at`, `updated_at`) VALUES
(1, 22, 1, '2026-05-22 01:28:54', '2026-05-22 01:28:54'),
(2, 23, 2, '2026-05-22 01:32:48', '2026-05-22 01:32:48'),
(3, 24, 1, '2026-05-22 02:12:55', '2026-05-22 02:12:55'),
(4, 25, 1, '2026-05-22 02:13:22', '2026-05-22 02:13:22'),
(5, 26, 2, '2026-05-28 04:44:02', '2026-05-28 04:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `company_id`, `created_at`, `updated_at`) VALUES
(1, 22, 1, 1, '2026-05-22 01:28:54', '2026-05-22 01:28:54'),
(2, 23, 2, 2, '2026-05-22 01:32:48', '2026-05-22 01:32:48'),
(3, 24, 3, 1, '2026-05-22 02:12:55', '2026-05-22 02:12:55'),
(4, 25, 3, 1, '2026-05-22 02:13:22', '2026-05-22 02:13:22'),
(5, 26, 4, 2, '2026-05-28 04:44:02', '2026-05-28 04:44:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_company_id_user_id_index` (`company_id`,`user_id`),
  ADD KEY `audit_logs_model_model_id_index` (`model`,`model_id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bank_accounts_company_id_account_code_unique` (`company_id`,`account_code`),
  ADD KEY `bank_accounts_created_by_foreign` (`created_by`),
  ADD KEY `bank_accounts_updated_by_foreign` (`updated_by`),
  ADD KEY `bank_accounts_company_id_account_type_status_index` (`company_id`,`account_type`,`status`),
  ADD KEY `bank_accounts_company_id_print_on_invoice_index` (`company_id`,`print_on_invoice`);

--
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_transactions_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `bank_transactions_related_bank_account_id_foreign` (`related_bank_account_id`),
  ADD KEY `bank_transactions_party_id_foreign` (`party_id`),
  ADD KEY `bank_transactions_created_by_foreign` (`created_by`),
  ADD KEY `bank_txn_account_date_idx` (`company_id`,`bank_account_id`,`transaction_date`),
  ADD KEY `bank_txn_type_idx` (`company_id`,`transaction_type`),
  ADD KEY `bank_transactions_transfer_group_index` (`transfer_group`);

--
-- Indexes for table `buyers`
--
ALTER TABLE `buyers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `buyers_company_id_buyer_code_unique` (`company_id`,`buyer_code`),
  ADD KEY `buyers_created_by_foreign` (`created_by`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_merges`
--
ALTER TABLE `company_merges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_merges_company_id_merged_with_company_id_unique` (`company_id`,`merged_with_company_id`),
  ADD KEY `company_merges_merged_with_company_id_foreign` (`merged_with_company_id`),
  ADD KEY `company_merges_created_by_foreign` (`created_by`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cost_centers_company_id_code_unique` (`company_id`,`code`),
  ADD KEY `cost_centers_created_by_foreign` (`created_by`),
  ADD KEY `cost_centers_company_id_status_index` (`company_id`,`status`);

--
-- Indexes for table `delivery_challans`
--
ALTER TABLE `delivery_challans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_challans_company_id_challan_no_unique` (`company_id`,`challan_no`),
  ADD KEY `delivery_challans_party_id_foreign` (`party_id`),
  ADD KEY `delivery_challans_cost_center_id_foreign` (`cost_center_id`),
  ADD KEY `delivery_challans_sub_cost_center_id_foreign` (`sub_cost_center_id`),
  ADD KEY `delivery_challans_created_by_foreign` (`created_by`),
  ADD KEY `challan_company_status_idx` (`company_id`,`status`);

--
-- Indexes for table `delivery_challan_items`
--
ALTER TABLE `delivery_challan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_challan_items_delivery_challan_id_foreign` (`delivery_challan_id`),
  ADD KEY `delivery_challan_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `entry_visibilities`
--
ALTER TABLE `entry_visibilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_visibilities_entry_type_entry_id_unique` (`entry_type`,`entry_id`),
  ADD KEY `entry_visibilities_company_id_foreign` (`company_id`);

--
-- Indexes for table `estimates`
--
ALTER TABLE `estimates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estimates_company_id_estimate_no_unique` (`company_id`,`estimate_no`),
  ADD KEY `estimates_party_id_foreign` (`party_id`),
  ADD KEY `estimates_cost_center_id_foreign` (`cost_center_id`),
  ADD KEY `estimates_sub_cost_center_id_foreign` (`sub_cost_center_id`),
  ADD KEY `estimates_converted_sales_invoice_id_foreign` (`converted_sales_invoice_id`),
  ADD KEY `estimates_created_by_foreign` (`created_by`),
  ADD KEY `estimate_company_status_idx` (`company_id`,`status`);

--
-- Indexes for table `estimate_items`
--
ALTER TABLE `estimate_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estimate_items_estimate_id_foreign` (`estimate_id`),
  ADD KEY `estimate_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expenses_company_id_expense_no_unique` (`company_id`,`expense_no`),
  ADD KEY `expenses_expense_ledger_id_foreign` (`expense_ledger_id`),
  ADD KEY `expenses_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `expenses_approved_by_foreign` (`approved_by`),
  ADD KEY `expenses_rejected_by_foreign` (`rejected_by`),
  ADD KEY `expenses_created_by_foreign` (`created_by`),
  ADD KEY `expenses_updated_by_foreign` (`updated_by`),
  ADD KEY `expenses_company_id_status_expense_date_index` (`company_id`,`status`,`expense_date`);

--
-- Indexes for table `expense_ledgers`
--
ALTER TABLE `expense_ledgers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_ledgers_company_id_ledger_code_unique` (`company_id`,`ledger_code`),
  ADD KEY `expense_ledgers_created_by_foreign` (`created_by`),
  ADD KEY `expense_ledgers_updated_by_foreign` (`updated_by`),
  ADD KEY `expense_ledgers_company_id_status_index` (`company_id`,`status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `items_company_id_item_code_unique` (`company_id`,`item_code`),
  ADD KEY `items_product_type_id_foreign` (`product_type_id`),
  ADD KEY `items_created_by_foreign` (`created_by`),
  ADD KEY `items_company_id_item_type_status_index` (`company_id`,`item_type`,`status`);

--
-- Indexes for table `item_boms`
--
ALTER TABLE `item_boms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_boms_finished_item_id_raw_item_id_unique` (`finished_item_id`,`raw_item_id`),
  ADD KEY `item_boms_company_id_foreign` (`company_id`),
  ADD KEY `item_boms_raw_item_id_foreign` (`raw_item_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parties_company_id_party_code_unique` (`company_id`,`party_code`),
  ADD KEY `parties_created_by_foreign` (`created_by`),
  ADD KEY `parties_updated_by_foreign` (`updated_by`),
  ADD KEY `parties_company_id_party_type_status_index` (`company_id`,`party_type`,`status`),
  ADD KEY `parties_company_id_display_name_index` (`company_id`,`display_name`);

--
-- Indexes for table `party_ledgers`
--
ALTER TABLE `party_ledgers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_ledgers_party_id_foreign` (`party_id`),
  ADD KEY `party_ledgers_created_by_foreign` (`created_by`),
  ADD KEY `party_ledgers_company_id_party_id_entry_date_index` (`company_id`,`party_id`,`entry_date`),
  ADD KEY `party_ledgers_reference_type_reference_id_index` (`reference_type`,`reference_id`);

--
-- Indexes for table `party_payments`
--
ALTER TABLE `party_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_payments_party_id_foreign` (`party_id`),
  ADD KEY `party_payments_bank_account_id_foreign` (`bank_account_id`),
  ADD KEY `party_payments_created_by_foreign` (`created_by`),
  ADD KEY `party_payments_company_id_party_id_payment_date_index` (`company_id`,`party_id`,`payment_date`),
  ADD KEY `party_payments_company_id_payment_type_index` (`company_id`,`payment_type`);

--
-- Indexes for table `party_payment_allocations`
--
ALTER TABLE `party_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_payment_allocations_party_payment_id_foreign` (`party_payment_id`),
  ADD KEY `party_payment_allocations_party_id_foreign` (`party_id`),
  ADD KEY `party_payment_allocations_company_id_party_id_bill_type_index` (`company_id`,`party_id`,`bill_type`),
  ADD KEY `party_payment_allocations_bill_model_bill_id_index` (`bill_model`,`bill_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`);

--
-- Indexes for table `production_batches`
--
ALTER TABLE `production_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_batches_company_id_batch_no_unique` (`company_id`,`batch_no`),
  ADD KEY `production_batches_finished_item_id_foreign` (`finished_item_id`),
  ADD KEY `production_batches_created_by_foreign` (`created_by`);

--
-- Indexes for table `product_types`
--
ALTER TABLE `product_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_types_company_id_code_unique` (`company_id`,`code`),
  ADD KEY `product_types_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_bills`
--
ALTER TABLE `purchase_bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_bills_company_id_invoice_no_unique` (`company_id`,`invoice_no`),
  ADD KEY `purchase_bills_party_id_foreign` (`party_id`),
  ADD KEY `purchase_bills_cost_center_id_foreign` (`cost_center_id`),
  ADD KEY `purchase_bills_sub_cost_center_id_foreign` (`sub_cost_center_id`),
  ADD KEY `purchase_bills_created_by_foreign` (`created_by`),
  ADD KEY `purchase_bills_source_sales_invoice_id_foreign` (`source_sales_invoice_id`),
  ADD KEY `purchase_bills_inter_company_source_company_id_foreign` (`inter_company_source_company_id`);

--
-- Indexes for table `purchase_bill_items`
--
ALTER TABLE `purchase_bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_bill_items_purchase_bill_id_foreign` (`purchase_bill_id`),
  ADD KEY `purchase_bill_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_returns_company_id_return_no_unique` (`company_id`,`return_no`),
  ADD KEY `purchase_returns_purchase_bill_id_foreign` (`purchase_bill_id`),
  ADD KEY `purchase_returns_party_id_foreign` (`party_id`),
  ADD KEY `purchase_returns_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_return_items_purchase_return_id_foreign` (`purchase_return_id`),
  ADD KEY `purchase_return_items_purchase_bill_item_id_foreign` (`purchase_bill_item_id`),
  ADD KEY `purchase_return_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `roles_company_id_foreign` (`company_id`);

--
-- Indexes for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permission_role_id_permission_id_unique` (`role_id`,`permission_id`),
  ADD KEY `role_permission_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_invoices_company_id_invoice_no_unique` (`company_id`,`invoice_no`),
  ADD KEY `sales_invoices_party_id_foreign` (`party_id`),
  ADD KEY `sales_invoices_cost_center_id_foreign` (`cost_center_id`),
  ADD KEY `sales_invoices_sub_cost_center_id_foreign` (`sub_cost_center_id`),
  ADD KEY `sales_invoices_created_by_foreign` (`created_by`);

--
-- Indexes for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_invoice_items_sales_invoice_id_foreign` (`sales_invoice_id`),
  ADD KEY `sales_invoice_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sales_returns_company_id_return_no_unique` (`company_id`,`return_no`),
  ADD KEY `sales_returns_sales_invoice_id_foreign` (`sales_invoice_id`),
  ADD KEY `sales_returns_party_id_foreign` (`party_id`),
  ADD KEY `sales_returns_created_by_foreign` (`created_by`);

--
-- Indexes for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_return_items_sales_return_id_foreign` (`sales_return_id`),
  ADD KEY `sales_return_items_sales_invoice_item_id_foreign` (`sales_invoice_item_id`),
  ADD KEY `sales_return_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_item_id_foreign` (`item_id`),
  ADD KEY `stock_movements_party_id_foreign` (`party_id`),
  ADD KEY `stock_movements_company_id_item_id_movement_date_index` (`company_id`,`item_id`,`movement_date`),
  ADD KEY `stock_movements_created_by_foreign` (`created_by`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_transfers_transfer_no_unique` (`transfer_no`),
  ADD KEY `stock_transfers_from_company_id_foreign` (`from_company_id`),
  ADD KEY `stock_transfers_to_company_id_foreign` (`to_company_id`),
  ADD KEY `stock_transfers_approved_by_foreign` (`approved_by`),
  ADD KEY `stock_transfers_created_by_foreign` (`created_by`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  ADD KEY `stock_transfer_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `sub_cost_centers`
--
ALTER TABLE `sub_cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_cost_centers_company_id_code_unique` (`company_id`,`code`),
  ADD KEY `sub_cost_centers_cost_center_id_foreign` (`cost_center_id`),
  ADD KEY `sub_cost_centers_created_by_foreign` (`created_by`),
  ADD KEY `sub_cost_centers_company_id_cost_center_id_status_index` (`company_id`,`cost_center_id`,`status`);

--
-- Indexes for table `terms_templates`
--
ALTER TABLE `terms_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `terms_templates_created_by_foreign` (`created_by`),
  ADD KEY `terms_templates_company_id_document_type_status_index` (`company_id`,`document_type`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_companies`
--
ALTER TABLE `user_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_companies_user_id_company_id_unique` (`user_id`,`company_id`),
  ADD KEY `user_companies_company_id_foreign` (`company_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_roles_user_id_foreign` (`user_id`),
  ADD KEY `user_roles_role_id_foreign` (`role_id`),
  ADD KEY `user_roles_company_id_foreign` (`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `buyers`
--
ALTER TABLE `buyers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_merges`
--
ALTER TABLE `company_merges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_challans`
--
ALTER TABLE `delivery_challans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery_challan_items`
--
ALTER TABLE `delivery_challan_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `entry_visibilities`
--
ALTER TABLE `entry_visibilities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `estimates`
--
ALTER TABLE `estimates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `estimate_items`
--
ALTER TABLE `estimate_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expense_ledgers`
--
ALTER TABLE `expense_ledgers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `item_boms`
--
ALTER TABLE `item_boms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `party_ledgers`
--
ALTER TABLE `party_ledgers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `party_payments`
--
ALTER TABLE `party_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `party_payment_allocations`
--
ALTER TABLE `party_payment_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `production_batches`
--
ALTER TABLE `production_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_types`
--
ALTER TABLE `product_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_bills`
--
ALTER TABLE `purchase_bills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_bill_items`
--
ALTER TABLE `purchase_bill_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permission`
--
ALTER TABLE `role_permission`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=227;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales_returns`
--
ALTER TABLE `sales_returns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sub_cost_centers`
--
ALTER TABLE `sub_cost_centers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terms_templates`
--
ALTER TABLE `terms_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_companies`
--
ALTER TABLE `user_companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bank_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `bank_transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bank_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bank_transactions_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bank_transactions_related_bank_account_id_foreign` FOREIGN KEY (`related_bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buyers`
--
ALTER TABLE `buyers`
  ADD CONSTRAINT `buyers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `buyers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `company_merges`
--
ALTER TABLE `company_merges`
  ADD CONSTRAINT `company_merges_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_merges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `company_merges_merged_with_company_id_foreign` FOREIGN KEY (`merged_with_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD CONSTRAINT `cost_centers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cost_centers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `delivery_challans`
--
ALTER TABLE `delivery_challans`
  ADD CONSTRAINT `delivery_challans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_challans_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_challans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_challans_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_challans_sub_cost_center_id_foreign` FOREIGN KEY (`sub_cost_center_id`) REFERENCES `sub_cost_centers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `delivery_challan_items`
--
ALTER TABLE `delivery_challan_items`
  ADD CONSTRAINT `delivery_challan_items_delivery_challan_id_foreign` FOREIGN KEY (`delivery_challan_id`) REFERENCES `delivery_challans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_challan_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `entry_visibilities`
--
ALTER TABLE `entry_visibilities`
  ADD CONSTRAINT `entry_visibilities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `estimates`
--
ALTER TABLE `estimates`
  ADD CONSTRAINT `estimates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estimates_converted_sales_invoice_id_foreign` FOREIGN KEY (`converted_sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estimates_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estimates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estimates_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estimates_sub_cost_center_id_foreign` FOREIGN KEY (`sub_cost_center_id`) REFERENCES `sub_cost_centers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `estimate_items`
--
ALTER TABLE `estimate_items`
  ADD CONSTRAINT `estimate_items_estimate_id_foreign` FOREIGN KEY (`estimate_id`) REFERENCES `estimates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `estimate_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_expense_ledger_id_foreign` FOREIGN KEY (`expense_ledger_id`) REFERENCES `expense_ledgers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_ledgers`
--
ALTER TABLE `expense_ledgers`
  ADD CONSTRAINT `expense_ledgers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expense_ledgers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expense_ledgers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `items_product_type_id_foreign` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `item_boms`
--
ALTER TABLE `item_boms`
  ADD CONSTRAINT `item_boms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_boms_finished_item_id_foreign` FOREIGN KEY (`finished_item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_boms_raw_item_id_foreign` FOREIGN KEY (`raw_item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parties`
--
ALTER TABLE `parties`
  ADD CONSTRAINT `parties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parties_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `parties_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `party_ledgers`
--
ALTER TABLE `party_ledgers`
  ADD CONSTRAINT `party_ledgers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `party_ledgers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `party_ledgers_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_payments`
--
ALTER TABLE `party_payments`
  ADD CONSTRAINT `party_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `party_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `party_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `party_payments_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_payment_allocations`
--
ALTER TABLE `party_payment_allocations`
  ADD CONSTRAINT `party_payment_allocations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `party_payment_allocations_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `party_payment_allocations_party_payment_id_foreign` FOREIGN KEY (`party_payment_id`) REFERENCES `party_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_batches`
--
ALTER TABLE `production_batches`
  ADD CONSTRAINT `production_batches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_batches_finished_item_id_foreign` FOREIGN KEY (`finished_item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_types`
--
ALTER TABLE `product_types`
  ADD CONSTRAINT `product_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_bills`
--
ALTER TABLE `purchase_bills`
  ADD CONSTRAINT `purchase_bills_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_bills_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_bills_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_bills_inter_company_source_company_id_foreign` FOREIGN KEY (`inter_company_source_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_bills_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_bills_source_sales_invoice_id_foreign` FOREIGN KEY (`source_sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_bills_sub_cost_center_id_foreign` FOREIGN KEY (`sub_cost_center_id`) REFERENCES `sub_cost_centers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_bill_items`
--
ALTER TABLE `purchase_bill_items`
  ADD CONSTRAINT `purchase_bill_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_bill_items_purchase_bill_id_foreign` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `purchase_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_returns_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_returns_purchase_bill_id_foreign` FOREIGN KEY (`purchase_bill_id`) REFERENCES `purchase_bills` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `purchase_return_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_return_items_purchase_bill_item_id_foreign` FOREIGN KEY (`purchase_bill_item_id`) REFERENCES `purchase_bill_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_return_items_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `role_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permission_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `sales_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_invoices_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_invoices_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_invoices_sub_cost_center_id_foreign` FOREIGN KEY (`sub_cost_center_id`) REFERENCES `sub_cost_centers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD CONSTRAINT `sales_invoice_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_invoice_items_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_returns`
--
ALTER TABLE `sales_returns`
  ADD CONSTRAINT `sales_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_returns_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_returns_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_return_items`
--
ALTER TABLE `sales_return_items`
  ADD CONSTRAINT `sales_return_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_return_items_sales_invoice_item_id_foreign` FOREIGN KEY (`sales_invoice_item_id`) REFERENCES `sales_invoice_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_return_items_sales_return_id_foreign` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_transfers_from_company_id_foreign` FOREIGN KEY (`from_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfers_to_company_id_foreign` FOREIGN KEY (`to_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_cost_centers`
--
ALTER TABLE `sub_cost_centers`
  ADD CONSTRAINT `sub_cost_centers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sub_cost_centers_cost_center_id_foreign` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sub_cost_centers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `terms_templates`
--
ALTER TABLE `terms_templates`
  ADD CONSTRAINT `terms_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `terms_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_companies`
--
ALTER TABLE `user_companies`
  ADD CONSTRAINT `user_companies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_companies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
