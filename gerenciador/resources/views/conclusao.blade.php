<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendação Final - Análise</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
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

                /* TOP BAR (Estilo Painel Principal) */
        .top-bar { background: var(--white); padding: 1.5rem clamp(1.5rem, 3vw, 3rem); display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08); }
        .profile { display: flex; align-items: center; gap: 1rem; }
        .avatar { width: 64px; height: 64px; border-radius: 18px; object-fit: cover; border: 3px solid var(--gray-200); }
        .profile-info strong { font-size: 1.25rem; display: block; }
        .profile-info span { color: var(--gray-500); font-size: 0.95rem; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }

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

        .button-icon { padding: 0.6rem 0.8rem; line-height: 1; font-size: 1.2rem; }

        .button-export {
            background-color: #374151; /* Cinza Escuro */
            color: #fff;
            border-radius: 50px; /* Redondo */
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }
        .button-export:hover { background-color: #1f2937; }
        .button-export:disabled { background-color: #9ca3af; cursor: wait; transform: none; }

        /* --- LAYOUT PRINCIPAL (padronizado) --- */
        main.content {
            flex: 1;
            width: min(1280px, 100%); /* Largura padronizada */
            margin: 0 auto;
            padding: 1.5rem; /* Padding padronizado */
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

        /* Cabeçalho (padronizado) */
        .header-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        .header-row h2 { margin: 0; color: var(--gray-700); font-size: 1.4rem; font-weight: 700; }
        .nav-group { display: flex; gap: 0.5rem; }


        /* Conteúdo dividido (Corpo da Análise) */
        /* Renomeado para analysis-body para consistência */
        .analysis-body {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 3rem;
            align-items: center;
            flex: 1; 
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
            padding: 15px; /* Padding padronizado */
            height: 380px;
            position: relative;
        }

        /* Rodapé de Ações (padronizado) */
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

        #msgLoading { font-weight: 600; color: var(--primary); display: none; animation: pulse 1.5s infinite; }

        @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }

        @media (max-width: 900px) {
            body { overflow: auto; height: auto; }
            .card { height: auto; overflow: visible; }
            .analysis-body { grid-template-columns: 1fr; height: auto; overflow: visible; padding-right: 0; }
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
            <div class="header-row">
                <div class="nav-group">
                    {{-- Botão Esquerda: Volta para Gráficos --}}
                    <a href="{{ route('previsoes.graficos.show', ['id' => $analysisId ?? $commodityId]) }}" 
                       class="button button-secondary button-icon" 
                       title="Voltar para Gráficos">
                    &larr;
                    </a>
                    {{-- Botão Direita: Desabilitado (final da navegação) --}}
                    <button class="button button-secondary button-icon" disabled>&rarr;</button>
                </div>
                
                <h2>Recomendação Estratégica</h2>
                
                <a href="{{ route('home') }}" class="button button-secondary button-icon" title="Voltar para Home">
                    &times;
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

            <div class="analysis-body">
                
                <div class="text-block">
                    <p>{{ $recomendacao }}</p>
                </div>

                <div class="chart-container">
                    <canvas id="projectionChart"></canvas>
                </div>

            </div>

            <div class="footer-actions">
                {{-- MENSAGEM DE LOADING (oculta por padrão) --}}
                <span id="msgLoading" style="display:none; color: var(--primary); font-weight: 600;">Gerando PDF...</span>

                {{-- BOTÃO EXPORTAR PDF (Via JavaScript + Iframe) --}}
                <button id="btnExportar" 
                        class="button button-export" 
                        data-url="{{ route('previsoes.exportarPdf', ['id' => $analysisId ?? $commodityId]) }}">
                    Exportar Relatório em PDF
                </button>
            </div>

        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('projectionChart').getContext('2d');
        
        // Dados do Controller (TimelineSeries)
        const timelineValues = @json($timelineValues->toArray());
        const timelineLabels = @json($timelineLabels->toArray());
        
        // Estilos e Configurações replicadas da Home:
        const homeBorderColor = '#F97316';
        const homeBackgroundColor = 'rgba(249, 115, 22, 0.1)';
        const homeTension = 0.1; // Linha mais reta/suave
        
        // Valores min/max calculados no PHP, garantindo o zoom nos valores.
        const chartMin = {{ $chartMin }};
        const chartMax = {{ $chartMax }};

        // Usa os dados do backend.
        const finalValues = timelineValues.length > 0 ? timelineValues : [0, 0];
        const finalLabels = timelineLabels.length > 0 ? timelineLabels : ['N/A', 'N/A'];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: finalLabels.slice(0, finalValues.length),
                datasets: [{
                    label: 'Preço Médio (R$/kg)',
                    data: finalValues,
                    // ESTILOS IGUAIS AO GRÁFICO HOME (Laranja, Linha Reta)
                    borderColor: homeBorderColor,
                    backgroundColor: homeBackgroundColor,
                    borderWidth: 2, 
                    fill: true,
                    tension: homeTension, // Corrigido para 0.1 (linha suave)
                    pointBackgroundColor: homeBorderColor,
                    pointBorderColor: '#ffffff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Tendência Histórica e Projetada', font: { size: 16 } },
                    tooltip: {
                        mode: 'index', intersect: false,
                        backgroundColor: '#1e293b',
                        callbacks: {
                            // Callbacks da Home/Geral
                            label: ctx => (ctx.dataset.label || '') + (ctx.parsed.y ? ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        // ESCALA DINÂMICA (Baseada nos valores do PHP)
                        min: chartMin,
                        max: chartMax,
                        ticks: { color: '#4b5563', callback: v => 'R$ ' + v.toLocaleString('pt-BR') }, // Ticks da Home
                        title: { display: true, text: 'Preço (R$/kg)', color: '#4b5563', font: { weight: 'bold' } },
                        grid: { display: true, color: '#e5e7eb' }, 
                        border: { display: true, width: 2, color: '#9ca3af' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // 2. LÓGICA DE EXPORTAÇÃO PDF
        const btnExport = document.getElementById('btnExportar');
        const msgLoading = document.getElementById('msgLoading');

        if (btnExport) {
            btnExport.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                
                this.disabled = true;
                this.style.opacity = '0.7';
                msgLoading.style.display = 'inline-block';

                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.left = '-9999px'; 
                iframe.style.top = '0';
                iframe.style.width = '1px';
                iframe.style.height = '1px';
                iframe.src = url;

                document.body.appendChild(iframe);

                setTimeout(() => {
                    this.disabled = false;
                    this.style.opacity = '1';
                    msgLoading.style.display = 'none';
                    
                    setTimeout(() => {
                        if(document.body.contains(iframe)) {
                            document.body.removeChild(iframe);
                        }
                    }, 2000);
                }, 5000); 
            });
        }
    });
</script>
</body>
</html> 