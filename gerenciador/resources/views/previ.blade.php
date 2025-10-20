<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsão de Commodities</title>
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

        .stats-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .card p {
            margin: 0.35rem 0 0;
            color: var(--gray-500);
        }

        .stat-card strong {
            font-size: 1.6rem;
            display: block;
            margin-top: 0.5rem;
        }

        .stat-card small {
            color: var(--gray-500);
            display: block;
            margin-top: 0.35rem;
        }

        .label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray-500);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .section-header p {
            margin: 0;
            color: var(--gray-500);
            font-size: 0.95rem;
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

        .empty-state {
            color: var(--gray-500);
            font-size: 0.95rem;
            padding: 0.75rem 0;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .filter-group label {
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        select {
            padding: 0.6rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--gray-300);
            font-size: 0.95rem;
            color: var(--gray-700);
            background: var(--white);
        }

        .chart-wrapper {
            position: relative;
            min-height: 320px;
        }

        canvas {
            width: 100%;
            height: 320px;
        }

        .location-details {
            margin-top: 1.5rem;
            display: grid;
            gap: 1rem;
        }

        .location-header {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .location-header strong {
            font-size: 1.1rem;
        }

        .location-header span {
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .location-items {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 0.75rem;
        }

        .location-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
        }

        .location-item .name {
            font-weight: 600;
        }

        .location-item .meta {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .location-item .price {
            font-weight: 700;
            font-size: 1rem;
        }

        .support-section {
            display: grid;
            gap: 1rem;
        }

        .support-body {
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            background: var(--gray-50);
            padding: 1rem;
            max-height: 320px;
            overflow-y: auto;
            display: grid;
            gap: 0.75rem;
        }

        #supportMessages {
            display: grid;
            gap: 0.75rem;
        }

        .support-message {
            display: grid;
            gap: 0.25rem;
            max-width: 420px;
            padding: 0.75rem 1rem;
            border-radius: 16px;
        }

        .support-message.user {
            margin-left: auto;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }

        .support-message.admin {
            margin-right: auto;
            background: var(--white);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .support-message p {
            margin: 0;
        }

        .support-message span {
            font-size: 0.8rem;
            color: inherit;
            opacity: 0.8;
        }

        .support-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .support-form textarea {
            resize: vertical;
            min-height: 90px;
            border-radius: 14px;
            border: 1px solid var(--gray-300);
            padding: 0.85rem 1rem;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .support-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .support-meta {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin: 0;
        }

        .admin-list {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .admin-item {
            padding: 0.9rem 1rem;
            border-radius: 14px;
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .admin-item strong {
            display: block;
            margin-bottom: 0.35rem;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 10;
        }

        .modal.active {
            display: flex;
        }

        .modal-dialog {
            background: var(--white);
            border-radius: 22px;
            width: min(480px, 100%);
            padding: 1.5rem;
            display: grid;
            gap: 1rem;
            box-shadow: 0 30px 65px -28px rgba(15, 23, 42, 0.45);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .form-grid {
            display: grid;
            gap: 1rem;
        }

        .form-group {
            display: grid;
            gap: 0.4rem;
        }

        .form-group label {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .form-group input {
            padding: 0.7rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--gray-300);
            font-size: 0.95rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
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

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-group {
                width: 100%;
            }

            select {
                width: 100%;
            }
        }
    </style>
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
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="stats-grid">
            <div class="card stat-card">
                <span class="label">Membro desde</span>
                <strong>{{ $stats['member_since'] }}</strong>
                <small>{{ $stats['account_age'] }}</small>
            </div>
            <div class="card stat-card">
                <span class="label">Última atualização</span>
                <strong>{{ $stats['last_update'] }}</strong>
                <small>Perfil sincronizado</small>
            </div>
            <div class="card stat-card">
                <span class="label">Status da conta</span>
                <strong>{{ $isAdmin ? 'Administrador' : 'Usuário' }}</strong>
                <small>Acesso ao painel de commodities</small>
            </div>
        </section>

     <div class="analitics-section">
         <!-- Conteúdo da seção de análises -->
        <section class="card market-section">
            <div class="section-header">
                <div>
                    <h2>Previsão de melhores oportunidades</h2>
                    <p>Resumo com a localidade mais barata cadastrada para cada item alimentício.</p>
                </div>
            </div>
            @if (empty($marketOverview))
                <p class="empty-state">Cadastre preços de commodities para visualizar o panorama geral.</p>
            @else
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Commodity</th>
                                <th>Categoria</th>
                                <th>Localidade</th>
                                <th>Preço</th>
                                <th>Atualização</th>
                                <th>Fonte</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($marketOverview as $item)
                                <tr>
                                    <td>{{ $item['commodity'] }}</td>
                                    <td>{{ $item['categoria'] ?? 'Não informado' }}</td>
                                    <td>{{ $item['location'] }}</td>
                                    <td>{{ $item['currency'] ?? 'BRL' }} {{ number_format($item['price'], 2, ',', '.') }}</td>
                                    <td>{{ $item['last_updated'] ?? 'Sem data' }}</td>
                                    <td>{{ $item['source'] ?? 'Não informado' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>