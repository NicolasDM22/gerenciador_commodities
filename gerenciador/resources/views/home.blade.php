<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Principal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

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

        /* TOP BAR */
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

        /* BOTÕES */
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
            text-decoration: none;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.18);
        }

        .button-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: var(--white) !important;
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
        .button-secondary:hover { background: var(--gray-50); }

        /* MAIN CONTENT */
        main.content {
            flex: 1; width: min(1180px, 100%); margin: 0 auto;
            padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem; display: grid; gap: 1.75rem;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            pointer-events: none;
            width: auto;
        }

        .toast-notification {
            background: var(--white);
            min-width: 300px;
            width: fit-content;
            max-width: 90vw;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            animation: slideDown 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            pointer-events: auto;
            border-left: 5px solid var(--gray-500);
        }

        .toast-success { border-left-color: var(--success); }
        .toast-error { border-left-color: var(--danger); }

        .toast-content { 
            font-size: 0.95rem; 
            color: var(--gray-700); 
            font-weight: 500;
            white-space: pre-line;
            line-height: 1.5;
            padding-top: 2px; 
        }

        .toast-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--gray-400); }
        .toast-close:hover { color: var(--gray-600); }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                transform: translateY(-100%);
                opacity: 0;
            }
        }
        /* --- CARD & DATATABLES CUSTOMIZATIONS --- */
        .card {
            background: var(--white); border-radius: 16px;
            padding: 1.5rem; box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.1);
            position: relative;
        }

        .card h2 { margin: 0 0 1.5rem 0; font-size: 1.15rem; font-weight: 600; color: var(--gray-700); }

        /* DATA TABLE CUSTOMIZATION */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px; border: 1px solid var(--gray-300); padding: 5px 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px; border: 1px solid var(--gray-300); padding: 5px;
        }
        table.dataTable thead th { background-color: var(--gray-50); color: var(--gray-700); }

        /* CHART */
        .chart-wrapper { position: relative; min-height: 350px; width: 100%; }

        /* WEBSOCKET ADMIN PANEL */
        .ws-controls { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0; }
        .ws-field { display: flex; flex: 1; gap: 0.5rem; align-items: center; margin-top: 1rem; }
        .ws-field input { flex: 1; padding: 0.6rem 0.8rem; border-radius: 10px; border: 1px solid var(--gray-300); }
        .ws-log {
            background: var(--gray-50); border-radius: 12px; padding: 1rem;
            font-family: monospace; font-size: 0.85rem;
            max-height: 200px; overflow-y: auto;
            border: 1px solid var(--gray-200); white-space: pre-wrap; margin-top: 1rem;
        }
        .ws-status { display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-600); }
        .ws-indicator { width: 10px; height: 10px; border-radius: 50%; background: var(--danger); transition: background 0.2s; }
        .ws-indicator.active { background: var(--success); }


        
        .card h2 { 
            margin: 0 0 1.5rem 0; 
            font-size: 1.15rem; 
            font-weight: 600; 
            color: var(--gray-700); 
        }

        /* AJUSTE PARA ALINHAR O FILTRO COM O TÍTULO */
        .dataTables_wrapper {
            position: relative;
        }

        .dataTables_wrapper .dataTables_filter {
            position: absolute;
            top: -3.5rem; /* Puxa o filtro para cima, na mesma linha do H2 */
            right: 0;
            z-index: 10;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px; 
            border: 1px solid var(--gray-300); 
            padding: 6px 15px;
            margin-left: 0.5rem;
            outline: none;
            transition: border-color 0.2s;
            font-size: 0.9rem;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary);
        }

        .dataTables_wrapper .dataTables_length {
            display: none; 
        }
        
        table.dataTable thead th { background-color: var(--gray-50); color: var(--gray-700); }
        table.dataTable.no-footer { border-bottom: 1px solid var(--gray-200); }

        /* Responsividade para o filtro não sobrepor o título em telas pequenas */
        @media (max-width: 650px) {
            .dataTables_wrapper .dataTables_filter {
                position: relative;
                top: 0;
                right: auto;
                text-align: left;
                margin-bottom: 1rem;
            }
            .dataTables_wrapper .dataTables_filter input {
                margin-left: 0;
                width: 100%;
                margin-top: 5px;
            }
        }

        /* CHART */
        .chart-wrapper { position: relative; min-height: 350px; width: 100%; }


        /* MODAL */
        .modal {
            position: fixed; inset: 0; background: rgba(17, 24, 39, 0.5);
            display: none; align-items: center; justify-content: center; z-index: 50;
        }
        .modal.active { display: flex; }
        .modal-dialog {
            background: var(--white); border-radius: 22px; width: min(600px, 95%);
            padding: 2rem; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; display: grid; gap: 0.5rem; }
        .form-group label { font-weight: 500; color: var(--gray-700); font-size: 0.9rem; }
        .form-group input { padding: 0.75rem; border-radius: 10px; border: 1px solid var(--gray-300); width: 100%; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; }

        @media (max-width: 820px) {
            .top-bar { flex-direction: column; align-items: flex-start; }
            .top-actions { width: 100%; justify-content: flex-start; }
        }
    </style>
