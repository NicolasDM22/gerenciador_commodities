import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

public class OpenMeteoClient {

    private static final String BASE_URL =
        "https://api.open-meteo.com/v1/forecast?current_weather=true";

    public String getClima(double latitude, double longitude) throws Exception {
        String url = BASE_URL + "&latitude=" + latitude + "&longitude=" + longitude;

        HttpURLConnection connection = (HttpURLConnection) new URL(url).openConnection();
        connection.setRequestMethod("GET");
        connection.setConnectTimeout(5000);
        connection.setReadTimeout(5000);

        int status = connection.getResponseCode();
        if (status != 200) {
            throw new RuntimeException("Erro na API OpenMeteo: HTTP " + status);
        }

        BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
        StringBuilder resposta = new StringBuilder();
        String linha;

        while ((linha = reader.readLine()) != null) {
            resposta.append(linha);
        }
        reader.close();

        ObjectMapper mapper = new ObjectMapper();
        JsonNode json = mapper.readTree(resposta.toString());

        JsonNode climaAtual = json.get("current_weather");
        if (climaAtual == null) {
            return "Não foi possível obter o clima.";
        }

        double temperatura = climaAtual.get("temperature").asDouble();
        double vento = climaAtual.get("windspeed").asDouble();

        return "Temperatura: " + temperatura + "°C, Vento: " + vento + " km/h";
    }
}
