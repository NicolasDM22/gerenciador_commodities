import java.net.InetSocketAddress;
import java.util.Collection;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import org.java_websocket.WebSocket;
import org.java_websocket.handshake.ClientHandshake;
import org.java_websocket.server.WebSocketServer;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import java.util.List;
import java.util.ArrayList;

public class ServidorWebSocket extends WebSocketServer {

    private GoogleAIClient openai;
    private OpenMeteoClient meteo = new OpenMeteoClient();
    private YahooFinanceClient yahoo = new YahooFinanceClient();
    
    // Cache para evitar múltiplas requisições ao Yahoo
    private final Map<String, List<YahooFinanceClient.PricePoint>> cache = new ConcurrentHashMap<>();
    private final Map<String, Long> cacheTimestamp = new ConcurrentHashMap<>();
    private final long TTL_MILLIS = 60_000; // Cache válido por 1 minuto

    public ServidorWebSocket(int porta) {
        super(new InetSocketAddress(porta));

        String apiKey = "";
        if (apiKey == null || apiKey.isEmpty()) {
            System.err.println("ERRO: variável de ambiente GOOGLE_AI_KEY não definida!");
        } else {
            this.openai = new GoogleAIClient(apiKey);
        }
    }

    @Override
    public void onOpen(WebSocket conexao, ClientHandshake handshake) {
        System.out.println("Novo usuário do site conectado: " + conexao.getRemoteSocketAddress());
    }

    @Override
    public void onClose(WebSocket conexao, int code, String reason, boolean remote) {
        System.out.println("Usuário do site desconectado: " + conexao.getRemoteSocketAddress());
    }

    @Override
    public void onMessage(WebSocket conexao, String mensagem) {
        System.out.println("Mensagem recebida de " + conexao.getRemoteSocketAddress() + ": " + mensagem);

        try {
            ObjectMapper mapper = new ObjectMapper();
            JsonNode json = mapper.readTree(mensagem);

            // --- PEDIDO DE SAIR ---
            if (json.has("tipo") && json.get("tipo").asText().equals("pedidoDeSair")) {
                conexao.close();
                return;
            }

            // --- CONSULTA DE CLIMA ---
            if (json.has("tipo") && json.get("tipo").asText().equals("clima")) {
                if (!json.has("lat") || !json.has("lon")) {
                    conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Esperado lat e lon.\"}");
                    return;
                }

                double lat = json.get("lat").asDouble();
                double lon = json.get("lon").asDouble();

                try {
                    String clima = meteo.getClima(lat, lon);
                    String respostaJson =
                        "{\"tipo\":\"clima\",\"conteudo\":\"" + clima.replace("\"", "\\\"") + "\"}";

                    conexao.send(respostaJson);
                } catch (Exception e) {
                    conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Erro ao consultar clima: " + e.getMessage() + "\"}");
                }
                return;
            }

            // --- CONSULTA À IA ---
            if (json.has("tipo") && json.get("tipo").asText().equals("perguntaIA") && json.has("texto")) {
                String promptDoUsuario = json.get("texto").asText();

                if (openai == null) {
                    conexao.send("Servidor sem API Key configurada.");
                    return;
                }

                String respostaIA = openai.ask(promptDoUsuario);
                String respostaJson = "{\"tipo\":\"respostaIA\", \"conteudo\":\"" + respostaIA.replace("\"", "\\\"") + "\"}";
                conexao.send(respostaJson);
                return;
            }

