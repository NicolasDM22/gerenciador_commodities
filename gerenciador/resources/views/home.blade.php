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
            white-space: nowrap; 
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

        .card {
            background: var(--white); border-radius: 16px;
            padding: 1.5rem; box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.1);
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
                            <td>{{ $analysis->commodity ?? 'N/A' }}</td>
                            <td>{{ $analysis->data_previsao ?? '-' }}</td>
                            <td>
                                <a href="{{ $analysis->url ?? '#' }}" style="color: var(--link-color); font-weight: 600; text-decoration: none;">
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
        // 1. Inicializar DataTables
        $('#commoditiesTable').DataTable({
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            responsive: true,
            language: {
                search: "Filtrar:", 
                searchPlaceholder: "Buscar registros...",
                lengthMenu: "Exibir _MENU_ resultados por página",
                zeroRecords: "Nenhum registro encontrado",
                info: "Mostrando _START_ até _END_ de _TOTAL_ registro(s)",
                infoEmpty: "Não há registros disponíveis",
                infoFiltered: "(filtrado de _MAX_ registros no total)",
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

        // 4. WebSocket (Apenas Admin)
        @if($isAdmin)
            const wsLog = document.getElementById('javaWsLog');
            const statusText = document.getElementById('javaWsStatus');
            const indicator = document.getElementById('javaWsIndicator');
            
            const appendLog = (msg) => {
                if(wsLog) {
                    wsLog.textContent += `[${new Date().toLocaleTimeString()}] ${msg}\n`;
                    wsLog.scrollTop = wsLog.scrollHeight;
                }
            };

            const updateWsUI = (connected) => {
                if(indicator) indicator.classList.toggle('active', connected);
                if(statusText) statusText.textContent = connected ? 'Conectado' : 'Desconectado';
                
                document.getElementById('javaWsConnect').disabled = connected;
                document.getElementById('javaWsDisconnect').disabled = !connected;
                document.getElementById('javaWsSend').disabled = !connected;
                document.getElementById('javaWsMessage').disabled = !connected;
                document.getElementById('javaWsSendExit').disabled = !connected;
            };

            let javaWs = null;
            const WS_URL = "ws://localhost:3000"; 

            const connectWs = () => {
                if (javaWs && javaWs.readyState === WebSocket.OPEN) return;
                
                appendLog(`Tentando conectar em ${WS_URL}...`);
                
                try {
                    javaWs = new WebSocket(WS_URL);

                    javaWs.onopen = () => {
                        appendLog('Conexão estabelecida!');
                        updateWsUI(true);
                        javaWs.send(JSON.stringify({ tipo: 'info', msg: 'Admin Laravel Conectado' }));
                    };

                    javaWs.onmessage = (e) => {
                    // Mantém o log visual no card
                    appendLog(`Recebido: ${e.data}`);

                    try {
                        // Tenta ler os dados como JSON
                        const data = JSON.parse(e.data);

                        // Verifica se é a mensagem de desligamento
                        if (data.tipo === "desligamento") {
                            // Exibe o alerta vermelho com a mensagem do servidor
                            showToast(data.msg, 'error');
                        }
                    } catch (err) {
                        // Se a mensagem não for um JSON válido, ignora o erro silenciosamente
                        // ou apenas trata como texto comum
                    }
                };

                    // --- MUDANÇA AQUI: Toast no evento onclose ---
                    javaWs.onclose = (e) => {
                        appendLog(`Conexão fechada (Código: ${e.code})`);
                        updateWsUI(false);
                        javaWs = null;
                    };

                    javaWs.onerror = () => {
                        appendLog('Erro de conexão.');
                    };

                } catch (err) {
                    appendLog('Erro crítico: ' + err.message);
                }
            };

            document.getElementById('javaWsConnect')?.addEventListener('click', connectWs);
            document.getElementById('javaWsDisconnect')?.addEventListener('click', () => {
                if(javaWs) javaWs.close(1000, 'Admin desconectou');
            });
            document.getElementById('javaWsSend')?.addEventListener('click', () => {
                const input = document.getElementById('javaWsMessage');
                if(input && javaWs && input.value) {
                    javaWs.send(input.value);
                    appendLog(`Enviado: ${input.value}`);
                    input.value = '';
                }
            });
            document.getElementById('javaWsSendExit')?.addEventListener('click', () => {
                if(javaWs) javaWs.send(JSON.stringify({ tipo: 'pedidoDeSair' }));
            });

            connectWs();
        @endif
    });

    // Função JS para criar o HTML do Toast
    function showToast(message, type = 'default') {
        const container = document.getElementById('toast-container');
        if(!container) return; 

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        
        let icon = '';
        if(type === 'success') icon = '✅ ';
        if(type === 'error') icon = '❌ ';

        toast.innerHTML = `
            <div class="toast-content">${icon}${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s forwards';
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }, 5000);
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
