<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficos - Previsão de Commodities</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Importação do Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    
    <style>
        /* --- 1. ESTILOS GERAIS --- */
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
            height: 100vh; /* Fixa altura do corpo */
            overflow: hidden; /* Evita scroll na página inteira */
        }

        .page {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* --- TOP BAR --- */
        .top-bar {
            background: var(--white);
            padding: 1rem clamp(1.5rem, 3vw, 3rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08);
            z-index: 10;
            flex-shrink: 0; /* Não encolhe */
        }

        .profile { display: flex; align-items: center; gap: 1rem; }
        .avatar { width: 48px; height: 48px; border-radius: 12px; object-fit: cover; border: 2px solid var(--gray-200); }
        .profile-info strong { font-size: 1.1rem; display: block; }
        .profile-info span { color: var(--gray-500); font-size: 0.85rem; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }

        /* --- BOTÕES --- */
        .button {
            border: none; border-radius: 10px; padding: 0.6rem 1.2rem;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 0.4rem; text-decoration: none;
        }
        .button:hover { transform: translateY(-1px); box-shadow: 0 8px 15px rgba(37, 99, 235, 0.15); }
        .button-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); }
        .button-secondary { background: var(--white); border: 1px solid var(--gray-300); color: var(--gray-700); }
        .button-secondary:hover { background: var(--gray-50); box-shadow: none; transform: none; }
        .button-icon { padding: 0.5rem 0.7rem; line-height: 1; }
        .button[disabled] { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; background: var(--gray-100); }

        /* --- LAYOUT PRINCIPAL --- */
        main.content {
            flex: 1; 
            width: min(1280px, 100%); 
            margin: 0 auto;
            padding: 1.5rem clamp(1rem, 2vw, 2.5rem);
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Segura o scroll */
        }

        .card {
            background: var(--white); 
            border-radius: 22px; 
            padding: 1.5rem;
            box-shadow: 0 22px 45px -30px rgba(15, 23, 42, 0.3);
            display: flex; 
            flex-direction: column;
            height: 100%; /* Ocupa todo espaço disponível */
            overflow: hidden; /* Garante que nada vaze */
        }

        .analysis-header {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; padding-bottom: 1rem; margin-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0; /* Cabeçalho fixo */
        }
        .analysis-header h2 { margin: 0; font-size: 1.4rem; color: var(--gray-600); font-weight: 700; }
        .analysis-header .nav-buttons { display: flex; gap: 0.5rem; }

        /* --- DASHBOARD GRID LAYOUT --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 220px 1fr; 
            gap: 1.5rem;
            height: 100%; /* Ocupa altura restante */
            overflow: hidden; /* Previne scroll no grid pai */
        }

        /* --- LEGENDA LATERAL --- */
        .legend-sidebar {
            background-color: #f8fafc;
            border-radius: 16px;
            padding: 1rem;
            border: 1px solid var(--gray-200);
            height: 100%; /* Altura total */
            overflow-y: auto; /* Scroll se muitos países */
        }

        .legend-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            font-weight: 700;
            margin-bottom: 1rem;
            padding-left: 0.2rem;
        }

        .legend-item {
            display: flex; align-items: center; gap: 0.8rem;
            font-weight: 600; font-size: 0.85rem; color: var(--gray-700);
            cursor: pointer;
            padding: 0.5rem 0.8rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            user-select: none;
            margin-bottom: 0.4rem;
        }

        .legend-item:hover { background-color: #e2e8f0; }
        .legend-item.hidden-item { opacity: 0.5; text-decoration: line-through; color: var(--gray-400); }
        .legend-color { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 2px #fff; }

        /* --- ÁREA DOS GRÁFICOS (SCROLLABLE) --- */
        .charts-container {
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
            height: 100%; /* Altura total */
            overflow-y: auto; /* O SCROLL ACONTECE AQUI */
            padding-right: 10px; /* Espaço para scrollbar */
            padding-bottom: 2rem;
        }

        /* Estilizando a scrollbar para ficar bonita */
        .charts-container::-webkit-scrollbar { width: 8px; }
        .charts-container::-webkit-scrollbar-track { background: transparent; }
        .charts-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .charts-container::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

        .chart-wrapper {
            width: 100%;
            position: relative;
            height: 350px; /* Altura um pouco menor para caber melhor */
            min-height: 350px;
            padding: 15px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            flex-shrink: 0; /* Impede achatamento */
        }

        .chart-wrapper.radar-wrapper {
            height: 450px;
            min-height: 450px;
        }

        /* Responsividade */
        @media (max-width: 1000px) {
            body { overflow: auto; height: auto; }
            .page { height: auto; }
            .card { height: auto; overflow: visible; }
            .top-bar { flex-direction: column; align-items: flex-start; }
            .dashboard-grid { grid-template-columns: 1fr; height: auto; overflow: visible; }
            .charts-container { overflow-y: visible; height: auto; padding-right: 0; }
            .legend-sidebar { height: auto; display: flex; flex-wrap: wrap; gap: 0.5rem; }
            .legend-title { width: 100%; }
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
                    <a href="{{ route('previsoes.show', ['id' => $commodityId]) }}" 
                       class="button button-secondary button-icon" title="Voltar">&larr;</a>
                    <a href="{{ route('previsoes.conclusao.show', ['id' => $commodityId]) }}" 
                       class="button button-secondary button-icon" title="Ir para Conclusão">&rarr;</a>
                </div>
                
                <h2>Análise Comparativa Detalhada</h2>

                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Fechar">&times;</a>
            </div>

            <div class="dashboard-grid">
                
                {{-- ASIDE: Legenda Fixa --}}
                <aside class="legend-sidebar">
                    <div class="legend-title">Países (Clique para filtrar)</div>
                    <div id="dynamicLegend">
                        <span style="font-size: 0.9rem; color: #666;">Carregando...</span>
                    </div>
                </aside>

                {{-- MAIN: Gráficos (Scroll aqui dentro) --}}
                <div class="charts-container">
                    <!-- 1. Gráfico de Preços -->
                    <div class="chart-wrapper">
                        <canvas id="chartPrice"></canvas>
                    </div>

                    <!-- 2. Gráfico de Logística -->
                    <div class="chart-wrapper">
                        <canvas id="chartLogistics"></canvas>
                    </div>

                    <!-- 3. Gráfico de Estabilidade -->
                    <div class="chart-wrapper">
                        <canvas id="chartStability"></canvas>
                    </div>

                    <!-- 4. Radar Geral -->
                    <div class="chart-wrapper radar-wrapper">
                        <canvas id="chartRadar"></canvas>
                    </div>
                </div>

            </div>
        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Dados do Banco
        const rawData = @json($chartData ?? []);

        if (!rawData || rawData.length === 0) {
            document.getElementById('dynamicLegend').innerHTML = '<span style="color:red; padding: 0.5rem;">Sem dados disponíveis.</span>';
            return;
        }

        // Estado de visibilidade
        let visibilityState = {};
        rawData.forEach(d => visibilityState[d.pais] = true);

        // 2. Cores
        function getColor(country) {
            if (country.toLowerCase().includes('brasil')) return '#10b981'; 
            const palette = ['#3b82f6', '#ef4444', '#f97316', '#8b5cf6', '#ec4899', '#06b6d4', '#eab308', '#64748b', '#14b8a6', '#6366f1'];
            let hash = 0;
            for (let i = 0; i < country.length; i++) hash = country.charCodeAt(i) + ((hash << 5) - hash);
            return palette[Math.abs(hash) % palette.length];
        }

        function getStabilityScore(text) {
            if (!text) return 0;
            text = text.toLowerCase();
            if (text.includes('alta')) return 3;
            if (text.includes('média') || text.includes('media')) return 2;
            if (text.includes('baixa')) return 1;
            return 0;
        }

        function getStabilityLabel(score) {
            if (score === 3) return 'Alta';
            if (score === 2) return 'Média';
            if (score === 1) return 'Baixa';
            return ''; 
        }

        // 3. CÁLCULO DOS VALORES MÁXIMOS E MÍNIMOS GLOBAIS (BASE ESTÁTICA)
        // Isso garante que a escala não mude quando filtramos países
        const allPrices = rawData.map(d => parseFloat(d.preco_medio));
        const allLogistics = rawData.map(d => parseFloat(d.logistica_perc));

        // Se só houver 1 item, evita NaN/Zero
        const globalMaxPrice = allPrices.length ? Math.max(...allPrices) : 1;
        const globalMinPrice = allPrices.length ? Math.min(...allPrices) : 0;
        
        const globalMaxLogistics = allLogistics.length ? Math.max(...allLogistics) : 1;
        const globalMinLogistics = allLogistics.length ? Math.min(...allLogistics) : 0;

        // Inicialização
        let chartPrice, chartLogistics, chartStability, chartRadar;

        function initCharts() {
            const activeData = rawData.filter(d => visibilityState[d.pais]);
            
            const labels = activeData.map(d => d.pais);
            const prices = activeData.map(d => parseFloat(d.preco_medio));
            const logistics = activeData.map(d => parseFloat(d.logistica_perc));
            const stability = activeData.map(d => getStabilityScore(d.estabilidade));
            const bgColors = labels.map(l => getColor(l));

            const commonOptions = {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { weight: 'bold' }, color: '#6b7280' } },
                    x: { grid: { display: false }, ticks: { font: { weight: 'bold' }, color: '#374151' } }
                }
            };

            const barOptions = { maxBarThickness: 40, borderRadius: 4 };

            // --- 1. PREÇO ---
            const ctxPrice = document.getElementById('chartPrice').getContext('2d');
            if(chartPrice) chartPrice.destroy();
            
            const optPrice = JSON.parse(JSON.stringify(commonOptions));
            optPrice.plugins.title = { display: true, text: '1. Comparativo de Preços (R$/kg)', font: {size: 16, weight: '700'} };
            
            chartPrice = new Chart(ctxPrice, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Preço', data: prices, backgroundColor: bgColors, ...barOptions }]
                },
                options: optPrice
            });

            // --- 2. LOGÍSTICA ---
            const ctxLog = document.getElementById('chartLogistics').getContext('2d');
            if(chartLogistics) chartLogistics.destroy();

            const optLog = JSON.parse(JSON.stringify(commonOptions));
            optLog.plugins.title = { display: true, text: '2. Custo Logístico (%)', font: {size: 16, weight: '700'} };

            chartLogistics = new Chart(ctxLog, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Logística', data: logistics, backgroundColor: bgColors, ...barOptions }]
                },
                options: optLog
            });

            // --- 3. ESTABILIDADE ---
            const ctxStab = document.getElementById('chartStability').getContext('2d');
            if(chartStability) chartStability.destroy();

            const optStab = JSON.parse(JSON.stringify(commonOptions));
            optStab.plugins.title = { display: true, text: '3. Estabilidade Política/Econômica', font: {size: 16, weight: '700'} };
            
            optStab.scales.y.min = 0; 
            optStab.scales.y.max = 4; 
            optStab.scales.y.ticks.stepSize = 0.5; 
            optStab.scales.y.ticks.callback = val => getStabilityLabel(val);

            chartStability = new Chart(ctxStab, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Estabilidade', data: stability, backgroundColor: bgColors, ...barOptions }]
                },
                options: optStab
            });

            // --- 4. RADAR (COM BASE GLOBAL ESTÁTICA E PISO MÍNIMO) ---
            const ctxRadar = document.getElementById('chartRadar').getContext('2d');
            if(chartRadar) chartRadar.destroy();

            const radarDatasets = activeData.map(d => {
                const c = getColor(d.pais);
                
                const valPrice = parseFloat(d.preco_medio);
                const valLog = parseFloat(d.logistica_perc);
                const valStab = getStabilityScore(d.estabilidade);

                // FUNÇÃO DE NORMALIZAÇÃO COM PISO (FLOOR)
                // Mapeia o intervalo [min, max] para [20, 100]
                // Evita que o triângulo colapse para 0
                const normalizeWithFloor = (val, min, max, invert) => {
                    if (max === min) return 100;
                    let ratio = 0;
                    if (invert) {
                        ratio = (max - val) / (max - min);
                    } else {
                        ratio = (val - min) / (max - min);
                    }
                    // Escala de 20 a 100
                    return 20 + (ratio * 80);
                };

                // 1. Preço (Menor é Melhor, Invertido)
                const sPrice = normalizeWithFloor(valPrice, globalMinPrice, globalMaxPrice, true);

                // 2. Logística (Menor é Melhor, Invertido)
                const sLog = normalizeWithFloor(valLog, globalMinLogistics, globalMaxLogistics, true);

                // 3. Estabilidade (Maior é Melhor) - Base fixa 3
                // 1=33%, 2=66%, 3=100%. 
                // Para o pior caso (1), o valor é 33, que já é um "piso" seguro.
                const sStab = (valStab / 3) * 100;

                return {
                    label: d.pais,
                    // GUARDA OS VALORES ORIGINAIS PARA EXIBIR NO TOOLTIP
                    rawValues: {
                        price: valPrice,
                        logistics: valLog,
                        stability: d.estabilidade
                    },
                    data: [sPrice, sLog, sStab],
                    fill: true,
                    backgroundColor: c + '20',
                    borderColor: c,
                    pointBackgroundColor: c,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: c
                };
            });

            chartRadar = new Chart(ctxRadar, {
                type: 'radar',
                data: {
                    // ALTERADO: Labels mais diretos conforme pedido
                    labels: ['Preço (R$/kg)', 'Logística (%)', 'Estabilidade'],
                    datasets: radarDatasets
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        title: { display: true, text: '4. Síntese de Atratividade (Base Comparativa Global)', font: {size: 16, weight: '700'} },
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                // TOOLTIP PERSONALIZADO PARA MOSTRAR VALOR REAL
                                label: function(context) {
                                    const dataset = context.dataset;
                                    const index = context.dataIndex; // 0=Preço, 1=Logistica, 2=Estabilidade
                                    const values = dataset.rawValues;
                                    
                                    let label = dataset.label || '';
                                    if (label) { label += ': '; }

                                    if (index === 0) { // Preço
                                        return label + 'R$ ' + values.price.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                    } else if (index === 1) { // Logística
                                        return label + values.logistics + '%';
                                    } else if (index === 2) { // Estabilidade
                                        return label + values.stability;
                                    }
                                    
                                    return label + Math.round(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            angleLines: { display: true },
                            suggestedMin: 0,
                            suggestedMax: 100,
                            ticks: { display: false, backdropColor: 'transparent' },
                            pointLabels: { font: { size: 12, weight: 'bold' }, color: '#4b5563' }
                        }
                    }
                }
            });
        }

        function renderLegend() {
            const container = document.getElementById('dynamicLegend');
            container.innerHTML = '';

            rawData.forEach(item => {
                const color = getColor(item.pais);
                const isVisible = visibilityState[item.pais];
                
                const itemDiv = document.createElement('div');
                itemDiv.className = `legend-item ${!isVisible ? 'hidden-item' : ''}`;
                itemDiv.innerHTML = `<div class="legend-color" style="background-color: ${color}"></div> ${item.pais}`;
                
                itemDiv.addEventListener('click', () => {
                    visibilityState[item.pais] = !visibilityState[item.pais];
                    renderLegend();
                    initCharts(); 
                });

                container.appendChild(itemDiv);
            });
        }

        renderLegend();
        initCharts();
    });
</script>
</body>
</html>