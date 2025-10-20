CREATE TABLE `commodity_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commodity_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BRL',
  `source` varchar(191) DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_commodity_location_date` (`commodity_id`,`location_id`,`last_updated`),
  KEY `fk_prices_location` (`location_id`),
  CONSTRAINT `fk_prices_commodity` FOREIGN KEY (`commodity_id`) REFERENCES `commodities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prices_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci