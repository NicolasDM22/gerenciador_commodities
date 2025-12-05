import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ObjectNode;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpServer;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.nio.charset.StandardCharsets;
/**
 * AiHttpBridge.java by Nicolas Duran Munhos
 */
/**
 * Ponte HTTP simples para que o PHP consiga disparar analises sem precisar implementar um cliente WebSocket.
 */
public class AiHttpBridge {

    private final HttpServer server;
    private final GoogleAIClient chatClient;
    private final ObjectMapper mapper = new ObjectMapper();

    public AiHttpBridge(GoogleAIClient chatClient, int porta) throws IOException {
        this.chatClient = chatClient;
        this.server = HttpServer.create(new InetSocketAddress(porta), 0);
        this.server.createContext("/analises", this::handleAnalise);
    }

    public void start() {
        server.start();
    }

    public void stop() {
        server.stop(0);
    }

    private void handleAnalise(HttpExchange exchange) throws IOException {
        if (!"POST".equalsIgnoreCase(exchange.getRequestMethod())) {
            respond(exchange, 405, "{\"error\":\"Use POST\"}");
            return;
        }

        JsonNode body;
        try {
            body = mapper.readTree(readStream(exchange.getRequestBody()));
        } catch (Exception ex) {
            respond(exchange, 400, "{\"error\":\"JSON invalido\"}");
            return;
        }

        String texto = body.path("texto").asText("");
        if (texto == null || texto.trim().isEmpty()) {
            respond(exchange, 400, "{\"error\":\"Campo 'texto' obrigatorio\"}");
            return;
        }

        String contexto = body.hasNonNull("contexto") ? body.get("contexto").asText() : null;

        try {
            String resposta = chatClient.ask(texto, contexto);
            ObjectNode ok = mapper.createObjectNode();
            ok.put("conteudo", resposta);
            ok.put("modelo", chatClient.getModel());
            ok.put("timestamp", System.currentTimeMillis());
            if (body.has("meta")) {
                ok.set("meta", body.get("meta"));
            }
            respond(exchange, 200, ok.toString());
        } catch (Exception ex) {
            ObjectNode err = mapper.createObjectNode();
            err.put("error", "Falha ao consultar a IA");
            err.put("detalhes", ex.getMessage());
            respond(exchange, 500, err.toString());
        }
    }

    private void respond(HttpExchange exchange, int status, String body) throws IOException {
        byte[] bytes = body.getBytes(StandardCharsets.UTF_8);
        exchange.getResponseHeaders().set("Content-Type", "application/json; charset=utf-8");
        exchange.sendResponseHeaders(status, bytes.length);
        try (OutputStream os = exchange.getResponseBody()) {
            os.write(bytes);
        }
    }

    private byte[] readStream(InputStream stream) throws IOException {
        try (InputStream in = stream; ByteArrayOutputStream buffer = new ByteArrayOutputStream()) {
            byte[] data = new byte[4096];
            int n;
            while ((n = in.read(data, 0, data.length)) != -1) {
                buffer.write(data, 0, n);
            }
            return buffer.toByteArray();
        }
    }
}
