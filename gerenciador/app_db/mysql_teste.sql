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
    -- 6. MILHO (ID 2) - Análise de Fevereiro/2026 (MAIOR ID)
    6, 2, '2026-02-01', 'PREVISAO_MENSAL', 
    15000.00, 62.00, 
    65.00, 62.50, 61.20, 60.50, -- Histórico
    65.50, 66.00, 67.00, 68.00, -- Forecast (Mar/26 a Jun/26)
    65.00, 66.00, 65.00, 4.62, 
    10.00, 'Baixo', 'Alta', 1
);