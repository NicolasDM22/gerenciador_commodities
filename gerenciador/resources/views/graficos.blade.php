<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficos - Previsão de Commodities</title>
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
            
            /* Cores específicas do gráfico */
            --chart-green: #22c55e;
            --chart-yellow: #eab308;
            --chart-red: #ef4444;
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
            justify-content: center;
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

        /* --- GRID DOS GRÁFICOS --- */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            flex: 1;
            align-items: center;
        }

        .chart-wrapper {
            width: 100%;
            position: relative;
            height: 400px;
            padding: 10px;
        }

        /* --- LEGENDA CUSTOMIZADA --- */
        .custom-legend {
            background-color: #e5e7eb;
            border-radius: 8px;
            padding: 1rem 2rem;
            display: flex;
            justify-content: center;
            gap: 2rem;
            width: fit-content;
            margin: 0 auto;
            margin-top: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 2px;
        }
        
        .color-green { background-color: var(--chart-green); }
        .color-yellow { background-color: var(--chart-yellow); }
        .color-red { background-color: var(--chart-red); }

        /* Responsividade */
        @media (max-width: 900px) {
            .top-bar { flex-direction: column; align-items: flex-start; }
            .charts-container { grid-template-columns: 1fr; gap: 3rem; }
            .chart-wrapper { height: 300px; }
            .custom-legend { flex-direction: column; gap: 0.5rem; width: 100%; align-items: center; }
        }
    </style>
</head>
<body>
<div class="page">
    <header class="top-bar">
        <div class="profile">
            <img class="avatar" src="{{ $avatarUrl ?? 'https://ui-avatars.com/api/?name=User&background=random' }}" alt="Avatar">
            <div class="profile-info">
                <strong>{{ $user->nome ?? 'Usuário' }}</strong>
                <span>{{ $user->email ?? 'Email' }}</span>
            </div>
        </div>
        <div class="top-actions">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="button button-primary" type="submit">Sair</button>
            </form>
        </div>
    </header>

    <main class="content">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <section class="card">
            <div class="analysis-header">
                <div class="nav-buttons">
                    
                    {{-- Botão Esquerda (Voltar para Descritivo) --}}
                    @if(isset($commodityId))
                        <a href="{{ route('forecasts.show', ['id' => $commodityId]) }}" 
                           class="button button-secondary button-icon" title="Voltar">&larr;</a>
                    @else
                        <a href="{{ route('forecasts') }}" 
                           class="button button-secondary button-icon" title="Voltar">&larr;</a>
                    @endif

                    {{-- Botão Direita (Ir para Conclusão) --}}
                    @if(isset($commodityId))
                        <a href="{{ route('previsoes.conclusao.show', ['id' => $commodityId]) }}" 
                           class="button button-secondary button-icon" title="Ir para Conclusão">&rarr;</a>
                    @else
                        <a href="{{ route('previsoes.conclusao') }}" 
                           class="button button-secondary button-icon" title="Ir para Conclusão">&rarr;</a>
                    @endif
                </div>
                
                <h2>Gráficos de Estabilidade</h2>

                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Fechar">&times;</a>
            </div>

            <div class="charts-container">
                <div class="chart-wrapper">
                    <canvas id="chartEconomic"></canvas>
                </div>

                <div class="chart-wrapper">
                    <canvas id="chartClimate"></canvas>
                </div>
            </div>

            <div class="custom-legend">
                <div class="legend-item">
                    <div class="legend-color color-green"></div>
                    Brasil
                </div>
                <div class="legend-item">
                    <div class="legend-color color-yellow"></div>
                    Indonésia
                </div>
                <div class="legend-item">
                    <div class="legend-color color-red"></div>
                    Costa do Marfim
                </div>
            </div>

        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Configurações Comuns ---
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, 
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.raw.country}: R$${context.raw.y.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: 14.5,
                    max: 18.5,
                    title: {
                        display: true,
                        text: 'Preço Médio (R$/kg)',
                        color: '#4b5563',
                        font: { weight: 'bold', size: 14 }
                    },
                    grid: { display: false, drawBorder: true },
                    border: { display: true, width: 2, color: '#9ca3af' },
                    ticks: { font: { weight: 'bold' }, color: '#374151' }
                },
                x: {
                    min: 0.5,
                    max: 5.5,
                    grid: { display: false },
                    border: { display: true, width: 2, color: '#9ca3af' },
                    ticks: { stepSize: 1, font: { weight: 'bold' }, color: '#374151' }
                }
            }
        };

        // --- Gráfico 1: Estabilidade Econômica ---
        const ctxEcon = document.getElementById('chartEconomic').getContext('2d');
        
        const optionsEcon = JSON.parse(JSON.stringify(commonOptions)); 
        optionsEcon.scales.x.title = {
            display: true,
            text: 'Estabilidade Econômica',
            color: '#4b5563',
            font: { weight: 'bold', size: 14 },
            padding: { top: 10 }
        };

        new Chart(ctxEcon, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: 'Brasil',
                        data: [{x: 5, y: 17.8, country: 'Brasil'}],
                        pointStyle: 'crossRot',
                        pointRadius: 8,
                        pointBorderWidth: 4,
                        borderColor: '#22c55e',
                        backgroundColor: '#22c55e'
                    },
                    {
                        label: 'Indonésia',
                        data: [{x: 3, y: 15.3, country: 'Indonésia'}],
                        pointStyle: 'crossRot',
                        pointRadius: 8,
                        pointBorderWidth: 4,
                        borderColor: '#eab308',
                        backgroundColor: '#eab308'
                    },
                    {
                        label: 'Costa do Marfim',
                        data: [{x: 1, y: 14.8, country: 'Costa do Marfim'}],
                        pointStyle: 'crossRot',
                        pointRadius: 8,
                        pointBorderWidth: 4,
                        borderColor: '#ef4444',
                        backgroundColor: '#ef4444'
                    }
                ]
            },
            options: optionsEcon
        });

        // --- Gráfico 2: Estabilidade Climática ---
        const ctxClim = document.getElementById('chartClimate').getContext('2d');
        
        const optionsClim = JSON.parse(JSON.stringify(commonOptions));
        optionsClim.scales.x.title = {
            display: true,
            text: 'Estabilidade Climática',
            color: '#4b5563',
            font: { weight: 'bold', size: 14 },
            padding: { top: 10 }
        };

        new Chart(ctxClim, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: 'Brasil',
                        data: [{x: 1.2, y: 17.8, country: 'Brasil'}],
                        pointStyle: 'crossRot',
                        pointRadius: 8,
                        pointBorderWidth: 4,
                        borderColor: '#22c55e',
                        backgroundColor: '#22c55e'
                    },
                    {
                        label: 'Indonésia',
                        data: [{x: 3, y: 15.3, country: 'Indonésia'}],
                        pointStyle: 'crossRot',
                        pointRadius: 8,
                        pointBorderWidth: 4,
                        borderColor: '#eab308',
                        backgroundColor: '#eab308'
                    },
                    {
                        label: 'Costa do Marfim',
                        data: [{x: 3, y: 14.8, country: 'Costa do Marfim'}],
                        pointStyle: 'crossRot',
                        pointRadius: 8,
                        pointBorderWidth: 4,
                        borderColor: '#ef4444',
                        backgroundColor: '#ef4444'
                    }
                ]
            },
            options: optionsClim
        });
    });
</script>
</body>
</html>