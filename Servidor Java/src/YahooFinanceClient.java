import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

public class YahooFinanceClient {
    private final HttpClient http = HttpClient.newHttpClient();
    private final ObjectMapper mapper = new ObjectMapper();

    // Busca histórico com retry automático para erro 429
    public JsonNode fetchChart(String symbol, String interval, String range) throws Exception {
        String url = String.format("https://query1.finance.yahoo.com/v8/finance/chart/%s?interval=%s&range=%s",
                                    uriEncode(symbol), uriEncode(interval), uriEncode(range));

        int maxRetries = 3;
        int delaySeconds = 2;

        for (int attempt = 1; attempt <= maxRetries; attempt++) {
            HttpRequest req = HttpRequest.newBuilder()
                    .uri(URI.create(url))
                    .GET()
                    .header("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
                    .build();

            HttpResponse<String> resp = http.send(req, HttpResponse.BodyHandlers.ofString());

            // Se receber 429 e ainda tiver tentativas, espera e tenta novamente
            if (resp.statusCode() == 429 && attempt < maxRetries) {
                System.out.println("Yahoo retornou 429. Aguardando " + delaySeconds + "s antes de tentar novamente...");
                Thread.sleep(delaySeconds * 1000L);
                delaySeconds *= 2; // backoff exponencial: 2s, 4s, 8s...
                continue;
            }

            if (resp.statusCode() != 200) {
                throw new RuntimeException("Erro Yahoo (HTTP " + resp.statusCode() + "): " + resp.body());
            }

            JsonNode root = mapper.readTree(resp.body());
            JsonNode chart = root.path("chart");
            if (!chart.path("error").isNull()) {
                throw new RuntimeException("Erro no payload Yahoo: " + chart.path("error").toString());
            }
            return root;
        }

        throw new RuntimeException("Limite de requisições do Yahoo atingido (HTTP 429). Tente novamente em alguns minutos.");
    }

    // Converte o JSON retornado em uma lista de pares (data ISO -> close)
    public List<PricePoint> parseClosePrices(JsonNode chartJson) {
        List<PricePoint> result = new ArrayList<>();
        JsonNode res = chartJson.path("chart").path("result").get(0);
        if (res == null) return result;

        JsonNode timestamps = res.path("timestamp");
        JsonNode closes = res.path("indicators").path("quote").get(0).path("close");

        if (timestamps == null || closes == null) return result;

        DateTimeFormatter fmt = DateTimeFormatter.ISO_LOCAL_DATE;
        for (int i = 0; i < timestamps.size(); i++) {
            long ts = timestamps.get(i).asLong();
            JsonNode closeNode = closes.get(i);
            if (closeNode == null || closeNode.isNull()) continue;
            double close = closeNode.asDouble();

            String date = Instant.ofEpochSecond(ts)
                                 .atZone(ZoneId.of("UTC"))
                                 .format(fmt);
            result.add(new PricePoint(date, close));
        }
        return result;
    }

    private static String uriEncode(String s) {
        return s.replace(" ", "%20");
    }

    public static class PricePoint {
        public final String date;
        public final double close;
        public PricePoint(String date, double close) {
            this.date = date;
            this.close = close;
        }
    }
}