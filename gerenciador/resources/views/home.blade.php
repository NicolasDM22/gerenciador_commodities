<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Principal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" defer></script>
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
            justify-content: center;
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
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem;
        }
        .card h2 { margin: 0; font-size: 1.15rem; font-weight: 600; color: var(--gray-700); }
        .card p { margin: 0.5rem 0 0; color: var(--gray-600); line-height: 1.6; }
        
        /* Link estilo texto */
        .link-action {
            color: var(--link-color);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .link-action:hover { text-decoration: underline; }

        .analysis-list { list-style: none; padding: 0; margin: 0; }
        .analysis-list li { 
            padding: 0.75rem 0; 
            border-bottom: 1px solid var(--gray-100); 
            display: flex; justify-content: space-between; align-items: center;
        }
        .analysis-list li:last-child { border-bottom: none; }
        .analysis-list a { color: var(--link-color); text-decoration: none; font-weight: 500; }
        .analysis-list a:hover { text-decoration: underline; }
        .analysis-list span { color: var(--gray-500); font-size: 0.85rem; }

        .chart-wrapper { position: relative; min-height: 350px; padding-top: 1rem; }
        canvas { width: 100%; height: 350px; }
        
        .ws-controls { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0; }
        .ws-field { display: flex; flex: 1; gap: 0.5rem; align-items: center; }
        .ws-field input { flex: 1; padding: 0.6rem 0.8rem; border-radius: 10px; border: 1px solid var(--gray-300); }
        .ws-log {
            background: var(--gray-50); border-radius: 12px; padding: 1rem;
            font-family: monospace; font-size: 0.85rem;
            max-height: 220px; overflow-y: auto;
            border: 1px solid var(--gray-200); white-space: pre-wrap;
        }
        .ws-status { display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-600); }
        .ws-indicator {
            width: 10px; height: 10px; border-radius: 999px;
            background: var(--danger); transition: background 0.2s ease;
        }
        .ws-indicator.active { background: var(--success); }

        .modal {
             position: fixed; inset: 0; background: rgba(17, 24, 39, 0.4);
             display: none; align-items: center; justify-content: center;
             padding: 1.5rem; z-index: 10;
         }
        .modal.active { display: flex; }
        .modal-dialog {
             background: var(--white); border-radius: 22px; width: min(550px, 100%);
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

        /* PAGINAÇÃO NO MODAL */
        .pagination-container {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }
        .page-btn {
            background: var(--white);
            border: 1px solid var(--gray-300);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--gray-700);
            transition: all 0.2s;
        }
        .page-btn:hover:not(:disabled) { background: var(--gray-100); border-color: var(--gray-400); }
        .page-btn:disabled { opacity: 0.5; cursor: default; }
        .page-info { font-size: 0.85rem; color: var(--gray-600); font-weight: 500; }
        
        table.data-table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        table.data-table thead {
            background-color: var(--gray-200);
        }
        table.data-table th, table.data-table td {
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--gray-100);
            text-align: left;
        }
        table.data-table tbody tr:houver {
            background-color: var(--gray-50);
        }
        .dataTable_wrapper .dataTables_filter input {
            border-radius: 999px;
            border: 1px solid var(--gray-300);
            padding: 0.35rem 0.75rem;
            font-size: 0.9rem;
        }

        .dataTables_wrapper .dataTables_length select{
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            padding: 0.2rem 0.5rem;
            font-size: 0.9rem;
        }

        .dataTable_wrapper .dataTables_paginate .paginate_button {
            border-radius: 999px
            border: 1px solid transparent  ;
            padding: 0.2rem 0.6rem;
            margin: 0 1px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            border-coler: var(--primary);
            background: rgba(37, 99, 235, 0.08);
        }
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

        <div class="card">
            <div class="card-header">
                <h2 href="#" id="openAnalysisManager" class="link-action">
                    Visualizar análises
                </h2>
            </div>
                    <!-- Data table -->
        <div style="margin-top: 2rem;">
            <table id="commoditiesTable" class="data-table">
                <thead>
                    <tr>
                        <th>Commodity</th>
                        <th>Data</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($previousAnalyses as $analysis)
                    <tr>
                        <td>{{ $analysis->commodity }}</td>
                        <td>{{ $analysis->date }}</td>
                        <td><a href="{{ $analysis->url }}">Ver Análise</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align:center; color: var(--gray-500);">
                            Nenhuma análise cadastrada até o momento.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
        <!-- Fim Data table -->

        <div class="card">
            <h2>{{ $chartData['commodityName'] ?? 'Histórico de Preços' }}</h2>
            <div class="chart-wrapper">
                <canvas id="priceHistoryChart"></canvas>
            </div>
        </div>

        @if($isAdmin)
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
        @endif

    </main>
