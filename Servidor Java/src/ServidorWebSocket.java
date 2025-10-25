import java.net.InetSocketAddress;
import java.util.Collection;
import org.java_websocket.WebSocket;
import org.java_websocket.handshake.ClientHandshake;
import org.java_websocket.server.WebSocketServer;

/**
 * Esta classe ÚNICA substitui Servidor, AceitadoraDeConexao e SupervisoraDeConexao.
 * Ela usa a biblioteca Java-WebSocket para falar com navegadores.
 */
public class ServidorWebSocket extends WebSocketServer {

    // A biblioteca já mantém uma lista de conexões.
    // Não precisamos mais do nosso 'ArrayList<Parceiro> usuarios'.

    public ServidorWebSocket(int porta) {
        super(new InetSocketAddress(porta));
    }

    /**
     * Chamado quando um novo cliente (navegador) se conecta.
     * Substitui o 'AceitadoraDeConexao.run()' e o início de 'SupervisoraDeConexao.run()'.
     */
    @Override
    public void onOpen(WebSocket conexao, ClientHandshake handshake) {
        System.out.println("Novo usuário do site conectado: " + conexao.getRemoteSocketAddress());
        // Se quisermos, podemos mandar uma mensagem de boas-vindas:
        // conexao.send("{\"tipo\":\"bemvindo\", \"msg\":\"Conectado ao servidor Java!\"}");
    }

    /**
     * Chamado quando um cliente (navegador) se desconecta.
     * Substitui a lógica de 'PedidoDeSair' na 'SupervisoraDeConexao'.
     */
    @Override
    public void onClose(WebSocket conexao, int code, String reason, boolean remote) {
        System.out.println("Usuário do site desconectado: " + conexao.getRemoteSocketAddress());
    }

    /**
     * Chamado quando o servidor recebe uma mensagem (JSON) do cliente.
     * Substitui o loop 'usuario.envie()' na 'SupervisoraDeConexao'.
     */
    @Override
    public void onMessage(WebSocket conexao, String mensagem) {
        System.out.println("Mensagem recebida de " + conexao.getRemoteSocketAddress() + ": " + mensagem);

        // Aqui, analisamos o JSON. Para este projeto, um 'contains' simples resolve.
        // Em um projeto maior, usaríamos uma biblioteca (Gson/Jackson) para converter JSON
        // em objetos.

        if (mensagem.contains("\"tipo\":\"pedidoDeSair\"")) {
            // O cliente pediu para sair
            System.out.println("Cliente pediu para sair. Fechando conexão.");
            conexao.close();
        }

        // else if (mensagem.contains("\"tipo\":\"outraCoisa\"")) {
        //     // Faria outra lógica...
        // }
    }

    @Override
    public void onError(WebSocket conexao, Exception ex) {
        System.err.println("Erro na conexão " + (conexao != null ? conexao.getRemoteSocketAddress() : "") + ": " + ex.getMessage());
        ex.printStackTrace();
    }

    @Override
    public void onStart() {
        System.out.println("Servidor WebSocket iniciado com sucesso na porta: " + getPort());
        setConnectionLostTimeout(0); // Para manter conexões abertas
        setConnectionLostTimeout(100);
    }

    /**
     * Método para enviar o 'ComunicadoDeDesligamento' para TODOS os clientes.
     */
    public void enviarDesligamentoParaTodos() {
        // Esta é a nossa "linguagem" JSON que o navegador vai entender
        String msgDesligamento = "{\"tipo\":\"desligamento\", \"msg\":\"O servidor está sendo desligado.\"}";

        // broadcast() é um método da biblioteca que envia para todos os conectados.
        broadcast(msgDesligamento);
    }


    // --- O Método Main (adaptado do seu Servidor.java original) ---

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

        // 1. Inicia o servidor WebSocket (em uma nova thread interna)
        ServidorWebSocket servidor = new ServidorWebSocket(porta);
        servidor.start();

        System.out.println("Servidor escutando na porta: " + porta);

        // 2. Loop principal para ler o comando "desativar" do console
        // (Lógica copiada do seu Servidor.java original)
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

                // 1. Envia o 'ComunicadoDeDesligamento' (em JSON) para todos
                servidor.enviarDesligamentoParaTodos();

                // 2. Para o servidor WebSocket
                try {
                    servidor.stop(1000); // 1 segundo de timeout para fechar
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