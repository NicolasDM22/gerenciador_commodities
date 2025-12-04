CREATE TABLE `commodity_saida` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cliente` bigint unsigned DEFAULT NULL,  -- você pode trocar pra NOT NULL se já tiver o vínculo pronto
  `commodity_id` bigint unsigned NOT NULL,

  `tipo_registro` enum('DESCRITIVO','FORECAST_NACIONAL','COMPARACAO_REGIONAL') NOT NULL,

  -- campos que podem ou não ser usados dependendo do tipo_registro
  `referencia_mes` date DEFAULT NULL,
  `pais` varchar(120) DEFAULT NULL,

  `volume_compra_ton` decimal(12,2) DEFAULT NULL,
  `preco_medio_global` decimal(12,2) DEFAULT NULL,
  `preco_medio_brasil` decimal(12,2) DEFAULT NULL,
  `preco_medio` decimal(12,2) DEFAULT NULL,
  `preco_alvo` decimal(12,2) DEFAULT NULL,
  `variacao_perc` decimal(6,2) DEFAULT NULL,

  `logistica_perc` decimal(6,2) DEFAULT NULL,
  `risco` varchar(191) DEFAULT NULL,
  `estabilidade` varchar(60) DEFAULT NULL,
  `ranking` tinyint unsigned DEFAULT NULL,

  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  KEY `idx_saida_cliente` (`id_cliente`),
  KEY `idx_saida_commodity` (`commodity_id`)

  -- se você tiver uma tabela clientes, pode habilitar a FK abaixo:
  -- ,CONSTRAINT `fk_saida_cliente`
  --   FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
