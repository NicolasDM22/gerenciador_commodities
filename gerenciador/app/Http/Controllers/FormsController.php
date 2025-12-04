<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FormsController extends Controller
{
    public function salvar(Request $request)
    {
        set_time_limit(120); // Evita timeout da IA

        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return redirect()->route('login');

        // 1. Validação dos Inputs
        $data = $request->validate([
            'materia_prima' => ['required', 'string', 'max:191'],
            'volume' => ['required', 'string', 'max:30'],
            'preco_alvo' => ['required', 'string', 'max:30'], // Quanto o usuário QUER pagar
            'cep' => ['required', 'string', 'max:9'],
        ]);

        $volume = $this->normalizeDecimal($data['volume']);
        $precoAlvo = $this->normalizeDecimal($data['preco_alvo']);
        $cep = preg_replace('/\D+/', '', $data['cep']) ?? '';

        if ($volume <= 0 || $precoAlvo <= 0) {
            return back()->withErrors('Volume e preço alvo precisam ser maiores que zero.');
        }

        // 2. Identificação/Criação da Matéria-Prima (Usa o Preço Alvo apenas como referência inicial se for nova)
        $nomeMateria = Str::ucfirst(Str::lower(trim($data['materia_prima'])));
        
        try {
            $commodityObj = $this->ensureCommodityExists($nomeMateria, $precoAlvo);
        } catch (\Exception $e) {
            return back()->withErrors('Erro ao processar commodity: ' . $e->getMessage());
        }

        // 3. Busca Localizações para a IA
        $locations = DB::table('locations')->select('nome', 'estado', 'regiao')->limit(30)->get();
        $locationsList = $locations->isEmpty() 
            ? "Nenhuma cadastrada (sugira as melhores globais)" 
            : $locations->map(fn($l) => "{$l->nome} ({$l->estado})")->implode('; ');

        // 4. Preparação para a IA
        // AQUI ESTÁ A MUDANÇA: Passamos o preço alvo, mas pedimos o preço de mercado separadamente
        $prompt = $this->montarPrompt($commodityObj->nome, $volume, $precoAlvo, $cep, $locationsList);

        // 5. Conexão com a IA
        $bridgeUrl = rtrim(config('services.java_ai_bridge.url', 'http://127.0.0.1:3100/analises'), '/');
        $payload = [
            'texto' => $prompt,
            'contexto' => "User Target: R$ {$precoAlvo}. Locais: {$locationsList}",
            'meta' => ['commodity_id' => $commodityObj->id, 'usuario_id' => $userId],
        ];

        try {
            $response = Http::timeout(60)->acceptJson()->post($bridgeUrl, $payload);
        } catch (\Throwable $e) {
            return back()->withErrors('Erro na conexão IA: ' . $e->getMessage());
        }

        if (!$response->successful()) return back()->withErrors('IA Error: ' . $response->status());

        $textoIA = trim((string) $response->json('conteudo', ''));
        $structured = $this->parseStructuredResponse($textoIA);

        if (!$structured) return back()->withErrors('A IA falhou em gerar os dados estruturados.');

        // 6. Salva
        $now = now();
        
        DB::transaction(function () use ($userId, $commodityObj, $volume, $precoAlvo, $cep, $prompt, $structured, $now) {
            // Salva LOG
            DB::table('ai_analysis_logs')->insert([
                'user_id' => $userId,
                'commodity_id' => $commodityObj->id,
                'materia_prima' => $commodityObj->nome,
                'volume_kg' => $volume,
                'preco_alvo' => $precoAlvo,
                'cep' => $cep,
                'prompt' => $prompt,
                'response' => json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'completed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Salva SAÍDA
            $this->persistCommoditySaida($commodityObj, $structured, $volume, $precoAlvo, $now);
        });

        return back()->with('status', "Análise criada! O mercado foi estimado pela IA.");
    }

    private function ensureCommodityExists(string $nome, float $price): object
    {
        $dbCommodity = DB::table('commodity_entrada')->where('nome', $nome)->first();
        if ($dbCommodity) return (object) ['id' => $dbCommodity->commodity_id, 'nome' => $dbCommodity->nome];

        $maxId = DB::table('commodity_entrada')->max('commodity_id') ?? 0;
        $newId = $maxId + 1;

        $loc = DB::table('locations')->first();
        $locId = $loc ? $loc->id : DB::table('locations')->insertGetId([
            'nome' => 'São Paulo (Auto)', 'estado' => 'SP', 'regiao' => 'Sudeste', 'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('commodity_entrada')->insert([
            'commodity_id' => $newId, 'nome' => $nome, 'unidade' => 'ton',
            'location_id' => $locId, 'price' => $price, 'currency' => 'BRL', 'source' => 'User',
            'last_updated' => now(), 'created_at' => now(), 'updated_at' => now()
        ]);

        return (object) ['id' => $newId, 'nome' => $nome];
    }

    private function montarPrompt(string $materia, float $volume, float $precoAlvo, string $cep, string $locationsList): string
    {
        // CORREÇÃO AQUI: Instrução clara para separar Alvo de Mercado
        return <<<EOT
Atue como um Especialista Sênior em Commodities. Gere uma análise de mercado real para: {$materia}.
DADOS DO USUÁRIO: 
- Volume: {$volume} kg
- Preço Alvo (Desejo de pagamento): R$ {$precoAlvo}
- CEP: {$cep}

TAREFA 1: PREÇO DE MERCADO REAL vs PREÇO ALVO
Identifique o PREÇO MÉDIO REAL de mercado (BRL/kg) hoje para {$materia}.
NÃO use o preço alvo do usuário como o preço atual, a menos que coincida com a realidade.
Se o açúcar custa R$ 5,00 e o usuário quer pagar R$ 50,00, a timeline deve mostrar R$ 5,00 (realidade).

TAREFA 2: TIMELINE
Crie estimativa dos últimos 3 meses e próximos 4 meses baseada no MERCADO REAL.

TAREFA 3: RANKING
Escolha as 3 melhores origens da lista: [{$locationsList}]

Retorne APENAS JSON válido:
{
  "timeline": {
    "preco_3_meses_anterior": 0.00,
    "preco_2_meses_anterior": 0.00,
    "preco_1_mes_anterior": 0.00,
    "preco_mes_atual": 0.00, 
    "preco_1_mes_depois": 0.00,
    "preco_2_meses_depois": 0.00,
    "preco_3_meses_depois": 0.00,
    "preco_4_meses_depois": 0.00
  },
  "mercados": [
    { 
      "nome": "Nome da Lista", 
      "preco": 0.00, 
      "moeda": "BRL", 
      "logistica_obs": "Resumo",
      "estabilidade_economica": "Alta",
      "estabilidade_climatica": "Alta",
      "risco_geral": "Baixo"
    }
  ],
  "indicadores": {
    "media_brasil": 0.00,
    "media_global": 0.00,
    "risco": "Baixo",
    "estabilidade": "Alta"
  },
  "logistica": {
    "custo_estimado": 0.00, 
    "melhor_rota": "Rota...", 
    "observacoes": "Obs..." 
  },
  "recomendacao": "Explique a diferença entre o preço alvo do usuário e a realidade do mercado."
}
EOT;
    }

    private function persistCommoditySaida(object $commodity, array $structured, float $volume, float $precoAlvo, Carbon $timestamp): void
    {
        $referencia = Carbon::now()->toDateString(); 
        
        $timeline = $structured['timeline'] ?? [];
        $ind = $structured['indicadores'] ?? [];
        $logistica = $structured['logistica'] ?? [];
        
        // CORREÇÃO: Pega o preço da IA. Se vier 0 ou nulo, aí sim usa o Alvo como fallback desesperado
        $precoMercadoIA = $this->toFloat($timeline['preco_mes_atual'] ?? 0);
        $pAtual = ($precoMercadoIA > 0) ? $precoMercadoIA : $precoAlvo;

        $pAnt1 = $this->toFloat($timeline['preco_1_mes_anterior'] ?? 0);
        $variacao = ($pAnt1 > 0) ? round((($pAtual - $pAnt1) / $pAnt1) * 100, 2) : 0;

        $avg = array_filter([$pAtual, $this->toFloat($timeline['preco_1_mes_depois']??0)]);
        $media = count($avg) ? round(array_sum($avg)/count($avg), 2) : $pAtual;

        $payload = [
            'commodity_id' => $commodity->id,
            'referencia_mes' => $referencia,
            'tipo_analise' => 'PREVISAO_MENSAL',
            'preco_3_meses_anterior' => $this->toFloat($timeline['preco_3_meses_anterior'] ?? 0),
            'preco_2_meses_anterior' => $this->toFloat($timeline['preco_2_meses_anterior'] ?? 0),
            'preco_1_mes_anterior' => $pAnt1,
            'preco_mes_atual' => $pAtual, // Aqui entra o preço real (ex: 5.00)
            'preco_1_mes_depois' => $this->toFloat($timeline['preco_1_mes_depois'] ?? 0),
            'preco_2_meses_depois' => $this->toFloat($timeline['preco_2_meses_depois'] ?? 0),
            'preco_3_meses_depois' => $this->toFloat($timeline['preco_3_meses_depois'] ?? 0),
            'preco_4_meses_depois' => $this->toFloat($timeline['preco_4_meses_depois'] ?? 0),
            'volume_compra_ton' => $volume / 1000,
            'preco_alvo' => $precoAlvo, // Aqui fica o alvo (ex: 50.00) para calcular o GAP no front
            'preco_medio' => $media,
            'preco_medio_brasil' => $this->toFloat($ind['media_brasil'] ?? 0),
            'preco_medio_global' => $this->toFloat($ind['media_global'] ?? 0),
            'variacao_perc' => $variacao,
            'logistica_perc' => $this->toFloat($logistica['custo_estimado'] ?? 0),
            'risco' => $ind['risco'] ?? 'N/A',
            'estabilidade' => $ind['estabilidade'] ?? 'N/A',
            'ranking' => 1,
            'updated_at' => $timestamp,
            'created_at' => $timestamp
        ];

        try {
            DB::table('commodity_saida')->insert($payload);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new \Exception("Erro: O banco de dados está bloqueando análises repetidas no mesmo dia. Execute o SQL para remover o índice UNIQUE.");
            }
            throw $e;
        }
    }

    private function parseStructuredResponse($payload) { 
        if (preg_match('/\{.*\}/s', $payload, $m)) return json_decode($m[0], true);
        return null; 
    }
    private function normalizeDecimal($v) { return $v ? (float) preg_replace('/[^0-9.]/', '', str_replace(['.',','], ['','.'], $v)) : 0; }
    private function toFloat($v) { return is_numeric($v) ? (float)$v : $this->normalizeDecimal((string)$v); }
}