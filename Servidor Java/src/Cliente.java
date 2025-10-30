import java.net.*;
import java.io.*;

public class Cliente {
    public static final String HOST_PADRAO = "localhost";
    public static final int PORTA_PADRAO = 3000;

    public static void main(String[] args) {
        if (args.length > 2) {
            System.err.println("Uso esperado: java Cliente [HOST [PORTA]]\n");
            return;
        }

        Socket conexao = null;
        try {
            String host = Cliente.HOST_PADRAO;
            int porta = Cliente.PORTA_PADRAO;

            if (args.length > 0) {
                host = args[0];
            }
            if (args.length == 2) {
                porta = Integer.parseInt(args[1]);
            }

            conexao = new Socket(host, porta);
        } catch (Exception erro) {
            System.err.println("Erro ao conectar ao servidor. Verifique o host e a porta.");
            return;
        }

        ObjectOutputStream transmissor = null;
        try {
            transmissor = new ObjectOutputStream(conexao.getOutputStream());
        } catch (Exception erro) {
            System.err.println("Erro ao criar stream de saída.");
            try { conexao.close(); } catch (Exception e) {}
            return;
        }

        ObjectInputStream receptor = null;
        try {
            receptor = new ObjectInputStream(conexao.getInputStream());
        } catch (Exception erro) {
            System.err.println("Erro ao criar stream de entrada.");
            try { transmissor.close(); conexao.close(); } catch (Exception e) {}
            return;
        }

        Parceiro servidor = null;
        try {
            servidor = new Parceiro(conexao, receptor, transmissor);
        } catch (Exception erro) {
            System.err.println("Erro ao criar o parceiro de comunicação.");
            try { receptor.close(); transmissor.close(); conexao.close(); } catch (Exception e) {}
            return;
        }

        TratadoraDeComunicadoDeDesligamento tratadoraDeComunicadoDeDesligamento = null;
        try {
            tratadoraDeComunicadoDeDesligamento = new TratadoraDeComunicadoDeDesligamento(servidor);
        } catch (Exception erro) {
            System.err.println("Erro ao criar a tratadora de desligamento.");
            try { servidor.adeus(); } catch (Exception e) {}
            return;
        }

        tratadoraDeComunicadoDeDesligamento.start();

        char opcao = ' ';
        do {
            System.out.print("Fechar servidor? [S]: ");
            try {
                opcao = Character.toUpperCase(Teclado.getUmChar());
            } catch (Exception erro) {
                System.err.println("Opção inválida!\n");
                continue;
            }

            if (opcao != 'S') {
                System.err.println("Opção inválida!\n");
            }

        } while (opcao != 'S');

        try {
            servidor.receba(new PedidoDeSair());
            servidor.adeus();
            System.out.println("Solicitação enviada. Desconectando...");
        } catch (Exception erro) {
            System.err.println("Erro ao enviar pedido de saída ou ao desconectar: " + erro.getMessage());
        }
    }
}