            // --- CONSULTA YAHOO COM CACHE ---
            if (json.has("tipo") && json.get("tipo").asText().equals("commodityYahoo")) {
                String symbol = json.has("symbol") ? json.get("symbol").asText() : "GC=F";
                String interval = json.has("interval") ? json.get("interval").asText() : "1d";
                String range = json.has("range") ? json.get("range").asText() : "1y";

                try {
                    List<YahooFinanceClient.PricePoint> lista = getPrecosCached(symbol, interval, range);
                    String resposta = new ObjectMapper().writeValueAsString(lista);

                    conexao.send("{\"tipo\":\"commodityData\",\"symbol\":\"" + symbol + "\",\"conteudo\":" + resposta + "}");
                } catch (RuntimeException e) {
                    String msgErro = e.getMessage();
                    if (msgErro != null && msgErro.contains("HTTP 429")) {
                        conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Limite de requisições do Yahoo atingido. Aguarde alguns segundos e tente novamente.\"}");
                    } else {
                        conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Erro Yahoo: " + msgErro.replace("\"","\\\"") + "\"}");
                    }
                } catch (Exception e) {
                    conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Erro ao processar dados: " + e.getMessage().replace("\"","\\\"") + "\"}");
                }
                return;
            }

            // --- TIPO INVÁLIDO ---
            conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Tipo de mensagem desconhecido.\"}");

        } catch (Exception e) {
            e.printStackTrace();
            conexao.send("{\"tipo\":\"erro\",\"conteudo\":\"Erro processando mensagem: " + e.getMessage() + "\"}");
        }
    }

    // Método com cache para evitar múltiplas chamadas ao Yahoo
    private List<YahooFinanceClient.PricePoint> getPrecosCached(String symbol, String interval, String range) throws Exception {
        String key = symbol + "|" + interval + "|" + range;
        long now = System.currentTimeMillis();

        // Verifica se tem no cache e se ainda é válido
        if (cache.containsKey(key)) {
            long ts = cacheTimestamp.getOrDefault(key, 0L);
            if (now - ts < TTL_MILLIS) {
                System.out.println("Retornando dados do cache para: " + key);
                return cache.get(key);
            }
        }

        // Se não tem no cache ou expirou, busca do Yahoo
        System.out.println("Buscando dados do Yahoo para: " + key);
        JsonNode raw = yahoo.fetchChart(symbol, interval, range);
        List<YahooFinanceClient.PricePoint> lista = yahoo.parseClosePrices(raw);
        
        // Armazena no cache
        cache.put(key, lista);
        cacheTimestamp.put(key, now);
        
        return lista;
    }

    @Override
    public void onError(WebSocket conexao, Exception ex) {
        System.err.println("Erro na conexão " + (conexao != null ? conexao.getRemoteSocketAddress() : "") + ": " + ex.getMessage());
        ex.printStackTrace();
    }

    @Override
    public void onStart() {
        System.out.println("Servidor WebSocket iniciado com sucesso na porta: " + getPort());
        setConnectionLostTimeout(100);
    }

    public void enviarDesligamentoParaTodos() {
        String msgDesligamento = "{\"tipo\":\"desligamento\", \"msg\":\"O servidor está sendo desligado.\"}";
        broadcast(msgDesligamento);
    }

    public static void main(String[] args) {
        if (args.length > 1) {
            System.err.println("Uso esperado: java ServidorWebSocket [PORTA]\n");
            return;
        }

        int porta;
        try {
            porta = (args.length == 1) ? Integer.parseInt(args[0]) : 3000;
        } catch (NumberFormatException e) {
            System.err.println("Porta inválida. Usando a padrão 3000.");
            porta = 3000;
        }

        ServidorWebSocket servidor = new ServidorWebSocket(porta);
        servidor.start();

        System.out.println("Servidor escutando na porta: " + porta);

        for (;;) {
            System.out.println("O servidor está ativo! Para desativá-lo,");
            System.out.println("use o comando \"desativar\"");
            System.out.print("> ");

            String comando = null;
            try {
                comando = Teclado.getUmString();
            } catch (Exception erro) {
                continue;
            }

            if (comando == null) continue;

            if (comando.toLowerCase().equals("desativar")) {
                System.out.println("Comando 'desativar' recebido. Encerrando...");
                servidor.enviarDesligamentoParaTodos();

                try {
                    servidor.stop(1000);
                } catch (InterruptedException e) {
                    System.err.println("Erro ao parar o servidor: " + e.getMessage());
                }

                System.out.println("O servidor foi desativado");
                System.exit(0);
            } else {
                System.err.println("Comando invalido!\n");
            }
        }
    }
}
