/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: myapp
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `address_project`
--

DROP TABLE IF EXISTS `address_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_project` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `address_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_project_address_id_project_id_unique` (`address_id`,`project_id`),
  KEY `address_project_project_id_foreign` (`project_id`),
  CONSTRAINT `address_project_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `address_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_project`
--

LOCK TABLES `address_project` WRITE;
/*!40000 ALTER TABLE `address_project` DISABLE KEYS */;
INSERT INTO `address_project` VALUES
(2,2,1,NULL,NULL),
(3,3,1,NULL,NULL);
/*!40000 ALTER TABLE `address_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `address_templates`
--

DROP TABLE IF EXISTS `address_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_address` text NOT NULL COMMENT 'Полный адрес',
  `location_type` varchar(255) NOT NULL COMMENT 'Тип локации',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Активен',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `address_templates_is_active_index` (`is_active`),
  KEY `address_templates_location_type_index` (`location_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address_templates`
--

LOCK TABLES `address_templates` WRITE;
/*!40000 ALTER TABLE `address_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `address_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `short_name` varchar(255) DEFAULT NULL,
  `full_address` text NOT NULL,
  `location_type` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES
(2,'asdfasdfasdf','Манежная плозадь дом 1',NULL,'2025-11-06 16:12:02','2025-11-06 16:17:13'),
(3,NULL,'Лубянка дом 12',NULL,'2025-11-06 16:12:25','2025-11-06 16:12:25');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_request_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_in_shift` enum('executor','brigadier') NOT NULL,
  `source` enum('dispatcher','initiator') NOT NULL,
  `planned_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `assignment_type` varchar(255) NOT NULL DEFAULT 'work_request' COMMENT 'brigadier_schedule, work_request, mass_personnel',
  `planned_start_time` time DEFAULT NULL COMMENT 'Планируемое время начала работы',
  `planned_duration_hours` decimal(4,1) DEFAULT NULL COMMENT 'Планируемая продолжительность смены',
  `assignment_comment` text DEFAULT NULL COMMENT 'Комментарий к назначению',
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, confirmed, rejected, completed',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `planned_address_id` bigint(20) unsigned DEFAULT NULL,
  `planned_custom_address` text DEFAULT NULL COMMENT 'Неофициальный адрес',
  `is_custom_planned_address` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Использовать неофициальный адрес',
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignment_unique_per_day` (`work_request_id`,`user_id`,`planned_date`,`role_in_shift`),
  KEY `assignments_user_id_planned_date_index` (`user_id`,`planned_date`),
  KEY `assignments_planned_address_id_foreign` (`planned_address_id`),
  KEY `assignments_shift_id_foreign` (`shift_id`),
  CONSTRAINT `assignments_planned_address_id_foreign` FOREIGN KEY (`planned_address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  CONSTRAINT `assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_work_request_id_foreign` FOREIGN KEY (`work_request_id`) REFERENCES `work_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brigadier_assignments`
--

DROP TABLE IF EXISTS `brigadier_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brigadier_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brigadier_id` bigint(20) unsigned NOT NULL,
  `initiator_id` bigint(20) unsigned NOT NULL,
  `assignment_date` date NOT NULL,
  `status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `planned_address_id` bigint(20) unsigned DEFAULT NULL,
  `planned_custom_address` text DEFAULT NULL,
  `is_custom_planned_address` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brigadier_assignments_brigadier_id_assignment_date_unique` (`brigadier_id`,`assignment_date`),
  KEY `brigadier_assignments_initiator_id_foreign` (`initiator_id`),
  KEY `brigadier_assignments_planned_address_id_index` (`planned_address_id`),
  CONSTRAINT `brigadier_assignments_brigadier_id_foreign` FOREIGN KEY (`brigadier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `brigadier_assignments_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `brigadier_assignments_planned_address_id_foreign` FOREIGN KEY (`planned_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brigadier_assignments`
--

LOCK TABLES `brigadier_assignments` WRITE;
/*!40000 ALTER TABLE `brigadier_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `brigadier_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES
('laravel-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:3:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";}s:11:\"permissions\";a:2:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:13:\"edit_database\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:14:\"view_addresses\";s:1:\"c\";s:3:\"web\";}}s:5:\"roles\";a:0:{}}',1763032122);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `prefix` varchar(10) DEFAULT NULL COMMENT 'Префикс для генерации номеров заявок (GARD, DECOR, etc.)',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Садовник','SAD','asdasdasdassdasdasdasd',1,'2025-11-06 15:59:34','2025-11-06 15:59:34');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compensations`
--

DROP TABLE IF EXISTS `compensations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compensations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `compensatable_type` varchar(255) NOT NULL,
  `compensatable_id` bigint(20) unsigned NOT NULL,
  `description` text NOT NULL COMMENT 'Описание компенсации',
  `requested_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Запрошенная сумма',
  `approved_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Утвержденная сумма',
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_notes` text DEFAULT NULL COMMENT 'Комментарии при утверждении',
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compensations_compensatable_type_compensatable_id_index` (`compensatable_type`,`compensatable_id`),
  KEY `compensations_approved_by_foreign` (`approved_by`),
  KEY `compensations_compensatable_type_compensatable_id_status_index` (`compensatable_type`,`compensatable_id`,`status`),
  CONSTRAINT `compensations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compensations`
--

LOCK TABLES `compensations` WRITE;
/*!40000 ALTER TABLE `compensations` DISABLE KEYS */;
/*!40000 ALTER TABLE `compensations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contract_types`
--

DROP TABLE IF EXISTS `contract_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contract_types`
--

LOCK TABLES `contract_types` WRITE;
/*!40000 ALTER TABLE `contract_types` DISABLE KEYS */;
INSERT INTO `contract_types` VALUES
(1,'Самозанятый','self_employed','Налог на профессиональный доход',1,NULL,NULL),
(2,'Гражданско-правовой договор','gph','Договор ГПХ с физ. лицом',1,NULL,NULL),
(3,'Индивидуальный предприниматель','ip','ИП на различных системах налогообложения',1,NULL,NULL),
(4,'Общество с ограниченной ответственностью','ooo','Юридическое лицо ООО',1,NULL,NULL),
(5,'Физическое лицо','individual','Работа по трудовому договору',1,NULL,NULL);
/*!40000 ALTER TABLE `contract_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor_rates`
--

DROP TABLE IF EXISTS `contractor_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contractor_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contractor_id` bigint(20) unsigned NOT NULL,
  `specialty_id` bigint(20) unsigned NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contractor_rates_contractor_id_specialty_id_is_anonymous_unique` (`contractor_id`,`specialty_id`,`is_anonymous`),
  KEY `contractor_rates_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `contractor_rates_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contractor_rates_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor_rates`
--

LOCK TABLES `contractor_rates` WRITE;
/*!40000 ALTER TABLE `contractor_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `contractor_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractors`
--

DROP TABLE IF EXISTS `contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contractors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contractor_code` varchar(10) DEFAULT NULL COMMENT 'Уникальный код подрядчика для массового персонала (ABC, XYZ, etc.)',
  `contact_person` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) DEFAULT NULL,
  `contact_person_phone` varchar(255) DEFAULT NULL,
  `contact_person_email` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `specializations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`specializations`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `inn` varchar(12) DEFAULT NULL,
  `bank_details` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `contract_type_id` bigint(20) unsigned DEFAULT NULL,
  `tax_status_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contractors_user_id_foreign` (`user_id`),
  KEY `contractors_contract_type_id_foreign` (`contract_type_id`),
  KEY `contractors_tax_status_id_foreign` (`tax_status_id`),
  CONSTRAINT `contractors_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
  CONSTRAINT `contractors_tax_status_id_foreign` FOREIGN KEY (`tax_status_id`) REFERENCES `tax_statuses` (`id`),
  CONSTRAINT `contractors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractors`
--

LOCK TABLES `contractors` WRITE;
/*!40000 ALTER TABLE `contractors` DISABLE KEYS */;
INSERT INTO `contractors` VALUES
(1,'ООО \"Стройхлам\"','С','Брюнькин Василий Петрович','+777 77 7 777',NULL,'+777 77 7 777','abc@abc.ru','abc@ab2.ru','[\"\\u0444\\u044b\\u0432\\u0430\\u0444\\u0432\\u0444\\u044b\\u0432\\u0444\\u044b\"]',1,'2025-11-06 17:07:38','2025-11-06 17:07:38',1,'г. Москва, Петровский бульвар дом 7','1234567890','фывфывфвфывфывфывфыв',NULL,4,7);
/*!40000 ALTER TABLE `contractors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `initiator_grants`
--

DROP TABLE IF EXISTS `initiator_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `initiator_grants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `initiator_id` bigint(20) unsigned NOT NULL,
  `brigadier_id` bigint(20) unsigned NOT NULL,
  `is_temporary` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `initiator_grants_initiator_id_foreign` (`initiator_id`),
  KEY `initiator_grants_brigadier_id_foreign` (`brigadier_id`),
  CONSTRAINT `initiator_grants_brigadier_id_foreign` FOREIGN KEY (`brigadier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `initiator_grants_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `initiator_grants`
--

LOCK TABLES `initiator_grants` WRITE;
/*!40000 ALTER TABLE `initiator_grants` DISABLE KEYS */;
/*!40000 ALTER TABLE `initiator_grants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mass_personnel_locations`
--

DROP TABLE IF EXISTS `mass_personnel_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mass_personnel_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `address_id` bigint(20) unsigned DEFAULT NULL,
  `custom_address` varchar(255) DEFAULT NULL,
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 0,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_last_location` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mass_personnel_locations_report_id_foreign` (`report_id`),
  KEY `mass_personnel_locations_address_id_foreign` (`address_id`),
  CONSTRAINT `mass_personnel_locations_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `mass_personnel_locations_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `mass_personnel_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mass_personnel_locations`
--

LOCK TABLES `mass_personnel_locations` WRITE;
/*!40000 ALTER TABLE `mass_personnel_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `mass_personnel_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mass_personnel_reports`
--

DROP TABLE IF EXISTS `mass_personnel_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mass_personnel_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_request_id` bigint(20) unsigned NOT NULL,
  `workers_count` int(11) NOT NULL,
  `total_hours` decimal(8,2) NOT NULL,
  `worker_names` text DEFAULT NULL COMMENT 'ФИО обезличенных работников',
  `compensation_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compensation_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mass_personnel_reports_request_id_foreign` (`work_request_id`),
  KEY `mass_personnel_reports_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `mass_personnel_reports_request_id_foreign` FOREIGN KEY (`work_request_id`) REFERENCES `work_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mass_personnel_reports_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mass_personnel_reports`
--

LOCK TABLES `mass_personnel_reports` WRITE;
/*!40000 ALTER TABLE `mass_personnel_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `mass_personnel_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2025_10_06_104438_create_permission_tables',1),
(5,'2025_10_06_104716_create_contractors_table',1),
(6,'2025_10_06_104808_create_work_requests_table',1),
(7,'2025_10_06_104809_create_brigadier_assignments_table',1),
(8,'2025_10_06_104810_create_shifts_table',1),
(9,'2025_10_06_104811_create_initiator_grants_table',1),
(10,'2025_10_06_105703_add_fields_to_users_table',1),
(11,'2025_10_06_115238_create_work_types_table',1),
(12,'2025_10_06_115239_create_project_assignments_table',1),
(13,'2025_10_06_132619_create_personal_access_tokens_table',1),
(14,'2025_10_10_000002_create_assignments_table',1),
(15,'2025_10_10_000004_create_expenses_table',1),
(16,'2025_10_10_000006_create_specialties_table',1),
(17,'2025_10_10_000008_create_user_specialties_table',1),
(18,'2025_10_10_000009_create_rates_table',1),
(19,'2025_10_10_000010_alter_shifts_add_totals_and_dimensions',1),
(20,'2025_10_10_000011_create_shift_segments_table',1),
(21,'2025_10_10_000012_alter_contractors_add_contact_person',1),
(22,'2025_10_10_000013_alter_shifts_add_time_and_travel_fields',1),
(23,'2025_10_10_000014_alter_work_requests_normalize_specialty_and_work_type',1),
(24,'2025_10_12_082735_create_visited_locations_table',1),
(25,'2025_10_12_082811_create_shift_photos_table',1),
(26,'2025_10_12_083322_add_role_to_shifts_table',1),
(27,'2025_10_12_083323_add_work_date_to_work_requests_table',1),
(28,'2025_10_12_083324_add_personal_fields_to_users_table',1),
(29,'2025_10_12_120050_add_base_hourly_rate_to_user_specialties_table',1),
(30,'2025_10_12_123614_remove_specialization_from_work_requests_table',1),
(31,'2025_10_12_202454_add_start_time_to_work_requests_table',1),
(32,'2025_10_15_132122_create_projects_table',1),
(33,'2025_10_15_132134_create_purposes_table',1),
(34,'2025_10_15_132144_create_addresses_table',1),
(35,'2025_10_15_132151_create_purpose_payer_companies_table',1),
(36,'2025_10_15_132159_create_purpose_address_rules_table',1),
(37,'2025_10_15_135808_add_selected_payer_company_to_work_requests',1),
(38,'2025_10_16_074638_create_purpose_templates_table',1),
(39,'2025_10_16_132420_add_foreign_keys_to_purpose_tables',1),
(40,'2025_10_16_141307_add_project_id_to_purpose_tables_final',1),
(41,'2025_10_17_082216_add_payer_selection_type_to_purposes_table',1),
(42,'2025_10_17_093453_add_payer_fields_to_purpose_templates_table',1),
(43,'2025_10_17_094532_create_address_project_table',1),
(44,'2025_10_17_102042_drop_address_programs_and_payer_rules_tables',1),
(45,'2025_10_17_105648_remove_payer_fields_from_purpose_templates_table',1),
(46,'2025_10_19_085618_add_fields_to_specialties_table',1),
(47,'2025_10_19_091104_add_fields_to_work_types_table',1),
(48,'2025_10_19_091356_add_description_to_work_types_table',1),
(49,'2025_10_21_125412_remove_category_from_specialties_table',17),
(50,'2025_10_21_130526_mark_remove_category_migration_as_completed',1),
(51,'2025_10_21_130752_fix_remove_category_migration',1),
(52,'2025_10_21_131132_final_fix_category_removal',1),
(53,'2025_10_22_073444_update_users_and_contractors_tables_final',1),
(54,'2025_10_22_073824_fix_problem_migrations_and_update_tables',1),
(55,'2025_10_22_121834_add_address_id_to_work_requests_final',1),
(56,'2025_10_22_122825_create_address_templates_table',1),
(57,'2025_10_22_124308_update_addresses_table_fields',1),
(58,'2025_10_22_132123_add_premium_to_work_types_table',1),
(59,'2025_10_22_140237_convert_expenses_to_shift_expenses_with_structure',1),
(60,'2025_10_22_140744_add_calculation_fields_to_shifts_table',1),
(61,'2025_10_22_141022_create_shift_settings_table',1),
(62,'2025_10_22_143204_update_specialties_table_remove_code_add_category',1),
(63,'2025_10_23_074115_drop_receipts_table',1),
(64,'2025_10_23_131625_create_categories_table',1),
(65,'2025_10_23_131643_update_specialties_add_category_id',1),
(66,'2025_10_23_131651_create_contractor_rates_table',1),
(67,'2025_10_23_132420_migrate_existing_categories_to_new_structure',1),
(68,'2025_10_23_135642_update_work_requests_table_for_new_structure',1),
(69,'2025_10_24_070401_create_contract_types_table',1),
(70,'2025_10_24_070403_create_tax_statuses_table',1),
(71,'2025_10_24_070428_add_contract_type_and_tax_status_to_users_table',1),
(72,'2025_10_24_070431_add_contract_type_and_tax_status_to_contractors_table',1),
(73,'2025_10_24_070433_add_tax_status_to_shifts_table',1),
(74,'2025_10_24_071819_add_contract_type_to_shifts_table',1),
(75,'2025_10_24_081313_rename_total_amount_to_gross_amount_in_shifts_table',1),
(76,'2025_10_24_141257_add_prefix_to_specialties_table',1),
(77,'2025_10_24_141331_add_contractor_code_to_contractors_table',1),
(78,'2025_10_24_141356_create_mass_personnel_reports_table',1),
(79,'2025_10_25_081348_create_compensation_table',1),
(80,'2025_10_25_081350_drop_shift_settings_table',1),
(81,'2025_10_25_081353_fix_work_request_structure',1),
(82,'2025_10_25_094310_create_work_request_statuses_table',1),
(83,'2025_10_25_113206_final_cleanup_work_requests_table',1),
(84,'2025_10_25_114131_fix_mass_personnel_reports_table',1),
(85,'2025_10_25_115930_simple_cleanup_mass_personnel_reports',1),
(86,'2025_10_25_120242_update_shifts_for_new_calculation_system',1),
(87,'2025_10_25_122616_add_additional_fields_to_shifts',1),
(88,'2025_10_25_123937_add_calculation_fields_to_shifts_final',1),
(89,'2025_10_29_082550_move_prefix_from_specialty_to_category',1),
(90,'2025_10_29_082838_cleanup_work_request_table',1),
(91,'2025_10_29_082911_drop_shift_segments_table',1),
(92,'2025_10_29_132836_add_status_to_work_requests_table',1),
(93,'2025_10_30_074211_remove_unused_fields_from_work_types_table',1),
(94,'2025_10_30_090639_add_specialty_id_to_mass_personnel_reports_table',1),
(95,'2025_10_30_130028_make_personnel_type_nullable_in_work_requests',1),
(96,'2025_10_30_134828_sync_work_request_statuses_enums',1),
(97,'2025_10_31_113255_add_planned_address_fields_to_brigadier_assignments_table',1),
(98,'2025_11_01_085308_make_request_id_nullable_in_shifts_table',1),
(99,'2025_11_01_112203_expand_assignments_table_corrected',1),
(100,'2025_11_01_112910_make_work_request_id_nullable_in_assignments',1),
(101,'2025_11_06_112910_make_fix',1),
(102,'2025_11_06_112911_make_fix',18),
(103,'2025_11_06_112912_make_fix',19),
(104,'2025_11_06_200000_add_contact_person_to_work_requests',20);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
INSERT INTO `model_has_permissions` VALUES
(1,'App\\Models\\User',1),
(2,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1),
(2,'App\\Models\\User',2),
(3,'App\\Models\\User',3),
(4,'App\\Models\\User',4),
(5,'App\\Models\\User',5);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'edit_database','web','2025-11-06 05:41:42','2025-11-06 05:41:42'),
(2,'view_addresses','web','2025-11-06 05:49:43','2025-11-06 05:49:43');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_assignments`
--

DROP TABLE IF EXISTS `project_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `assignment_name` varchar(255) NOT NULL,
  `payer_company` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_assignments`
--

LOCK TABLES `project_assignments` WRITE;
/*!40000 ALTER TABLE `project_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `default_payer_company` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES
(1,'Проект 1',NULL,'2025-11-03','2026-06-11',NULL,'active','2025-11-06 16:01:17','2025-11-06 16:01:17');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purpose_address_rules`
--

DROP TABLE IF EXISTS `purpose_address_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_address_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `purpose_id` bigint(20) unsigned NOT NULL,
  `address_id` bigint(20) unsigned DEFAULT NULL,
  `payer_company` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purpose_address_rules_purpose_id_address_id_unique` (`purpose_id`,`address_id`),
  KEY `purpose_address_rules_address_id_foreign` (`address_id`),
  KEY `purpose_address_rules_project_id_purpose_id_address_id_index` (`project_id`,`purpose_id`,`address_id`),
  CONSTRAINT `purpose_address_rules_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purpose_address_rules_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purpose_address_rules_purpose_id_foreign` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purpose_address_rules`
--

LOCK TABLES `purpose_address_rules` WRITE;
/*!40000 ALTER TABLE `purpose_address_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `purpose_address_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purpose_payer_companies`
--

DROP TABLE IF EXISTS `purpose_payer_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_payer_companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `purpose_id` bigint(20) unsigned NOT NULL,
  `payer_company` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purpose_payer_companies_purpose_id_foreign` (`purpose_id`),
  KEY `purpose_payer_companies_project_id_purpose_id_index` (`project_id`,`purpose_id`),
  CONSTRAINT `purpose_payer_companies_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purpose_payer_companies_purpose_id_foreign` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purpose_payer_companies`
--

LOCK TABLES `purpose_payer_companies` WRITE;
/*!40000 ALTER TABLE `purpose_payer_companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `purpose_payer_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purpose_templates`
--

DROP TABLE IF EXISTS `purpose_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Описание шаблона назначения',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purpose_templates`
--

LOCK TABLES `purpose_templates` WRITE;
/*!40000 ALTER TABLE `purpose_templates` DISABLE KEYS */;
INSERT INTO `purpose_templates` VALUES
(1,'Застройка','Конструктив, МАФ, оборудование, инструменты, утилизация',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(2,'Монтаж, демонтаж','Инвентарь, расходные материалы, сыпучие материалы, работы по монтажу/демонтажу',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(3,'Уход','Расходы по уходу за растениями, фонд замен растений, перемещение растений',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(4,'Орг расходы','Административные прочие расходы по монтажу/демонтажу',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(5,'Ботанический сад/Монтаж','Работы по приемке растений и материалов в Ботаническом саду',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(6,'Магистральная/Монтаж','Работы по приемке/отгрузке материалов на Магистральной',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(7,'Техзона/Уход','Работы на Тверской тех зоне',1,'2025-11-06 05:44:59','2025-11-06 05:44:59'),
(8,'Магистральная/Уход','Работы на Магистральной с ИЮЛЯ',1,'2025-11-06 05:44:59','2025-11-06 05:44:59');
/*!40000 ALTER TABLE `purpose_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purposes`
--

DROP TABLE IF EXISTS `purposes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purposes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `has_custom_payer_selection` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `payer_selection_type` enum('strict','optional','address_based') NOT NULL DEFAULT 'strict',
  `default_payer_company` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purposes_project_id_foreign` (`project_id`),
  CONSTRAINT `purposes_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purposes`
--

LOCK TABLES `purposes` WRITE;
/*!40000 ALTER TABLE `purposes` DISABLE KEYS */;
INSERT INTO `purposes` VALUES
(1,1,'Назначение 1',NULL,0,1,'strict','БС','2025-11-06 16:20:02','2025-11-06 16:20:02');
/*!40000 ALTER TABLE `purposes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rates`
--

DROP TABLE IF EXISTS `rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rates_specialty_id_foreign` (`specialty_id`),
  KEY `rates_work_type_id_foreign` (`work_type_id`),
  KEY `idx_rate_lookup` (`user_id`,`specialty_id`,`work_type_id`,`effective_from`,`effective_to`),
  CONSTRAINT `rates_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rates_work_type_id_foreign` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rates`
--

LOCK TABLES `rates` WRITE;
/*!40000 ALTER TABLE `rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'admin','web','2025-11-06 05:41:42','2025-11-06 05:41:42'),
(2,'initiator','web','2025-11-06 05:41:42','2025-11-06 05:41:42'),
(3,'dispatcher','web','2025-11-06 05:41:42','2025-11-06 05:41:42'),
(4,'executor','web','2025-11-06 05:41:42','2025-11-06 05:41:42'),
(5,'brigadier','web','2025-11-06 05:41:42','2025-11-06 05:41:42');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_expenses`
--

DROP TABLE IF EXISTS `shift_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint(20) unsigned NOT NULL,
  `type` enum('lunch','travel','unforeseen') NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `receipt_photo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_shift_id_foreign` (`shift_id`),
  CONSTRAINT `expenses_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_expenses`
--

LOCK TABLES `shift_expenses` WRITE;
/*!40000 ALTER TABLE `shift_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_photos`
--

DROP TABLE IF EXISTS `shift_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_photos`
--

LOCK TABLES `shift_photos` WRITE;
/*!40000 ALTER TABLE `shift_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `contractor_id` bigint(20) unsigned DEFAULT NULL,
  `role` enum('executor','brigadier') NOT NULL DEFAULT 'executor',
  `work_date` date NOT NULL,
  `month_period` varchar(7) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('scheduled','active','pending_approval','completed','paid','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `worked_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `compensation_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compensation_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `specialty_id` bigint(20) unsigned DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `address_id` bigint(20) unsigned DEFAULT NULL,
  `base_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hand_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payout_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `expenses_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_status_id` bigint(20) unsigned DEFAULT NULL,
  `contract_type_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shifts_request_id_foreign` (`request_id`),
  KEY `shifts_contractor_id_foreign` (`contractor_id`),
  KEY `shifts_specialty_id_foreign` (`specialty_id`),
  KEY `shifts_work_type_id_foreign` (`work_type_id`),
  KEY `shifts_user_id_work_date_role_index` (`user_id`,`work_date`,`role`),
  KEY `shifts_tax_status_id_foreign` (`tax_status_id`),
  KEY `shifts_contract_type_id_foreign` (`contract_type_id`),
  KEY `shifts_address_id_foreign` (`address_id`),
  KEY `shifts_month_user_idx` (`month_period`,`user_id`),
  KEY `shifts_month_contractor_idx` (`month_period`,`contractor_id`),
  KEY `shifts_status_date_idx` (`status`,`work_date`),
  CONSTRAINT `shifts_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `shifts_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
  CONSTRAINT `shifts_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`),
  CONSTRAINT `shifts_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `work_requests` (`id`),
  CONSTRAINT `shifts_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_tax_status_id_foreign` FOREIGN KEY (`tax_status_id`) REFERENCES `tax_statuses` (`id`),
  CONSTRAINT `shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `shifts_work_type_id_foreign` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES
(1,1,4,1,'executor','2025-11-05',NULL,'07:00:00','23:00:00','scheduled',NULL,960,12.00,NULL,'2025-11-06 17:29:45','2025-11-12 08:18:32',1,1,NULL,300.00,0.00,0.00,0.00,0,0.00,7,4);
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `specialties`
--

DROP TABLE IF EXISTS `specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `specialties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `base_hourly_rate` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `specialties_category_id_foreign` (`category_id`),
  CONSTRAINT `specialties_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `specialties`
--

LOCK TABLES `specialties` WRITE;
/*!40000 ALTER TABLE `specialties` DISABLE KEYS */;
INSERT INTO `specialties` VALUES
(1,'Садовник поливальщик 3го разряда',NULL,NULL,300.00,1,'2025-11-06 17:08:49','2025-11-06 17:08:49',1);
/*!40000 ALTER TABLE `specialties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_statuses`
--

DROP TABLE IF EXISTS `tax_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contract_type_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `tax_rate` decimal(5,3) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_statuses_contract_type_id_foreign` (`contract_type_id`),
  CONSTRAINT `tax_statuses_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_statuses`
--

LOCK TABLES `tax_statuses` WRITE;
/*!40000 ALTER TABLE `tax_statuses` DISABLE KEYS */;
INSERT INTO `tax_statuses` VALUES
(1,1,'НПД 4%',0.040,'Налог на профессиональный доход 4%',1,1,NULL,NULL),
(2,1,'НПД 6%',0.060,'Налог на профессиональный доход 6%',1,0,NULL,NULL),
(3,2,'НДФЛ 13%',0.130,'Налог на доходы физ. лиц 13%',1,1,NULL,NULL),
(4,3,'УСН 6%',0.060,'Упрощенная система налогообложения 6%',1,1,NULL,NULL),
(5,3,'УСН 15%',0.150,'Упрощенная система налогообложения 15%',1,0,NULL,NULL),
(6,4,'ОСНО 20%',0.200,'Общая система налогообложения 20%',1,1,NULL,NULL),
(7,4,'УСН 15%',0.150,'Упрощенная система налогообложения 15%',1,0,NULL,NULL),
(8,5,'НДФЛ 13%',0.130,'Налог на доходы физ. лиц 13%',1,1,NULL,NULL);
/*!40000 ALTER TABLE `tax_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_specialties`
--

DROP TABLE IF EXISTS `user_specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_specialties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `specialty_id` bigint(20) unsigned NOT NULL,
  `base_hourly_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_specialties_user_id_specialty_id_unique` (`user_id`,`specialty_id`),
  KEY `user_specialties_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `user_specialties_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_specialties_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_specialties`
--

LOCK TABLES `user_specialties` WRITE;
/*!40000 ALTER TABLE `user_specialties` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_specialties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `patronymic` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `telegram_id` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `contractor_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `contract_type_id` bigint(20) unsigned DEFAULT NULL,
  `tax_status_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_contractor_id_foreign` (`contractor_id`),
  KEY `users_surname_name_index` (`surname`,`name`),
  KEY `users_contract_type_id_foreign` (`contract_type_id`),
  KEY `users_tax_status_id_foreign` (`tax_status_id`),
  CONSTRAINT `users_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
  CONSTRAINT `users_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`),
  CONSTRAINT `users_tax_status_id_foreign` FOREIGN KEY (`tax_status_id`) REFERENCES `tax_statuses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Администратор',NULL,NULL,'admin@example.com',NULL,'$2y$12$cwi9fhlPXh7IXHdSKfj41.XX2SwXkHgqMDyPTMaXwHSSyBolvbxW2','LDh3yvOfFR6gkXcjfgChr0jUjwGMQIVFT9Q2IPjCk1Sj8RM68tR7OJL30ayo','2025-11-06 05:41:42','2025-11-06 05:46:56','+79999999999',NULL,NULL,NULL,NULL,NULL,NULL),
(2,'Инициатор Тестовый',NULL,NULL,'initiator@example.com',NULL,'$2y$12$leR/jFhp88xKyuUyxn.R7eXVogYudTbgmTh..q0zbNcDUFUtwOc.K',NULL,'2025-11-06 05:41:42','2025-11-06 05:41:42','+79999999998',NULL,NULL,NULL,NULL,NULL,NULL),
(3,'Диспетчер Тестовый',NULL,NULL,'dispatcher@example.com',NULL,'$2y$12$EiJ2DxgwxCTuuHvy5U4jtejtYW2L7SsHrZJk0ZDFfPpVDUb.FRzgm',NULL,'2025-11-06 05:41:42','2025-11-06 05:41:42','+79999999997',NULL,NULL,NULL,NULL,NULL,NULL),
(4,'Исполнитель Тестовый',NULL,NULL,'executor@example.com',NULL,'$2y$12$sCqNfI50TAyjlvSxy0.1t.W6VGRlNRkml5HpsLWV673xu0wSPmXlW',NULL,'2025-11-06 05:41:42','2025-11-06 05:41:42','+79999999996',NULL,NULL,NULL,NULL,NULL,NULL),
(5,'Бригадир Тестовый',NULL,NULL,'brigadier@example.com',NULL,'$2y$12$42IPZM7buNSOi9V5R1H8O.6A9hOAvDnzTvLWzTK9NUlclg6os5AuG',NULL,'2025-11-06 05:41:42','2025-11-06 05:41:42','+79999999995',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visited_locations`
--

DROP TABLE IF EXISTS `visited_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visited_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visited_locations`
--

LOCK TABLES `visited_locations` WRITE;
/*!40000 ALTER TABLE `visited_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `visited_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_request_statuses`
--

DROP TABLE IF EXISTS `work_request_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_request_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_request_id` bigint(20) unsigned NOT NULL,
  `status` text NOT NULL,
  `changed_at` timestamp NOT NULL,
  `changed_by_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_request_statuses_changed_by_id_foreign` (`changed_by_id`),
  KEY `work_request_statuses_work_request_id_changed_at_index` (`work_request_id`,`changed_at`),
  CONSTRAINT `work_request_statuses_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_request_statuses_work_request_id_foreign` FOREIGN KEY (`work_request_id`) REFERENCES `work_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_request_statuses`
--

LOCK TABLES `work_request_statuses` WRITE;
/*!40000 ALTER TABLE `work_request_statuses` DISABLE KEYS */;
INSERT INTO `work_request_statuses` VALUES
(1,1,'draft','2025-11-06 16:43:37',2,'Work request created','2025-11-06 16:43:37','2025-11-06 16:43:37');
/*!40000 ALTER TABLE `work_request_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_requests`
--

DROP TABLE IF EXISTS `work_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(255) DEFAULT NULL,
  `initiator_id` bigint(20) unsigned NOT NULL,
  `brigadier_id` bigint(20) unsigned NOT NULL,
  `brigadier_manual` tinyint(1) NOT NULL DEFAULT 0,
  `contact_person` varchar(255) DEFAULT NULL COMMENT 'Контактное лицо, если это не бригадир',
  `workers_count` int(11) NOT NULL,
  `estimated_shift_duration` int(11) NOT NULL,
  `work_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft' COMMENT 'Статус заявки: draft, published, in_progress, closed, no_shifts, working, unclosed, completed, cancelled',
  `dispatcher_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `address_id` bigint(20) unsigned DEFAULT NULL,
  `is_custom_address` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `mass_personnel_names` text DEFAULT NULL COMMENT 'Имена массового персонала',
  `total_worked_hours` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Общее кол-во отработанных часов',
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `purpose_id` bigint(20) unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL COMMENT 'Дата публикации',
  `staffed_at` timestamp NULL DEFAULT NULL COMMENT 'Дата комплектования',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Дата завершения',
  `personnel_type` enum('our','contractor') NOT NULL DEFAULT 'our',
  PRIMARY KEY (`id`),
  KEY `work_requests_initiator_id_foreign` (`initiator_id`),
  KEY `work_requests_brigadier_id_foreign` (`brigadier_id`),
  KEY `work_requests_dispatcher_id_foreign` (`dispatcher_id`),
  KEY `work_requests_work_type_id_foreign` (`work_type_id`),
  KEY `work_requests_work_date_status_index` (`work_date`),
  KEY `work_requests_address_id_foreign` (`address_id`),
  KEY `work_requests_category_id_foreign` (`category_id`),
  KEY `work_requests_project_id_foreign` (`project_id`),
  KEY `work_requests_purpose_id_foreign` (`purpose_id`),
  KEY `request_number` (`request_number`),
  CONSTRAINT `work_requests_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_requests_brigadier_id_foreign` FOREIGN KEY (`brigadier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_requests_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `work_requests_dispatcher_id_foreign` FOREIGN KEY (`dispatcher_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_requests_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `work_requests_purpose_id_foreign` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`id`),
  CONSTRAINT `work_requests_work_type_id_foreign` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_requests`
--

LOCK TABLES `work_requests` WRITE;
/*!40000 ALTER TABLE `work_requests` DISABLE KEYS */;
INSERT INTO `work_requests` VALUES
(1,NULL,2,5,0,NULL,12,12,'2025-11-06','12:11:00','123123123','draft',1,'2025-11-06 16:43:37','2025-11-06 16:43:37',1,3,0,1,NULL,0.00,1,1,NULL,NULL,NULL,'our');
/*!40000 ALTER TABLE `work_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_types`
--

DROP TABLE IF EXISTS `work_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_types`
--

LOCK TABLES `work_types` WRITE;
/*!40000 ALTER TABLE `work_types` DISABLE KEYS */;
INSERT INTO `work_types` VALUES
(1,'Озеленение',NULL,'2025-11-06 16:00:36','2025-11-06 16:00:36',1);
/*!40000 ALTER TABLE `work_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-12 14:29:38
