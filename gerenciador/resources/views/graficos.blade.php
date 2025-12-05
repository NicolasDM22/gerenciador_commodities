<!--by Matias Amma e Gustavo Cavalheiro-->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficos - Análise de {{ $nomeCommodity ?? 'Commodities' }}</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-500: #6b7280; --gray-600: #4b5563;
            --gray-700: #374151; --gray-900: #111827;
            --primary: #2563eb; --primary-dark: #1d4ed8;
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

        /* TOP BAR (Estilo Painel Principal) */
        .top-bar { background: var(--white); padding: 1.5rem clamp(1.5rem, 3vw, 3rem); display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08); }
        .profile { display: flex; align-items: center; gap: 1rem; }
        .avatar { width: 64px; height: 64px; border-radius: 18px; object-fit: cover; border: 3px solid var(--gray-200); }
        .profile-info strong { font-size: 1.25rem; display: block; }
        .profile-info span { color: var(--gray-500); font-size: 0.95rem; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }

        .button {
            border: none; border-radius: 12px; padding: 0.6rem 1.2rem;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; color: var(--gray-700); border: 1px solid var(--gray-300);
            background: var(--white);
        }
        .button:hover { transform: translateY(-1px); background: var(--gray-50); }
        .button-primary { border-color: var(--primary); color: var(--primary); }
        
        /* Estilos adicionais para botões de navegação (setas) */
        .button-secondary { background: var(--white); border: 1px solid var(--gray-300); color: var(--gray-700); }
        .button-secondary:hover { background: var(--gray-50); }
        .button-icon { padding: 0.6rem 0.8rem; line-height: 1; font-size: 1.2rem; }

        main.content {
            flex: 1;
            width: min(1400px, 100%);
            margin: 0 auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .card {
            background: var(--white);
            border-radius: 22px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            position: relative; 
        }

        .header-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        .header-row h2 { margin: 0; color: var(--gray-700); font-size: 1.4rem; font-weight: 700; }
        .nav-group { display: flex; gap: 0.5rem; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 1.5rem;
            height: 100%;
            overflow: hidden;
        }

        .legend-sidebar {
            background: #f8fafc;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 1rem;
            overflow-y: auto;
        }
        .legend-title {
            font-size: 0.8rem; text-transform: uppercase; color: var(--gray-500);
            font-weight: bold; margin-bottom: 1rem; letter-spacing: 0.5px;
        }
        .legend-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; margin-bottom: 5px;
            background: white; border-radius: 8px; border: 1px solid transparent;
            cursor: pointer; transition: all 0.2s; font-size: 0.9rem; font-weight: 500;
        }
        .legend-item:hover { border-color: var(--gray-300); transform: translateX(2px); }
        .legend-item.hidden-item { opacity: 0.5; text-decoration: line-through; filter: grayscale(1); }
        .color-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }

        .charts-scroll-area {
            overflow-y: auto;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding-bottom: 2rem;
        }
        
        .charts-scroll-area::-webkit-scrollbar { width: 8px; }
        .charts-scroll-area::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }

        .chart-box {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 15px;
            height: 350px;
            position: relative;
            flex-shrink: 0;
        }
        .radar-box { height: 500px; }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-500);
            text-align: center;
            width: 100%;
        }

        @media (max-width: 900px) {
            body { overflow: auto; height: auto; }
            .dashboard-grid { grid-template-columns: 1fr; overflow: visible; height: auto; }
            .legend-sidebar { height: auto; display: flex; flex-wrap: wrap; gap: 0.5rem; }
            .charts-scroll-area { overflow: visible; height: auto; }
        }
    </style>
