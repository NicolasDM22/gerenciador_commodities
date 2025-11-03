drop TABLE IF EXISTS `commodity_descriptive_metrics`;
drop TABLE IF EXISTS `commodity_national_forecasts`;
drop TABLE IF EXISTS `commodity_regional_comparisons`;
CREATE TABLE IF NOT EXISTS `commodity_descriptive_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commodity_id` bigint unsigned NOT NULL,
  `referencia_mes` date NOT NULL,
  `volume_compra_ton` decimal(12,2) NOT NULL,
  `preco_medio_global` decimal(12,2) NOT NULL,
  `preco_medio_brasil` decimal(12,2) NOT NULL,
  `preco_alvo` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_descriptive_reference` (`commodity_id`,`referencia_mes`),
  CONSTRAINT `fk_descriptive_commodity` FOREIGN KEY (`commodity_id`) REFERENCES `commodities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commodity_national_forecasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commodity_id` bigint unsigned NOT NULL,
  `referencia_mes` date NOT NULL,
  `preco_medio` decimal(12,2) NOT NULL,
  `variacao_perc` decimal(6,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_national_forecast` (`commodity_id`,`referencia_mes`),
  CONSTRAINT `fk_national_forecast_commodity` FOREIGN KEY (`commodity_id`) REFERENCES `commodities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commodity_regional_comparisons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commodity_id` bigint unsigned NOT NULL,
  `pais` varchar(120) NOT NULL,
  `preco_medio` decimal(12,2) NOT NULL,
  `logistica_perc` decimal(6,2) NOT NULL,
  `risco` varchar(191) NOT NULL,
  `estabilidade` varchar(60) NOT NULL,
  `ranking` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_regional_country` (`commodity_id`,`pais`),
  CONSTRAINT `fk_regional_commodity` FOREIGN KEY (`commodity_id`) REFERENCES `commodities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

