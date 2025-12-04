<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficos - Análise de Commodities</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Importação do Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    
    <style>
        /* --- 1. ESTILOS GERAIS (Mantendo consistência) --- */
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
            height: 100vh; /* Ocupa altura total da tela */
            overflow: hidden; /* Impede rolagem na janela principal */
        }

        .page {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* --- BOTÕES --- */
        .button {
            border: none; border-radius: 12px; padding: 0.6rem 1.2rem;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; color: var(--gray-700); border: 1px solid var(--gray-300);
            background: var(--white);
        }
        .button:hover { transform: translateY(-1px); background: var(--gray-50); }
        .button[disabled] { opacity: 0.5; cursor: not-allowed; }

        /* --- LAYOUT DO DASHBOARD --- */
        main.content {
            flex: 1;
            width: min(1400px, 100%);
            margin: 0 auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Segura o conteúdo */
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
        }

        .header-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }
        .header-row h2 { margin: 0; color: var(--gray-700); font-size: 1.4rem; }

        /* GRID PRINCIPAL: Legenda (Esquerda) + Gráficos (Direita) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 240px 1fr; /* Largura fixa para legenda */
            gap: 1.5rem;
            height: 100%;
            overflow: hidden;
        }

        /* --- LEGENDA LATERAL --- */
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

        /* --- ÁREA DE GRÁFICOS (COM SCROLL) --- */
        .charts-scroll-area {
            overflow-y: auto;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding-bottom: 2rem;
        }
        
        /* Scrollbar estilizada */
        .charts-scroll-area::-webkit-scrollbar { width: 8px; }
        .charts-scroll-area::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }

        /* Containers dos Gráficos */
        .chart-box {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 15px;
            height: 350px; /* Altura fixa para barras */
            position: relative;
            flex-shrink: 0;
        }
        .radar-box { height: 500px; } /* Radar precisa de mais altura */

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
    {{-- Topbar Component --}}
    <x-topbar :user="$user" />

    <main class="content">
        <section class="card">
            <div class="analysis-header">
                <div class="nav-buttons">
                    <a href="{{ route('previsoes.show', ['id' => $analysisId ?? $commodityId]) }}" 
                       class="button button-secondary button-icon" title="Voltar">&larr;</a>
                    <a href="{{ route('previsoes.conclusao.show', ['id' => $analysisId ?? $commodityId]) }}" 
                       class="button button-secondary button-icon" title="Ir para Conclusão">&rarr;</a>
                </div>
                <h2>Gráficos: {{ $commodityName }}</h2>
                <a href="{{ route('home') }}" class="button" style="padding: 0.5rem 0.8rem;">&times;</a>
            </div>

            <div class="dashboard-grid">
                
                {{-- COLUNA 1: Legenda Interativa --}}
                <aside class="legend-sidebar">
                    <div class="legend-title">Países/Regiões(Clique para filtrar)</div>
                    <div id="dynamicLegend">
                        <span style="font-size: 0.9rem; color: #666;">Carregando...</span>
                    </div>

                    {{-- NOVA SEÇÃO: Filtro de Localizações --}}
                    <div class="legend-title" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                        Localizações
                    </div>
                    <div id="locationFilters">
                        <span style="font-size: 0.9rem; color: #666;">Carregando...</span>
                    </div>
                </aside>

                {{-- MAIN: Gráficos (Scroll aqui dentro) --}}
                <div class="charts-container">
                    <!-- 0. Gráfico de Comparação por Localização (NOVO) -->
                    <div class="chart-wrapper">
                        <canvas id="chartLocationPrice"></canvas>
                    </div>

                    <!-- 1. Gráfico de Preços -->
                    <div class="chart-wrapper">
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
        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Recebe dados do Controller
        const rawData = @json($chartData ?? []);
        const locationData = @json($locationComparison ?? []);

        if (!rawData || rawData.length === 0) {
            document.getElementById('dynamicLegend').innerHTML = '<span style="color:red; padding: 0.5rem;">Sem dados disponíveis.</span>';
            return;
        }

        // Estado de visibilidade
        let visibilityState = {};
        rawData.forEach(d => visibilityState[d.pais] = true);

        // Estado de visibilidade para localizações
        let locationVisibilityState = {};
        if (locationData && locationData.length > 0) {
            locationData.forEach(loc => locationVisibilityState[loc.location_id] = true);
        }

        // 2. Cores
        function getColor(country) {
            if(country.toLowerCase().includes('brasil')) return '#10b981'; // Emerald 500
            if(country.toLowerCase().includes('china')) return '#ef4444';
            if(country.toLowerCase().includes('eua') || country.toLowerCase().includes('usa')) return '#3b82f6';
            
            // Hash simples para cor aleatória consistente
            let hash = 0;
            for (let i = 0; i < country.length; i++) hash = country.charCodeAt(i) + ((hash << 5) - hash);
            const c = (hash & 0x00FFFFFF).toString(16).toUpperCase();
            return '#' + "00000".substring(0, 6 - c.length) + c;
        }

        // Converte texto de estabilidade em número
        function getStabilityScore(text) {
            if(!text) return 0;
            const t = text.toLowerCase();
            if(t.includes('alta')) return 3;
            if(t.includes('média') || t.includes('media')) return 2;
            return 1; // Baixa
        }

        // Instâncias dos gráficos
        let charts = {};

        // 3. CÁLCULO DOS VALORES MÁXIMOS E MÍNIMOS GLOBAIS (BASE ESTÁTICA)
        // Isso garante que a escala não mude quando filtramos países
        const allPrices = rawData.map(d => parseFloat(d.preco_medio ?? 0));
        const allLogistics = rawData.map(d => parseFloat(d.logistica_perc ?? 0));

        // Se só houver 1 item, evita NaN/Zero
        const globalMaxPrice = allPrices.length ? Math.max(...allPrices) : 1;
        const globalMinPrice = allPrices.length ? Math.min(...allPrices) : 0;
        
        const globalMaxLogistics = allLogistics.length ? Math.max(...allLogistics) : 1;
        const globalMinLogistics = allLogistics.length ? Math.min(...allLogistics) : 0;

        // Inicialização
        let chartPrice, chartLogistics, chartStability, chartRadar, chartLocationPrice;

        function initCharts() {
            const activeData = rawData.filter(d => visibilityState[d.pais]);
            const labels = activeData.map(d => d.pais);
            const prices = activeData.map(d => parseFloat(d.preco_medio ?? 0));
            const logistics = activeData.map(d => parseFloat(d.logistica_perc ?? 0));
            const stability = activeData.map(d => getStabilityScore(d.estabilidade));
            const bgColors = labels.map(l => getColor(l));

            // Configuração comum
            const commonConfig = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                }
            };

            const barOptions = { maxBarThickness: 40, borderRadius: 4 };

            // --- 0. COMPARAÇÃO POR LOCALIZAÇÃO (NOVO) ---
            if (locationData && locationData.length > 0) {
                const ctxLocation = document.getElementById('chartLocationPrice').getContext('2d');
                if(chartLocationPrice) chartLocationPrice.destroy();

                const locationLabels = locationData.map(loc => `${loc.location_name} (${loc.regiao})`);
                const locationPrices = locationData.map(loc => parseFloat(loc.price));
                const locationColors = locationLabels.map((_, idx) => {
                    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
                    return colors[idx % colors.length];
                });

                const optLocation = JSON.parse(JSON.stringify(commonOptions));
                optLocation.plugins.title = { 
                    display: true, 
                    text: `0. Comparativo de Preços por Localização - ${locationData[0]?.commodity_name || 'Commodity'}`, 
                    font: {size: 16, weight: '700'} 
                };
                optLocation.plugins.tooltip = {
                    callbacks: {
                        label: function(context) {
                            const loc = locationData[context.dataIndex];
                            return `${loc.price.toFixed(2)} ${loc.currency}/${loc.unidade}`;
                        }
                    }
                };

                chartLocationPrice = new Chart(ctxLocation, {
                    type: 'bar',
                    data: {
                        labels: locationLabels,
                        datasets: [{ 
                            label: 'Preço', 
                            data: locationPrices, 
                            backgroundColor: locationColors, 
                            ...barOptions 
                        }]
                    },
                    options: optLocation
                });
            }

            // --- 1. PREÇO ---
            const ctxPrice = document.getElementById('chartPrice').getContext('2d');
            if(chartPrice) chartPrice.destroy();
            
            const optPrice = JSON.parse(JSON.stringify(commonOptions));
            optPrice.plugins.title = { display: true, text: '1. Comparativo de Preços (R$/kg)', font: {size: 16, weight: '700'} };
            
            chartPrice = new Chart(ctxPrice, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Preço Médio (R$/kg)',
                        data: activeData.map(d => d.preco_medio),
                        backgroundColor: bgColors,
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: { 
                    ...commonConfig, 
                    plugins: { title: { display: true, text: 'Comparativo de Preços (R$/kg)', font: {size:16} }, legend: {display:false} }
                }
            });

            // 2. CHART LOGÍSTICA
            if(charts.logistics) charts.logistics.destroy();
            charts.logistics = new Chart(document.getElementById('chartLogistics'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Custo Logístico (%)',
                        data: activeData.map(d => d.logistica_perc),
                        backgroundColor: bgColors.map(c => c + 'AA'), // Leve transparência
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: { 
                    ...commonConfig, 
                    plugins: { title: { display: true, text: 'Custo Logístico (%)', font: {size:16} }, legend: {display:false} }
                }
            });

            // 3. CHART ESTABILIDADE
            if(charts.stability) charts.stability.destroy();
            charts.stability = new Chart(document.getElementById('chartStability'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score Estabilidade (1-3)',
                        data: activeData.map(d => getStabilityScore(d.estabilidade)),
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

            // 4. CHART RADAR (Normalizado)
            if(charts.radar) charts.radar.destroy();
            
            // Lógica de normalização para o Radar (0 a 100)
            const radarDatasets = activeData.map(d => {
                const color = getColor(d.pais);
                
                const valPrice = parseFloat(d.preco_medio ?? 0);
                const valLog = parseFloat(d.logistica_perc ?? 0);
                const valStab = getStabilityScore(d.estabilidade);

                // Normaliza Estabilidade
                const stabScore = getStabilityScore(d.estabilidade) * 33;

                return {
                    label: d.pais,
                    data: [priceScore, logScore, stabScore],
                    borderColor: color,
                    backgroundColor: color + '20', // Transparente
                    pointBackgroundColor: color,
                    borderWidth: 2
                };
            });

            charts.radar = new Chart(document.getElementById('chartRadar'), {
                type: 'radar',
                data: {
                    labels: ['Competitividade Preço', 'Eficiência Logística', 'Segurança/Estabilidade'],
                    datasets: radarDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        title: { display: true, text: 'Radar de Oportunidade (Score 0-100)', font: {size:16} }
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

        // --- Renderiza Legenda ---
        function renderLegend() {
            const container = document.getElementById('legendContainer');
            container.innerHTML = '';

            rawData.forEach(d => {
                const isVisible = visibilityState[d.pais];
                const color = getColor(d.pais);

                const item = document.createElement('div');
                item.className = `legend-item ${!isVisible ? 'hidden-item' : ''}`;
                item.innerHTML = `<div class="color-dot" style="background:${color}"></div> ${d.pais}`;
                
                item.onclick = () => {
                    visibilityState[d.pais] = !isVisible;
                    renderLegend(); // Atualiza visual da legenda
                    renderCharts(); // Redesenha gráficos
                };

                container.appendChild(item);
            });
        }

        function renderLocationFilters() {
            const container = document.getElementById('locationFilters');
            
            if (!locationData || locationData.length === 0) {
                container.innerHTML = '<span style="font-size: 0.85rem; color: #999;">Nenhuma localização disponível</span>';
                return;
            }

            container.innerHTML = '';

            locationData.forEach((loc, idx) => {
                const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
                const color = colors[idx % colors.length];
                const isVisible = locationVisibilityState[loc.location_id];
                
                const itemDiv = document.createElement('div');
                itemDiv.className = `legend-item ${!isVisible ? 'hidden-item' : ''}`;
                itemDiv.innerHTML = `<div class="legend-color" style="background-color: ${color}"></div> ${loc.location_name}`;
                
                itemDiv.addEventListener('click', () => {
                    locationVisibilityState[loc.location_id] = !locationVisibilityState[loc.location_id];
                    renderLocationFilters();
                    updateLocationChart();
                });

                container.appendChild(itemDiv);
            });
        }

        function updateLocationChart() {
            if (!locationData || locationData.length === 0 || !chartLocationPrice) return;

            const activeLocations = locationData.filter(loc => locationVisibilityState[loc.location_id]);
            const locationLabels = activeLocations.map(loc => `${loc.location_name} (${loc.regiao})`);
            const locationPrices = activeLocations.map(loc => parseFloat(loc.price));
            
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
            const locationColors = activeLocations.map((_, idx) => {
                const originalIdx = locationData.indexOf(_);
                return colors[originalIdx % colors.length];
            });

            chartLocationPrice.data.labels = locationLabels;
            chartLocationPrice.data.datasets[0].data = locationPrices;
            chartLocationPrice.data.datasets[0].backgroundColor = locationColors;
            chartLocationPrice.update();
        }

        renderLegend();
        renderLocationFilters();
        initCharts();
    });
</script>
</body>
</html>