</head>
<body>
<div class="page">
    
    <x-topbar :user="$user" :isAdmin="$isAdmin ?? false">
        <!-- Botões adicionais específicos desta tela, se necessário -->
    </x-topbar>

    <main class="content">
        <section class="card">
            <div class="header-row">
                <div class="nav-group">
                    <a href="{{ route('previsoes.show', ['id' => $analysisId, 'commodity_id' => $commodityId]) }}" class="button button-secondary button-icon" title="Voltar para Descritiva">&larr;</a>
                    
                    <a href="{{ route('previsoes.conclusao.show', ['id' => $analysisId, 'commodity_id' => $commodityId]) }}" class="button button-secondary button-icon" title="Ir para Conclusão">&rarr;</a>
                </div>
                
                <h2 id="pageTitle">Gráficos: {{ $nomeCommodity }}</h2>
                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Voltar">&times;</a>
            </div>

            @if(isset($chartData) && count($chartData) > 0)
            <div class="dashboard-grid">
                
                <aside class="legend-sidebar">
                    <div class="legend-title">Filtro de Países</div>
                    <div id="legendContainer">
                        <div style="color:#666; font-size:0.9rem;">Carregando...</div>
                    </div>
                </aside>

                <div class="charts-scroll-area">
                    
                    <div class="chart-box">
                        <canvas id="chartPrice"></canvas>
                    </div>

                    <div class="chart-box">
                        <canvas id="chartLogistics"></canvas>
                    </div>

                    <div class="chart-box">
                        <canvas id="chartStability"></canvas>
                    </div>

                    <div class="chart-box radar-box">
                        <canvas id="chartRadar"></canvas>
                    </div>

                </div>
            </div>
            @else
            <div class="empty-state">
                <h3>Ainda não existem análises disponíveis</h3>
                <p>Não há dados suficientes para gerar os gráficos comparativos para esta commodity no momento.</p>
            </div>
            @endif
        </section>
    </main>
</div>

