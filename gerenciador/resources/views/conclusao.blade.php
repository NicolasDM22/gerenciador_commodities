<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendação Final - Previsão de Commodities</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    <style>
        /* --- Variáveis e Reset (Mantidos do padrão) --- */
        :root {
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
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
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Top Bar --- */
        .top-bar {
            background: var(--white);
            padding: 1.5rem clamp(1.5rem, 3vw, 3rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08);
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            object-fit: cover;
            border: 3px solid var(--gray-200);
        }

        .profile-info strong { font-size: 1.25rem; display: block; }
        .profile-info span { color: var(--gray-500); font-size: 0.95rem; }

        /* --- Botões Gerais --- */
        .button {
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.4rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center; /* Centraliza texto no link */
            gap: 0.4rem;
            text-decoration: none;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.18);
        }

        .button-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }

        .button-secondary {
            background: var(--white);
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }
        
        .button-secondary:hover { background: var(--gray-50); box-shadow: none; transform: none; }

        .button-icon { padding: 0.6rem 0.8rem; line-height: 1; }
        .button[disabled] { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; background: var(--gray-100); }

        /* --- Layout Principal --- */
        main.content {
            flex: 1;
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem;
            display: grid;
            gap: 1.75rem;
        }

        .card {
            background: var(--white);
            border-radius: 22px;
            padding: 1.5rem;
            box-shadow: 0 22px 45px -30px rgba(15, 23, 42, 0.3);
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .analysis-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1.25rem;
            margin-bottom: 1rem;
        }

        .analysis-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--gray-600);
            font-weight: 700;
        }
        
        .analysis-header .nav-buttons { display: flex; gap: 0.5rem; }

        /* --- GRID DA CONCLUSÃO --- */
        .conclusion-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr; 
            gap: 3rem;
            align-items: center;
            flex: 1; 
        }

        .conclusion-text {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .conclusion-text p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--gray-700);
            font-weight: 600;
        }

        .chart-wrapper {
            width: 100%;
            position: relative;
            height: 350px;
        }

        /* --- Footer de Ações --- */
        .actions-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center; /* Alinha o texto de loading verticalmente */
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
        }

        .button-export {
            background-color: #d1d5db;
            color: var(--gray-700);
            border-radius: 25px;
            font-weight: 700;
            padding: 0.6rem 1.5rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 1rem;
        }

        .button-export:hover {
            background-color: #9ca3af; 
            color: var(--gray-900);
        }

        .button-export:disabled {
            cursor: wait;
            opacity: 0.7;
        }

        /* Responsividade */
        @media (max-width: 820px) {
            .top-bar { flex-direction: column; align-items: flex-start; }
            .conclusion-container { grid-template-columns: 1fr; gap: 2rem; }
            .chart-wrapper { height: 300px; }
            .actions-footer { justify-content: center; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<div class="page">
    <x-topbar :user="$user" />

    <main class="content">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <section class="card">
            <div class="analysis-header">
                <div class="nav-buttons">
                    {{-- Botão Esquerda: Volta para Gráficos --}}
                    <a href="{{ route('previsoes.graficos.show', ['id' => $analysisId ?? $commodityId]) }}" 
                       class="button button-secondary button-icon" 
                       title="Voltar para Gráficos">
                    &larr;
                    </a>

                    {{-- Botão Direita: Fim do fluxo --}}
                    <button class="button button-secondary button-icon" disabled type="button">&rarr;</button>
                </div>
                
                <h2>Recomendação final</h2>
                
                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Fechar">&times;</a>
            </div>

            @php
                $recomendacao = $aiSummary['recomendacao'] ?? 'Recomendação automática indisponível.';
                $logistica = $aiSummary['logistica'] ?? [];
                $indicadores = $aiSummary['indicadores'] ?? [];
                $timelineLabels = ($timelineSeries ?? collect())->pluck('mes_ano');
                $timelineValues = ($timelineSeries ?? collect())->pluck('preco_medio');
                $chartMin = $timelineValues->count() ? max(min($timelineValues->toArray()) - 5, 0) : 0;
                $chartMax = $timelineValues->count() ? max($timelineValues->toArray()) + 5 : 100;
            @endphp

            <div class="conclusion-container">
                
                <div class="conclusion-text">
                    <p>{{ $recomendacao }}</p>
                    @if(!empty($logistica['melhor_rota']))
                        <p><strong>Melhor rota logística:</strong> {{ $logistica['melhor_rota'] }}</p>
                    @endif
                    @if(isset($logistica['custo_estimado']))
                        <p><strong>Custo logístico estimado:</strong> {{ number_format($logistica['custo_estimado'], 2, ',', '.') }}%</p>
                    @endif
                    @if(!empty($logistica['observacoes']))
                        <p>{{ $logistica['observacoes'] }}</p>
                    @endif
                    <p>
                        Indicadores atuais: média Brasil em 
                        <strong>R${{ number_format($indicadores['media_brasil'] ?? 0, 2, ',', '.') }}/kg</strong>,
                        média global em 
                        <strong>R${{ number_format($indicadores['media_global'] ?? 0, 2, ',', '.') }}/kg</strong>,
                        risco <strong>{{ $indicadores['risco'] ?? '-' }}</strong> e
                        estabilidade <strong>{{ $indicadores['estabilidade'] ?? '-' }}</strong>.
                    </p>
                </div>

                <div class="chart-wrapper">
                    <canvas id="finalChart"></canvas>
                </div>

            </div>

            <div class="actions-footer">
                {{-- MENSAGEM DE LOADING (oculta por padrão) --}}
                <span id="msgLoading" style="display:none; color: var(--gray-500); font-weight: 600;">Gerando PDF...</span>

                {{-- BOTÃO EXPORTAR PDF (Via JavaScript + Iframe) --}}
                <button id="btnExportar" 
                        class="button button-export" 
                        data-url="{{ route('previsoes.exportarPdf', ['id' => $analysisId ?? $commodityId]) }}">
                    Exportar PDF
                </button>
                
                <button class="button button-export">Exportar CSV</button>
            </div>

        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartLabels = @json($timelineLabels->toArray());
        const chartValues = @json($timelineValues->toArray());
        const ctx = document.getElementById('finalChart').getContext('2d');
        const finalLabels = chartLabels.length ? chartLabels : ['Sem dados'];
        const finalValues = chartValues.length ? chartValues : [0];

        const data = {
            labels: finalLabels,
            datasets: [{
                label: 'Preço Médio (R$/kg)',
                data: finalValues,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#f97316',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.3
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) { return `Preço: R$${context.raw.toFixed(2)}`; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: {{ $chartMin }},
                        max: {{ $chartMax }},
                        title: { display: true, text: 'Previsão de Preço (R$/kg)', color: '#4b5563', font: { weight: 'bold' } },
                        grid: { display: false, drawBorder: true },
                        border: { display: true, width: 2, color: '#9ca3af' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold' }, color: '#374151' },
                        border: { display: true, width: 2, color: '#9ca3af' }
                    }
                }
            }
        };

        new Chart(ctx, config);


        // --- LÓGICA DE EXPORTAÇÃO PDF CORRIGIDA (IFRAME OFF-SCREEN) ---
        const btnExportar = document.getElementById('btnExportar');
        if(btnExportar) {
            btnExportar.addEventListener('click', function() {
                var btn = this;
                var url = btn.getAttribute('data-url');
                var msg = document.getElementById('msgLoading');
                var originalText = btn.innerText;

                // 1. Feedback visual
                btn.disabled = true;
                btn.innerText = 'Processando...';
                if(msg) msg.style.display = 'inline';

                // 2. Cria o iframe (FORA DA TELA, NÃO INVISÍVEL COM DISPLAY:NONE)
                // Se usar display:none, o html2pdf não consegue renderizar o conteúdo.
                var iframe = document.createElement('iframe');
                
                // Truque: Mover o iframe para fora da viewport visível
                iframe.style.position = 'fixed';
                iframe.style.left = '-10000px'; 
                iframe.style.top = '0';
                iframe.style.width = '1000px'; // Tamanho físico necessário para renderizar
                iframe.style.height = '1000px';
                
                iframe.src = url;
                
                // 3. Adiciona ao DOM para disparar a requisição
                document.body.appendChild(iframe);

                // 4. Restaura estado após 5s
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    if(msg) msg.style.display = 'none';
                    
                    // Remove o iframe para limpar memória
                    setTimeout(() => {
                        if(document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }, 1000); 
                }, 8000); // 8 segundos para garantir o tempo de processamento dos gráficos
            });
        }
    });
</script>
</body>
</html>
