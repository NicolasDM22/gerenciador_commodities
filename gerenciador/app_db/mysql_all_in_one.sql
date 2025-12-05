SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------
-- DDL DE TABELAS DE SUPORTE (USUÁRIOS, NOTIFICAÇÕES, SESSÕES)
-- ---------------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL, 
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL, 
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL, 
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL, 
  `foto_blob` longtext COLLATE utf8mb4_unicode_ci,
  `foto_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_usuario_unique` (`usuario`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admin_notifications`;
CREATE TABLE `admin_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'novo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_notifications_user_id_index` (`user_id`),
  CONSTRAINT `admin_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------
-- BLOCO 1: LIMPEZA E CRIAÇÃO DAS ESTRUTURAS (DDL)
-- ---------------------------------------------------------------

-- 1. Criação da Tabela de LOCALIZAÇÕES (locations)
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nome` varchar(120) NOT NULL,
    `estado` varchar(60) NOT NULL,
    `regiao` varchar(60) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Criação da Tabela de SAÍDA (commodity_saida)
DROP TABLE IF EXISTS `commodity_saida`;
CREATE TABLE `commodity_saida` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `commodity_id` bigint unsigned NOT NULL,
    
    `referencia_mes` date NOT NULL,
    `tipo_analise` enum('PREVISAO_MENSAL', 'COMPARACAO') NOT NULL,
    
    `volume_compra_ton` decimal(12,2) DEFAULT NULL,
    `preco_alvo` decimal(12,2) DEFAULT NULL,
    
    `preco_mes_atual` decimal(12,2) DEFAULT NULL,
    `preco_1_mes_anterior` decimal(12,2) DEFAULT NULL,
    `preco_2_meses_anterior` decimal(12,2) DEFAULT NULL,
    `preco_3_meses_anterior` decimal(12,2) DEFAULT NULL,
    
    `preco_1_mes_depois` decimal(12,2) DEFAULT NULL,
    `preco_2_meses_depois` decimal(12,2) DEFAULT NULL,
    `preco_3_meses_depois` decimal(12,2) DEFAULT NULL,
    `preco_4_meses_depois` decimal(12,2) DEFAULT NULL,

    `preco_medio` decimal(12,2) DEFAULT NULL,
    `preco_medio_global` decimal(12,2) DEFAULT NULL,
    `preco_medio_brasil` decimal(12,2) DEFAULT NULL,
    `variacao_perc` decimal(6,2) DEFAULT NULL,
    `logistica_perc` decimal(6,2) DEFAULT NULL,
    `risco` varchar(191) DEFAULT NULL,
    `estabilidade` varchar(60) DEFAULT NULL,
    `ranking` tinyint unsigned DEFAULT NULL,

    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_saida_commodity_mes` (`commodity_id`, `referencia_mes`, `tipo_analise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Criação da Tabela de ENTRADA (commodity_entrada)
DROP TABLE IF EXISTS `commodity_entrada`;
CREATE TABLE `commodity_entrada` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `commodity_id` bigint unsigned NOT NULL,
    `nome` varchar(120) NOT NULL,
    `unidade` varchar(30) DEFAULT NULL,
    `location_id` bigint unsigned NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `currency` char(3) NOT NULL DEFAULT 'BRL',
    `source` varchar(191) DEFAULT NULL,
    `last_updated` date DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_entrada_commodity_location_date` (`commodity_id`,`location_id`,`last_updated`),
    KEY `fk_entrada_location` (`location_id`),
    CONSTRAINT `fk_entrada_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reativa checagem de chave estrangeira
SET FOREIGN_KEY_CHECKS = 1;


-- ---------------------------------------------------------------
-- BLOCO 2: INSERÇÃO DE DADOS (DML)
-- ---------------------------------------------------------------

-- 1. Inserir Localizações (locations)
INSERT INTO `locations` (`id`, `nome`, `estado`, `regiao`) 
VALUES
    (101, 'São Paulo', 'SP', 'Sudeste'),
    (102, 'Curitiba', 'PR', 'Sul'),
    (103, 'Chicago', 'Illinois', 'EUA'),
    (104, 'Dalian', 'Liaoning', 'China'),
    (105, 'Buenos Aires', 'Capital Federal', 'Argentina'),
    (106, 'Paris', 'Ilha de França', 'Europa'),
    (107, 'Moscou', 'Rússia', 'Leste Europeu'),
    (110, 'Londres', 'Inglaterra', 'Europa');

-- 2. Inserir Dados de Entrada (commodity_entrada - Preços Brutos)
INSERT INTO `commodity_entrada` 
    (`id`, `commodity_id`, `nome`, `unidade`, `location_id`, `price`, `currency`, `source`, `last_updated`)
VALUES
    (1, 1, 'Soja', 'USD/bushel', 103, 13.80, 'USD', 'CBOT', '2025-11-20'), 
    (2, 2, 'Milho', 'BRL/saca', 102, 60.50, 'BRL', 'Cepea', '2025-11-25'), 
    (3, 3, 'Açúcar', 'USD/cents', 110, 19.80, 'USD', 'ICE Futures', '2025-12-01'), 
    (4, 4, 'Cacau', 'USD/ton', 110, 3850.00, 'USD', 'ICE Futures', '2025-12-01'),
    (5, 1, 'Soja', 'BRL/saca', 101, 145.50, 'BRL', 'Cepea', '2025-12-02'), 
    (6, 2, 'Milho', 'USD/bushel', 103, 5.25, 'USD', 'Chicago', '2025-12-03'),
    (7, 3, 'Açúcar', 'BRL/ton', 101, 2300.00, 'BRL', 'Esalq', '2025-12-03');


-- 3. Inserir Dados de Saída (commodity_saida - 10 Previsões Distintas)
-- commodity_id: 1=Soja, 2=Milho, 3=Açúcar, 4=Cacau
INSERT INTO `commodity_saida` (
    `id`, `commodity_id`, `referencia_mes`, `tipo_analise`, 
    `volume_compra_ton`, `preco_alvo`, 
    `preco_mes_atual`, `preco_1_mes_anterior`, `preco_2_meses_anterior`, `preco_3_meses_anterior`,
    `preco_1_mes_depois`, `preco_2_meses_depois`, `preco_3_meses_depois`, `preco_4_meses_depois`,
    `preco_medio`, `preco_medio_global`, `preco_medio_brasil`, `variacao_perc`,
    `logistica_perc`, `risco`, `estabilidade`, `ranking`
)
VALUES
(
    -- 1. SOJA (Mês 1)
    1, 1, '2025-10-01', 'PREVISAO_MENSAL', 
    50000.00, 140.00, 140.00, 138.00, 135.00, 132.00, 
    142.00, 145.00, 148.00, 150.00, 140.00, 142.00, 140.00, 3.00, 
    8.50, 'Muito Baixo', 'Alta', 1
),
(
    -- 2. MILHO (Mês 1)
    2, 2, '2025-10-01', 'PREVISAO_MENSAL', 
    12000.00, 60.50, 60.50, 60.00, 59.50, 59.00, 
    61.00, 61.50, 62.00, 62.50, 60.50, 61.50, 60.50, 2.50, 
    10.50, 'Baixo', 'Média', 2
),
(
    -- 3. AÇÚCAR (Mês 1)
    3, 3, '2025-10-01', 'PREVISAO_MENSAL', 
    80000.00, 2.05, 1.90, 1.95, 2.00, 2.05,
    2.10, 2.15, 2.20, 2.25, 1.90, 2.00, 1.90, 7.89, 
    12.00, 'Baixo', 'Média', 1
),
(
    -- 4. CACAU (Mês 1)
    4, 4, '2025-10-01', 'PREVISAO_MENSAL', 
    5000.00, 3900.00, 3950.00, 3850.00, 3800.00, 3700.00,
    4000.00, 4050.00, 4100.00, 4150.00, 3950.00, 3900.00, 3950.00, 5.00, 
    9.00, 'Média', 'Alta', 1
),
(
    -- 5. SOJA (Mês 2)
    5, 1, '2025-11-01', 'PREVISAO_MENSAL', 
    55000.00, 150.00, 145.00, 140.00, 138.00, 135.00,
    155.00, 160.00, 165.00, 170.00, 145.00, 148.00, 145.00, 10.00, 
    7.50, 'Baixo', 'Alta', 1
),
(
    -- 6. MILHO (Mês 2)
    6, 2, '2025-11-01', 'PREVISAO_MENSAL', 
    13000.00, 65.00, 62.00, 60.50, 60.00, 59.50,
    63.00, 64.00, 65.00, 66.00, 62.00, 63.00, 62.00, 4.00, 
    11.00, 'Baixo', 'Média', 2
),
(
    -- 7. AÇÚCAR (Mês 2)
    7, 3, '2025-11-01', 'PREVISAO_MENSAL', 
    85000.00, 2.15, 2.00, 1.90, 1.95, 2.00,
    2.20, 2.25, 2.30, 2.35, 2.00, 2.10, 2.00, 8.50, 
    11.50, 'Baixo', 'Média', 1
),
(
    -- 8. CACAU (Mês 2)
    8, 4, '2025-11-01', 'PREVISAO_MENSAL', 
    6000.00, 4000.00, 3900.00, 3950.00, 3850.00, 3800.00,
    4050.00, 4100.00, 4150.00, 4200.00, 3900.00, 3950.00, 3900.00, 5.50, 
    9.50, 'Média', 'Alta', 1
),
(
    -- 9. SOJA (Mês 3)
    9, 1, '2025-12-01', 'PREVISAO_MENSAL', 
    60000.00, 160.00, 155.00, 145.00, 140.00, 138.00,
    165.00, 170.00, 175.00, 180.00, 155.00, 158.00, 155.00, 12.00, 
    7.00, 'Muito Baixo', 'Alta', 1
),
(
    -- 10. MILHO (Mês 3)
    10, 2, '2025-12-01', 'PREVISAO_MENSAL', 
    14000.00, 68.00, 64.00, 62.00, 60.50, 60.00,
    69.00, 70.00, 71.00, 72.00, 64.00, 65.00, 64.00, 6.00, 
    10.00, 'Baixo', 'Alta', 2
);

