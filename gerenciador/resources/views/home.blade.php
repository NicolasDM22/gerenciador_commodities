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
        .ws-controls { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0; }
        .ws-field { display: flex; flex: 1; gap: 0.5rem; align-items: center; }
        .ws-field input { flex: 1; padding: 0.6rem 0.8rem; border-radius: 10px; border: 1px solid var(--gray-300); }
        .ws-log {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid var(--gray-200);
            white-space: pre-wrap;
        }
        .ws-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--gray-600);
        }
        .ws-indicator {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--danger);
            transition: background 0.2s ease;
        }
        .ws-indicator.active { background: var(--success); }
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
                <span>{{ $user->email ?? 'E-mail nao informado' }}</span>
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
                <a href="#" class="card-link" id="openFormsModal">Análise do zero</a>
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

        <div class="card" id="javaWsCard">
            <h2>Servidor Java (WebSocket)</h2>
            <p class="ws-status">
                <span class="ws-indicator" id="javaWsIndicator"></span>
                <span id="javaWsStatus">Desconectado</span>
            </p>
            <div class="ws-controls">
                <button class="button button-secondary" type="button" id="javaWsConnect">Conectar</button>
                <button class="button button-outline" type="button" id="javaWsDisconnect" disabled>Desconectar</button>
                <button class="button button-outline" type="button" id="javaWsSendExit" disabled>Enviar pedido de sair</button>
            </div>
            <div class="ws-field">
                <input type="text" id="javaWsMessage" placeholder='Mensagem JSON, ex: {"tipo":"echo","payload":"teste"}' disabled>
                <button class="button button-primary" type="button" id="javaWsSend" disabled>Enviar</button>
            </div>
            <div class="ws-log" id="javaWsLog"></div>
        </div>

    </main>
</div>

<script>
    const chartRawData = @json($chartData);

    window.addEventListener('DOMContentLoaded', () => {
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

        // WebSocket
        const wsIndicator = document.getElementById('javaWsIndicator');
        const wsStatus = document.getElementById('javaWsStatus');
        const wsConnectBtn = document.getElementById('javaWsConnect');
        const wsDisconnectBtn = document.getElementById('javaWsDisconnect');
        const wsSendExitBtn = document.getElementById('javaWsSendExit');
        const wsSendBtn = document.getElementById('javaWsSend');
        const wsMessageInput = document.getElementById('javaWsMessage');
        const wsLog = document.getElementById('javaWsLog');

        const appendLog = (message) => {
            if (!wsLog) return;
            const time = new Date().toLocaleTimeString();
            wsLog.textContent += `[${time}] ${message}\n`;
            wsLog.scrollTop = wsLog.scrollHeight;
        };

        const toggleWsControls = (isConnected) => {
            wsIndicator?.classList.toggle('active', isConnected);
            if (wsStatus) wsStatus.textContent = isConnected ? 'Conectado' : 'Desconectado';
            wsConnectBtn.disabled = isConnected;
            wsDisconnectBtn.disabled = !isConnected;
            wsSendExitBtn.disabled = !isConnected;
            wsSendBtn.disabled = !isConnected;
            wsMessageInput.disabled = !isConnected;
            if (!isConnected) wsMessageInput.value = '';
        };

        const resolveWsUrl = () => {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            return `${protocol}//${window.location.host}/ws`;
        };

        let javaWs = null;

        const notifyHomeView = () => {
            if (!javaWs || javaWs.readyState !== WebSocket.OPEN) return;
            const payload = JSON.stringify({ tipo: 'info', mensagem: 'Usuario abriu a tela home' });
            appendLog(`Informando o servidor: ${payload}`);
            javaWs.send(payload);
        };

        const connectToJavaWs = () => {
            if (javaWs && javaWs.readyState === WebSocket.OPEN) return;
            const url = resolveWsUrl();
            appendLog(`Tentando conectar em ${url}`);

            try {
                javaWs = new WebSocket(url);
            } catch (error) {
                appendLog(`Erro ao criar WebSocket: ${error.message}`);
                return;
            }

            javaWs.addEventListener('open', () => {
                appendLog('Conexao estabelecida com sucesso.');
                toggleWsControls(true);
                notifyHomeView();
            });

            javaWs.addEventListener('message', (event) => {
                appendLog(`Mensagem recebida: ${event.data}`);
            });

            javaWs.addEventListener('close', (event) => {
                appendLog(`Conexao finalizada (code=${event.code}, reason=${event.reason || 'n/a'}).`);
                toggleWsControls(false);
                javaWs = null;
            });

            javaWs.addEventListener('error', () => appendLog('Erro no WebSocket.'));
        };

        wsConnectBtn.addEventListener('click', connectToJavaWs);
        connectToJavaWs(); // auto conectar

        wsDisconnectBtn.addEventListener('click', () => {
            if (!javaWs || javaWs.readyState !== WebSocket.OPEN) return;
            appendLog('Encerrando conexao a pedido do usuario.');
            javaWs.close(1000, 'Cliente encerrou a conexao');
        });

        wsSendExitBtn.addEventListener('click', () => {
            if (!javaWs || javaWs.readyState !== WebSocket.OPEN) return appendLog('Nao ha conexao ativa.');
            const payload = JSON.stringify({ tipo: 'pedidoDeSair' });
            appendLog(`Enviando pedido de sair: ${payload}`);
            javaWs.send(payload);
        });

        wsSendBtn.addEventListener('click', () => {
            if (!javaWs || javaWs.readyState !== WebSocket.OPEN) return appendLog('Nao ha conexao ativa.');
            const raw = wsMessageInput?.value.trim();
            if (!raw) return appendLog('Mensagem vazia.');
            let toSend = raw;
            try {
                toSend = JSON.stringify(JSON.parse(raw));
            } catch {
                appendLog('JSON invalido.');
                return;
            }
            appendLog(`Enviando: ${toSend}`);
            javaWs.send(toSend);
            wsMessageInput.value = '';
        });

        window.addEventListener('beforeunload', () => {
            if (javaWs && javaWs.readyState === WebSocket.OPEN) {
                javaWs.close(1001, 'Pagina recarregada ou fechada');
            }
        });
    });
</script>
@include('forms')
@include('profile')
</body>
</html>