</div>


<div class="modal" id="analysisModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Histórico de Análises</h3>
            <button class="button button-outline" type="button" id="closeAnalysisModal">Fechar</button>
        </div>
        
        <div style="margin-top: 0.5rem; margin-bottom: 1rem;">
            <button class="button button-primary" style="width: 100%;" id="btnCreateNewAnalysis">
                + Nova Análise
            </button>
        </div>

        <div>
            @if($previousAnalyses->isNotEmpty())
                <ul class="analysis-list" id="paginatedList">
                    @foreach($previousAnalyses as $analysis)
                        <li class="analysis-item">
                            <a href="{{ $analysis->url }}">{{ $analysis->commodity }}</a>
                            <span>{{ $analysis->date }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="pagination-container" id="paginationControls">
                    <button class="page-btn" id="btnPrevPage" disabled>Anterior</button>
                    <span class="page-info" id="pageInfo">Pág 1</span>
                    <button class="page-btn" id="btnNextPage">Próxima</button>
                </div>
            @else
                <p style="color: var(--gray-500); font-size: 0.9rem; text-align: center; padding: 1rem;">
                    Nenhum histórico disponível.
                </p>
            @endif
        </div>
    </div>
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
                 <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required>
             </div>
             <div class="form-group">
                 <label for="telefone">Telefone</label>
                 <input id="telefone" name="telefone" type="tel" value="{{ old('telefone', $user->telefone ?? '') }}" pattern="[\d\s\-\(\)\+]{10,20}" required>
             </div>
             <div class="form-group">
                 <label for="endereco">Endereco</label>
                 <input id="endereco" name="endereco" type="text" value="{{ old('endereco', $user->endereco ?? '') }}" maxlength="255" required>
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
        // --- LÓGICA MODAL PERFIL ---
        const profileModal = document.getElementById('profileModal');
        const openProfileModalBtn = document.getElementById('openProfileModal');
        const closeProfileModalBtn = document.getElementById('closeProfileModal');
        const cancelProfileModalBtn = document.getElementById('cancelProfileModal');
        const toggleProfileModal = (show) => profileModal?.classList.toggle('active', show);

        openProfileModalBtn?.addEventListener('click', () => toggleProfileModal(true));
        closeProfileModalBtn?.addEventListener('click', () => toggleProfileModal(false));
        cancelProfileModalBtn?.addEventListener('click', () => toggleProfileModal(false));
        profileModal?.addEventListener('click', (e) => { if(e.target === profileModal) toggleProfileModal(false); });

        // --- LÓGICA MODAL ANÁLISE + PAGINAÇÃO CLIENT-SIDE ---
        const analysisModal = document.getElementById('analysisModal');
        const openAnalysisLink = document.getElementById('openAnalysisManager');
        const closeAnalysisBtn = document.getElementById('closeAnalysisModal');
        const btnCreateNewAnalysis = document.getElementById('btnCreateNewAnalysis');
        const triggerFormsModal = document.getElementById('openFormsModal'); // Botão oculto do include

        const toggleAnalysisModal = (show) => {
            analysisModal?.classList.toggle('active', show);
            if(show) renderPage(1); // Reseta para página 1 ao abrir
        };

        openAnalysisLink?.addEventListener('click', (e) => {
            e.preventDefault(); // Evita o scroll para o topo padrão do href="#"
            toggleAnalysisModal(true);
        });
        closeAnalysisBtn?.addEventListener('click', () => toggleAnalysisModal(false));
        analysisModal?.addEventListener('click', (e) => { if(e.target === analysisModal) toggleAnalysisModal(false); });

        // Abrir nova análise (fecha modal atual, abre form)
        btnCreateNewAnalysis?.addEventListener('click', () => {
            toggleAnalysisModal(false);
            if (triggerFormsModal) triggerFormsModal.click();
            else document.getElementById('formsModal')?.classList.add('active');
        });

        // --- LÓGICA DE PAGINAÇÃO (JavaScript puro) ---
        const listItems = document.querySelectorAll('#paginatedList .analysis-item');
        const itemsPerPage = 10;
        let currentPage = 1;
        const totalPages = Math.ceil(listItems.length / itemsPerPage);
        
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');
        const pageInfo = document.getElementById('pageInfo');
        const paginationContainer = document.getElementById('paginationControls');

        // Se tiver menos itens que uma página, esconde a paginação
        if (paginationContainer && listItems.length <= itemsPerPage) {
            paginationContainer.style.display = 'none';
        }

        function renderPage(page) {
            currentPage = page;
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;

            listItems.forEach((item, index) => {
                if (index >= start && index < end) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });

            if(pageInfo) pageInfo.textContent = `Pág ${currentPage} de ${totalPages}`;
            if(btnPrevPage) btnPrevPage.disabled = (currentPage === 1);
            if(btnNextPage) btnNextPage.disabled = (currentPage === totalPages || totalPages === 0);
        }

        btnPrevPage?.addEventListener('click', () => {
            if (currentPage > 1) renderPage(currentPage - 1);
        });

        btnNextPage?.addEventListener('click', () => {
            if (currentPage < totalPages) renderPage(currentPage + 1);
        });

        // Inicializa a paginação na carga (oculta itens além do limite)
        renderPage(1);


        // --- CHART JS ---
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
                            ticks: { color: '#4b5563', callback: v => 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0 }) },
                            grid: { color: '#e5e7eb' }
                        },
                        x: { ticks: { color: '#4b5563' }, grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#374151', titleColor: '#fff', bodyColor: '#fff',
                            callbacks: {
                                label: ctx => (ctx.dataset.label || '') + (ctx.parsed.y ? ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '')
                            }
                        }
                    }
                }
            });
        }

        // --- DATA TABLE ---
    const commoditiesTable = document.getElementById('commoditiesTables');
        if(commoditiesTable && window.jQuery && $.fn.DataTable){
            $(commoditiesTable).DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json'
                }
            });
        }
        // --- WEBSOCKET (Lógica protegida pelo isAdmin) ---
        @if($isAdmin)
        const wsIndicator = document.getElementById('javaWsIndicator');
        const wsStatus = document.getElementById('javaWsStatus');
        const wsConnectBtn = document.getElementById('javaWsConnect');
        const wsDisconnectBtn = document.getElementById('javaWsDisconnect');
        const wsSendExitBtn = document.getElementById('javaWsSendExit');
        const wsSendBtn = document.getElementById('javaWsSend');
        const wsMessageInput = document.getElementById('javaWsMessage');
        const wsLog = document.getElementById('javaWsLog');

        const appendLog = (msg) => {
            if (!wsLog) return;
            wsLog.textContent += `[${new Date().toLocaleTimeString()}] ${msg}\n`;
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

        const toggleWsControls = (conn) => {
            wsIndicator?.classList.toggle('active', conn);
            if(wsStatus) wsStatus.textContent = conn ? 'Conectado' : 'Desconectado';
            if(wsConnectBtn) wsConnectBtn.disabled = conn;
            if(wsDisconnectBtn) wsDisconnectBtn.disabled = !conn;
            if(wsSendExitBtn) wsSendExitBtn.disabled = !conn;
            if(wsSendBtn) wsSendBtn.disabled = !conn;
            if(wsMessageInput) { wsMessageInput.disabled = !conn; if(!conn) wsMessageInput.value = ''; }

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


        const connectToJavaWs = () => {
            if (javaWs && javaWs.readyState === WebSocket.OPEN) return;

            try {
                javaWs = new WebSocket("ws://localhost:3000");
            } catch (e) { appendLog(`Erro WS: ${e.message}`); return; }
            
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

                appendLog('Conectado.'); toggleWsControls(true);
                javaWs.send(JSON.stringify({ tipo: 'info', mensagem: 'Home aberta' }));
            });
            javaWs.addEventListener('message', e => appendLog(`Recebido: ${e.data}`));
            javaWs.addEventListener('close', () => { appendLog('Fechado.'); toggleWsControls(false); javaWs = null; });
            javaWs.addEventListener('error', () => appendLog('Erro WS.'));
        };

        wsConnectBtn?.addEventListener('click', connectToJavaWs);
        connectToJavaWs();
        wsDisconnectBtn?.addEventListener('click', () => javaWs?.close(1000, 'User closed'));
        wsSendExitBtn?.addEventListener('click', () => javaWs?.send(JSON.stringify({ tipo: 'pedidoDeSair' })));
        wsSendBtn?.addEventListener('click', () => {
            const val = wsMessageInput?.value.trim();
            if(!val) return;
            try { javaWs?.send(JSON.stringify(JSON.parse(val))); wsMessageInput.value = ''; } 
            catch { appendLog('JSON Inválido'); }

        });
        window.addEventListener('beforeunload', () => javaWs?.close(1001));
        @endif
    });
</script>
@include('forms')

@include('profile')

</body>
</html>
