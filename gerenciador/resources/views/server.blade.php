<!--by Gustavo Cavalheiro, Nicolas Duran e Matias Amma-->

{{-- server.blade.php - Card do Servidor Java WebSocket --}}
<style>
    .server-card {
        background: var(--white);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .server-card h2 {
        margin: 0 0 1rem;
        font-size: 1.15rem;
        font-weight: 600;
        color: var(--gray-700);
    }
    
    .ws-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        font-weight: 500;
        color: var(--gray-600);
    }
    
    .ws-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--danger);
        transition: background 0.2s ease;
    }
    
    .ws-indicator.active {
        background: var(--success);
    }
    
    .ws-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .ws-controls .button {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
    
    .ws-field {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .ws-field input {
        flex: 1;
        padding: 0.625rem 0.875rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 0.875rem;
        color: var(--gray-700);
    }
    
    .ws-field input:disabled {
        background: var(--gray-50);
        color: var(--gray-400);
    }
    
    .ws-field .button {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
    }
    
    .ws-log {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        padding: 0.875rem;
        font-family: 'Courier New', monospace;
        font-size: 0.8125rem;
        color: var(--gray-700);
        max-height: 200px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>

<div class="server-card" id="javaWsCard" data-ws-url="{{ config('services.java_ws.url', '') }}">
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

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const wsCard = document.getElementById('javaWsCard');
        const formatWsUrl = (raw) => {
            if (!raw || typeof raw !== 'string') return '';
            const trimmed = raw.trim();
            if (!trimmed) return '';
            if (trimmed.startsWith('ws://') || trimmed.startsWith('wss://')) return trimmed;
            if (trimmed.startsWith('/')) {
                const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                return `${proto}//${window.location.host}${trimmed}`;
            }
            return trimmed;
        };
        const resolveWsUrl = () => {
            const fromDataset = formatWsUrl(wsCard?.dataset?.wsUrl);
            if (fromDataset) return fromDataset;
            const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const hostname = window.location.hostname || 'localhost';
            const fallbackPort = wsCard?.dataset?.wsPort || 3000;
            return `${proto}//${hostname}:${fallbackPort}`;
        };

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

        const toggleWsControls = (conn) => {
            wsIndicator?.classList.toggle('active', conn);
            if (wsStatus) wsStatus.textContent = conn ? 'Conectado' : 'Desconectado';
            if (wsConnectBtn) wsConnectBtn.disabled = conn;
            if (wsDisconnectBtn) wsDisconnectBtn.disabled = !conn;
            if (wsSendExitBtn) wsSendExitBtn.disabled = !conn;
            if (wsSendBtn) wsSendBtn.disabled = !conn;
            if (wsMessageInput) {
                wsMessageInput.disabled = !conn;
                if (!conn) wsMessageInput.value = '';
            }
        };
        let javaWs = null;

        const connectToJavaWs = () => {
            if (javaWs && javaWs.readyState === WebSocket.OPEN) return;
            const targetUrl = resolveWsUrl();
            appendLog(`Conectando em ${targetUrl} ...`);
            try {
                javaWs = new WebSocket(targetUrl);
            } catch (e) {
                appendLog(`Erro WS: ${e.message}`);
                return;
            }

            javaWs.addEventListener('open', () => {
                appendLog('Conectado.');
                toggleWsControls(true);
                javaWs.send(JSON.stringify({ tipo: 'info', mensagem: 'Home aberta' }));
            });
            javaWs.addEventListener('message', (event) => appendLog(`Recebido: ${event.data}`));
            javaWs.addEventListener('close', () => {
                appendLog('Fechado.');
                toggleWsControls(false);
                javaWs = null;
            });
            javaWs.addEventListener('error', () => appendLog('Erro WS.'));
        };

        wsConnectBtn?.addEventListener('click', connectToJavaWs);
        connectToJavaWs();
        wsDisconnectBtn?.addEventListener('click', () => javaWs?.close(1000, 'User closed'));
        wsSendExitBtn?.addEventListener('click', () => javaWs?.send(JSON.stringify({ tipo: 'pedidoDeSair' })));
        wsSendBtn?.addEventListener('click', () => {
            const val = wsMessageInput?.value.trim();
            if (!val) return;
            try {
                javaWs?.send(JSON.stringify(JSON.parse(val)));
                wsMessageInput.value = '';
            } catch {
                appendLog('JSON invalido');
            }
        });
        window.addEventListener('beforeunload', () => javaWs?.close(1001));
    });
</script>