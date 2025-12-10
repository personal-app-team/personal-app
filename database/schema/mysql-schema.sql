/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_created_at_index` (`created_at`),
  KEY `activity_log_event_index` (`event`),
  KEY `subject_type_event_created_at_index` (`subject_type`,`event`,`created_at`),
  KEY `activity_log_batch_uuid_index` (`batch_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `address_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_project` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `address_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_project_address_id_project_id_unique` (`address_id`,`project_id`),
  KEY `address_project_project_id_foreign` (`project_id`),
  CONSTRAINT `address_project_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `address_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Связь адресов с проектами';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `address_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_address` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Полный адрес',
  `location_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Тип локации',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Активен',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `address_templates_is_active_index` (`is_active`),
  KEY `address_templates_location_type_index` (`location_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `short_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_type` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Адреса';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_request_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role_in_shift` enum('executor','brigadier') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('dispatcher','initiator') COLLATE utf8mb4_unicode_ci NOT NULL,
  `planned_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `assignment_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Уникальный номер назначения для бригадиров',
  `assignment_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'work_request' COMMENT 'brigadier_schedule, work_request, mass_personnel',
  `planned_start_time` time DEFAULT NULL COMMENT 'Планируемое время начала работы',
  `planned_duration_hours` decimal(4,1) DEFAULT NULL COMMENT 'Планируемая продолжительность смены',
  `assignment_comment` text COLLATE utf8mb4_unicode_ci COMMENT 'Комментарий к назначению',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending, confirmed, rejected, completed',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `planned_address_id` bigint unsigned DEFAULT NULL,
  `planned_custom_address` text COLLATE utf8mb4_unicode_ci COMMENT 'Неофициальный адрес',
  `is_custom_planned_address` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Использовать неофициальный адрес',
  `shift_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignment_unique_per_day` (`work_request_id`,`user_id`,`planned_date`,`role_in_shift`),
  KEY `assignments_user_id_planned_date_index` (`user_id`,`planned_date`),
  KEY `assignments_assignment_number_index` (`assignment_number`),
  KEY `assignments_planned_address_id_foreign` (`planned_address_id`),
  KEY `assignments_shift_id_foreign` (`shift_id`),
  KEY `assignments_assignment_type_status_index` (`assignment_type`,`status`),
  CONSTRAINT `assignments_planned_address_id_foreign` FOREIGN KEY (`planned_address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  CONSTRAINT `assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_work_request_id_foreign` FOREIGN KEY (`work_request_id`) REFERENCES `work_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Назначения исполнителей';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `decision` enum('reject','reserve','interview','other_vacancy') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `decision_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `candidate_decisions_user_id_foreign` (`user_id`),
  KEY `candidate_decisions_candidate_id_decision_date_index` (`candidate_id`,`decision_date`),
  CONSTRAINT `candidate_decisions_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidate_decisions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidate_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidate_status_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint unsigned NOT NULL,
  `status` enum('new','contacted','sent_for_approval','approved_for_interview','in_reserve','rejected') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `changed_by_id` bigint unsigned NOT NULL,
  `previous_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `candidate_status_histories_changed_by_id_foreign` (`changed_by_id`),
  KEY `candidate_status_histories_candidate_id_created_at_index` (`candidate_id`,`created_at`),
  CONSTRAINT `candidate_status_histories_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidate_status_histories_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recruitment_request_id` bigint unsigned NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` enum('hh','linkedin','recruitment','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `first_contact_date` date DEFAULT NULL,
  `hr_contact_date` date DEFAULT NULL,
  `expert_id` bigint unsigned DEFAULT NULL,
  `status` enum('new','contacted','sent_for_approval','approved_for_interview','in_reserve','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `current_stage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'initial_contact',
  `created_by_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `candidates_expert_id_foreign` (`expert_id`),
  KEY `candidates_created_by_id_foreign` (`created_by_id`),
  KEY `candidates_status_current_stage_index` (`status`,`current_stage`),
  KEY `candidates_recruitment_request_id_status_index` (`recruitment_request_id`,`status`),
  CONSTRAINT `candidates_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `candidates_expert_id_foreign` FOREIGN KEY (`expert_id`) REFERENCES `users` (`id`),
  CONSTRAINT `candidates_recruitment_request_id_foreign` FOREIGN KEY (`recruitment_request_id`) REFERENCES `recruitment_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Префикс для генерации номеров заявок (GARD, DECOR, etc.)',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compensations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compensations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `compensatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compensatable_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Описание компенсации',
  `requested_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Запрошенная сумма',
  `approved_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Утвержденная сумма',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approval_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Комментарии при утверждении',
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
DROP TABLE IF EXISTS `contract_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contractor_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contractor_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contractor_id` bigint unsigned NOT NULL,
  `specialty_id` bigint unsigned NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contractor_rates_contractor_id_specialty_id_is_anonymous_unique` (`contractor_id`,`specialty_id`,`is_anonymous`),
  KEY `contractor_rates_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `contractor_rates_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contractor_rates_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contractors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contractor_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Уникальный код подрядчика для массового персонала (ABC, XYZ, etc.)',
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specializations` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inn` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_details` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `contract_type_id` bigint unsigned DEFAULT NULL,
  `tax_status_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contractors_user_id_foreign` (`user_id`),
  KEY `contractors_contract_type_id_foreign` (`contract_type_id`),
  KEY `contractors_tax_status_id_foreign` (`tax_status_id`),
  CONSTRAINT `contractors_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
  CONSTRAINT `contractors_tax_status_id_foreign` FOREIGN KEY (`tax_status_id`) REFERENCES `tax_statuses` (`id`),
  CONSTRAINT `contractors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Подрядчики';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` bigint unsigned DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_parent_id_foreign` (`parent_id`),
  KEY `departments_manager_id_foreign` (`manager_id`),
  CONSTRAINT `departments_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`),
  CONSTRAINT `departments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `employment_form` enum('permanent','temporary') COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `termination_reason` enum('contract_end','dismissal','transfer','converted_to_permanent') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `contract_type_id` bigint unsigned DEFAULT NULL,
  `tax_status_id` bigint unsigned DEFAULT NULL,
  `payment_type` enum('salary','rate') COLLATE utf8mb4_unicode_ci NOT NULL,
  `salary_amount` decimal(10,2) DEFAULT NULL,
  `has_overtime` tinyint(1) NOT NULL DEFAULT '0',
  `overtime_rate` decimal(10,2) DEFAULT NULL,
  `work_schedule` enum('5/2','2/2','piecework') COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_specialty_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `position_change_request_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employment_history_department_id_foreign` (`department_id`),
  KEY `employment_history_contract_type_id_foreign` (`contract_type_id`),
  KEY `employment_history_tax_status_id_foreign` (`tax_status_id`),
  KEY `employment_history_primary_specialty_id_foreign` (`primary_specialty_id`),
  KEY `employment_history_created_by_id_foreign` (`created_by_id`),
  KEY `employment_history_user_id_start_date_index` (`user_id`,`start_date`),
  KEY `employment_history_end_date_index` (`end_date`),
  KEY `employment_history_position_change_request_id_foreign` (`position_change_request_id`),
  CONSTRAINT `employment_history_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
  CONSTRAINT `employment_history_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `employment_history_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `employment_history_position_change_request_id_foreign` FOREIGN KEY (`position_change_request_id`) REFERENCES `position_change_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employment_history_primary_specialty_id_foreign` FOREIGN KEY (`primary_specialty_id`) REFERENCES `specialties` (`id`),
  CONSTRAINT `employment_history_tax_status_id_foreign` FOREIGN KEY (`tax_status_id`) REFERENCES `tax_statuses` (`id`),
  CONSTRAINT `employment_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `hiring_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hiring_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint unsigned NOT NULL,
  `position_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_id` bigint unsigned DEFAULT NULL,
  `employment_type` enum('temporary','permanent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_type` enum('rate','salary','combined') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_value` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `decision_makers` json DEFAULT NULL COMMENT 'Кто принимал решение [user_ids]',
  `approved_by_id` bigint unsigned NOT NULL,
  `status` enum('draft','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `trainee_period_days` int DEFAULT NULL COMMENT 'Испытательный срок в днях',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hiring_decisions_specialty_id_foreign` (`specialty_id`),
  KEY `hiring_decisions_approved_by_id_foreign` (`approved_by_id`),
  KEY `hiring_decisions_candidate_id_status_index` (`candidate_id`,`status`),
  KEY `hiring_decisions_employment_type_status_index` (`employment_type`,`status`),
  CONSTRAINT `hiring_decisions_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hiring_decisions_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hiring_decisions_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `initiator_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `initiator_grants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `initiator_id` bigint unsigned NOT NULL,
  `brigadier_id` bigint unsigned NOT NULL,
  `is_temporary` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `initiator_grants_initiator_id_foreign` (`initiator_id`),
  KEY `initiator_grants_brigadier_id_foreign` (`brigadier_id`),
  CONSTRAINT `initiator_grants_brigadier_id_foreign` FOREIGN KEY (`brigadier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `initiator_grants_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Права инициаторов';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint unsigned NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `interview_type` enum('technical','managerial','cultural','combined') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interviewer_id` bigint unsigned NOT NULL,
  `status` enum('scheduled','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `result` enum('hire','reject','reserve','other_vacancy','trainee') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL DEFAULT '60',
  `created_by_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interviews_created_by_id_foreign` (`created_by_id`),
  KEY `interviews_candidate_id_scheduled_at_index` (`candidate_id`,`scheduled_at`),
  KEY `interviews_interviewer_id_status_index` (`interviewer_id`,`status`),
  CONSTRAINT `interviews_candidate_id_foreign` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `interviews_interviewer_id_foreign` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mass_personnel_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mass_personnel_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `address_id` bigint unsigned DEFAULT NULL,
  `custom_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '0',
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_last_location` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mass_personnel_locations_report_id_foreign` (`report_id`),
  KEY `mass_personnel_locations_address_id_foreign` (`address_id`),
  CONSTRAINT `mass_personnel_locations_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `mass_personnel_locations_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `mass_personnel_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mass_personnel_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mass_personnel_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_request_id` bigint unsigned NOT NULL,
  `workers_count` int NOT NULL,
  `total_hours` decimal(8,2) NOT NULL,
  `worker_names` text COLLATE utf8mb4_unicode_ci COMMENT 'ФИО обезличенных работников',
  `compensation_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `compensation_description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mass_personnel_reports_request_id_foreign` (`work_request_id`),
  CONSTRAINT `mass_personnel_reports_request_id_foreign` FOREIGN KEY (`work_request_id`) REFERENCES `work_requests` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Связь моделей с разрешениями';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Связь моделей с ролями';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Токены сброса пароля';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Разрешения системы';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Токены персонального доступа';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Фотографии смен';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `position_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `position_change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `current_position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_payment_type` enum('rate','salary','combined') COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_payment_type` enum('rate','salary','combined') COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_payment_value` decimal(10,2) NOT NULL,
  `new_payment_value` decimal(10,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by_id` bigint unsigned NOT NULL,
  `approved_by_id` bigint unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `effective_date` date NOT NULL,
  `notification_users` json DEFAULT NULL COMMENT 'Кого уведомить [user_ids]',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `position_change_requests_requested_by_id_foreign` (`requested_by_id`),
  KEY `position_change_requests_approved_by_id_foreign` (`approved_by_id`),
  KEY `position_change_requests_user_id_status_index` (`user_id`,`status`),
  KEY `position_change_requests_effective_date_status_index` (`effective_date`,`status`),
  CONSTRAINT `position_change_requests_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `position_change_requests_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `position_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignment_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payer_company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Назначения проектов';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `default_payer_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Проекты';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purpose_address_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_address_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `purpose_id` bigint unsigned NOT NULL,
  `address_id` bigint unsigned DEFAULT NULL,
  `payer_company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` int NOT NULL DEFAULT '1',
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
DROP TABLE IF EXISTS `purpose_payer_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_payer_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `purpose_id` bigint unsigned NOT NULL,
  `payer_company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `order` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purpose_payer_companies_purpose_id_foreign` (`purpose_id`),
  KEY `purpose_payer_companies_project_id_purpose_id_index` (`project_id`,`purpose_id`),
  CONSTRAINT `purpose_payer_companies_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purpose_payer_companies_purpose_id_foreign` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purpose_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purpose_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purposes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purposes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `has_custom_payer_selection` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `payer_selection_type` enum('strict','optional','address_based') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'strict',
  `default_payer_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purposes_project_id_foreign` (`project_id`),
  CONSTRAINT `purposes_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `specialty_id` bigint unsigned DEFAULT NULL,
  `work_type_id` bigint unsigned DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ставки оплаты';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recruitment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruitment_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vacancy_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `required_count` int NOT NULL DEFAULT '1',
  `employment_type` enum('temporary','permanent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'Для временных сотрудников',
  `hr_responsible_id` bigint unsigned DEFAULT NULL,
  `status` enum('new','assigned','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `urgency` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `deadline` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recruitment_requests_vacancy_id_foreign` (`vacancy_id`),
  KEY `recruitment_requests_user_id_foreign` (`user_id`),
  KEY `recruitment_requests_department_id_foreign` (`department_id`),
  KEY `recruitment_requests_status_urgency_index` (`status`,`urgency`),
  KEY `recruitment_requests_hr_responsible_id_status_index` (`hr_responsible_id`,`status`),
  KEY `recruitment_requests_deadline_status_index` (`deadline`,`status`),
  CONSTRAINT `recruitment_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `recruitment_requests_hr_responsible_id_foreign` FOREIGN KEY (`hr_responsible_id`) REFERENCES `users` (`id`),
  CONSTRAINT `recruitment_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `recruitment_requests_vacancy_id_foreign` FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Связь ролей с разрешениями';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Роли пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Сессии пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `type` enum('lunch','travel','unforeseen') COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `receipt_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_shift_id_foreign` (`shift_id`),
  CONSTRAINT `expenses_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Расходы';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `contractor_id` bigint unsigned DEFAULT NULL,
  `role` enum('executor','brigadier') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'executor',
  `work_date` date NOT NULL,
  `month_period` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('scheduled','active','pending_approval','completed','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `worked_minutes` int unsigned NOT NULL DEFAULT '0',
  `compensation_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `compensation_description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `specialty_id` bigint unsigned DEFAULT NULL,
  `work_type_id` bigint unsigned DEFAULT NULL,
  `address_id` bigint unsigned DEFAULT NULL,
  `base_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `hand_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payout_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `expenses_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_status_id` bigint unsigned DEFAULT NULL,
  `contract_type_id` bigint unsigned DEFAULT NULL,
  `assignment_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Номер назначения для бригадиров',
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
  KEY `shifts_assignment_number_index` (`assignment_number`),
  CONSTRAINT `shifts_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  CONSTRAINT `shifts_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`),
  CONSTRAINT `shifts_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`),
  CONSTRAINT `shifts_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `work_requests` (`id`),
  CONSTRAINT `shifts_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_tax_status_id_foreign` FOREIGN KEY (`tax_status_id`) REFERENCES `tax_statuses` (`id`),
  CONSTRAINT `shifts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `shifts_work_type_id_foreign` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Смены';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specialties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `base_hourly_rate` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `specialties_category_id_foreign` (`category_id`),
  CONSTRAINT `specialties_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Специальности';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tax_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_type_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_rate` decimal(5,3) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_statuses_contract_type_id_foreign` (`contract_type_id`),
  CONSTRAINT `tax_statuses_contract_type_id_foreign` FOREIGN KEY (`contract_type_id`) REFERENCES `contract_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trainee_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainee_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `candidate_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `candidate_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `candidate_position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_id` bigint unsigned NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `proposed_rate` decimal(10,2) DEFAULT NULL,
  `duration_days` int NOT NULL DEFAULT '7',
  `status` enum('pending','hr_approved','hr_rejected','manager_approved','active','completed','hired','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `hr_comment` text COLLATE utf8mb4_unicode_ci,
  `hr_user_id` bigint unsigned DEFAULT NULL,
  `hr_approved_at` timestamp NULL DEFAULT NULL,
  `manager_comment` text COLLATE utf8mb4_unicode_ci,
  `manager_user_id` bigint unsigned DEFAULT NULL,
  `manager_approved_at` timestamp NULL DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `trainee_user_id` bigint unsigned DEFAULT NULL,
  `decision_required_at` timestamp NULL DEFAULT NULL,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_requests_user_id_foreign` (`user_id`),
  KEY `trainee_requests_specialty_id_foreign` (`specialty_id`),
  KEY `trainee_requests_hr_user_id_foreign` (`hr_user_id`),
  KEY `trainee_requests_manager_user_id_foreign` (`manager_user_id`),
  KEY `trainee_requests_trainee_user_id_foreign` (`trainee_user_id`),
  KEY `trainee_requests_status_index` (`status`),
  KEY `trainee_requests_start_date_index` (`start_date`),
  KEY `trainee_requests_end_date_index` (`end_date`),
  KEY `trainee_requests_decision_required_at_index` (`decision_required_at`),
  CONSTRAINT `trainee_requests_hr_user_id_foreign` FOREIGN KEY (`hr_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trainee_requests_manager_user_id_foreign` FOREIGN KEY (`manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trainee_requests_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainee_requests_trainee_user_id_foreign` FOREIGN KEY (`trainee_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trainee_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_specialties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `specialty_id` bigint unsigned NOT NULL,
  `base_hourly_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_specialties_user_id_specialty_id_unique` (`user_id`,`specialty_id`),
  KEY `user_specialties_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `user_specialties_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_specialties_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Специальности пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID пользователя',
  `user_type` enum('employee','contractor') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Имя',
  `surname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patronymic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(coalesce(nullif(trim(`surname`),_utf8mb4''),_utf8mb4''),(case when ((trim(`surname`) <> _utf8mb4'') and ((trim(`name`) <> _utf8mb4'') or (trim(`patronymic`) <> _utf8mb4''))) then _utf8mb4' ' else _utf8mb4'' end),coalesce(nullif(trim(`name`),_utf8mb4''),_utf8mb4''),(case when (trim(`patronymic`) <> _utf8mb4'') then _utf8mb4' ' else _utf8mb4'' end),coalesce(nullif(trim(`patronymic`),_utf8mb4''),_utf8mb4''))) VIRTUAL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Дата подтверждения email',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Пароль',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Токен запоминания',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Дата создания',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Дата обновления',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contractor_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_contractor_id_foreign` (`contractor_id`),
  KEY `users_surname_name_index` (`surname`,`name`),
  KEY `users_full_name_index` (`full_name`),
  CONSTRAINT `users_contractor_id_foreign` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Пользователи системы';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vacancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacancies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` text COLLATE utf8mb4_unicode_ci,
  `employment_type` enum('temporary','permanent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `created_by_id` bigint unsigned NOT NULL,
  `status` enum('active','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vacancies_created_by_id_foreign` (`created_by_id`),
  KEY `vacancies_status_employment_type_index` (`status`,`employment_type`),
  KEY `vacancies_department_id_status_index` (`department_id`,`status`),
  CONSTRAINT `vacancies_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `vacancies_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vacancy_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacancy_conditions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vacancy_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vacancy_conditions_vacancy_id_foreign` (`vacancy_id`),
  CONSTRAINT `vacancy_conditions_vacancy_id_foreign` FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vacancy_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacancy_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vacancy_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vacancy_requirements_vacancy_id_foreign` (`vacancy_id`),
  CONSTRAINT `vacancy_requirements_vacancy_id_foreign` FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vacancy_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacancy_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vacancy_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vacancy_tasks_vacancy_id_foreign` (`vacancy_id`),
  CONSTRAINT `vacancy_tasks_vacancy_id_foreign` FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `visited_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visited_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_request_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_request_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_request_id` bigint unsigned NOT NULL,
  `status` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` timestamp NOT NULL,
  `changed_by_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_request_statuses_changed_by_id_foreign` (`changed_by_id`),
  KEY `work_request_statuses_work_request_id_changed_at_index` (`work_request_id`,`changed_at`),
  CONSTRAINT `work_request_statuses_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_request_statuses_work_request_id_foreign` FOREIGN KEY (`work_request_id`) REFERENCES `work_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID заявки',
  `initiator_id` bigint unsigned NOT NULL,
  `brigadier_id` bigint unsigned DEFAULT NULL COMMENT 'ID бригадира',
  `workers_count` int NOT NULL COMMENT 'Количество рабочих',
  `estimated_shift_duration` int NOT NULL COMMENT 'Продолжительность смены (часы)',
  `work_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `additional_info` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft' COMMENT 'Статус заявки: draft, published, in_progress, closed, no_shifts, working, unclosed, completed, cancelled',
  `dispatcher_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Дата создания',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT 'Дата обновления',
  `work_type_id` bigint unsigned DEFAULT NULL,
  `address_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `mass_personnel_names` text COLLATE utf8mb4_unicode_ci COMMENT 'Имена массового персонала',
  `total_worked_hours` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Общее кол-во отработанных часов',
  `project_id` bigint unsigned DEFAULT NULL,
  `purpose_id` bigint unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL COMMENT 'Дата публикации',
  `staffed_at` timestamp NULL DEFAULT NULL COMMENT 'Дата комплектования',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Дата завершения',
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
  CONSTRAINT `work_requests_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_requests_brigadier_id_foreign` FOREIGN KEY (`brigadier_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_requests_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `work_requests_dispatcher_id_foreign` FOREIGN KEY (`dispatcher_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_requests_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `work_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `work_requests_purpose_id_foreign` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`id`),
  CONSTRAINT `work_requests_work_type_id_foreign` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Заявки на работы';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `work_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Типы работ';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_10_06_104438_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_10_06_104716_create_contractors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_10_06_104808_create_work_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_10_06_104809_create_brigadier_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_10_06_104810_create_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_10_06_104811_create_initiator_grants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_10_06_105703_add_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_10_06_115238_create_work_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_10_06_115239_create_project_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_10_06_132619_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_10_09_104017_update_brigadier_assignments_for_multiple_dates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_10_10_000002_create_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_10_10_000004_create_expenses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_10_10_000006_create_specialties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_10_10_000008_create_user_specialties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_10_10_000009_create_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_10_10_000010_alter_shifts_add_totals_and_dimensions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_10_10_000011_create_shift_segments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_10_10_000012_alter_contractors_add_contact_person',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_10_10_000013_alter_shifts_add_time_and_travel_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_10_10_000014_alter_work_requests_normalize_specialty_and_work_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_10_12_082735_create_visited_locations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_10_12_082811_create_shift_photos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_10_12_083322_add_role_to_shifts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_10_12_083323_add_work_date_to_work_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_10_12_083324_add_personal_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_10_12_083325_update_brigadier_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_10_12_120050_add_base_hourly_rate_to_user_specialties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_10_12_123614_remove_specialization_from_work_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_10_12_202454_add_start_time_to_work_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_10_15_132122_create_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_10_15_132134_create_purposes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_10_15_132144_create_addresses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_10_15_132151_create_purpose_payer_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_10_15_132159_create_purpose_address_rules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_10_15_135808_add_selected_payer_company_to_work_requests',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_10_16_074638_create_purpose_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_10_16_132420_add_foreign_keys_to_purpose_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_10_16_141307_add_project_id_to_purpose_tables_final',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_10_17_082216_add_payer_selection_type_to_purposes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_10_17_093453_add_payer_fields_to_purpose_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_10_17_094532_create_address_project_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_10_17_102042_drop_address_programs_and_payer_rules_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_10_17_105648_remove_payer_fields_from_purpose_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_10_19_085618_add_fields_to_specialties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_10_19_091104_add_fields_to_work_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_10_19_091356_add_description_to_work_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_10_20_093511_add_russian_comments_to_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_10_21_125412_remove_category_from_specialties_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_10_21_130526_mark_remove_category_migration_as_completed',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_10_21_130752_fix_remove_category_migration',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_10_21_131132_final_fix_category_removal',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_10_21_142627_add_comment_to_brigadier_assignments_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_10_21_163233_add_foreign_key_to_brigadier_assignment_dates_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_10_22_073444_update_users_and_contractors_tables_final',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_10_22_073824_fix_problem_migrations_and_update_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_10_22_121834_add_address_id_to_work_requests_final',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_10_22_122825_create_address_templates_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_10_22_124308_update_addresses_table_fields',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_10_22_132123_add_premium_to_work_types_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_10_22_140237_convert_expenses_to_shift_expenses_with_structure',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_10_22_140744_add_calculation_fields_to_shifts_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_10_22_143204_update_specialties_table_remove_code_add_category',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_10_23_074115_drop_receipts_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_10_23_131625_create_categories_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_10_23_131643_update_specialties_add_category_id',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_10_23_131651_create_contractor_rates_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_10_23_132420_migrate_existing_categories_to_new_structure',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_10_23_135642_update_work_requests_table_for_new_structure',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_10_24_070401_create_contract_types_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_10_24_070403_create_tax_statuses_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_10_24_070428_add_contract_type_and_tax_status_to_users_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_10_24_070431_add_contract_type_and_tax_status_to_contractors_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_10_24_070433_add_tax_status_to_shifts_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_10_24_071819_add_contract_type_to_shifts_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_10_24_081313_rename_total_amount_to_gross_amount_in_shifts_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_10_24_141257_add_prefix_to_specialties_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_10_24_141331_add_contractor_code_to_contractors_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_10_24_141356_create_mass_personnel_reports_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_10_25_081348_create_compensation_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_10_25_081350_drop_shift_settings_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_10_25_081353_fix_work_request_structure',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_10_25_094310_create_work_request_statuses_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_10_25_113206_final_cleanup_work_requests_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_10_25_114131_fix_mass_personnel_reports_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_10_25_115930_simple_cleanup_mass_personnel_reports',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_10_25_120242_update_shifts_for_new_calculation_system',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_10_25_122616_add_additional_fields_to_shifts',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_10_25_123937_add_calculation_fields_to_shifts_final',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_10_29_082550_move_prefix_from_specialty_to_category',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_10_29_082838_cleanup_work_request_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_10_29_082911_drop_shift_segments_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_10_29_132836_add_status_to_work_requests_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_10_30_074211_remove_unused_fields_from_work_types_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_10_30_134828_sync_work_request_statuses_enums',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_10_31_113255_add_planned_address_fields_to_brigadier_assignments_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_11_01_083428_add_unified_assignment_system_fields',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_11_01_085308_make_request_id_nullable_in_shifts_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_11_01_112203_expand_assignments_table_corrected',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_11_01_112910_make_work_request_id_nullable_in_assignments',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_11_01_113542_drop_brigadier_assignment_tables',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_11_24_140715_create_trainee_requests_table',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_11_28_080128_create_departments_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_11_28_080149_create_employment_history_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_11_28_080202_add_user_type_to_users_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_11_28_082328_move_contract_and_tax_fields_to_employment_history',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_11_28_094742_remove_contract_type_and_tax_status_from_users_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_11_28_114556_create_vacancies_table',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_11_28_114717_create_vacancy_tasks_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_11_28_114723_create_vacancy_requirements_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_11_28_114730_create_vacancy_conditions_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_11_28_114926_create_recruitment_requests_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_11_28_120620_create_candidates_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_11_28_124414_create_candidate_status_histories_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_11_28_124613_create_candidate_decisions_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_11_28_130721_create_interviews_table',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_11_28_130830_create_hiring_decisions_table',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_11_28_141351_create_position_change_requests_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_11_28_141739_add_position_change_request_id_to_employment_history_table',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_12_03_112151_add_full_name_to_users_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_12_03_140927_create_activity_log_table',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_12_03_140928_add_event_column_to_activity_log_table',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_12_03_140929_add_batch_uuid_column_to_activity_log_table',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_12_05_102912_add_missing_indexes_to_activity_log_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_12_05_124355_add_custom_type_to_shift_expenses_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_12_05_153638_add_indexes_to_expenses_table',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_12_08_161122_add_logs_activity_to_work_request_statuses_table',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_12_08_171546_drop_receipt_photo_from_expenses',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_10_22_141022_create_shift_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_10_30_090639_add_specialty_id_to_mass_personnel_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_10_30_130028_make_personnel_type_nullable_in_work_requests',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_11_06_200000_add_contact_person_to_work_requests',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_12_08_135220_setup_unified_visited_locations_system',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_12_08_171539_setup_unified_photos_system',1);
