import http from 'http';
import httpProxy from 'http-proxy';

const HTTP_TARGET = process.env.HTTP_TARGET ?? 'http://127.0.0.1:8002';
const WS_TARGET = process.env.WS_TARGET ?? 'ws://127.0.0.1:3000';
const SHARED_PORT = Number(process.env.SHARED_PORT ?? 8001);
const WS_PATH = process.env.WS_PATH ?? '/ws';

const proxy = httpProxy.createProxyServer({
    changeOrigin: false,
    xfwd: true,
    ws: true,
});

proxy.on('error', (error, _req, res) => {
    if (res.writeHead) {
        res.writeHead(502, { 'Content-Type': 'application/json' });
    }
    res.end(JSON.stringify({ error: 'Proxy error', details: error.message }));
});

const isWsRequest = (urlPath) => {
    if (!urlPath) return false;
    if (urlPath === WS_PATH) return true;
    return urlPath.startsWith(`${WS_PATH}/`);
};

const server = http.createServer((req, res) => {
    proxy.web(req, res, { target: HTTP_TARGET });
});

server.on('upgrade', (req, socket, head) => {
    const { url } = req;
    const hostHeader = req.headers.host ?? `127.0.0.1:${SHARED_PORT}`;
    let pathname = WS_PATH;

    try {
        const parsed = new URL(url ?? WS_PATH, `http://${hostHeader}`);
        pathname = parsed.pathname;
    } catch (error) {
        // keep default WS_PATH
    }

    if (!isWsRequest(pathname)) {
        socket.write('HTTP/1.1 400 Bad Request\r\n\r\n');
        socket.destroy();
        return;
    }

    proxy.ws(req, socket, head, { target: WS_TARGET });
});

server.listen(SHARED_PORT, '0.0.0.0', () => {
    console.log(`[proxy] Listening on port ${SHARED_PORT}`);
    console.log(`[proxy] HTTP traffic -> ${HTTP_TARGET}`);
    console.log(`[proxy] WS traffic on path "${WS_PATH}" -> ${WS_TARGET}`);
});
