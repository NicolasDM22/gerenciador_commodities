<!DOCTYPE html>
<!-- by Matias Amma -->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório PDF</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #fff; 
            color: #333; 
            margin: 0; padding: 0;
        }
        
        #loading {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: #fff; z-index: 9999; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid #f3f3f3;
            border-top: 4px solid #2563eb; border-radius: 50%;
            animation: spin 1s linear infinite; margin-bottom: 15px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* --- CONTAINER --- */
        #contentToPrint { 
            width: 700px; 
            margin: 0 auto; 
            padding: 0; 
        }
        
        /* --- UTILITÁRIOS --- */
        .page-break {
            page-break-before: always;
            padding-top: 30px; 
        }
        
        .no-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .mb-4 { margin-bottom: 25px; }

        /* --- CABEÇALHO --- */
        .page-header { 
            text-align: center; 
            border-bottom: 2px solid #2563eb; 
            margin-bottom: 30px; 
            padding: 20px 0 10px 0; 
        }
        h1 { color: #1e3a8a; margin: 0; text-transform: uppercase; font-size: 20px; }
        
        /* --- TÍTULOS --- */
        h4 { 
            margin: 0 0 15px 0; 
            font-size: 13px; 
            color: #4b5563; 
            font-weight: 700; 
            display: flex; 
            align-items: center;
        }
        
        .title-bar {
            display: inline-block;
            width: 5px;
            height: 16px;
            background-color: #2563eb;
            margin-right: 10px;
            border-radius: 1px;
        }
        
        /* --- TABELAS --- */
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #f3f4f6; padding: 8px; border-bottom: 2px solid #e5e7eb; text-align: left; text-transform: uppercase; color: #4b5563; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        
        .text-success { color: #059669; font-weight: bold; }
        .text-danger { color: #dc2626; font-weight: bold; }

        /* --- GRÁFICOS DA PÁGINA 1 (CLEAN / ALINHADOS) --- */
        .chart-clean-stack {
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
        }

        .chart-clean { 
            width: 100%;
            height: 210px; 
            padding: 0;
        }
        
        /* --- GRÁFICOS DA PÁGINA 2 --- */
        .chart-box-lg {
            border: 1px solid #e5e7eb; 
            padding: 10px; 
            height: 210px; 
            border-radius: 8px; 
            background: #fff;
            margin-bottom: 30px;
        }

        .radar-box {
            border: 1px solid #e5e7eb;
            padding: 10px;
            height: 280px; 
            border-radius: 8px;
            background: #fff;
            margin-bottom: 25px;
        }

        /* --- CONCLUSÃO --- */
        .conclusion-text { 
            font-size: 11px; 
            color: #333; 
            line-height: 1.6; 
            text-align: justify;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div id="loading">
        <div class="spinner"></div>
        <h3 style="font-family: sans-serif; color: #333; font-size: 14px;">Gerando PDF...</h3>
    </div>

    <div id="contentToPrint">
        
        <div class="page-header no-break">
            <h1>Análise: {{ $commodity->nome }}</h1>
            <p style="font-size: 10px; color: #666; margin-top: 4px;">Emitido em: {{ $date }}</p>
        </div>

        <div class="no-break mb-4">
            <h4><span class="title-bar"></span>Dados Regionais</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%">Rank</th>
                        <th style="width: 20%">País</th>
                        <th style="width: 20%">Preço Médio</th>
                        <th style="width: 15%">Logística</th>
                        <th style="width: 20%">Risco Climático</th>
                        <th style="width: 15%">Estabilidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($regionalComparisons as $reg)
                    <tr>
                        <td>#{{ $reg->ranking }}</td>
                        <td><strong>{{ $reg->pais }}</strong></td>
                        <td>R$ {{ number_format($reg->preco_medio, 2, ',', '.') }}</td>
                        <td>{{ number_format($reg->logistica_perc, 1, ',', '.') }}%</td>
                        <td>{{ $reg->risco }}</td>
                        <td>{{ $reg->estabilidade }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="chart-clean-stack">
            
            <div class="chart-clean no-break">
                <h4><span class="title-bar"></span>Comparativo Preço (R$)</h4>
                <canvas id="c1"></canvas>
            </div>
            
            <div class="chart-clean no-break">
                <h4><span class="title-bar"></span>Custo Logístico (%)</h4>
                <canvas id="c2"></canvas>
            </div>
            
            <div class="chart-clean no-break">
                <h4><span class="title-bar"></span>Estabilidade Econômica</h4>
                <canvas id="c3"></canvas>
            </div>

        </div>


        <div class="page-break"></div>

        <div class="radar-box no-break">
            <h4><span class="title-bar"></span>Síntese de Atratividade</h4>
            <canvas id="c4"></canvas>
        </div>

        <div class="no-break mb-4">
            <h4><span class="title-bar"></span>Tendência Nacional</h4>
            <table>
                <thead>
                    <tr><th>Mês/Ano</th><th>Preço Previsto</th><th>Variação (%)</th><th style="text-align:center">Tendência</th></tr>
                </thead>
                <tbody>
                    @foreach($nationalForecasts as $forecast)
                    <tr>
                        <td>{{ $forecast->mes_ano }}</td>
                        <td>R$ {{ number_format($forecast->preco_medio, 2, ',', '.') }}</td>
                        <td class="{{ $forecast->variacao_perc >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $forecast->variacao_perc >= 0 ? '+' : '' }}{{ number_format($forecast->variacao_perc, 1, ',', '.') }}%
                        </td>
                        <td style="text-align: center;" class="{{ $forecast->variacao_perc >= 0 ? 'text-success' : 'text-danger' }}">
                            {!! $forecast->variacao_perc >= 0 ? '&uarr;' : '&darr;' !!}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="chart-box-lg no-break mb-4">
            <h4><span class="title-bar"></span>Tendência de Mercado (Gráfico)</h4>
            <canvas id="c5"></canvas>
        </div>

        <div class="no-break">
            <h4><span class="title-bar"></span>Conclusão</h4>
            <div class="conclusion-text">
                {{ $conclusionText }}
            </div>
        </div>
        
        <div class="no-break" style="text-align: center; margin-top: 30px; font-size: 10px; color: #9ca3af;">
            Este documento é apenas uma recomendação de compras basedo em estimativas. Não nos responsabilizamos por decisões tomadas com base nele.
        </div>
    </div>

    <script>
        Chart.register(ChartDataLabels);
        
        const data = @json($regionalComparisons);
        const labels = data.map(d => d.pais);
        
        const allPrices = data.map(d => parseFloat(d.preco_medio));
        const allLogistics = data.map(d => parseFloat(d.logistica_perc));

        const maxP = Math.max(...allPrices);
        const minP = Math.min(...allPrices);
        const maxL = Math.max(...allLogistics);
        const minL = Math.min(...allLogistics);

        const normalizeWithFloor = (val, min, max, invert) => {
            if (max === min) return 100;
            let ratio = 0;
            if (invert) {
                ratio = (max - val) / (max - min);
            } else {
                ratio = (val - min) / (max - min);
            }
            return 20 + (ratio * 80);
        };

        const stabScore = {'Alta': 3, 'Média': 2, 'Baixa': 1, 'Media': 2};

        // Cores
        const palette = ['#f97316', '#8b5cf6', '#1e3a8a']; 
        let colorIdx = 0;
        const barColors = labels.map(l => {
            if(l.toLowerCase().includes('brasil')) {
                return '#10b981'; 
            } else {
                const c = palette[colorIdx % palette.length];
                colorIdx++;
                return c;
            }
        });
        let radarIdx = 0;
        
        const barThick = 35; 
        const fontBase = {size: 9}; 
        
        // --- CONFIGURAÇÃO DE ALINHAMENTO ---
        const fixedYWidth = 50; // Largura fixa do eixo Y em pixels

        const opt = { 
            responsive: true, 
            maintainAspectRatio: false, 
            layout: { padding: { top: 10, left: 0, right: 0, bottom: 20 } },
            plugins: { legend: {display: false} }, 
            animation: false 
        };

        // 1. Preço (Eixo Y Invisível mas ocupando 50px)
        new Chart(document.getElementById('c1'), {
            type: 'bar', 
            data: { labels, datasets: [{ data: data.map(d => d.preco_medio), backgroundColor: barColors, barThickness: barThick }] },
            options: { 
                ...opt, 
                plugins: { legend: {display:false}, datalabels: {anchor:'end', align:'top', font:fontBase} }, 
                scales: { 
                    y: { 
                        display: true, // Precisa ser true para ocupar espaço
                        afterFit: (ctx) => { ctx.width = fixedYWidth; }, // Força a largura
                        ticks: { display: false }, // Esconde os números
                        grid: { display: false, drawBorder: false }, // Esconde a grade
                        grace: '15%' 
                    }, 
                    x: { ticks:{font:fontBase}, grid:{display:false} } 
                } 
            }
        });

        // 2. Logística (Eixo Y Invisível mas ocupando 50px)
        new Chart(document.getElementById('c2'), {
            type: 'bar', 
            data: { labels, datasets: [{ data: data.map(d => d.logistica_perc), backgroundColor: barColors, barThickness: barThick }] },
            options: { 
                ...opt, 
                plugins: { legend: {display:false}, datalabels: {anchor:'end', align:'top', formatter: v=>v+'%', font:fontBase} }, 
                scales: { 
                    y: { 
                        display: true,
                        afterFit: (ctx) => { ctx.width = fixedYWidth; }, // Força a largura
                        ticks: { display: false },
                        grid: { display: false, drawBorder: false },
                        grace: '15%' 
                    }, 
                    x: { ticks:{font:fontBase}, grid:{display:false} } 
                } 
            }
        });

        // 3. Estabilidade (Eixo Y Visível ocupando 50px)
        new Chart(document.getElementById('c3'), {
            type: 'bar', 
            data: { labels, datasets: [{ data: data.map(d => stabScore[d.estabilidade]||0), backgroundColor: barColors, barThickness: barThick }] },
            options: { 
                ...opt, 
                plugins: { legend: {display:false}, datalabels: {display:false} }, 
                scales: { 
                    y: { 
                        afterFit: (ctx) => { ctx.width = fixedYWidth; }, // Força a MESMA largura
                        min: 0, max: 4, 
                        ticks: { callback: v => ({1:'Baixa',2:'Média',3:'Alta'}[v]||''), font:{size:9, weight:'bold'} } 
                    }, 
                    x: { ticks:{font:fontBase}, grid:{display:false} } 
                } 
            }
        });

        // 4. Radar
        new Chart(document.getElementById('c4'), {
            type: 'radar',
            data: { 
                labels: ['Preço', 'Logística', 'Estabilidade'], 
                datasets: data.map((d, i) => {
                    let color;
                    if(d.pais.toLowerCase().includes('brasil')) {
                        color = '#10b981'; 
                    } else {
                        color = palette[radarIdx % palette.length];
                        radarIdx++;
                    }

                    const valP = parseFloat(d.preco_medio);
                    const valL = parseFloat(d.logistica_perc);
                    const valS = stabScore[d.estabilidade] || 0;

                    const normP = normalizeWithFloor(valP, minP, maxP, true); 
                    const normL = normalizeWithFloor(valL, minL, maxL, true); 
                    const normS = (valS / 3) * 100;

                    return { 
                        label: d.pais, 
                        data: [normP, normL, normS], 
                        borderColor: color, 
                        backgroundColor: color + '40', 
                        fill: true 
                    };
                }) 
            },
            options: { 
                ...opt, 
                plugins: { 
                    legend: {
                        display: true, 
                        position: 'bottom', 
                        labels: { font:{size:9}, boxWidth: 10, usePointStyle: true, padding: 15 }
                    }, 
                    datalabels: {display:false} 
                }, 
                scales: { 
                    r: { 
                        ticks: { display: false }, 
                        pointLabels: {font: {size: 10}},
                        suggestedMin: 0,
                        suggestedMax: 100
                    } 
                } 
            }
        });

        // 5. Tendência
        new Chart(document.getElementById('c5'), {
            type: 'line',
            data: { 
                labels: ['Set','Out','Nov','Dez','Jan','Fev'], 
                datasets: [{ 
                    label: 'Preço Nacional', 
                    data: [60,58,57,57.5,60,62], 
                    borderColor: '#10b981', 
                    backgroundColor: 'rgba(16, 185, 129, 0.1)', 
                    fill: true, 
                    tension: 0 
                }] 
            },
            options: { ...opt, plugins: { legend: {display:false}, datalabels: {display:false} }, scales: { y: { ticks:{font:fontBase} }, x: {ticks:{font:fontBase}} } }
        });

        window.onload = function() {
            setTimeout(() => {
                const element = document.getElementById('contentToPrint');
                const pdfOpt = {
                    margin: [0.3, 0.3],
                    filename: 'relatorio_{{ Str::slug($commodity->nome) }}.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, scrollY: 0 },
                    pagebreak: { mode: ['css', 'legacy'] },
                    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                };
                html2pdf().set(pdfOpt).from(element).save().then(function() {
                    console.log('PDF Gerado.');
                });
            }, 800);
        };
    </script>
</body>
</html>