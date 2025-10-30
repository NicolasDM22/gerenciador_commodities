import java.io.*;
import java.net.*;
import java.util.*;
import java.util.concurrent.Semaphore;

public class Parceiro {
    private Socket conexao;
    private ObjectInputStream receptor;
    private ObjectOutputStream transmissor;

    private Comunicado proximoComunicado = null;

    private Semaphore mutEx = new Semaphore(1, true);

    public Parceiro(Socket conexao, ObjectInputStream receptor, ObjectOutputStream transmissor) throws Exception {
        if (conexao == null)
            throw new Exception("Conexão Ausente");
        if (receptor == null)
            throw new Exception("Receptor Ausente");
        if (transmissor == null)
            throw new Exception("Transmissor Ausente");

        this.conexao = conexao;
        this.receptor = receptor;
        this.transmissor = transmissor;
    }

    // Receba transmite ao servidor
    public void receba(Comunicado x) throws Exception{
        try{
             this.transmissor.writeObject(x);
             this.transmissor.flush();
        } catch (IOException erro){
            throw new Exception ("Erro de transmissão");
        }
    }

    // Espie permite ver o que foi mandado sem consumir a informação
    public Comunicado espie() throws Exception{
        try{
            this.mutEx.acquireUninterruptibly();
            if (this.proximoComunicado==null) this.proximoComunicado = (Comunicado) this.receptor.readObject();
            this.mutEx.release();
            return this.proximoComunicado;
        } catch (Exception erro){
            throw new Exception("Erro de recepção");
        }
    }

    // Envie permite ver o que foi mandado e consome a informação
    public Comunicado envie() throws Exception{
        try{
            if (this.proximoComunicado==null) this.proximoComunicado = (Comunicado) this.receptor.readObject();
            Comunicado ret = this.proximoComunicado;
            this.proximoComunicado = null;
            return ret;
        } catch (Exception erro){
            throw new Exception("Erro de recepção");
        }
    }

    public void adeus() throws Exception{
        try{
            this.transmissor.close();
            this.receptor.close();
            this.conexao.close();
        } catch (Exception erro){
            throw new Exception("Erro de desconexão");
        }
    }
}
