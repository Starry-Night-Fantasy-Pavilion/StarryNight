DROP TABLE IF EXISTS `__PREFIX__admin_plugins`;

CREATE TABLE `__PREFIX__admin_plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `author` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `namespace` varchar(255) DEFAULT NULL,
  `main_class` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'uninstalled',
  `config_json` text,
  `dependencies_json` text,
  `requirements_json` text,
  `install_sql_path` varchar(255) DEFAULT NULL,
  `uninstall_sql_path` varchar(255) DEFAULT NULL,
  `frontend_entry` varchar(255) DEFAULT NULL,
  `admin_entry` varchar(255) DEFAULT NULL,
  `installed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plugin_id` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
