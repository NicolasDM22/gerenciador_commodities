-- ###############################################################
-- BLOCO 1: LIMPEZA E CRIAÇÃO DAS ESTRUTURAS (DDL)
-- ###############################################################

-- Desativa checagem de chave estrangeira
SET FOREIGN_KEY_CHECKS = 0;

-- 1. LIMPEZA DE DADOS
TRUNCATE TABLE `commodity_entrada`;
TRUNCATE TABLE `commodity_saida`;

-- 2. CRIAÇÃO DA TABELA DE LOCALIZAÇÕES (locations)
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

-- 3. CRIAÇÃO DA TABELA DE SAÍDA (commodity_saida - Esquema Temporal Horizontal COMPLETO)
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

-- 4. CRIAÇÃO DA TABELA DE ENTRADA (commodity_entrada)
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


-- ###############################################################
-- BLOCO 2: INSERÇÃO DE DADOS (PREENCHIMENTO)
-- ###############################################################

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
    (110, 'Londres', 'Inglaterra', 'Europa'); -- Reduzi para 8, garantindo que os IDs sejam 101-110

-- 2. Inserir Dados de Entrada (commodity_entrada - Preços Brutos)
INSERT INTO `commodity_entrada` 
    (`id`, `commodity_id`, `nome`, `unidade`, `location_id`, `price`, `currency`, `source`, `last_updated`)
VALUES
    (1, 1, 'Soja', 'USD/bushel', 103, 13.80, 'USD', 'CBOT', '2025-12-01'),  
    (2, 1, 'Soja', 'BRL/saca', 101, 145.50, 'BRL', 'Cepea', '2025-12-01'),   
    (3, 2, 'Milho', 'USD/bushel', 103, 5.25, 'USD', 'Chicago', '2025-12-01'),
    (4, 2, 'Milho', 'BRL/saca', 102, 60.50, 'BRL', 'Cepea', '2025-12-01'),  
    (5, 3, 'Açúcar', 'USD/cents', 110, 19.80, 'USD', 'ICE Futures', '2025-12-01'), 
    (6, 3, 'Açúcar', 'BRL/ton', 101, 2300.00, 'BRL', 'Esalq', '2025-12-01'),
    (7, 4, 'Cacau', 'USD/ton', 110, 3850.00, 'USD', 'ICE Futures', '2025-12-01');


-- 3. Inserir Dados de Saída (commodity_saida - 5 Análises no novo formato horizontal)
-- ORDEM CORRIGIDA: As colunas devem ser estritamente seguidas pela ordem do CREATE TABLE
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
    -- 1. SOJA ANTIGA (ID 1) - Análise 2025-12-01
    1, 1, '2025-12-01', 'PREVISAO_MENSAL', 
    50000.00, 145.00, -- Descritivo
    148.50, 145.00, 142.00, 140.00, -- Histórico
    150.00, 152.50, 155.00, 156.00, -- Forecast
    148.50, 150.00, 148.50, 5.05, -- Métricas
    8.50, 'Muito Baixo', 'Alta', 1 -- Regional
),
(
    -- 2. MILHO (ID 2) - Análise 2025-12-01
    2, 2, '2025-12-01', 'PREVISAO_MENSAL', 
    12000.00, 60.00, 
    61.20, 60.00, 60.50, 62.00, 
    62.50, 63.00, 62.80, 63.50,
    61.20, 63.00, 61.20, 3.76, 
    10.50, 'Baixo', 'Média', 2
),
(
    -- 3. AÇÚCAR (ID 3) - Análise 2025-12-01
    3, 3, '2025-12-01', 'PREVISAO_MENSAL', 
    80000.00, 2.05, 
    1.90, 1.95, 2.00, 2.05,
    2.10, 2.15, 2.20, 2.25,
    1.90, 2.00, 1.90, 7.89, 
    12.00, 'Baixo', 'Média', 1
),
(
    -- 4. CACAU (ID 4) - Análise 2025-12-01
    4, 4, '2025-12-01', 'PREVISAO_MENSAL', 
    5000.00, 3750.00, 
    3850.00, 3800.00, 3700.00, 3650.00,
    3900.00, 3850.00, 3800.00, 3750.00,
    3850.00, 3800.00, 3850.00, 1.30, 
    9.00, 'Média', 'Alta', 1
),
(
    -- 5. NOVA PREVISÃO SOJA (ID 5 - MAIOR ID, FOCO DO GRÁFICO)
    5, 1, '2026-01-01', 'PREVISAO_MENSAL', 
    55000.00, 155.00, 
    150.00, 148.50, 145.00, 142.00,
    160.00, 165.00, 170.00, 175.00,
    150.00, 152.00, 150.00, 16.67, 
    7.00, 'Muito Baixo', 'Alta', 1
);