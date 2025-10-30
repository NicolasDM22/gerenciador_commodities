import java.io.*;

public class Teclado {
    private static BufferedReader teclado = new BufferedReader(new InputStreamReader(System.in));

    public static String getUmString() throws Exception {
        return teclado.readLine();
    }

    public static char getUmChar() throws Exception {
        String str = teclado.readLine();

        if (str == null || str.length() == 0) {
            throw new Exception("Entrada vazia ou nula");
        }

        return str.charAt(0);
    }
}