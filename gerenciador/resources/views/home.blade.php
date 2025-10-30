<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Principal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    <style>
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
            --link-color: #3b82f6;
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
        .top-bar {
            background: var(--white);
            padding: 1.5rem clamp(1.5rem, 3vw, 3rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08);
        }
        .profile { display: flex; align-items: center; gap: 1rem; }
        .avatar {
            width: 64px; height: 64px; border-radius: 18px;
            object-fit: cover; border: 3px solid var(--gray-200);
        }
        .profile-info strong { font-size: 1.25rem; display: block; }
        .profile-info span { color: var(--gray-500); font-size: 0.95rem; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
        .button {
            border: none; border-radius: 12px; padding: 0.75rem 1.4rem;
            font-size: 0.95rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            display: inline-flex; align-items: center; gap: 0.4rem;
            text-decoration: none;
        }
        .button:hover { transform: translateY(-1px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.18); }
        .button-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: var(--white); }
        .button-outline { background: transparent; color: var(--primary); border: 1px solid rgba(37, 99, 235, 0.4); }
        .button-secondary { background: var(--white); border: 1px solid var(--gray-300); color: var(--gray-700); }
        .button[disabled] { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; }
        main.content {
            flex: 1; width: min(1180px, 100%); margin: 0 auto;
            padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem; display: grid; gap: 1.75rem;
        }
        .alert { padding: 1rem 1.25rem; border-radius: 16px; font-size: 0.95rem; }
        .alert-success { background: rgba(5, 150, 105, 0.12); color: var(--success); }
        .alert-danger { background: rgba(220, 38, 38, 0.12); color: var(--danger); }
        .alert-danger ul { margin: 0.75rem 0 0 1.2rem; padding: 0; }
        .card {
            background: var(--white); border-radius: 16px;
            padding: 1.5rem; box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.1);
        }
        .card h2 { margin: 0 0 1rem 0; font-size: 1.15rem; font-weight: 600; color: var(--gray-700); }
        .card p { margin: 0.5rem 0 0; color: var(--gray-600); line-height: 1.6; }
        .top-cards-grid {
            display: grid;
            gap: 1.75rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        .card-link {
            display: block;
            color: var(--link-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }
        .card-link:hover { background-color: var(--gray-50); text-decoration: underline; }
        .analysis-list { list-style: none; padding: 0; margin: 0; }
        .analysis-list li { margin-bottom: 0.5rem; font-size: 0.9rem; }
        .analysis-list a { color: var(--link-color); text-decoration: none; }
        .analysis-list a:hover { text-decoration: underline; }
        .analysis-list span { color: var(--gray-500); margin-left: 0.75rem; font-size: 0.85rem; }
        .chart-wrapper { position: relative; min-height: 350px; padding-top: 1rem; }
        canvas { width: 100%; height: 350px; }
        .modal {
             position: fixed; inset: 0; background: rgba(17, 24, 39, 0.4);
             display: none; align-items: center; justify-content: center;
             padding: 1.5rem; z-index: 10;
         }
        .modal.active { display: flex; }
        .modal-dialog {
             background: var(--white); border-radius: 22px; width: min(480px, 100%);
             padding: 1.5rem; display: grid; gap: 1rem;
             box-shadow: 0 30px 65px -28px rgba(15, 23, 42, 0.45);
         }
        .modal-header { display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-size: 1.2rem; }
        .form-grid { display: grid; gap: 1rem; }
        .form-group { display: grid; gap: 0.4rem; }
        .form-group label { font-size: 0.9rem; color: var(--gray-600); }
        .form-group input { padding: 0.7rem 1rem; border-radius: 12px; border: 1px solid var(--gray-300); font-size: 0.95rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; }
        @media (max-width: 820px) {
            .top-bar { flex-direction: column; align-items: flex-start; }
            .top-actions { width: 100%; justify-content: flex-start; }
        }
    </style>
</head>
<body>
<div class="page">
    <header class="top-bar">
        <div class="profile">
            <img class="avatar" src="{{ $avatarUrl }}" alt="Avatar de {{ $user->nome ?? $user->usuario }}">
            <div class="profile-info">
                <strong>{{ $user->nome ?? $user->usuario }}</strong>
                <span>{{ $user->email ?? 'E-mail não informado' }}</span>
            </div>
        </div>
        <div class="top-actions">
            @if($isAdmin)
                <a href="{{ route('forecasts') }}" class="button button-secondary">Debug Previsões</a>
            @endif
            <button class="button button-outline" id="openProfileModal" type="button">Atualizar perfil</button>
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
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Ops! Algo precisa de atenção.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="top-cards-grid">
            <div class="card">
                <h2>Realizar uma nova análise</h2>
                <a href="#" class="card-link">Análise do zero</a>
                <a href="#" class="card-link">Usar template antigo</a>
            </div>

            <div class="card">
                <h2>Visualizar análises anteriores</h2>
                @if($previousAnalyses->isNotEmpty())
                    <ul class="analysis-list">
                        @foreach($previousAnalyses as $analysis)
                            <li>
                                <a href="{{ $analysis->url }}">{{ $analysis->commodity }}</a>
                                <span>{{ $analysis->date }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>Nenhuma análise anterior encontrada.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <h2>{{ $chartData['commodityName'] ?? 'Histórico de Preços' }}</h2>
            <div class="chart-wrapper">
                <canvas id="priceHistoryChart"></canvas>
            </div>
        </div>

    </main>
</div>

<div class="modal" id="profileModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Atualizar perfil</h3>
            <button class="button button-outline" type="button" id="closeProfileModal">Fechar</button>
        </div>
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="form-grid">
             @csrf
             <div class="form-group">
                 <label for="usuario">Usuário</label>
                 <input id="usuario" name="usuario" type="text" value="{{ old('usuario', $user->usuario ?? '') }}" required>
             </div>
              <div class="form-group">
                 <label for="nome">Nome completo</label>
                 <input id="nome" name="nome" type="text" value="{{ old('nome', $user->nome ?? '') }}">
             </div>
             <div class="form-group">
                 <label for="email">E-mail</label>
                 <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}">
             </div>
             <div class="form-group">
                 <label for="nova_senha">Nova senha</label>
                 <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password">
             </div>
             <div class="form-group">
                 <label for="nova_senha_confirmation">Confirmar nova senha</label>
                 <input id="nova_senha_confirmation" name="nova_senha_confirmation" type="password" autocomplete="new-password">
             </div>
             <div class="form-group">
                 <label for="foto">Foto de perfil</label>
                 <input id="foto" name="foto" type="file" accept="image/*">
             </div>
             <div class="modal-footer">
                <button class="button button-outline" type="button" id="cancelProfileModal">Cancelar</button>
                <button class="button button-primary" type="submit">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    const chartRawData = @json($chartData);

    window.addEventListener('DOMContentLoaded', () => {
        const profileModal = document.getElementById('profileModal');
        const openProfileModalBtn = document.getElementById('openProfileModal');
        const closeProfileModalBtn = document.getElementById('closeProfileModal');
        const cancelProfileModalBtn = document.getElementById('cancelProfileModal');

        const toggleProfileModal = (show) => {
            profileModal?.classList.toggle('active', show);
        };

        openProfileModalBtn?.addEventListener('click', () => toggleProfileModal(true));
        closeProfileModalBtn?.addEventListener('click', () => toggleProfileModal(false));
        cancelProfileModalBtn?.addEventListener('click', () => toggleProfileModal(false));
        profileModal?.addEventListener('click', (event) => {
            if (event.target === profileModal) toggleProfileModal(false);
        });

        const chartCanvas = document.getElementById('priceHistoryChart');
        if (chartCanvas && typeof Chart !== 'undefined' && chartRawData) {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartRawData.labels || [],
                    datasets: [{
                        label: 'Preço Médio (R$/kg)',
                        data: chartRawData.prices || [],
                        borderColor: '#F97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#F97316',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                color: '#4b5563',
                                callback: function(value) {
                                     return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                }
                            },
                             grid: { color: '#e5e7eb' }
                        },
                        x: {
                             ticks: { color: '#4b5563' },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#374151',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed.y !== null) {
                                         label += 'R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

</body>
</html>