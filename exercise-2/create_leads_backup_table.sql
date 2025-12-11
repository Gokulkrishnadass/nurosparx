CREATE TABLE IF NOT EXISTS `wp_nxs_lead_backups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` DATETIME NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `full_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `company` VARCHAR(191) DEFAULT NULL,
  `message` TEXT,
  `utm_source` VARCHAR(191) DEFAULT NULL,
  `utm_medium` VARCHAR(191) DEFAULT NULL,
  `utm_campaign` VARCHAR(191) DEFAULT NULL,
  `hubspot_status` VARCHAR(50) DEFAULT NULL,
  `hubspot_lead_id` VARCHAR(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
