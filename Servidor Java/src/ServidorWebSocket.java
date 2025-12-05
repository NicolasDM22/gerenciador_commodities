import java.net.InetSocketAddress;

import org.java_websocket.WebSocket;
import org.java_websocket.handshake.ClientHandshake;
import org.java_websocket.server.WebSocketServer;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ObjectNode;

/**
 * Servidor WebSocket que expA5e integraAAo com a API Gemini (Google AI).
 */
public class ServidorWebSocket extends WebSocketServer {

    private final GoogleAIClient chatClient;
    private final ObjectMapper mapper = new ObjectMapper();

    public ServidorWebSocket(int porta, GoogleAIClient chatClient) {
        super(new InetSocketAddress(porta));
        this.chatClient = chatClient;
    }

    @Override
    public void onOpen(WebSocket conexao, ClientHandshake handshake) {
        System.out.println("Novo usuario conectado: " + conexao.getRemoteSocketAddress());
    }

    @Override
    public void onClose(WebSocket conexao, int code, String reason, boolean remote) {
        System.out.println("Usuario desconectado: " + conexao.getRemoteSocketAddress());
    }

    @Override
    public void onMessage(WebSocket conexao, String mensagem) {
        System.out.println("Mensagem recebida de " + conexao.getRemoteSocketAddress() + ": " + mensagem);

        try {
            JsonNode json = mapper.readTree(mensagem);
            String tipo = json.path("tipo").asText("");

            if ("pedidoDeSair".equals(tipo)) {
                System.out.println("Cliente pediu para sair. Fechando conexao.");
                conexao.close();
                return;
            }

            if ("perguntaIA".equals(tipo)) {
                processarPerguntaIA(conexao, json);
                return;
            }

            if ("ping".equals(tipo)) {
                ObjectNode pong = mapper.createObjectNode();
                pong.put("tipo", "pong");
                pong.put("msg", "alive");
                conexao.send(pong.toString());
                return;
            }

            enviarErro(conexao, "Mensagem invalida ou tipo nao suportado.");
        } catch (Exception e) {
            e.printStackTrace();
            enviarErro(conexao, "Erro ao processar mensagem: " + e.getMessage());
        }
    }

    private void processarPerguntaIA(WebSocket conexao, JsonNode json) throws Exception {
        String prompt = json.path("texto").asText("");
        if (prompt == null || prompt.trim().isEmpty()) {
            enviarErro(conexao, "Campo 'texto' obrigatorio.");
            return;
        }

        String contexto = null;
        if (json.hasNonNull("contexto")) {
            contexto = json.get("contexto").asText();
        }

        String respostaIA = chatClient.ask(prompt, contexto);

        ObjectNode resposta = mapper.createObjectNode();
        resposta.put("tipo", "respostaIA");
        resposta.put("conteudo", respostaIA);
        resposta.put("timestamp", System.currentTimeMillis());
        conexao.send(resposta.toString());
    }

    private void enviarErro(WebSocket conexao, String mensagem) {
        try {
            ObjectNode erro = mapper.createObjectNode();
            erro.put("tipo", "erro");
            erro.put("conteudo", mensagem);
            conexao.send(erro.toString());
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    @Override
    public void onError(WebSocket conexao, Exception ex) {
        System.err.println("Erro na conexao " + (conexao != null ? conexao.getRemoteSocketAddress() : "") + ": " + ex.getMessage());
        ex.printStackTrace();
    }

    @Override
    public void onStart() {
        System.out.println("Servidor WebSocket iniciado com sucesso na porta: " + getPort());
        setConnectionLostTimeout(0);
        setConnectionLostTimeout(100);
    }

    public void enviarDesligamentoParaTodos() {
        String msgDesligamento = "{\"tipo\":\"desligamento\", \"msg\":\"O servidor esta sendo desligado.\"}";
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
            System.err.println("Porta invalida. Usando a padrao 3000.");
            porta = 3000;
        }

        String apiKey = "AIzaSyB9Hzwo3av53_eggMXcXU7sUcXymFNU38M";
        if (apiKey == null || apiKey.trim().isEmpty()) {
            System.err.println("ERRO: defina GOOGLE_AI_KEY para usar o Gemini.");
            return;
        }

        String model = System.getenv().getOrDefault("GOOGLE_AI_MODEL", "gemini-1.5-flash");
        String systemPrompt = System.getenv().getOrDefault(
            "GOOGLE_SYSTEM_PROMPT",
            "Voce e um analista especialista em commodities agricolas. Responda em PT-BR de forma objetiva."
        );

        GoogleAIClient chatClient = new GoogleAIClient(apiKey, model, systemPrompt);

        ServidorWebSocket servidor = new ServidorWebSocket(porta, chatClient);
        servidor.start();

        AiHttpBridge httpBridge = null;
        try {
            int httpPort = Integer.parseInt(System.getenv().getOrDefault("AI_BRIDGE_PORT", String.valueOf(porta + 100)));
            httpBridge = new AiHttpBridge(chatClient, httpPort);
            httpBridge.start();
            System.out.println("Ponte HTTP para PHP ouvindo na porta: " + httpPort);
        } catch (Exception ex) {
            System.err.println("Nao foi possivel iniciar a ponte HTTP: " + ex.getMessage());
        }

        System.out.println("Servidor escutando na porta: " + porta);

        for (;;) {
            System.out.println("O servidor esta ativo! Para desativa-lo,");
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
                if (httpBridge != null) {
                    httpBridge.stop();
                }
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
