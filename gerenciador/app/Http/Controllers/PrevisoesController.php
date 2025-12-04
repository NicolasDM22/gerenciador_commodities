<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrevisoesController extends Controller
{
    public function index(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $avatarUrl = $this->resolveAvatarUrl($user);

        $payload = $this->buildAnalysisPayload($id, $request->query('commodity_id'));
        if (!$payload) {
            return redirect()->route('home')->withErrors('Nenhuma análise disponível.');
        }

        return view('previ', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'descriptiveData' => $payload['descriptiveData'],
            'nationalForecasts' => $payload['nationalForecasts'],
            'regionalComparisons' => $payload['regionalComparisons'],
            'selectedCommodity' => $payload['commodity'],
            'aiSummary' => $payload['aiSummary'],
            'analysisId' => $payload['analysis']->id,
        ]);
    }

    public function graficos(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        $payload = $this->buildAnalysisPayload($id ?? $request->query('analysis_id'), $request->query('commodity_id'));
        if (!$payload) {
            return redirect()->route('home')->withErrors('Nenhuma análise disponível para gerar gráficos.');
        }

        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $payload['commodity']->id,
            'analysisId' => $payload['analysis']->id,
            'chartData' => $payload['regionalComparisons'],
            'timelineSeries' => $payload['nationalForecasts'],
            'locationComparison' => $payload['locationComparison'] ?? [],
            'aiSummary' => $payload['aiSummary'],
        ]);
    }

    public function conclusao(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        $payload = $this->buildAnalysisPayload($id ?? $request->query('analysis_id'), $request->query('commodity_id'));
        if (!$payload) {
            return redirect()->route('home')->withErrors('Nenhuma análise disponível.');
        }

        return view('conclusao', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $payload['commodity']->id,
            'analysisId' => $payload['analysis']->id,
            'aiSummary' => $payload['aiSummary'],
            'timelineSeries' => $payload['nationalForecasts'],
        ]);
    }

    public function exportarPdf($id)
    {
        $payload = $this->buildAnalysisPayload($id, null);
        if (!$payload) {
            abort(404);
        }

        $conclusionText = $payload['aiSummary']['recomendacao'] ?? 'Recomendação gerada automaticamente com base na última análise.';

        return view('pdfs.relatorio_completo', [
            'commodity'           => $payload['commodity'],
            'descriptiveData'     => $payload['descriptiveData'],
            'nationalForecasts'   => $payload['nationalForecasts'],
            'regionalComparisons' => $payload['regionalComparisons'],
            'conclusionText'      => $conclusionText,
            'date'                => date('d/m/Y H:i')
        ]);
    }

    private function getAuthenticatedUser(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return null;

        return DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'foto_blob', 'foto_mime', 'is_admin')
            ->where('id', $userId)
            ->first();
    }

    private function buildAnalysisPayload(?int $analysisId, ?int $commodityId): ?array
    {
        $analysis = $this->resolveAnalysis($analysisId, $commodityId);
        if (!$analysis) {
            return null;
        }

        $commodity = DB::table('commodity_entrada')->where('commodity_id', $analysis->commodity_id)->first();
        if (!$commodity) {
            return null;
        }

        $aiLog = $this->fetchAiLogForAnalysis($analysis);
        $descriptiveData = $this->buildDescriptiveData($analysis, $commodity);
        $timeline = $this->buildTimelineSeries($analysis);
        $aiSummary = $this->buildAiSummary($analysis, $commodity, $aiLog);
        $regional = $this->buildRegionalComparisons($aiSummary);
        $locationComparison = $this->buildLocationComparison($analysis->commodity_id, $commodity->nome);

        return [
            'analysis' => $analysis,
            'commodity' => $commodity,
            'descriptiveData' => $descriptiveData,
            'nationalForecasts' => $timeline,
            'regionalComparisons' => $regional,
            'locationComparison' => $locationComparison,
            'aiSummary' => $aiSummary,
        ];
    }

    private function resolveAnalysis(?int $analysisId, ?int $commodityId): ?object
    {
        if ($analysisId) {
            $analysis = DB::table('commodity_saida')
                ->where('tipo_analise', 'PREVISAO_MENSAL')
                ->where('id', $analysisId)
                ->first();
            if ($analysis) {
                return $analysis;
            }
        }

        if ($commodityId) {
            $analysis = DB::table('commodity_saida')
                ->where('tipo_analise', 'PREVISAO_MENSAL')
                ->where('commodity_id', $commodityId)
                ->orderByDesc('referencia_mes')
                ->orderByDesc('updated_at')
                ->first();
            if ($analysis) {
                return $analysis;
            }
        }

        return DB::table('commodity_saida')
            ->where('tipo_analise', 'PREVISAO_MENSAL')
            ->orderByDesc('referencia_mes')
            ->orderByDesc('updated_at')
            ->first();
    }

    private function fetchAiLogForAnalysis(object $analysis): ?object
    {
        $inicioMes = Carbon::parse($analysis->referencia_mes)->startOfMonth();
        $fimMes = Carbon::parse($analysis->referencia_mes)->endOfMonth();

        $log = DB::table('ai_analysis_logs')
            ->where('commodity_id', $analysis->commodity_id)
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->orderByDesc('created_at')
            ->first();

        if ($log) {
            return $log;
        }

        return DB::table('ai_analysis_logs')
            ->where('commodity_id', $analysis->commodity_id)
            ->orderByDesc('created_at')
            ->first();
    }

    private function buildDescriptiveData(object $analysis, object $commodity): object
    {
        return (object) [
            'materia_prima' => $commodity->nome,
            'volume_compra_ton' => $analysis->volume_compra_ton ?? 0,
            'preco_medio_global' => $analysis->preco_medio_global ?? 0,
            'preco_medio_brasil' => $analysis->preco_medio_brasil ?? 0,
            'preco_alvo' => $analysis->preco_alvo ?? 0,
            'referencia_mes' => $analysis->referencia_mes,
        ];
    }

    private function buildTimelineSeries(object $analysis)
    {
        $offsetMap = [
            -3 => 'preco_3_meses_anterior',
            -2 => 'preco_2_meses_anterior',
            -1 => 'preco_1_mes_anterior',
             0 => 'preco_mes_atual',
             1 => 'preco_1_mes_depois',
             2 => 'preco_2_meses_depois',
             3 => 'preco_3_meses_depois',
             4 => 'preco_4_meses_depois',
        ];

        $series = [];
        $ref = Carbon::parse($analysis->referencia_mes);
        $ultimaReferencia = null;

        foreach ($offsetMap as $offset => $field) {
            $valor = $analysis->{$field} ?? null;
            if ($valor === null) {
                continue;
            }

            $mes = Str::ucfirst($ref->copy()->addMonths($offset)->locale('pt_BR')->translatedFormat('M/Y'));
            $variacao = $ultimaReferencia && $ultimaReferencia > 0
                ? round((($valor - $ultimaReferencia) / $ultimaReferencia) * 100, 2)
                : 0;

            $series[] = (object) [
                'mes_ano' => $mes,
                'preco_medio' => (float) $valor,
                'variacao_perc' => $variacao,
                'offset' => $offset,
            ];

            $ultimaReferencia = $valor;
        }

        return collect($series);
    }

    private function buildAiSummary(object $analysis, object $commodity, ?object $aiLog): array
    {
        $summary = [
            'materia_prima' => $commodity->nome,
            'volume_ton' => $analysis->volume_compra_ton ?? 0,
            'indicadores' => [
                'media_brasil' => $analysis->preco_medio_brasil ?? 0,
                'media_global' => $analysis->preco_medio_global ?? 0,
                'risco' => $analysis->risco ?? '-',
                'estabilidade' => $analysis->estabilidade ?? '-',
            ],
            'logistica' => [
                'custo_estimado' => $analysis->logistica_perc,
                'melhor_rota' => null,
                'observacoes' => null,
            ],
            'mercados' => [],
            'recomendacao' => null,
            'registrada_em' => $analysis->updated_at
                ? Carbon::parse($analysis->updated_at)->format('d/m/Y H:i')
                : null,
        ];

        if ($aiLog) {
            $parsed = json_decode($aiLog->response ?? '', true);
            if (is_array($parsed)) {
                if (!empty($parsed['mercados']) && is_array($parsed['mercados'])) {
                    $summary['mercados'] = $parsed['mercados'];
                }
                if (!empty($parsed['logistica']) && is_array($parsed['logistica'])) {
                    $summary['logistica'] = array_merge($summary['logistica'], $parsed['logistica']);
                }
                if (!empty($parsed['indicadores']) && is_array($parsed['indicadores'])) {
                    $summary['indicadores'] = array_merge($summary['indicadores'], $parsed['indicadores']);
                }
                if (!empty($parsed['recomendacao'])) {
                    $summary['recomendacao'] = $parsed['recomendacao'];
                }
            }
        }

        return $summary;
    }

    private function buildRegionalComparisons(array $aiSummary)
    {
        $mercados = $aiSummary['mercados'] ?? [];
        $indicadores = $aiSummary['indicadores'] ?? [];
        $logisticaDefault = $aiSummary['logistica']['custo_estimado'] ?? null;

        return collect($mercados)->map(function ($mercado, $index) use ($indicadores, $logisticaDefault) {
            $preco = $mercado['preco'] ?? null;
            return (object) [
                'pais' => $mercado['nome'] ?? ('Mercado ' . ($index + 1)),
                'preco_medio' => $preco !== null ? (float) $preco : null,
                'logistica_perc' => $mercado['logistica_perc'] ?? $logisticaDefault,
                'risco' => $mercado['risco'] ?? ($indicadores['risco'] ?? '-'),
                'estabilidade' => $mercado['estabilidade'] ?? ($indicadores['estabilidade'] ?? '-'),
                'ranking' => $mercado['ranking'] ?? ($index + 1),
                'prazo_estimado' => $mercado['prazo_estimado_dias'] ?? null,
                'moeda' => $mercado['moeda'] ?? 'BRL',
            ];
        })->values();
    }

    private function buildLocationComparison(int $commodityId, string $commodityName): array
    {
        // Busca os dados da commodity_entrada agrupados por localização
        $entries = DB::table('commodity_entrada as ce')
            ->join('locations as l', 'ce.location_id', '=', 'l.id')
            ->where('ce.commodity_id', $commodityId)
            ->select(
                'l.id as location_id',
                'l.nome as location_name',
                'l.estado',
                'l.regiao',
                'ce.price',
                'ce.currency',
                'ce.unidade',
                'ce.last_updated'
            )
            ->orderBy('ce.price', 'asc')
            ->get();

        if ($entries->isEmpty()) {
            return [];
        }

        // Converte os dados para o formato esperado pelos gráficos
        $comparison = [];
        foreach ($entries as $entry) {
            $comparison[] = [
                'location_id' => $entry->location_id,
                'location_name' => $entry->location_name,
                'estado' => $entry->estado,
                'regiao' => $entry->regiao,
                'price' => (float) $entry->price,
                'currency' => $entry->currency,
                'unidade' => $entry->unidade,
                'last_updated' => $entry->last_updated,
                'commodity_name' => $commodityName,
            ];
        }

        return $comparison;
    }
}