<script>
    // Injeção de dados segura vinda do Controller
    const SERVER_DATA = @json($chartData ?? []);
    const COMMODITY_NAME = @json($nomeCommodity ?? 'Geral');

    class ChartController {
        constructor() {
            this.commodityName = COMMODITY_NAME;
            this.charts = {}; 
            this.visibilityState = {}; 
            
            if(!SERVER_DATA || SERVER_DATA.length === 0) return;

            this.chartData = SERVER_DATA; 
            
            this.init();
        }

        init() {
            this.chartData.forEach(d => this.visibilityState[d.pais] = true);

            this.renderLegend();
            this.renderCharts();
        }

        getColor(country) {
            const c = country.toLowerCase();
            if(c.includes('brasil')) return '#10b981'; 
            if(c.includes('china')) return '#ef4444'; 
            if(c.includes('eua') || c.includes('usa')) return '#3b82f6'; 
            if(c.includes('argentina')) return '#60a5fa'; 
            
            let hash = 0;
            for (let i = 0; i < country.length; i++) hash = country.charCodeAt(i) + ((hash << 5) - hash);
            const hex = (hash & 0x00FFFFFF).toString(16).toUpperCase();
            return '#' + "00000".substring(0, 6 - hex.length) + hex;
        }

        getStabilityScore(text) {
            if(!text) return 0;
            const t = text.toLowerCase();
            if(t.includes('alta')) return 3;
            if(t.includes('média') || t.includes('media')) return 2;
            return 1; 
        }

        renderLegend() {
            const container = document.getElementById('legendContainer');
            if(!container) return;
            container.innerHTML = '';

            this.chartData.forEach(d => {
                const isVisible = this.visibilityState[d.pais];
                const color = this.getColor(d.pais);

                const item = document.createElement('div');
                item.className = `legend-item ${!isVisible ? 'hidden-item' : ''}`;
                item.innerHTML = `<div class="color-dot" style="background:${color}"></div> ${d.pais}`;
                
                item.onclick = () => {
                    this.visibilityState[d.pais] = !isVisible;
                    this.renderLegend();
                    this.renderCharts();
                };

                container.appendChild(item);
            });
        }

        renderCharts() {
            const activeData = this.chartData.filter(d => this.visibilityState[d.pais]);
            const labels = activeData.map(d => d.pais);
            const bgColors = labels.map(l => this.getColor(l));

            const commonConfig = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                }
            };

            // Plugin Simples para desenhar valores em cima das barras
            const barValuesPlugin = {
                id: 'barValuesPlugin',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        if (meta.hidden) return;
                        
                        meta.data.forEach((element, index) => {
                            const value = dataset.data[index];
                            if (value !== null && value !== undefined) {
                                ctx.save();
                                ctx.font = 'bold 11px "Segoe UI", sans-serif';
                                ctx.fillStyle = '#4b5563'; 
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                // Exibe apenas o número (Score), sem %
                                ctx.fillText(value.toFixed(1), element.x, element.y - 5);
                                ctx.restore();
                            }
                        });
                    });
                }
            };

            // 1. CHART PREÇO
            if(this.charts.price) this.charts.price.destroy();
            this.charts.price = new Chart(document.getElementById('chartPrice'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Preço Médio (Convertido)',
                        data: activeData.map(d => d.preco_medio),
                        backgroundColor: bgColors,
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: { 
                    ...commonConfig, 
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { callback: v => 'R$ ' + v },
                            grid: { color: '#f3f4f6' } 
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: { 
                        title: { display: true, text: 'Comparativo de Preços (Base BRL)', font: {size:16} }, 
                        legend: {display:false},
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                            }
                        }
                    }
                }
            });

            // 2. CHART LOGÍSTICA (Alterado para Eficiência - Score 0 a 100)
            if(this.charts.logistics) this.charts.logistics.destroy();
            this.charts.logistics = new Chart(document.getElementById('chartLogistics'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Eficiência Logística (Score)',
                        // Lógica idêntica ao Radar: 100 - (custo * 4), mínimo 0
                        data: activeData.map(d => Math.max(0, 100 - (d.logistica_perc * 4))),
                        backgroundColor: bgColors.map(c => c + 'AA'),
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                plugins: [barValuesPlugin], 
                options: { 
                    ...commonConfig,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: 100, // Score máximo é 100
                            ticks: { callback: v => v }, // Sem símbolo %
                            grid: { color: '#f3f4f6' } 
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: { 
                        title: { display: true, text: 'Eficiência Logística (Score 0-100)', font: {size:16} }, 
                        legend: {display:false},
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(1)
                            }
                        }
                    }
                }
            });

            // 3. CHART ESTABILIDADE
            if(this.charts.stability) this.charts.stability.destroy();
            this.charts.stability = new Chart(document.getElementById('chartStability'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score Estabilidade (1-3)',
                        data: activeData.map(d => this.getStabilityScore(d.estabilidade)),
                        backgroundColor: bgColors,
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: { 
                    ...commonConfig, 
                    scales: { y: { min:0, max:4, ticks: { stepSize: 1, callback: v => ['','Baixa','Média','Alta'][v] || '' } }, x: {grid:{display:false}} },
                    plugins: { title: { display: true, text: 'Nível de Estabilidade', font: {size:16} }, legend: {display:false} }
                }
            });

            // 4. CHART RADAR
            if(this.charts.radar) this.charts.radar.destroy();
            
            const radarDatasets = activeData.map(d => {
                const color = this.getColor(d.pais);
                
                // Normalização simples para Radar 0-100
                const priceMax = Math.max(...this.chartData.map(i => i.preco_medio)) || 1;
                const priceScore = d.preco_medio > 0 ? ((priceMax / d.preco_medio) * 80) : 50; 
                const logScore = Math.max(0, 100 - (d.logistica_perc * 4));
                const stabScore = this.getStabilityScore(d.estabilidade) * 33;

                return {
                    label: d.pais,
                    data: [Math.min(100, priceScore), Math.min(100, logScore), stabScore],
                    borderColor: color,
                    backgroundColor: color + '20',
                    pointBackgroundColor: color,
                    borderWidth: 2
                };
            });

            this.charts.radar = new Chart(document.getElementById('chartRadar'), {
                type: 'radar',
                data: {
                    labels: ['Competitividade Preço', 'Eficiência Logística', 'Segurança/Estabilidade'],
                    datasets: radarDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        title: { display: true, text: 'Radar de Oportunidade (Score)', font: {size:16} }
                    },
                    scales: {
                        r: {
                            suggestedMin: 0, suggestedMax: 100,
                            pointLabels: { font: {size: 12, weight:'bold'}, color: '#374151' }
                        }
                    }
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        new ChartController();
    });
</script>
</body>
</html>
