import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
/**
 * GoogleAIClient.java by Nicolas Duran Munhos & Matias Amma & João Pedro de Moura
 */
/**
 * Cliente simples para consumir a API Gemini (Google AI).
 */
public class GoogleAIClient {

    private static final String DEFAULT_MODEL = "models/gemini-1.5-flash-latest";
    private static final String BASE_URL = "https://generativelanguage.googleapis.com/v1/models/";

    private final ObjectMapper mapper = new ObjectMapper();
    private final String apiKey;
    private final String model;
    private final String systemPrompt;

    public GoogleAIClient(String apiKey, String model, String systemPrompt) {
        if (apiKey == null || apiKey.trim().isEmpty()) {
            throw new IllegalArgumentException("GOOGLE_AI_KEY nao configurada.");
        }

        this.apiKey = apiKey.trim();
        this.model = (model == null || model.trim().isEmpty()) ? DEFAULT_MODEL : model.trim();
        this.systemPrompt = systemPrompt == null ? "" : systemPrompt.trim();
    }

    public String getModel() {
        return model;
    }

    public String ask(String prompt, String contexto) throws IOException {
        if (prompt == null || prompt.trim().isEmpty()) {
            throw new IllegalArgumentException("Prompt vazio nao pode ser enviado a IA.");
        }

        StringBuilder userPrompt = new StringBuilder();
        if (!systemPrompt.isEmpty()) {
            userPrompt.append(systemPrompt).append("\n\n");
        }
        if (contexto != null && !contexto.trim().isEmpty()) {
            userPrompt.append("Contexto conhecido:\n").append(contexto.trim()).append("\n\n");
        }
        userPrompt.append(prompt.trim());

        Map<String, Object> part = new HashMap<>();
        part.put("text", userPrompt.toString());

        Map<String, Object> content = new HashMap<>();
        List<Map<String, Object>> parts = new ArrayList<>();
        parts.add(part);
        content.put("parts", parts);

        Map<String, Object> body = new HashMap<>();
        List<Map<String, Object>> contents = new ArrayList<>();
        contents.add(content);
        body.put("contents", contents);

        String endpoint = BASE_URL + model + ":generateContent?key=" + apiKey;
        String response = executeRequest(endpoint, mapper.writeValueAsBytes(body));

        JsonNode json = mapper.readTree(response);
        JsonNode textNode = json.path("candidates").path(0).path("content").path("parts").path(0).path("text");
        if (!textNode.isTextual()) {
            throw new IOException("Resposta da API invalida: " + response);
        }

        return textNode.asText().trim();
    }

    private String executeRequest(String endpoint, byte[] payload) throws IOException {
        HttpURLConnection connection = (HttpURLConnection) new URL(endpoint).openConnection();
        connection.setConnectTimeout(20000);
        connection.setReadTimeout(45000);
        connection.setRequestMethod("POST");
        connection.setDoOutput(true);
        connection.setRequestProperty("Content-Type", "application/json");

        try (OutputStream os = connection.getOutputStream()) {
            os.write(payload);
        }

        int status = connection.getResponseCode();
        String body = readBody(status >= 400 ? connection.getErrorStream() : connection.getInputStream());
        if (status >= 300) {
            throw new IOException("Google AI retornou HTTP " + status + ": " + body);
        }
        return body;
    }

    private String readBody(InputStream stream) throws IOException {
        if (stream == null) return "";
        try (InputStream in = stream; ByteArrayOutputStream buffer = new ByteArrayOutputStream()) {
            byte[] data = new byte[4096];
            int n;
            while ((n = in.read(data)) != -1) {
                buffer.write(data, 0, n);
            }
            return buffer.toString(StandardCharsets.UTF_8.name());
        }
    }
}
