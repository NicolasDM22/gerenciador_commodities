<?php
 //By Gustavo, Otávio e Matias
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FormsController extends Controller
{
    public function salvar(Request $request)
    {
        set_time_limit(120);

        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return redirect()->route('login');

        // 1. Validação
        $data = $request->validate([
            'materia_prima' => ['required', 'string', 'max:191'],
            'volume' => ['required', 'string', 'max:30'],
            'preco_alvo' => ['required', 'string', 'max:30'],
            'cep' => ['required', 'string', 'max:9'],
        ]);

        $volume = $this->normalizeDecimal($data['volume']);
        $precoAlvo = $this->normalizeDecimal($data['preco_alvo']);
        $cep = preg_replace('/\D+/', '', $data['cep']) ?? '';

        if ($volume <= 0 || $precoAlvo <= 0) {
            return back()->withErrors('Volume e preço alvo precisam ser maiores que zero.');
        }

        // 2. Identificação da Matéria-Prima
        $nomeMateria = Str::ucfirst(Str::lower(trim($data['materia_prima'])));
        
        try {
            // Garante que existe o registro "User" (input do usuário)
            $commodityObj = $this->ensureCommodityExists($nomeMateria, $precoAlvo);
        } catch (\Exception $e) {
            return back()->withErrors('Erro ao processar commodity: ' . $e->getMessage());
        }

        // 3. Busca Localizações (para contexto do prompt)
        $locations = DB::table('locations')->select('nome', 'estado', 'regiao')->limit(15)->get();
        $locationsList = $locations->isEmpty() 
            ? "Nenhuma cadastrada (sugira as melhores globais)" 
            : $locations->map(fn($l) => "{$l->nome} ({$l->estado})")->implode('; ');

        // 4. Preparação Payload IA
        $prompt = $this->montarPrompt($commodityObj->nome, $volume, $precoAlvo, $cep, $locationsList);
        $bridgeUrl = rtrim(config('services.java_ai_bridge.url', 'http://127.0.0.1:3100/analises'), '/');
        
        $payload = [
            'texto' => $prompt,
            'contexto' => "User Target: R$ {$precoAlvo}. Locais: {$locationsList}",
            'meta' => ['commodity_id' => $commodityObj->id, 'usuario_id' => $userId],
        ];

        // 5. Conexão IA
        $structured = null;
        $aiStatus = 'completed';

        try {
            $response = Http::retry(2, 500)->timeout(60)->acceptJson()->post($bridgeUrl, $payload);
            
            if ($response->successful()) {
                $json = $response->json();
                $textoIA = $json['conteudo'] ?? $json['content'] ?? $json['text'] ?? $json['response'] ?? null;
                
                if (empty($textoIA)) {
                    $textoIA = $response->body();
                }

                $textoIA = trim((string) $textoIA);
                $structured = $this->parseStructuredResponse($textoIA);
                
                if (!$structured) {
                    Log::warning("IA Response Invalid Format: " . substr($textoIA, 0, 500));
                    throw new \Exception("JSON inválido ou incompleto retornado pela IA.");
                }
            } else {
                Log::warning("IA Bridge Error {$response->status()}: " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("IA Connection Failed: " . $e->getMessage());
        }

        // Fallback
        if (!$structured) {
            $structured = $this->generateFallbackAnalysis($nomeMateria, $precoAlvo);
            $aiStatus = 'fallback'; 
            $prompt .= " [FALLBACK ACTIVATED]";
        }

        // 6. Persistência Segura
        $now = now();
        
        try {
            DB::transaction(function () use ($userId, $commodityObj, $volume, $precoAlvo, $cep, $prompt, $structured, $now, $aiStatus) {
                // Log da Análise
                DB::table('ai_analysis_logs')->insert([
                    'user_id' => $userId,
                    'commodity_id' => $commodityObj->id,
                    'materia_prima' => $commodityObj->nome,
                    'volume_kg' => $volume,
                    'preco_alvo' => $precoAlvo,
                    'cep' => $cep,
                    'prompt' => $prompt,
                    'response' => json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => $aiStatus,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Atualiza Mercados
                if ($aiStatus === 'completed' && !empty($structured['mercados'])) {
                    $this->persistRegionalMarkets($commodityObj->nome, $structured['mercados'], $now);
                }

                // Salva/Atualiza Análise de Saída (Timeline e Indicadores)
                $this->persistCommoditySaida($commodityObj, $structured, $volume, $precoAlvo, $now);
            });
        } catch (\Throwable $e) {
            Log::error("Erro no DB Transaction: " . $e->getMessage());
            return back()->withErrors("Erro ao salvar análise no banco de dados: " . $e->getMessage());
        }

        $msg = ($aiStatus === 'fallback') 
            ? "Aviso: IA instável. Estimativa gerada com base no preço alvo." 
            : "Análise criada! O mercado foi estimado pela IA.";

        return back()->with('status', $msg);
    }

    // --- Métodos Auxiliares ---

    private function ensureCommodityExists(string $nome, float $price): object
    {
        $dbCommodity = DB::table('commodity_entrada')->where('nome', $nome)->where('source', 'User')->first();
        if ($dbCommodity) return (object) ['id' => $dbCommodity->commodity_id, 'nome' => $dbCommodity->nome];

        $maxId = DB::table('commodity_entrada')->max('commodity_id') ?? 0;
        $newId = $maxId + 1;

        $loc = DB::table('locations')->where('nome', 'LIKE', '%São Paulo%')->first();
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
        // Alterado para exigir cálculo real e penalizar distâncias internacionais
        return <<<EOT
Atue como um Especialista Sênior em Commodities. Gere uma análise de mercado real para: {$materia}.
DADOS DO USUÁRIO: 
- Volume: {$volume} kg
- Preço Alvo: R$ {$precoAlvo}
- CEP de Entrega: {$cep}

TAREFA 1: PREÇO DE MERCADO
Identifique o PREÇO MÉDIO REAL de mercado (BRL/kg) hoje.

TAREFA 2: TIMELINE (3 meses atrás, atual, 4 meses futuro)

TAREFA 3: RANKING
Escolha as 3 melhores origens da lista: [{$locationsList}].

TAREFA 4: LOGÍSTICA (CRÍTICO)
Calcule a estimativa percentual do Custo Logístico (Frete + Seguro) até o CEP {$cep}.
- Se for nacional próximo: 5% a 10%.
- Se for nacional distante: 10% a 18%.
- Se for IMPORTAÇÃO (Ex: EUA, China): Deve ser ALTO (15% a 30%) devido ao frete marítimo e taxas.
NÃO retorne 0.00. Use valores realistas.

TAREFA 5: RECOMENDAÇÃO (RESUMO EXECUTIVO)
Gere uma recomendação estratégica e concisa (MÁXIMO 3 SENTENÇAS), focando na decisão de compra (momento/preço/local) e justificando o preço alvo do usuário vs. o preço de mercado.

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
    { "nome": "Cidade A", "preco": 0.00, "moeda": "BRL", "logistica_obs": "Frete rodoviário...", "estabilidade_economica": "Alta", "risco_geral": "Baixo" },
    { "nome": "Cidade B", "preco": 0.00, "moeda": "BRL", "logistica_obs": "Frete marítimo...", "estabilidade_economica": "Média", "risco_geral": "Médio" },
    { "nome": "Cidade C", "preco": 0.00, "moeda": "BRL", "logistica_obs": "...", "estabilidade_economica": "Alta", "risco_geral": "Baixo" }
  ],
  "indicadores": { "media_brasil": 0.00, "media_global": 0.00, "risco": "Baixo", "estabilidade": "Alta" },
  "logistica": { "custo_estimado": 12.50, "melhor_rota": "Rodoviário/Marítimo", "observacoes": "Cálculo base..." },
  "recomendacao": "RESUMO EXECUTIVO CONCISO AQUI (Máximo 3 frases)."
}
EOT;
    }

    private function generateFallbackAnalysis($materia, $precoAlvo)
    {
        return [
            'timeline' => [
                'preco_3_meses_anterior' => $precoAlvo * 0.95,
                'preco_2_meses_anterior' => $precoAlvo * 0.97,
                'preco_1_mes_anterior' => $precoAlvo * 0.98,
                'preco_mes_atual' => $precoAlvo,
                'preco_1_mes_depois' => $precoAlvo * 1.02,
                'preco_2_meses_depois' => $precoAlvo * 1.03,
                'preco_3_meses_depois' => $precoAlvo * 1.05,
                'preco_4_meses_depois' => $precoAlvo * 1.06,
            ],
            'mercados' => [], 
            'indicadores' => [
                'media_brasil' => $precoAlvo,
                'media_global' => $precoAlvo * 1.1,
                'risco' => 'Estimado (Fallback)',
                'estabilidade' => 'Média'
            ],
            'logistica' => [
                'custo_estimado' => 12.5,
                'melhor_rota' => 'Rodoviário Padrão',
                'observacoes' => 'Cálculo de contingência (IA Indisponível).'
            ],
            'recomendacao' => "O sistema de IA está temporariamente indisponível. Esta é uma estimativa baseada no seu preço alvo para {$materia}."
        ];
    }

    private function persistRegionalMarkets($commodityName, $markets, $timestamp) 
    {
        if (empty($markets) || !is_array($markets)) return;

        $top3 = array_slice($markets, 0, 3);

        foreach ($top3 as $m) {
            $price = $this->toFloat($m['preco'] ?? 0);
            if ($price <= 0) continue;

            $nomeCidade = trim(explode('(', $m['nome'])[0]); 
            $location = DB::table('locations')->where('nome', 'LIKE', "%{$nomeCidade}%")->first();
            
            if (!$location) continue;

            $existingEntry = DB::table('commodity_entrada')
                ->where('nome', $commodityName)
                ->where('location_id', $location->id)
                ->first();

            $dadosComuns = [
                'price' => $price,
                'currency' => $m['moeda'] ?? 'BRL',
                'source' => 'AI_RANKING',
                'last_updated' => $timestamp,
                'updated_at' => $timestamp,
                'unidade' => 'ton'
            ];

            if ($existingEntry) {
                DB::table('commodity_entrada')
                    ->where('commodity_id', $existingEntry->commodity_id)
                    ->update($dadosComuns);
            } else {
                $maxId = DB::table('commodity_entrada')->max('commodity_id') ?? 0;
                $newId = $maxId + 1;

                DB::table('commodity_entrada')->insert(array_merge([
                    'commodity_id' => $newId, 
                    'nome' => $commodityName,
                    'location_id' => $location->id,
                    'created_at' => $timestamp
                ], $dadosComuns));
            }
        }
    }

    private function persistCommoditySaida(object $commodity, array $structured, float $volume, float $precoAlvo, Carbon $timestamp): void
    {
        $referencia = Carbon::now()->toDateString(); 
        
        $timeline = $structured['timeline'] ?? [];
        $ind = $structured['indicadores'] ?? [];
        $logistica = $structured['logistica'] ?? [];
        
        $precoMercadoIA = $this->toFloat($timeline['preco_mes_atual'] ?? 0);
        $pAtual = ($precoMercadoIA > 0) ? $precoMercadoIA : $precoAlvo;
        $pAnt1 = $this->toFloat($timeline['preco_1_mes_anterior'] ?? 0);
        $variacao = ($pAnt1 > 0) ? round((($pAtual - $pAnt1) / $pAnt1) * 100, 2) : 0;
        $avg = array_filter([$pAtual, $this->toFloat($timeline['preco_1_mes_depois']??0)]);
        $media = count($avg) ? round(array_sum($avg)/count($avg), 2) : $pAtual;

        // Recupera o custo logístico da IA. Se falhar e vier 0, usa 10.0 como fallback de segurança.
        $custoLogistico = $this->toFloat($logistica['custo_estimado'] ?? 0);
        if ($custoLogistico <= 0) {
            $custoLogistico = 10.0; 
        }

        $payload = [
            'commodity_id' => $commodity->id,
            'referencia_mes' => $referencia,
            'tipo_analise' => 'PREVISAO_MENSAL',
            'preco_3_meses_anterior' => $this->toFloat($timeline['preco_3_meses_anterior'] ?? 0),
            'preco_2_meses_anterior' => $this->toFloat($timeline['preco_2_meses_anterior'] ?? 0),
            'preco_1_mes_anterior' => $pAnt1,
            'preco_mes_atual' => $pAtual,
            'preco_1_mes_depois' => $this->toFloat($timeline['preco_1_mes_depois'] ?? 0),
            'preco_2_meses_depois' => $this->toFloat($timeline['preco_2_meses_depois'] ?? 0),
            'preco_3_meses_depois' => $this->toFloat($timeline['preco_3_meses_depois'] ?? 0),
            'preco_4_meses_depois' => $this->toFloat($timeline['preco_4_meses_depois'] ?? 0),
            'volume_compra_ton' => $volume / 1000,
            'preco_alvo' => $precoAlvo, 
            'preco_medio' => $media,
            'preco_medio_brasil' => $this->toFloat($ind['media_brasil'] ?? 0),
            'preco_medio_global' => $this->toFloat($ind['media_global'] ?? 0),
            'variacao_perc' => $variacao,
            'logistica_perc' => $custoLogistico,
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
                DB::table('commodity_saida')
                    ->where('commodity_id', $commodity->id)
                    ->where('referencia_mes', $referencia)
                    ->update($payload);
            } else {
                throw $e;
            }
        }
    }

    private function parseStructuredResponse($payload) { 
        $clean = preg_replace('/^```json\s*|\s*```$/i', '', $payload);
        $clean = preg_replace('/^```\s*|\s*```$/i', '', $clean);
        
        if (preg_match('/\{.*\}/s', $clean, $m)) {
             $decoded = json_decode($m[0], true);
             if (is_array($decoded) && isset($decoded['timeline'])) return $decoded;
        }
        return null; 
    }
    private function normalizeDecimal($v) { return $v ? (float) preg_replace('/[^0-9.]/', '', str_replace(['.',','], ['','.'], $v)) : 0; }
    private function toFloat($v) { return is_numeric($v) ? (float)$v : $this->normalizeDecimal((string)$v); }
}