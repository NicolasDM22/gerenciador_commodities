import java.net.InetSocketAddress;
import java.util.Scanner; 

import org.java_websocket.WebSocket;
import org.java_websocket.handshake.ClientHandshake;
import org.java_websocket.server.WebSocketServer;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ObjectNode;
/**
 * ServidorWebSocket.java by Nicolas Duran Munhos & Matias Amma & João Pedro de Moura
 */
/**
 * Servidor WebSocket que expõe integração com a API Gemini (Google AI).
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

        // --- ATENCAO: SUA CHAVE FOI BLOQUEADA POR SEGURANCA ---
        // 1. Gere uma nova chave em: https://aistudio.google.com/app/apikey
        // 2. Cole a nova chave abaixo substituindo o texto entre aspas.
        String apiKey = ""; 
        
        if (apiKey == null || apiKey.trim().isEmpty() || apiKey.contains("COLOQUE_SUA_NOVA_KEY")) {
            // Tenta pegar da variável de ambiente se não foi definida no código
            String envKey = System.getenv("GOOGLE_AI_KEY");
            if (envKey != null && !envKey.isEmpty()) {
                apiKey = envKey;
            } else {
                System.err.println("ERRO CRITICO: Voce precisa definir uma API Key valida.");
                System.err.println("Edite o arquivo ServidorWebSocket.java e coloque sua chave na linha 134.");
                return;
            }
        }

        String model = System.getenv().getOrDefault("GOOGLE_AI_MODEL", "gemini-1.5-flash");
        String systemPrompt = System.getenv().getOrDefault(
            "GOOGLE_SYSTEM_PROMPT",
            "Voce e um analista especialista em commodities agricolas. Responda em PT-BR de forma objetiva."
        );

        GoogleAIClient chatClient = new GoogleAIClient(apiKey, model, systemPrompt);

        // --- DIAGNÓSTICO DE INICIALIZAÇÃO ---
        System.out.println("------------------------------------------------");
        System.out.println("DIAGNOSTICO DE INICIALIZACAO:");
        System.out.println("1. API Key detectada (tamanho): " + apiKey.length() + " chars");
        System.out.println("2. Testando conexao com Google Gemini...");
        
        try {
            // Teste rápido para validar a chave antes de subir o servidor
            String teste = chatClient.ask("Responda apenas com a palavra OK se estiver me ouvindo.", null);
            System.out.println("3. RESPOSTA DO GOOGLE: " + teste);
            System.out.println(">>> SUCESSO: A IA ESTA OPERACIONAL! <<<");
        } catch (Exception e) {
            System.err.println(">>> FALHA CRITICA NO TESTE DA IA <<<");
            System.err.println("Erro: " + e.getMessage());
            System.err.println("Causa provavel: API Key invalida, sem quota ou sem internet.");
            System.err.println("O servidor vai iniciar, mas o PHP recebera erro 500/Instavel.");
        }
        System.out.println("------------------------------------------------");
        // ------------------------------------

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
        
        Scanner scanner = new Scanner(System.in);

        for (;;) {
            System.out.println("O servidor esta ativo! Para desativa-lo,");
            System.out.println("use o comando \"desativar\"");
            System.out.print("> ");

            String comando = null;
            try {
                if (scanner.hasNextLine()) {
                    comando = scanner.nextLine();
                }
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
                scanner.close();
                System.exit(0);
            } else {
                System.err.println("Comando invalido!\n");
            }
        }
    }
}