<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendação Final - Análise</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Importação do Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    
    <style>
        /* --- 1. ESTILOS GERAIS (Consistente com as outras telas) --- */
        :root {
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-500: #6b7280; --gray-600: #4b5563;
            --gray-700: #374151; --gray-900: #111827;
            --primary: #2563eb; --primary-dark: #1d4ed8;
            --success: #059669; --danger: #dc2626;
            --white: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--gray-100);
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--gray-900);
            height: 100vh;
            overflow: hidden;
        }

        .page {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* --- BOTÕES --- */
        .button {
            border: none; border-radius: 12px; padding: 0.7rem 1.4rem;
            font-size: 0.95rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 0.5rem; text-decoration: none;
        }
        .button:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .button-secondary {
            background: var(--white);
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }
        .button-secondary:hover { background: var(--gray-50); }
        .button-secondary[disabled] { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        .button-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border: none;
        }

        .button-export {
            background-color: #374151; /* Cinza Escuro */
            color: #fff;
            border-radius: 50px; /* Redondo */
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }
        .button-export:hover { background-color: #1f2937; }
        .button-export:disabled { background-color: #9ca3af; cursor: wait; transform: none; }

        /* --- LAYOUT PRINCIPAL --- */
        main.content {
            flex: 1;
            width: min(1200px, 100%);
            margin: 0 auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .card {
            background: var(--white);
            border-radius: 22px;
            padding: 2rem;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        /* Cabeçalho */
        .header-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        .header-row h2 { margin: 0; color: var(--gray-600); font-size: 1.6rem; font-weight: 700; }

        /* Conteúdo dividido (Texto e Gráfico) */
        .conclusion-body {
            display: grid;
            grid-template-columns: 1fr 1.2fr; /* Texto ocupa menos, gráfico mais */
            gap: 3rem;
            align-items: center;
            flex: 1; /* Ocupa altura disponível */
            overflow-y: auto;
            padding-right: 1rem;
        }

        .text-block p {
            font-size: 1.05rem; line-height: 1.7; color: var(--gray-600);
            margin-bottom: 1.5rem;
        }
        .text-block strong { color: var(--gray-900); font-weight: 700; }
        
        .highlight-box {
            background: #f0fdf4; border-left: 5px solid var(--success);
            padding: 1.5rem; border-radius: 8px; margin-top: 1rem;
        }
        .highlight-box h4 { margin: 0 0 0.5rem 0; color: #166534; font-size: 1.1rem; }
        .highlight-box p { margin: 0; font-size: 0.95rem; color: #14532d; }

        /* Área do Gráfico */
        .chart-container {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 1.5rem;
            height: 380px;
            position: relative;
        }

        /* Rodapé de Ações */
        .footer-actions {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1.5rem;
            flex-shrink: 0;
        }

        #loadingMsg { font-weight: 600; color: var(--primary); display: none; animation: pulse 1.5s infinite; }

        @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }

        @media (max-width: 900px) {
            body { overflow: auto; height: auto; }
            .card { height: auto; overflow: visible; }
            .conclusion-body { grid-template-columns: 1fr; height: auto; overflow: visible; }
            .chart-container { height: 300px; }
            .footer-actions { justify-content: center; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<div class="page">
    <x-topbar :user="$user" />

    <main class="content">
        <section class="card">
            <div class="analysis-header">
                <div class="nav-buttons">
                    {{-- Botão Esquerda: Volta para Gráficos --}}
                    <a href="{{ route('previsoes.graficos.show', ['id' => $analysisId ?? $commodityId]) }}" 
                       class="button button-secondary button-icon" 
                       title="Voltar para Gráficos">
                    &larr;
                    </a>
                    <button class="button button-secondary" disabled>&rarr;</button>
                </div>
                
                <h2>Recomendação Estratégica</h2>
                
                <a href="{{ route('home') }}" class="button button-secondary" style="padding: 0.6rem 1rem;">
                    &times; Fechar
                </a>
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

                <div class="chart-container">
                    <canvas id="projectionChart"></canvas>
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

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mês Atual', '+1 Mês', '+2 Meses', '+3 Meses', '+4 Meses'],
                datasets: [{
                    label: 'Projeção de Preço (R$/kg)',
                    data: [148.50, 150.00, 152.50, 155.00, 156.00], // Dados simulados (Mock da projeção)
                    borderColor: '#2563eb', // Primary Blue
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4, // Curva suave
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Tendência Projetada (Curto Prazo)', font: { size: 16 } },
                    tooltip: {
                        mode: 'index', intersect: false,
                        backgroundColor: '#1e293b',
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2);
                            }
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
                        grid: { display: false }
                    }
                }
            }
        });

        // 2. LÓGICA DE EXPORTAÇÃO PDF (IFRAME INVISÍVEL)
        const btnExport = document.getElementById('btnExportar');
        const msgLoading = document.getElementById('loadingMsg');

        if (btnExport) {
            btnExport.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                
                // Feedback Visual
                this.disabled = true;
                this.style.opacity = '0.7';
                msgLoading.style.display = 'inline-block';

                // Cria o iframe fora da tela
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.left = '-9999px'; // Move para fora da visão
                iframe.style.top = '0';
                iframe.style.width = '1px';
                iframe.style.height = '1px';
                iframe.src = url;

                document.body.appendChild(iframe);

                // Timeout de segurança para reativar o botão
                // (O ideal seria o servidor retornar um cookie de confirmação, mas timeout funciona bem para UX simples)
                setTimeout(() => {
                    this.disabled = false;
                    this.style.opacity = '1';
                    msgLoading.style.display = 'none';
                    
                    // Limpa o iframe do DOM após um tempo seguro
                    setTimeout(() => {
                        if(document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }, 2000);
                }, 5000); // 5 segundos de "loading" simulado enquanto o browser baixa o arquivo
            });
        }
    });
</script>
</body>
</html>