</head>
<body>

<div id="toast-container" class="toast-container"></div>

<div class="page">
    <x-topbar :user="$user" :isAdmin="$isAdmin ?? false">
        
        @if($isAdmin ?? false)
            <a href="{{ route('forecasts') }}" class="button button-secondary">Debug Previsões</a>
        @endif
        
        <button class="button button-outline" id="btnOpenFormsModal" type="button">Nova análise</button>
        <button class="button button-outline" id="btnOpenProfileModal" type="button">Atualizar perfil</button>

    </x-topbar>

    <main class="content">
        <div class="card">
            <h2>Visualizar análises</h2>
            <table id="commoditiesTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Commodity</th>
                        <th>Data da Análise</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($previousAnalyses ?? [] as $analysis)
                        <tr>
                            <td>{{ $analysis->id ?? '-' }}</td>
                            <td>{{ $analysis->commodity_nome ?? 'N/A' }}</td>
                            <td>{{ $analysis->data_previsao ?? '-' }}</td>
                            <td>
                                <a href="{{ url('/previsoes/' . $analysis->id) }}" style="color: var(--link-color); font-weight: 600; text-decoration: none;">
                                    Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    @empty
                        {{-- DataTables lida com tabela vazia --}}
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>{{ $chartData['commodityName'] ?? 'Histórico de Preços (Geral)' }}</h2>
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
                <input type="text" id="javaWsMessage" placeholder='Mensagem JSON, ex: {"tipo":"echo"}' disabled>
                <button class="button button-primary" type="button" id="javaWsSend" disabled>Enviar</button>
            </div>
            
            <div class="ws-log" id="javaWsLog">Aguardando conexão...</div>
        </div>
        @endif

    </main>
</div>

