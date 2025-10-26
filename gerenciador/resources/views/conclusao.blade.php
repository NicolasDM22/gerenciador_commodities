<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conclusão - Previsão de Commodities</title> {{-- <--- Título Alterado --}}
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
        }

        * {
            box-sizing: border-box;
        }

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

        .profile-info strong {
            font-size: 1.25rem;
            display: block;
        }

        .profile-info span {
            color: var(--gray-500);
            font-size: 0.95rem;
        }

        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

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

        .button-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid rgba(37, 99, 235, 0.4);
        }

        .button-secondary {
            background: var(--white);
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }

        .button[disabled] {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            background: var(--gray-100);
        }
        
        .button-icon {
            padding: 0.6rem 0.8rem;
            line-height: 1;
        }
        
        .button-secondary:hover {
             background: var(--gray-50);
             box-shadow: none;
             transform: none;
        }

        main.content {
            flex: 1;
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem;
            display: grid;
            gap: 1.75rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 16px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.12);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.12);
            color: var(--danger);
        }

        .alert-danger ul {
            margin: 0.75rem 0 0 1.2rem;
            padding: 0;
        }

        .card {
            background: var(--white);
            border-radius: 22px;
            padding: 1.5rem;
            box-shadow: 0 22px 45px -30px rgba(15, 23, 42, 0.3);
        }

        .card h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .table-wrapper {
            overflow: auto;
            border-radius: 18px;
            border: 1px solid var(--gray-200);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 640px;
        }

        th,
        td {
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.94rem;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .analysis-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
        }
        
        .analysis-header .nav-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .analysis-body {
            display: grid;
            gap: 2.5rem; 
        }
        
        .analysis-section h2 {
             margin-bottom: 1.25rem;
             font-size: 1.15rem;
             font-weight: 600;
             color: var(--gray-700);
        }
        
        .descriptive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem 2rem;
        }
        
        .descriptive-grid p {
            margin: 0.6rem 0;
            font-size: 0.95rem;
            color: var(--gray-700);
            line-height: 1.5;
        }
        
        .descriptive-grid strong {
            font-weight: 600;
            color: var(--gray-600);
            display: inline-block;
            min-width: 180px; 
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-success {
            color: var(--success);
        }
        
        .text-danger {
            color: var(--danger);
        }

        @media (max-width: 820px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .top-actions {
                width: 100%;
                justify-content: flex-start;
            }
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
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="button button-primary" type="submit">Sair</button>
            </form>
        </div>
    </header>

    <main class="content">
        {{-- Alertas (opcional) --}}
        @if (session('status'))
             {{-- ... alerta ... --}}
        @endif
        @if ($errors->any())
            {{-- ... alerta de erro ... --}}
        @endif

        {{-- [CONTEÚDO ESPECÍFICO DESTA PÁGINA] --}}
                <section class="card">
            <div class="analysis-header">
                <div class="nav-buttons">
                    <a href="{{ route('previsoes.graficos') }}" class="button button-secondary button-icon" title="Voltar para Gráficos">&larr;</a>
                    <button class="button button-secondary button-icon" disabled type="button">&rarr;</button>
                </div>
                <h2>Conclusão</h2>
                <a href="{{ route('home') }}" class="button button-secondary button-icon" title="Voltar para Home">&times;</a>
            </div>
            <div class="analysis-body">
                <p>Resumo e conclusão da análise + gráfico com histórico e previsão de preços</p>
            </div>
        </section>
    </main>
</div>
</body>
</html>