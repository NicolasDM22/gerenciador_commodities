<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsão de Commodities - Descritiva</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        /* --- 1. ESTILOS GERAIS --- */
        :root {
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #059669;
            --danger: #dc2626;
            --white: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--gray-100);
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--gray-900);
            height: 100vh;
            overflow: hidden; /* Trava a rolagem da página inteira */
        }

        .page {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* --- BOTÕES --- */
        .button {
            border: none; border-radius: 12px; padding: 0.75rem 1.4rem;
            font-size: 0.95rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 0.4rem; text-decoration: none;
        }
        .button:hover { transform: translateY(-1px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.18); }
        
        .button-secondary {
            background: var(--white);
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }
        .button-secondary:hover { background: var(--gray-50); box-shadow: none; transform: none; }
        .button[disabled] { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; background: var(--gray-100); }
        .button-icon { padding: 0.6rem 0.8rem; line-height: 1; }

        /* --- LAYOUT PRINCIPAL --- */
        main.content {
            flex: 1;
            width: min(1280px, 100%);
            margin: 0 auto;
            padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            gap: 1.75rem;
        }

        .card {
            background: var(--white);
            border-radius: 22px;
            padding: 1.5rem;
            box-shadow: 0 22px 45px -30px rgba(15, 23, 42, 0.3);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        /* --- CABEÇALHO DO CARD --- */
        .analysis-header {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; padding-bottom: 1.25rem; margin-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        
        .analysis-header h2 {
            margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--gray-600);
        }
        
        .analysis-header .nav-buttons { display: flex; gap: 0.5rem; }

        /* --- CORPO COM SCROLL --- */
        .analysis-body {
            display: flex; flex-direction: column; gap: 1.5rem;
            overflow-y: auto; flex: 1; padding-right: 1rem;
        }

        /* Scrollbar customizada */
        .analysis-body::-webkit-scrollbar { width: 8px; }
        .analysis-body::-webkit-scrollbar-track { background: transparent; }
        .analysis-body::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .analysis-body::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
        
.analysis-section h2 {
     margin-bottom: 1.25rem;
     font-size: 1.15rem;
     font-weight: 600;
     color: var(--gray-700);
}

.ai-markets {
     display: grid;
     grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
     gap: 1rem;
}

.ai-market-card {
     border: 1px solid var(--gray-200);
     border-radius: 16px;
     background: var(--gray-50);
     padding: 1rem;
}

.ai-market-card h3 {
     margin: 0 0 0.5rem;
     font-size: 1rem;
     color: var(--gray-700);
}
        
        /* --- GRIDS DE DADOS --- */
        .descriptive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem 2rem;
        }

        .descriptive-grid p {
            margin: 0.6rem 0; font-size: 0.95rem; color: var(--gray-700);
            line-height: 1.5; display: flex; justify-content: space-between; align-items: baseline;
            border-bottom: 1px dashed #f0f0f0; padding-bottom: 5px;
        }
        .descriptive-grid strong { font-weight: 600; color: var(--gray-600); padding-right: 1rem; }
        .descriptive-grid p > span { font-weight: 600; text-align: right; color: var(--gray-900); }
        
        .text-success { color: var(--success) !important; }
        .text-danger { color: var(--danger) !important; }

        /* --- TABELAS --- */
        .table-wrapper {
            overflow: auto; border-radius: 12px; border: 1px solid var(--gray-200);
        }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 0.85rem 1rem; text-align: left; font-size: 0.9rem; border-bottom: 1px solid var(--gray-200); }
        th { background: var(--gray-50); font-weight: 600; color: var(--gray-700); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
        td { font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        .text-center { text-align: center; }

        @media (max-width: 1000px) {
            body { height: auto; overflow: auto; }
            .content { height: auto; overflow: visible; }
            .card { height: auto; overflow: visible; }
            .analysis-body { overflow: visible; height: auto; }
        }
    </style>
</head>
<body>
<div class="page">
    <x-topbar :user="$user" />

    <main class="content">
        @if (session('status'))
            <div style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1rem;">
                {{ session('status') }}
            </div>
        @endif

        <section class="card">
            
            {{-- CABEÇALHO --}}
            <div class="analysis-header">
                <div class="nav-buttons">
                    <button class="button button-secondary button-icon" disabled type="button" title="Anterior">&larr;</button>
                    
                    <a href="{{ route('previsoes.graficos.show', ['id' => $analysisId ?? $selectedCommodity->id]) }}" 
                    class="button button-secondary button-icon" 
                    title="Ir para Gráficos">
                    &rarr;
                    </a>
                </div>

                <h2>Análise Descritiva - {{ $selectedCommodity->nome }}</h2>
                
                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Voltar para Home">&times;</a>
            </div>
            
            {{-- CORPO COM SCROLL --}}
            <div class="analysis-body">
                
                <div class="analysis-section">
                    <div class="descriptive-grid">
                        @php
                            $variacao = $descriptiveData->preco_medio_brasil - $descriptiveData->preco_alvo;
                        @endphp
                        <div>
                            <p><strong>Matéria Prima:</strong> <span>{{ $descriptiveData->materia_prima }}</span></p>
                            <p><strong>Mês de Referência:</strong> 
                                <span>{{ $descriptiveData->referencia_mes ? date('m/Y', strtotime($descriptiveData->referencia_mes)) : 'N/A' }}</span>
                            </p>
                            <p><strong>Volume Compra:</strong> <span>{{ number_format($descriptiveData->volume_compra_ton, 0, ',', '.') }} Ton</span></p>
                            <p><strong>Preço Global (Médio):</strong> <span>R$ {{ number_format($descriptiveData->preco_medio_global, 2, ',', '.') }}</span></p>
                        </div>
                        <div>
                            <p><strong>Preço Brasil (Atual):</strong> <span>R$ {{ number_format($descriptiveData->preco_medio_brasil, 2, ',', '.') }}</span></p>
                            <p><strong>Preço Alvo:</strong> <span>R$ {{ number_format($descriptiveData->preco_alvo, 2, ',', '.') }}</span></p>
                            <p><strong>Gap / Variação:</strong> 
                                <span class="{{ $variacao > 0 ? 'text-danger' : 'text-success' }}">
                                    R$ {{ number_format($variacao, 2, ',', '.') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                @if (!empty($aiSummary['mercados']))
                <div class="analysis-section">
                    <h2>Mercados recomendados pela IA</h2>
                    <div class="ai-markets">
                        @foreach ($aiSummary['mercados'] as $mercado)
                            <div class="ai-market-card">
                                <h3>{{ $mercado['nome'] ?? 'Mercado' }}</h3>
                                <p><strong>Preço estimado:</strong> {{ $mercado['moeda'] ?? 'BRL' }} {{ number_format($mercado['preco'] ?? 0, 2, ',', '.') }}</p>
                                @if (!empty($mercado['prazo_estimado_dias']))
                                    <p><strong>Prazo estimado:</strong> {{ $mercado['prazo_estimado_dias'] }} dias</p>
                                @endif
                                @if (!empty($mercado['justificativa']))
                                    <p>{{ $mercado['justificativa'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <div class="analysis-section">
                    <h3>Tendência do Mercado Nacional (Projeção)</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mês/Ano</th>
                                    <th>Preço Projetado (R$/kg)</th>
                                    <th>Variação Mensal (%)</th>
                                    <th class="text-center">Tendência</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($nationalForecasts as $forecast)
                                    <tr>
                                        <td>{{ $forecast->mes_ano }}</td>
                                        <td>R$ {{ number_format($forecast->preco_medio, 2, ',', '.') }}</td>
                                        <td class="{{ $forecast->variacao_perc >= 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $forecast->variacao_perc > 0 ? '+' : '' }}{{ number_format($forecast->variacao_perc, 2, ',', '.') }}%
                                        </td>
                                        <td class="text-center">
                                            @if($forecast->variacao_perc > 0)
                                                <span style="color:#dc2626">▲ Alta</span>
                                            @elseif($forecast->variacao_perc < 0)
                                                <span style="color:#059669">▼ Baixa</span>
                                            @else
                                                <span style="color:#6b7280">➖ Estável</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Nenhuma previsão calculada para este período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="analysis-section">
                    <h3>Comparativo Regional (Baseado em Preços Atuais de Entrada)</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Localização</th>
                                    <th>Preço Atual (R$/kg)</th>
                                    <th>Logística (Est.)</th>
                                    <th>Risco</th>
                                    <th>Estabilidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($regionalComparisons as $region)
                                    <tr>
                                        <td>{{ $region->pais }}</td>
                                        <td>R$ {{ number_format($region->preco_medio, 2, ',', '.') }}</td>
                                        <td>{{ number_format($region->logistica_perc, 1) }}%</td>
                                        <td>
                                            <span style="
                                                padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;
                                                background: {{ $region->risco == 'Baixo' || $region->risco == 'Muito Baixo' ? '#d1fae5' : ($region->risco == 'Alto' ? '#fee2e2' : '#ffedd5') }};
                                                color: {{ $region->risco == 'Baixo' || $region->risco == 'Muito Baixo' ? '#065f46' : ($region->risco == 'Alto' ? '#991b1b' : '#9a3412') }};
                                            ">
                                                {{ $region->risco }}
                                            </span>
                                        </td>
                                        <td>{{ $region->estabilidade }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum dado regional disponível na tabela de entrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> </section>
    </main>
</div>
</body>
</html>
