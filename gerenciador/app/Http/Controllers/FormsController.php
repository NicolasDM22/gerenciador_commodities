<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormsController extends Controller
{
    private array $materiaPrimaMap = [
        'soja' => ['nome' => 'Soja', 'id' => 1],
        'acucar' => ['nome' => 'Acucar', 'id' => 3],
        'trigo' => ['nome' => 'Trigo', 'id' => 2],
        'cacau' => ['nome' => 'Cacau', 'id' => 4],
    ];

    public function salvar(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'materia_prima' => ['required', Rule::in(array_keys($this->materiaPrimaMap))],
            'volume' => ['required', 'string', 'max:30'],
            'preco_alvo' => ['required', 'string', 'max:30'],
            'cep' => ['required', 'string', 'max:9'],
        ]);

        $volume = $this->normalizeDecimal($data['volume']);
        $preco = $this->normalizeDecimal($data['preco_alvo']);
        $cep = preg_replace('/\D+/', '', $data['cep']) ?? '';

        if ($volume <= 0 || $preco <= 0) {
            return back()->withErrors('Volume e preço alvo precisam ser maiores que zero.');
        }

        $commodity = $this->resolveCommodity($data['materia_prima']);
        if (!$commodity) {
            return back()->withErrors('Matéria-prima não encontrada no banco de dados.');
        }

        $contexto = $this->montarContexto($commodity->id);
        $prompt = $this->montarPrompt($commodity->nome ?? Str::ucfirst($data['materia_prima']), $volume, $preco, $cep, $contexto);

        $bridgeUrl = rtrim(config('services.java_ai_bridge.url', 'http://127.0.0.1:3100/analises'), '/');
        $payload = [
            'texto' => $prompt,
            'contexto' => $contexto,
            'meta' => [
                'commodity_id' => $commodity->id,
                'materia_prima' => $commodity->nome,
                'usuario_id' => $userId,
            ],
        ];

        try {
            $response = Http::timeout(45)->acceptJson()->post($bridgeUrl, $payload);
        } catch (\Throwable $e) {
            return back()->withErrors('Não foi possível se conectar ao servidor Java: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            return back()->withErrors('Servidor Java retornou erro ' . $response->status() . ': ' . $response->body());
        }

        $textoIA = trim((string) $response->json('conteudo', ''));
        if ($textoIA === '') {
            return back()->withErrors('Servidor Java respondeu sem conteúdo de análise.');
        }

        $structured = $this->parseStructuredResponse($textoIA);
        if (!$structured) {
            return back()->withErrors('A IA retornou dados em formato inesperado. Tente novamente.');
        }

        $now = now();
        DB::transaction(function () use ($userId, $commodity, $volume, $preco, $cep, $prompt, $contexto, $structured, $now) {
            DB::table('ai_analysis_logs')->insert([
                'user_id' => $userId,
                'commodity_id' => $commodity->id,
                'materia_prima' => $commodity->nome,
                'volume_kg' => $volume,
                'preco_alvo' => $preco,
                'cep' => $cep,
                'prompt' => $prompt,
                'context_snapshot' => $contexto,
                'response' => json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'completed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->persistCommoditySaida($commodity, $structured, $volume, $preco, $now);
        });

        return back()
            ->with('status', 'Nova análise gerada com sucesso.')
            ->with('analysis_structured', $structured);
    }

    private function resolveCommodity(string $slug): ?object
    {
        $slug = Str::lower($slug);
        $info = $this->materiaPrimaMap[$slug] ?? ['nome' => Str::title($slug), 'id' => null];
        $nomeBusca = $info['nome'];

        $commodity = DB::table('commodities')
            ->whereRaw('LOWER(nome) = ?', [Str::lower($nomeBusca)])
            ->orderByDesc('updated_at')
            ->first();

        if ($commodity) {
            return $commodity;
        }

        if (!empty($info['id'])) {
            $existeSaida = DB::table('commodity_saida')
                ->where('commodity_id', $info['id'])
                ->exists();

            if ($existeSaida) {
                return (object) [
                    'id' => $info['id'],
                    'nome' => $nomeBusca,
                ];
            }
        }

        $fallback = DB::table('commodity_saida')
            ->select('commodity_id')
            ->when($info['id'], fn ($query) => $query->where('commodity_id', $info['id']))
            ->orderByDesc('referencia_mes')
            ->first();

        if ($fallback) {
            return (object) [
                'id' => $fallback->commodity_id,
                'nome' => $nomeBusca,
            ];
        }

        return null;
    }

    private function montarContexto(int $commodityId): string
    {
        $partes = [];

        $ultimaSaida = DB::table('commodity_saida')
            ->where('commodity_id', $commodityId)
            ->orderByDesc('referencia_mes')
            ->first();

        if ($ultimaSaida) {
            $ref = $ultimaSaida->referencia_mes ? Carbon::parse($ultimaSaida->referencia_mes)->format('m/Y') : 'sem referência';
            $partes[] = sprintf(
                'Último registro (%s): preço atual R$ %.2f, média Brasil R$ %.2f e variação %.2f%%.',
                $ref,
                $ultimaSaida->preco_mes_atual ?? 0,
                $ultimaSaida->preco_medio_brasil ?? 0,
                $ultimaSaida->variacao_perc ?? 0
            );
        }

        $ultimaIA = DB::table('ai_analysis_logs')
            ->where('commodity_id', $commodityId)
            ->whereNotNull('response')
            ->orderByDesc('created_at')
            ->first();

        if ($ultimaIA) {
            $partes[] = 'Conclusão anterior: ' . Str::limit($ultimaIA->response, 400);
        }

        return implode("\n\n", array_filter($partes));
    }

    private function montarPrompt(string $materia, float $volume, float $preco, string $cep, string $contexto): string
    {
        $prompt = "Considere os dados do cliente abaixo e gere uma análise tática de compra para {$materia}.";
        $prompt .= "\n- Volume solicitado: " . number_format($volume, 2, ',', '.') . " kg";
        $prompt .= "\n- Preço alvo: R$ " . number_format($preco, 2, ',', '.');
        $prompt .= "\n- CEP para entrega: " . ($cep ?: 'não informado');

        if (!empty($contexto)) {
            $prompt .= "\n\nHistórico conhecido:\n" . $contexto;
        }

        $prompt .= <<<EOT

Retorne APENAS JSON seguindo este formato (não inclua texto fora do JSON):
{
  "mercados": [
    {
      "nome": "região ou país",
      "preco": 0,
      "moeda": "BRL",
      "fonte": "ex: Cepea",
      "prazo_estimado_dias": 0,
      "justificativa": "texto objetivo de 1 frase"
    }
  ],
  "indicadores": {
    "media_brasil": 0,
    "media_global": 0,
    "risco": "Baixo|Medio|Alto",
    "estabilidade": "Alta|Media|Baixa"
  },
  "logistica": {
    "melhor_rota": "rota/resumida",
    "custo_estimado": 0,
    "observacoes": "texto curto"
  },
  "recomendacao": "máximo 3 frases com a decisão final"
}
EOT;

        return $prompt;
    }

    private function normalizeDecimal(?string $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $clean = str_replace(['R$', 'r$', ' '], '', $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
        $clean = preg_replace('/[^0-9.\-]/', '', $clean);

        return (float) $clean;
    }

    private function parseStructuredResponse(string $payload): ?array
    {
        $candidate = $this->extractJson($payload);
        if (!$candidate) {
            return null;
        }

        $json = json_decode($candidate, true);
        if (!is_array($json) || empty($json['mercados']) || !isset($json['indicadores'])) {
            return null;
        }

        $json['mercados'] = array_values(array_filter(array_map(function ($mercado) {
            if (!is_array($mercado)) {
                return null;
            }
            return [
                'nome' => $mercado['nome'] ?? 'Sem nome',
                'preco' => (float) ($mercado['preco'] ?? 0),
                'moeda' => $mercado['moeda'] ?? 'BRL',
                'fonte' => $mercado['fonte'] ?? 'Desconhecida',
                'prazo_estimado_dias' => (int) ($mercado['prazo_estimado_dias'] ?? 0),
                'justificativa' => $mercado['justificativa'] ?? '',
            ];
        }, $json['mercados'])));

        if (empty($json['mercados'])) {
            return null;
        }

        return $json;
    }

    private function extractJson(string $payload): ?string
    {
        $payload = trim($payload);
        if (str_starts_with($payload, '{') && str_ends_with($payload, '}')) {
            return $payload;
        }

        if (preg_match('/\{.*\}/s', $payload, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function persistCommoditySaida(object $commodity, array $structured, float $volumeKg, float $precoAlvo, Carbon $timestamp): void
    {
        $mercadoPrincipal = $structured['mercados'][0] ?? [];
        $indicadores = $structured['indicadores'] ?? [];

        $referencia = Carbon::now()->startOfMonth()->toDateString();

        DB::table('commodity_saida')->updateOrInsert(
            [
                'commodity_id' => $commodity->id,
                'referencia_mes' => $referencia,
                'tipo_analise' => 'PREVISAO_MENSAL',
            ],
            [
                'volume_compra_ton' => max($volumeKg / 1000, 0),
                'preco_alvo' => $precoAlvo,
                'preco_mes_atual' => $this->toFloat($mercadoPrincipal['preco'] ?? null),
                'preco_medio' => $this->toFloat($mercadoPrincipal['preco'] ?? null),
                'preco_medio_brasil' => $this->toFloat($indicadores['media_brasil'] ?? null),
                'preco_medio_global' => $this->toFloat($indicadores['media_global'] ?? null),
                'risco' => $indicadores['risco'] ?? null,
                'estabilidade' => $indicadores['estabilidade'] ?? null,
                'variacao_perc' => null,
                'logistica_perc' => null,
                'ranking' => 1,
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ]
        );
    }

    private function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $clean = str_replace(['R$', 'r$', ' '], '', $value);
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
            $clean = preg_replace('/[^0-9.\-]/', '', $clean);
            return $clean === '' ? null : (float) $clean;
        }

        return null;
    }
}
