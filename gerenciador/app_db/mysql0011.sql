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

INSERT INTO `commodities` (`nome`, `categoria`, `unidade`, `created_at`, `updated_at`)
SELECT 'Cacau (Tipo Forasteiro)', 'Agricola', 'kg', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `commodities` WHERE `nome` = 'Cacau (Tipo Forasteiro)'
);

INSERT INTO `commodity_descriptive_metrics` (
  `commodity_id`,
  `referencia_mes`,
  `volume_compra_ton`,
  `preco_medio_global`,
  `preco_medio_brasil`,
  `preco_alvo`,
  `created_at`,
  `updated_at`
)
SELECT
  c.`id`,
  '2026-01-01',
  10.00,
  60.00,
  43.50,
  35.00,
  NOW(),
  NOW()
FROM `commodities` c
WHERE c.`nome` = 'Cacau (Tipo Forasteiro)'
ON DUPLICATE KEY UPDATE
  `volume_compra_ton` = VALUES(`volume_compra_ton`),
  `preco_medio_global` = VALUES(`preco_medio_global`),
  `preco_medio_brasil` = VALUES(`preco_medio_brasil`),
  `preco_alvo` = VALUES(`preco_alvo`),
  `updated_at` = NOW();

INSERT INTO `commodity_national_forecasts` (
  `commodity_id`,
  `referencia_mes`,
  `preco_medio`,
  `variacao_perc`,
  `created_at`,
  `updated_at`
)
SELECT c.`id`, t.`referencia_mes`, t.`preco_medio`, t.`variacao_perc`, NOW(), NOW()
FROM `commodities` c
JOIN (
    SELECT '2026-01-01' AS `referencia_mes`, 60.00 AS `preco_medio`, -10.00 AS `variacao_perc`
    UNION ALL SELECT '2026-02-01', 63.00, 5.00
    UNION ALL SELECT '2026-03-01', 56.00, -11.11
    UNION ALL SELECT '2026-04-01', 52.00, -7.14
) AS t ON c.`nome` = 'Cacau (Tipo Forasteiro)'
ON DUPLICATE KEY UPDATE
  `preco_medio` = VALUES(`preco_medio`),
  `variacao_perc` = VALUES(`variacao_perc`),
  `updated_at` = NOW();

INSERT INTO `commodity_regional_comparisons` (
  `commodity_id`,
  `pais`,
  `preco_medio`,
  `logistica_perc`,
  `risco`,
  `estabilidade`,
  `ranking`,
  `created_at`,
  `updated_at`
)
SELECT c.`id`, t.`pais`, t.`preco_medio`, t.`logistica_perc`, t.`risco`, t.`estabilidade`, t.`ranking`, NOW(), NOW()
FROM `commodities` c
JOIN (
    SELECT 'Brasil' AS `pais`, 17.80 AS `preco_medio`, 6.00 AS `logistica_perc`, 'Medio (Chuvas)' AS `risco`, 'Alta' AS `estabilidade`, 1 AS `ranking`
    UNION ALL SELECT 'Indonesia', 15.40, 18.00, 'Alto (Alta umidade)', 'Media', 3
    UNION ALL SELECT 'Costa do Marfim', 14.90, 12.00, 'Alto (Instabilidade)', 'Baixa', 2
) AS t ON c.`nome` = 'Cacau (Tipo Forasteiro)'
ON DUPLICATE KEY UPDATE
  `preco_medio` = VALUES(`preco_medio`),
  `logistica_perc` = VALUES(`logistica_perc`),
  `risco` = VALUES(`risco`),
  `estabilidade` = VALUES(`estabilidade`),
  `ranking` = VALUES(`ranking`),
  `updated_at` = NOW();
