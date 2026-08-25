/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `application_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_releases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `release_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `release_id` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` enum('application','module','feature','hotfix') COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feature` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('uploaded','validated','installed','failed','rolled_back','superseded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploaded',
  `package_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_sha256` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_notes` text COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `installed_at` timestamp NULL DEFAULT NULL,
  `rolled_back_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `installed_by` bigint unsigned DEFAULT NULL,
  `rolled_back_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_releases_release_uuid_unique` (`release_uuid`),
  UNIQUE KEY `application_releases_release_id_unique` (`release_id`),
  KEY `release_scope_version_idx` (`scope`,`module`,`feature`,`version`),
  KEY `application_releases_module_index` (`module`),
  KEY `application_releases_feature_index` (`feature`),
  KEY `application_releases_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone1` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npwp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_sales_support` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ttd_sales_support` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_do` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ttd_do` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_inv` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ttd_inv` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ttd_inv2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_company_code_unique` (`company_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `document_address` text COLLATE utf8mb4_unicode_ci,
  `tax_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_quotation_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_quotation_phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_do_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_do_phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_invoice_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_invoice_phone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_order_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_order_details_delivery_order_id_foreign` (`delivery_order_id`),
  KEY `delivery_order_details_product_id_foreign` (`product_id`),
  CONSTRAINT `delivery_order_details_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_order_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `do_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_customer_id` bigint unsigned NOT NULL,
  `delivery_date` date NOT NULL,
  `shipping_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_invoice_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','partial','shipped','delivered') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `delivery_by` enum('Wisnu','JNE','Courier Online') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_orders_do_number_unique` (`do_number`),
  KEY `delivery_orders_po_customer_id_foreign` (`po_customer_id`),
  CONSTRAINT `delivery_orders_po_customer_id_foreign` FOREIGN KEY (`po_customer_id`) REFERENCES `po_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deployment_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deployment_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_release_id` bigint unsigned NOT NULL,
  `operation` enum('add','replace','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `relative_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sha256` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `backup_sha256` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installed` tinyint(1) NOT NULL DEFAULT '0',
  `installed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deployment_file_release_path_unique` (`application_release_id`,`relative_path`),
  KEY `deployment_files_application_release_id_index` (`application_release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deployment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deployment_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deployment_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_release_id` bigint unsigned NOT NULL,
  `action` enum('validate','install','rollback') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('started','success','failed','rolled_back') COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by` bigint unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `recovery_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `database_backup_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` longtext COLLATE utf8mb4_unicode_ci,
  `error_message` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deployment_history_deployment_uuid_unique` (`deployment_uuid`),
  KEY `deployment_history_release_idx` (`application_release_id`,`action`,`status`),
  KEY `deployment_history_application_release_id_index` (`application_release_id`),
  KEY `deployment_history_action_index` (`action`),
  KEY `deployment_history_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deployment_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deployment_migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_release_id` bigint unsigned NOT NULL,
  `migration_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relative_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sha256` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','executed','failed','rolled_back') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `executed_at` timestamp NULL DEFAULT NULL,
  `rolled_back_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deployment_migration_release_unique` (`application_release_id`,`migration_name`),
  KEY `deployment_migrations_application_release_id_index` (`application_release_id`),
  KEY `deployment_migrations_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_order_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','sent','delivered') COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_receipts_delivery_order_id_foreign` (`delivery_order_id`),
  CONSTRAINT `document_receipts_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dpp_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dpp_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dpp_no` varchar(50) DEFAULT NULL,
  `date_production` date DEFAULT NULL,
  `production_month` date DEFAULT NULL,
  `status` enum('Open','Process','Closed','Cancel') DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expense_date` date NOT NULL,
  `expense_doc_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_doc_date` date DEFAULT NULL,
  `period_month` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `project_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` enum('Daily','Weekly','Monthly','Quarterly','Semesterly','Annual','One Time') COLLATE utf8mb4_unicode_ci NOT NULL,
  `expense_type` enum('Operational','Maintenance','Project','Emergency','Subscription','CAPEX','OPEX') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_no` (`expense_no`,`expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_receipt_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipt_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` bigint unsigned NOT NULL,
  `po_supplier_detail_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity_received` int NOT NULL,
  `quantity_remaining` int NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_details_goods_receipt_id_foreign` (`goods_receipt_id`),
  KEY `goods_receipt_details_po_supplier_detail_id_foreign` (`po_supplier_detail_id`),
  KEY `goods_receipt_details_product_id_foreign` (`product_id`),
  CONSTRAINT `goods_receipt_details_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_receipt_details_po_supplier_detail_id_foreign` FOREIGN KEY (`po_supplier_detail_id`) REFERENCES `po_supplier_details` (`id`),
  CONSTRAINT `goods_receipt_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_supplier_id` bigint unsigned NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('partial','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'partial',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `received_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_receipts_receipt_number_unique` (`receipt_number`),
  KEY `goods_receipts_po_supplier_id_foreign` (`po_supplier_id`),
  CONSTRAINT `goods_receipts_po_supplier_id_foreign` FOREIGN KEY (`po_supplier_id`) REFERENCES `po_suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_customer_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_customer_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_customer_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_customer_details_invoice_customer_id_foreign` (`invoice_customer_id`),
  KEY `invoice_customer_details_product_id_foreign` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_customer_id` bigint unsigned NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `tax_invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_invoice_attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('sent','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `invoice_status` enum('issue yet','partial','cpmpleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issue yet',
  `subtotal` decimal(15,2) NOT NULL,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pph23_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `pph23_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL,
  `dp_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `add_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `dp_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_terms` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_time` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_invoice_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_customers_invoice_number_unique` (`invoice_number`),
  KEY `invoice_customers_po_customer_id_foreign` (`po_customer_id`),
  KEY `invoice_customers_parent_invoice_id_foreign` (`parent_invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_supplier_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_supplier_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_supplier_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_supplier_details_invoice_supplier_id_foreign` (`invoice_supplier_id`),
  KEY `invoice_supplier_details_product_id_foreign` (`product_id`),
  CONSTRAINT `invoice_supplier_details_invoice_supplier_id_foreign` FOREIGN KEY (`invoice_supplier_id`) REFERENCES `invoice_suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_supplier_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_supplier_id` bigint unsigned NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','paid','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `amount` decimal(15,2) NOT NULL,
  `tax` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_suppliers_invoice_number_unique` (`invoice_number`),
  KEY `invoice_suppliers_po_supplier_id_foreign` (`po_supplier_id`),
  CONSTRAINT `invoice_suppliers_po_supplier_id_foreign` FOREIGN KEY (`po_supplier_id`) REFERENCES `po_suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  KEY `permissions_module_index` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `po_customer_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po_customer_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_customer_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `po_customer_details_po_customer_id_foreign` (`po_customer_id`),
  KEY `po_customer_details_product_id_foreign` (`product_id`),
  CONSTRAINT `po_customer_details_po_customer_id_foreign` FOREIGN KEY (`po_customer_id`) REFERENCES `po_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `po_customer_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `po_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `quotation_id` bigint unsigned DEFAULT NULL,
  `payment_terms` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_time` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('received','proceed','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `procurement_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `invoice_status` enum('issue yet','partial','completed','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issue yet',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '11.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_customers_po_number_unique` (`po_number`),
  KEY `po_customers_customer_id_foreign` (`customer_id`),
  KEY `po_customers_quotation_id_foreign` (`quotation_id`),
  KEY `po_customers_procurement_status_idx` (`procurement_status`),
  CONSTRAINT `po_customers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `po_customers_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `po_supplier_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po_supplier_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_supplier_id` bigint unsigned NOT NULL,
  `po_customer_detail_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `po_supplier_details_po_supplier_id_foreign` (`po_supplier_id`),
  KEY `po_supplier_details_product_id_foreign` (`product_id`),
  KEY `psd_po_customer_detail_idx` (`po_customer_detail_id`),
  CONSTRAINT `po_supplier_details_po_supplier_id_foreign` FOREIGN KEY (`po_supplier_id`) REFERENCES `po_suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `po_supplier_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `psd_pcd_fk` FOREIGN KEY (`po_customer_detail_id`) REFERENCES `po_customer_details` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `po_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po_suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_supplier_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_customer_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `po_date` date NOT NULL,
  `status` enum('draft','sent','confirmed','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `receipt_status` enum('pending','partial','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(8,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '11.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_suppliers_po_supplier_number_unique` (`po_supplier_number`),
  KEY `po_suppliers_customer_id_foreign` (`customer_id`),
  KEY `po_suppliers_supplier_id_foreign` (`supplier_id`),
  KEY `po_suppliers_po_customer_id_foreign` (`po_customer_id`),
  CONSTRAINT `po_suppliers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `po_suppliers_po_customer_id_foreign` FOREIGN KEY (`po_customer_id`) REFERENCES `po_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `po_suppliers_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_code2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `name2` text COLLATE utf8mb4_unicode_ci,
  `specification` text COLLATE utf8mb4_unicode_ci,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_product_code_unique` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotation_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotation_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `specification` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_details_quotation_id_foreign` (`quotation_id`),
  KEY `quotation_details_product_id_foreign` (`product_id`),
  CONSTRAINT `quotation_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `quotation_details_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('draft','sent','approved','rec_po','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `add_cost` decimal(15,2) DEFAULT NULL,
  `dpp` decimal(15,2) DEFAULT NULL,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pph23_percent` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pph23_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `payment_terms` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_time` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_image_on_print` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_quotation_number_unique` (`quotation_number`),
  KEY `quotations_customer_id_foreign` (`customer_id`),
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_order_id` bigint unsigned NOT NULL,
  `received_date` date NOT NULL,
  `received_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipts_receipt_number_unique` (`receipt_number`),
  KEY `receipts_delivery_order_id_foreign` (`delivery_order_id`),
  CONSTRAINT `receipts_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `receipts_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts_detail` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipts_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipts_detail_receipts_id_foreign` (`receipts_id`),
  KEY `receipts_detail_product_id_foreign` (`product_id`),
  CONSTRAINT `receipts_detail_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `receipts_detail_receipts_id_foreign` FOREIGN KEY (`receipts_id`) REFERENCES `receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permission_unique` (`role`,`permission_id`),
  KEY `role_permissions_role_index` (`role`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `tax_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_manuals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_manuals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('modul','sub_modul','feature') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'modul',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_manuals_parent_id_foreign` (`parent_id`),
  CONSTRAINT `user_manuals_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `user_manuals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','manager','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_05_04_015956_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_05_04_020010_create_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_05_04_020016_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_05_04_020035_create_quotations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_05_04_020042_create_quotation_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_05_04_020048_create_po_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_05_04_020053_create_po_customer_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_05_04_020100_create_po_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_05_04_020107_create_po_supplier_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_05_04_020112_create_invoice_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_05_04_020119_create_invoice_supplier_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_05_04_020125_create_delivery_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_05_04_020130_create_delivery_order_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_05_04_020135_create_receipts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_05_04_020141_create_invoice_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_05_04_020146_create_invoice_customer_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_05_04_020446_create_receipts_detail_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_04_070700_add_image_to_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_04_073902_add_print_image_option_to_quotations_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_04_075037_add_discount_to_quotations_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_04_085750_create_companies_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_04_104703_add_pic_fields_to_customers_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_04_104742_add_brand_to_products_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_04_104806_add_payment_terms_to_quotations_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_04_140505_create_po_customers_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_05_04_140513_create_po_customer_details_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_05_05_045005_add_purchase_price_to_products_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_05_05_045034_create_po_suppliers_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_05_05_070004_create_po_supplier_details_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_05_06_041419_create_goods_receipts_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_05_06_041510_create_goods_receipt_details_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_05_06_041645_add_receipt_status_to_po_suppliers',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_05_21_044658_add_parent_invoice_id_to_invoice_customers_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_05_21_092605_add_role_to_users_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_05_25_002632_add_attachment_to_po_customers_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_05_25_005235_add_delivery_by_and_attachment_to_delivery_orders_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_05_25_013555_create_expenses_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_05_25_094833_add_payment_status_to_invoice_customers_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_05_29_010945_create_document_receipts_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_05_31_234225_update_po_suppliers_foreign_key_cascade',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_23_014116_add_procurement_lineage_to_po_supplier_details_and_po_customers',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_23_120000_canonicalize_po_customer_status',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_08_23_155029_add_discount_fields_to_po_suppliers_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_08_24_021057_create_permissions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_08_24_021057_create_role_permissions_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_08_24_021057_create_application_releases_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_08_24_021058_create_deployment_history_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_08_24_021058_create_deployment_files_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_08_24_021058_create_deployment_migrations_table',26);

-- BGS canonical baseline migration-ledger reconciliation.
-- Schema effects of these migrations are already represented above.
INSERT IGNORE INTO `migrations` (`id`, `migration`, `batch`) VALUES
(55, '2026_06_08_002409_create_user_manuals_table', 26),
(56, '2026_06_08_014218_update_user_manuals_table_for_hierarchy', 26),
(57, '2026_06_08_091022_add_tax_invoice_attachment_to_invoice_customers_table', 26),
(58, '2026_07_14_032436_create_expenses_table', 26);
