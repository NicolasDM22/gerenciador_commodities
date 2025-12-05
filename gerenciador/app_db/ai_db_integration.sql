-- Script para criar a tabela de logs de análises da IA.
-- Execute no banco principal (gerenciador_commodities).

CREATE TABLE IF NOT EXISTS `ai_analysis_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `commodity_id` BIGINT UNSIGNED NULL,
    `materia_prima` VARCHAR(120) NULL,
    `volume_kg` DECIMAL(15,2) NOT NULL,
    `preco_alvo` DECIMAL(12,2) NOT NULL,
    `cep` VARCHAR(12) NULL,
    `context_snapshot` TEXT NULL,
    `prompt` TEXT NOT NULL,
    `response` LONGTEXT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ai_logs_user_id_idx` (`user_id`),
    KEY `ai_logs_commodity_id_idx` (`commodity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;