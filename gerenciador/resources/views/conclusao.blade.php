<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendação Final - Previsão de Commodities</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    <style>
        /* --- Variáveis e Reset (Mantidos do seu padrão) --- */
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
            gap: 0.4rem;
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
            font-size: 1.5rem; /* Aumentei um pouco para destacar como na imagem */
            color: var(--gray-600); /* Tom mais cinza conforme imagem */
            font-weight: 700;
        }
        
        .analysis-header .nav-buttons { display: flex; gap: 0.5rem; }

        /* --- GRID DA CONCLUSÃO (NOVO) --- */
        .conclusion-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr; /* Texto ocupa um pouco menos que o gráfico */
            gap: 3rem;
            align-items: center;
            flex: 1; /* Ocupa o espaço restante do card */
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
            font-weight: 600; /* Texto em negrito suave conforme imagem */
        }

        .chart-wrapper {
            width: 100%;
            position: relative;
            height: 350px; /* Altura fixa para o gráfico */
        }

        /* --- Footer de Ações (Botões Exportar) --- */
        .actions-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
        }

        .button-export {
            background-color: #d1d5db; /* Cinza mais escuro que o gray-200 */
            color: var(--gray-700);
            border-radius: 25px; /* Bem arredondado */
            font-weight: 700;
            padding: 0.6rem 1.5rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .button-export:hover {
            background-color: #9ca3af; /* gray-400 */
            color: var(--gray-900);
        }

        /* Responsividade */
        @media (max-width: 820px) {
            .top-bar { flex-direction: column; align-items: flex-start; }
            .conclusion-container { grid-template-columns: 1fr; gap: 2rem; }
            .chart-wrapper { height: 300px; }
            .actions-footer { justify-content: center; }
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
                    <a href="{{ route('previsoes.graficos') }}" class="button button-secondary button-icon">&larr;</a>
                    <button class="button button-secondary button-icon" disabled type="button">&rarr;</button>
                </div>
                
                <h2>Recomendação final</h2>
                
                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Fechar">&times;</a>
            </div>

            <div class="conclusion-container">
                
                <div class="conclusion-text">
                    <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Aut eligendi, molestiae doloremque, iure ipsa quis cupiditate aperiam assumenda iusto accusantium nostrum maiores facere numquam harum fugiat molestias dolores. Cupiditate, molestiae.</p>
                    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Dolore dignissimos unde alias atque laudantium numquam repellat beatae. Ullam distinctio tempore, ea fugit accusamus nulla nesciunt consequatur sequi repellendus exercitationem corporis?</p>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Omnis quibusdam eius perspiciatis accusantium. Omnis vel rem possimus est, dolor atque blanditiis, soluta officia perferendis, consectetur optio veniam dolore quos vero.</p>
                </div>

                <div class="chart-wrapper">
                    <canvas id="finalChart"></canvas>
                </div>

            </div>

            <div class="actions-footer">
                <button class="button button-export">Exportar PDF</button>
                <button class="button button-export">Exportar CSV</button>
            </div>

        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('finalChart').getContext('2d');

        // Dados Mockados para replicar a imagem (Declínio, leve subida, pico, queda brusca)
        // Labels aproximados: 09/25 a 04/26
        const data = {
            labels: ['09/25', '10/25', '11/25', '12/25', '01/26', '02/26', '03/26', '04/26'],
            datasets: [{
                label: 'Preço Médio (R$/kg)',
                data: [60, 57.8, 56.9, 57.3, 60.0, 62.0, 56.0, 52.0], // Valores visuais aproximados
                borderColor: '#f97316', // Cor Laranja similar à imagem
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#f97316',
                pointRadius: 0, // Remove os pontos por padrão para ficar igual a imagem (linha limpa)
                pointHoverRadius: 4,
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Oculta a legenda padrão
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 50, // Ajuste conforme seus dados reais
                        max: 64,
                        title: {
                            display: true,
                            text: 'Preço Médio (R$/kg)',
                            color: '#4b5563',
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false, // Remove linhas de grade horizontais (limpeza visual)
                            drawBorder: true
                        },
                        border: {
                             display: true,
                             width: 2,
                             color: '#9ca3af'
                        }
                    },
                    x: {
                        grid: {
                            display: false, // Remove linhas de grade verticais
                        },
                        ticks: {
                            font: {
                                weight: 'bold'
                            },
                            color: '#374151'
                        },
                         border: {
                             display: true,
                             width: 2,
                             color: '#9ca3af'
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
    });
</script>
</body>
</html>