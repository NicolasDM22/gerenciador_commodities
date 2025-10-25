public class TratadoraDeComunicadoDeDesligamento extends Thread {

    private Parceiro servidor;

    public TratadoraDeComunicadoDeDesligamento(Parceiro servidor) throws Exception {
        if (servidor == null) {
            throw new Exception("Servidor ausente");
        }
        this.servidor = servidor;
    }

    public void run() {
        while (true) {
            try {
                Comunicado comunicado = this.servidor.envie();

                if (comunicado instanceof ComunicadoDeDesligamento) {
                    System.err.println("\nO servidor foi desligado. Encerrando o cliente...");
                    System.exit(0);
                }

            } catch (Exception erro) {
                System.err.println("\nConexão com o servidor perdida. Encerrando...");
                System.exit(1);
            }
        }
    }
}