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
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-500: #6b7280; --gray-600: #4b5563;
            --gray-700: #374151; --gray-900: #111827;
            --primary: #2563eb; --primary-dark: #1d4ed8;
            --success: #059669; --danger: #dc2626;
            --white: #ffffff; --link-color: #3b82f6;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--gray-100); font-family: "Segoe UI", Arial, sans-serif; color: var(--gray-900); }
        .page { min-height: 100vh; display: flex; flex-direction: column; }

        /* TOP BAR */
        .top-bar { background: var(--white); padding: 1.5rem clamp(1.5rem, 3vw, 3rem); display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08); }
        .profile { display: flex; align-items: center; gap: 1rem; }
        .avatar { width: 64px; height: 64px; border-radius: 18px; object-fit: cover; border: 3px solid var(--gray-200); }
        .profile-info strong { font-size: 1.25rem; display: block; }
        .profile-info span { color: var(--gray-500); font-size: 0.95rem; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }

        /* BOTÕES */
        .button { border: none; border-radius: 12px; padding: 0.75rem 1.4rem; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; }
        .button:hover { transform: translateY(-1px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.18); }
        .button-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important; color: var(--white) !important; }
        .button-outline { background: transparent; color: var(--primary); border: 1px solid rgba(37, 99, 235, 0.4); }
        .button-secondary { background: var(--white); border: 1px solid var(--gray-300); color: var(--gray-700); }
        .button-secondary:hover { background: var(--gray-50); }

        /* MAIN CONTENT */
        main.content { flex: 1; width: min(1180px, 100%); margin: 0 auto; padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem; display: grid; gap: 1.75rem; }

        /* CARD */
        .card { background: var(--white); border-radius: 16px; padding: 1.5rem; box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.1); position: relative; }
        .card h2 { margin: 0 0 1.5rem 0; font-size: 1.15rem; font-weight: 600; color: var(--gray-700); }

        /* TABLES */
        .simple-table { width: 100%; border-collapse: collapse; }
        .simple-table thead { background: var(--gray-50); }
        .simple-table th, .simple-table td { padding: 0.65rem 0.8rem; border-bottom: 1px solid var(--gray-200); text-align: left; font-size: 0.9rem; }
        .simple-table tbody tr:last-child td { border-bottom: none; }

        /* DATATABLES CUSTOM */
        .dataTables_wrapper .dataTables_filter { position: absolute; top: -3.5rem; right: 0; z-index: 10; }
        .dataTables_wrapper .dataTables_filter input { border-radius: 20px; border: 1px solid var(--gray-300); padding: 6px 15px; margin-left: 0.5rem; outline: none; font-size: 0.9rem; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--primary); }
        .dataTables_wrapper .dataTables_length { display: none; }
        table.dataTable thead th { background-color: var(--gray-50); color: var(--gray-700); }
        table.dataTable.no-footer { border-bottom: 1px solid var(--gray-200); }

        @media (max-width: 650px) {
            .dataTables_wrapper .dataTables_filter { position: relative; top: 0; right: auto; text-align: left; margin-bottom: 1rem; }
            .dataTables_wrapper .dataTables_filter input { margin-left: 0; width: 100%; margin-top: 5px; }
        }

        /* CHART */
        .chart-wrapper { position: relative; min-height: 350px; width: 100%; }

        /* WEBSOCKET */
        .ws-controls { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0; }
        .ws-field { display: flex; flex: 1; gap: 0.5rem; align-items: center; margin-top: 1rem; }
        .ws-field input { flex: 1; padding: 0.6rem 0.8rem; border-radius: 10px; border: 1px solid var(--gray-300); }
        .ws-log { background: var(--gray-50); border-radius: 12px; padding: 1rem; font-family: monospace; font-size: 0.85rem; max-height: 200px; overflow-y: auto; border: 1px solid var(--gray-200); white-space: pre-wrap; margin-top: 1rem; }
        .ws-status { display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; color: var(--gray-600); }
        .ws-indicator { width: 10px; height: 10px; border-radius: 50%; background: var(--danger); transition: background 0.2s; }
        .ws-indicator.active { background: var(--success); }

        /* TOAST */
        .toast-container { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; display: flex; flex-direction: column; align-items: center; gap: 10px; pointer-events: none; width: auto; }
        .toast-notification { background: var(--white); min-width: 300px; width: fit-content; max-width: 90vw; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; animation: slideDown 0.4s forwards; pointer-events: auto; border-left: 5px solid var(--gray-500); }
        .toast-success { border-left-color: var(--success); }
        .toast-error { border-left-color: var(--danger); }
        .toast-content { font-size: 0.95rem; color: var(--gray-700); font-weight: 500; white-space: pre-line; line-height: 1.5; }
        .toast-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--gray-400); }
        @keyframes slideDown { from { transform: translateY(-100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeOut { to { transform: translateY(-100%); opacity: 0; } }

        /* MODAL */
        .modal { position: fixed; inset: 0; background: rgba(17, 24, 39, 0.5); display: none; align-items: center; justify-content: center; z-index: 50; }
        .modal.active { display: flex; }
        .modal-dialog { background: var(--white); border-radius: 22px; width: min(600px, 95%); padding: 2rem; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; display: grid; gap: 0.5rem; }
        .form-group label { font-weight: 500; color: var(--gray-700); font-size: 0.9rem; }
        .form-group input { padding: 0.75rem; border-radius: 10px; border: 1px solid var(--gray-300); width: 100%; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; }
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
                        <th>Data/Hora Registro</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previousAnalyses ?? [] as $analysis)
                        <tr>
                            <td>{{ $analysis->id ?? '-' }}</td>
                            <td>{{ $analysis->commodity_nome ?? 'N/A' }}</td>
                            <td data-order="{{ !empty($analysis->updated_at) ? strtotime($analysis->updated_at) : 0 }}">
                                {{ $analysis->data_previsao ?? '-' }}
                            </td>
                            <td>
                                <a href="{{ route('previsoes.show', $analysis->id ?? 0) }}" style="color: var(--link-color); font-weight: 600; text-decoration: none;">
                                    Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card">
            @if(empty($chartData['labels']))
                <h2>Ainda não existem análises</h2>
                <div style="display: flex; justify-content: center; align-items: center; height: 350px; color: #6b7280; background-color: #f9fafb; border-radius: 12px; border: 1px dashed #d1d5db;">
                    <p style="font-size: 1rem; font-weight: 500;">Nenhum dado disponível para exibir o gráfico.</p>
                </div>
            @else
                <h2>{{ $chartData['commodityName'] ?? 'Histórico de Preços (Geral)' }}</h2>
                <div class="chart-wrapper">
                    <canvas id="priceHistoryChart"></canvas>
                </div>
            @endif
        </div>

        @if(!empty($aiAnalyses) && count($aiAnalyses) > 0)
        <div class="card">
            <h2>Últimas análises automáticas</h2>
            <table class="simple-table">
                <thead>
                    <tr>
                        <th>Matéria-prima</th>
                        <th>Mercado recomendado</th>
                        <th>Preço estimado</th>
                        <th>Gerada em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($aiAnalyses as $log)
                        @php
                            $parsed = is_array($log->parsed) ? $log->parsed : [];
                            $mercado = $parsed['mercados'][0] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $log->materia_prima ?? 'N/D' }}</td>
                            <td>{{ $mercado['nome'] ?? 'N/D' }}</td>
                            <td>
                                @if($mercado)
                                    {{ $mercado['moeda'] ?? 'BRL' }} {{ number_format($mercado['preco'] ?? 0, 2, ',', '.') }}
                                @else
                                    N/D
                                @endif
                            </td>
                            <td>{{ $log->created_at_formatted }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

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

<!-- MODAL PERFIL -->
<div class="modal" id="profileModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 style="margin:0">Atualizar perfil</h3>
            <button class="button button-secondary" type="button" id="btnCloseProfileModal" style="padding: 0.4rem 0.8rem;">&times;</button>
        </div>
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group"><label for="usuario">Usuário</label><input id="usuario" name="usuario" type="text" value="{{ old('usuario', $user->usuario ?? '') }}" required></div>
            <div class="form-group"><label for="nome">Nome completo</label><input id="nome" name="nome" type="text" value="{{ old('nome', $user->nome ?? '') }}"></div>
            <div class="form-group"><label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required></div>
            <div class="form-group"><label for="telefone">Telefone</label><input id="telefone" name="telefone" type="tel" value="{{ old('telefone', $user->telefone ?? '') }}"></div>
            <div class="form-group"><label for="endereco">Endereço</label><input id="endereco" name="endereco" type="text" value="{{ old('endereco', $user->endereco ?? '') }}"></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group"><label for="nova_senha">Nova senha</label><input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password"></div>
                <div class="form-group"><label for="nova_senha_confirmation">Confirmar senha</label><input id="nova_senha_confirmation" name="nova_senha_confirmation" type="password" autocomplete="new-password"></div>
            </div>
            <div class="form-group"><label for="foto">Foto de perfil</label><input id="foto" name="foto" type="file" accept="image/*"></div>
            <div class="modal-footer">
                <button class="button button-outline" type="button" id="btnCancelProfileModal">Cancelar</button>
                <button class="button button-primary" type="submit">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Dados para o Gráfico
    const chartRawData = @json($chartData ?? null);

    $(document).ready(function() {
        // DataTables
        $('#commoditiesTable').DataTable({
            pageLength: 5, lengthChange: false, responsive: true,
            order: [[2, 'desc']], // Ordena pela coluna de data (índice 2)
            columnDefs: [
                { targets: [0], orderable: false, searchable: false },
                { targets: [3], orderable: false, searchable: false }  
            ],
            language: {
                search: "", searchPlaceholder: "Filtrar análises...",
                info: "Mostrando _START_ até _END_ de _TOTAL_ registro(s)",
                infoFiltered: "(filtrado de _MAX_ registros no total)",
                zeroRecords: "Nenhum registro encontrado",
                infoEmpty: "Não há registros disponíveis",
                paginate: { first: "Primeiro", last: "Último", next: "Próximo", previous: "Anterior" },
                loadingRecords: "Carregando...", processing: "Processando...", emptyTable: "Nenhum registro encontrado"
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Modais
        const profileModal = document.getElementById('profileModal');
        const toggleProfile = (show) => profileModal?.classList.toggle('active', show);
        document.getElementById('btnOpenProfileModal')?.addEventListener('click', () => toggleProfile(true));
        document.getElementById('btnCloseProfileModal')?.addEventListener('click', () => toggleProfile(false));
        document.getElementById('btnCancelProfileModal')?.addEventListener('click', () => toggleProfile(false));
        
        // Forms Modal (Externo)
        const btnOpenForms = document.getElementById('btnOpenFormsModal');
        const formsModal = document.getElementById('formsModal'); 
        if (btnOpenForms) {
            btnOpenForms.addEventListener('click', () => {
                if(formsModal) formsModal.classList.add('active');
                else alert('Erro: Modal de forms não encontrado.');
            });
        }

        // --- CORREÇÃO DO GRÁFICO: Removemos o Mock Data ---
        const chartCanvas = document.getElementById('priceHistoryChart');
        if (chartCanvas && typeof Chart !== 'undefined') {
            let dataToUse = chartRawData;
            
            // Se não houver dados, limpamos o objeto para o gráfico ficar vazio/zerado
            if (!dataToUse || !dataToUse.labels || dataToUse.labels.length === 0) {
                dataToUse = { labels: [], prices: [] };
            }

            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataToUse.labels || [],
                    datasets: [{
                        label: 'Preço Médio (R$/kg)',
                        data: dataToUse.prices || [],
                        borderColor: '#F97316', backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 2, pointBackgroundColor: '#F97316',
                        pointRadius: 4, pointHoverRadius: 6, tension: 0.1 
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: false,
                            ticks: { color: '#4b5563', callback: v => 'R$ ' + v.toLocaleString('pt-BR') },
                            grid: { color: '#e5e7eb' }
                        },
                        x: { ticks: { color: '#4b5563' }, grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => (ctx.dataset.label || '') + (ctx.parsed.y ? ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '')
                            }
                        }
                    }
                }
            });
        }

        // --- WEBSOCKET ---
        const wsUrl = "ws://localhost:3000"; 
        let websocket;
        const btnConnect = document.getElementById('javaWsConnect');
        const btnDisconnect = document.getElementById('javaWsDisconnect');
        const btnSend = document.getElementById('javaWsSend');
        const btnExit = document.getElementById('javaWsSendExit');
        const inputMsg = document.getElementById('javaWsMessage');
        const logArea = document.getElementById('javaWsLog');
        const statusLabel = document.getElementById('javaWsStatus');
        const statusDot = document.getElementById('javaWsIndicator');

        function writeLog(msg) { if(logArea) { logArea.textContent += msg + "\n"; logArea.scrollTop = logArea.scrollHeight; } }
        function updateState(isConnected) {
            if(statusLabel) statusLabel.textContent = isConnected ? "Conectado" : "Desconectado";
            if(statusDot) isConnected ? statusDot.classList.add('active') : statusDot.classList.remove('active');
            if(btnConnect) btnConnect.disabled = isConnected;
            if(btnDisconnect) btnDisconnect.disabled = !isConnected;
            if(btnSend) btnSend.disabled = !isConnected;
            if(btnExit) btnExit.disabled = !isConnected;
            if(inputMsg) inputMsg.disabled = !isConnected;
        }

        function initWebSocket() {
            if(!btnConnect) return;
            writeLog("Tentando conectar em " + wsUrl + "...");
            try {
                websocket = new WebSocket(wsUrl);
                websocket.onopen = function(evt) { writeLog("Conectado!"); updateState(true); };
                websocket.onclose = function(evt) { writeLog("Desconectado."); updateState(false); };
                websocket.onmessage = function(evt) {
                    writeLog("Recebido: " + evt.data);
                    try {
                        const data = JSON.parse(evt.data);
                        if(data.tipo === 'desligamento') showToast(data.msg, 'error');
                    } catch(e) {}
                };
                websocket.onerror = function(evt) { writeLog("Erro."); updateState(false); };
            } catch(e) { writeLog("Erro: " + e.message); }
        }

        if(btnConnect) btnConnect.addEventListener('click', initWebSocket);
        if(btnDisconnect) btnDisconnect.addEventListener('click', () => websocket?.close());
        if(btnSend) btnSend.addEventListener('click', () => { if(websocket && inputMsg.value) { websocket.send(inputMsg.value); writeLog("Enviado: "+inputMsg.value); inputMsg.value=""; } });
        if(btnExit) btnExit.addEventListener('click', () => { if(websocket) { websocket.send('{"tipo":"pedidoDeSair"}'); writeLog("Pedido de sair enviado."); } });
        if (document.getElementById('javaWsCard')) initWebSocket();
    });

    // TOAST
    function showToast(message, type = 'default') {
        const container = document.getElementById('toast-container');
        if(!container) return;
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        let icon = type === 'success' ? '&#10003;' : (type === 'error' ? '&#10007;' : '');
        toast.innerHTML = `<div class="toast-content"><span style="margin-right:8px">${icon}</span>${message}</div><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>`;
        container.appendChild(toast);
        setTimeout(() => { toast.style.animation = 'fadeOut 0.3s forwards'; toast.addEventListener('animationend', () => toast.remove()); }, 6000);
    }

    @if (session('status')) showToast("{{ session('status') }}", 'success'); @endif
    @if ($errors->any()) @foreach ($errors->all() as $error) showToast("{{ $error }}", 'error'); @endforeach @endif
</script>

@include('forms')
@include('profile')
</body>
</html>