<div class="modal" id="profileModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 style="margin:0">Atualizar perfil</h3>
            <button class="button button-secondary" type="button" id="btnCloseProfileModal" style="padding: 0.4rem 0.8rem;">&times;</button>
        </div>
        
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
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
                <input id="telefone" name="telefone" type="tel" value="{{ old('telefone', $user->telefone ?? '') }}">
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço</label>
                <input id="endereco" name="endereco" type="text" value="{{ old('endereco', $user->endereco ?? '') }}">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="nova_senha">Nova senha</label>
                    <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="nova_senha_confirmation">Confirmar senha</label>
                    <input id="nova_senha_confirmation" name="nova_senha_confirmation" type="password" autocomplete="new-password">
                </div>
            </div>
            
            <div class="form-group">
                <label for="foto">Foto de perfil</label>
                <input id="foto" name="foto" type="file" accept="image/*">
            </div>
            
            <div class="modal-footer">
                <button class="button button-outline" type="button" id="btnCancelProfileModal">Cancelar</button>
                <button class="button button-primary" type="submit">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Dados para o Gráfico (Do Controller ou Vazio)
    const chartRawData = @json($chartData ?? null);

    $(document).ready(function() {
        // 1. Inicializar DataTables com as alterações pedidas
        $('#commoditiesTable').DataTable({
            pageLength: 5, // Exibe 5 por vez (mas agora recebe todos os dados)
            lengthChange: false, // <--- REMOVE O SELETOR "Exibir X resultados"
            responsive: true,
            language: {
                search: "", // Remove o label "Search:", deixa só o input
                searchPlaceholder: "Filtrar análises...",
                // Se não houver filtro, mostra o total. Se houver, mostra "filtrado de X"
                info: "Mostrando _START_ até _END_ de _TOTAL_ registro(s)",
                infoFiltered: "(filtrado de _MAX_ registros no total)",
                zeroRecords: "Nenhum registro encontrado",
                infoEmpty: "Não há registros disponíveis",
                paginate: { first: "Primeiro", last: "Último", next: "Próximo", previous: "Anterior" },
                loadingRecords: "Carregando...",
                processing: "Processando...",
                emptyTable: "Nenhum registro encontrado"
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        
        // 2. Lógica dos Modais
        const profileModal = document.getElementById('profileModal');
        const toggleProfile = (show) => profileModal?.classList.toggle('active', show);

        document.getElementById('btnOpenProfileModal')?.addEventListener('click', () => toggleProfile(true));
        document.getElementById('btnCloseProfileModal')?.addEventListener('click', () => toggleProfile(false));
        document.getElementById('btnCancelProfileModal')?.addEventListener('click', () => toggleProfile(false));

        // Conexão com o Modal do Forms
        const btnOpenForms = document.getElementById('btnOpenFormsModal');
        const formsModal = document.getElementById('formsModal'); 
        
        if (btnOpenForms) {
            btnOpenForms.addEventListener('click', () => {
                if(formsModal) formsModal.classList.add('active');
                else alert('Erro: Modal de forms não encontrado no include.');
            });
        }

        // 3. Gráfico RESTAURADO
        const chartCanvas = document.getElementById('priceHistoryChart');

        if (chartCanvas && typeof Chart !== 'undefined') {
            
            let dataToUse = chartRawData;
            if (!dataToUse || !dataToUse.labels) {
                dataToUse = {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr'],
                    prices: [50, 52, 51, 54]
                };
            }

            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataToUse.labels || [],
                    datasets: [{
                        label: 'Preço Médio (R$/kg)',
                        data: dataToUse.prices || [],
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

        // -------------------------------------------------------
        // 4. LÓGICA DO WEBSOCKET
        // -------------------------------------------------------
        const wsUrl = "ws://localhost:3000"; // Porta deve bater com a do Servidor Java
        let websocket;

        // Referências aos elementos do DOM
        const btnConnect    = document.getElementById('javaWsConnect');
        const btnDisconnect = document.getElementById('javaWsDisconnect');
        const btnSend       = document.getElementById('javaWsSend');
        const btnExit       = document.getElementById('javaWsSendExit');
        const inputMsg      = document.getElementById('javaWsMessage');
        const logArea       = document.getElementById('javaWsLog');
        const statusLabel   = document.getElementById('javaWsStatus');
        const statusDot     = document.getElementById('javaWsIndicator');

        // Função auxiliar de log
        function writeLog(msg) {
            if(logArea) {
                logArea.textContent += msg + "\n";
                logArea.scrollTop = logArea.scrollHeight;
            }
        }

        // Atualiza a UI (botões e texto) conforme estado
        function updateState(isConnected) {
            if(statusLabel) statusLabel.textContent = isConnected ? "Conectado" : "Desconectado";
            if(statusDot) {
                if(isConnected) statusDot.classList.add('active');
                else statusDot.classList.remove('active');
            }
            
            if(btnConnect)    btnConnect.disabled    = isConnected;
            if(btnDisconnect) btnDisconnect.disabled = !isConnected;
            if(btnSend)       btnSend.disabled       = !isConnected;
            if(btnExit)       btnExit.disabled       = !isConnected;
            if(inputMsg)      inputMsg.disabled      = !isConnected;
        }

        function initWebSocket() {
            if(!btnConnect) return; 

            writeLog("Tentando conectar em " + wsUrl + "...");
            
            try {
                websocket = new WebSocket(wsUrl);

                websocket.onopen = function(evt) {
                    writeLog("Conectado com sucesso!");
                    updateState(true);
                };

                websocket.onclose = function(evt) {
                    writeLog("Desconectado.");
                    updateState(false);
                };

                websocket.onmessage = function(evt) {
                    writeLog("Recebido: " + evt.data);
                    
                    try {
                        const data = JSON.parse(evt.data);
                        if(data.tipo === 'desligamento') {
                            showToast(data.msg, 'error');
                        }
                    } catch(e) {
                    }
                };

                websocket.onerror = function(evt) {
                    writeLog("Erro na conexão.");
                    updateState(false);
                };

            } catch(e) {
                writeLog("Erro ao inicializar: " + e.message);
            }
        }

        function closeWebSocket() {
            if(websocket) websocket.close();
        }

        function sendMessage() {
            if(websocket && inputMsg && inputMsg.value) {
                websocket.send(inputMsg.value);
                writeLog("Enviado: " + inputMsg.value);
                inputMsg.value = "";
            }
        }

        function sendExitRequest() {
            if(websocket) {
                const msg = '{"tipo":"pedidoDeSair"}';
                websocket.send(msg);
                writeLog("Enviado pedido de sair...");
            }
        }

        if(btnConnect)    btnConnect.addEventListener('click', initWebSocket);
        if(btnDisconnect) btnDisconnect.addEventListener('click', closeWebSocket);
        if(btnSend)       btnSend.addEventListener('click', sendMessage);
        if(btnExit)       btnExit.addEventListener('click', sendExitRequest);
        
        initWebSocket();
    });

    // Função JS para criar o HTML do Toast
    function showToast(message, type = 'default') {
        const container = document.getElementById('toast-container');
        if(!container) return; 

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        
        let icon = '';
        if(type === 'success') icon = '<span style="margin-right: 8px">✅</span>';
        if(type === 'error') icon = '<span style="margin-right: 8px">❌</span>';

        toast.innerHTML = `
            <div class="toast-content">${icon}${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()" style="margin-left: auto;">&times;</button>
        `;

        container.appendChild(toast);

        // Remove automaticamente
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s forwards';
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }, 6000); 
    }

    @if (session('status'))
        showToast("{{ session('status') }}", 'success');
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            showToast("{{ $error }}", 'error');
        @endforeach
    @endif
</script>

@include('forms')
@include('profile')
</body>
</html>