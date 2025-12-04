CREATE TABLE `commodity_entrada` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  -- id original da commodity (vem da antiga commodities.id)
  `commodity_id` bigint unsigned NOT NULL,
  `nome` varchar(120) NOT NULL,
  `categoria` varchar(120) DEFAULT NULL,
  `unidade` varchar(30) DEFAULT NULL,

  -- parte que vem de commodity_prices
  `location_id` bigint unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BRL',
  `source` varchar(191) DEFAULT NULL,
  `last_updated` date DEFAULT NULL,

  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- mesma ideia do índice único antigo em commodity_prices
  UNIQUE KEY `uniq_entrada_commodity_location_date` (`commodity_id`,`location_id`,`last_updated`),

  KEY `fk_entrada_location` (`location_id`),

  CONSTRAINT `fk_entrada_location`
    FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
