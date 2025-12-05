<?php
/**
 * PrevisoesController.php by Nicolas Duran Munhos & Matias Amma
 */
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrevisoesController extends Controller
{
    private $commodityMap = [1 => 'Soja', 2 => 'Milho', 3 => 'Açúcar', 4 => 'Cacau'];

    public function index(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        
        $avatarUrl = $this->resolveAvatarUrl($user);

        $payload = $this->buildAnalysisPayload($id, $request->query('commodity_id'));
        if (!$payload) return redirect()->route('home')->withErrors('Nenhuma análise disponível.');

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
        if (!$payload) return redirect()->route('home');

        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $payload['analysis']->commodity_id,
            'nomeCommodity' => $payload['commodity']->nome ?? 'Commodity',
            'analysisId' => $payload['analysis']->id,
            'analise' => $payload['analysis'], 
            'chartData' => $payload['regionalComparisons'],
            'timelineSeries' => $payload['nationalForecasts'],
            'locationComparison' => [],
            'aiSummary' => $payload['aiSummary'],
        ]);
    }

    public function conclusao(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        $payload = $this->buildAnalysisPayload($id ?? $request->query('analysis_id'), $request->query('commodity_id'));
        if (!$payload) return redirect()->route('home');

        return view('conclusao', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $payload['analysis']->commodity_id,
            'analysisId' => $payload['analysis']->id,
            'aiSummary' => $payload['aiSummary'],
            'timelineSeries' => $payload['nationalForecasts'],
        ]);
    }

    public function exportarPdf($id)
    {
        $payload = $this->buildAnalysisPayload($id, null);
        if (!$payload) abort(404);
        return view('pdfs.relatorio_completo', [
            'commodity' => $payload['commodity'],
            'descriptiveData' => $payload['descriptiveData'],
            'nationalForecasts' => $payload['nationalForecasts'],
            'regionalComparisons' => $payload['regionalComparisons'],
            'conclusionText' => $payload['aiSummary']['recomendacao'] ?? '',
            'date' => date('d/m/Y H:i')
        ]);
    }

    private function getAuthenticatedUser(Request $request) {
        $userId = $request->session()->get('auth_user_id');
        return $userId ? DB::table('users')->where('id', $userId)->first() : null;
    }

    private function buildAnalysisPayload(?int $analysisId, ?int $commodityId): ?array
    {
        $analysis = $this->resolveAnalysis($analysisId, $commodityId);
        if (!$analysis) return null;

        $commodity = DB::table('commodity_entrada')
            ->where('commodity_id', $analysis->commodity_id)
            ->orderByDesc('last_updated')
            ->first();
            
        if (!$commodity) {
            $commodity = (object)[
                'nome' => $this->commodityMap[$analysis->commodity_id] ?? 'Produto #' . $analysis->commodity_id,
                'commodity_id' => $analysis->commodity_id
            ];
        }

        $aiLog = $this->fetchAiLogForAnalysis($analysis);

        return [
            'analysis' => $analysis,
            'commodity' => $commodity,
            'descriptiveData' => (object)[
                'materia_prima' => $commodity->nome,
                'referencia_mes' => $analysis->referencia_mes,
                'volume_compra_ton' => $analysis->volume_compra_ton,
                'preco_medio_brasil' => $analysis->preco_medio_brasil,
                'preco_alvo' => $analysis->preco_alvo,
                'logistica_perc' => $analysis->logistica_perc,
                'risco' => $analysis->risco,
                'estabilidade' => $analysis->estabilidade
            ],
            'nationalForecasts' => $this->buildTimelineSeries($analysis),
            'aiSummary' => $this->buildAiSummary($analysis, $commodity, $aiLog),
            'regionalComparisons' => $this->buildRegionalComparisons($analysis)
        ];
    }

    private function resolveAnalysis(?int $analysisId, ?int $commodityId)
    {
        $q = DB::table('commodity_saida')->orderByDesc('updated_at');
        if ($analysisId) $q->where('id', $analysisId);
        elseif ($commodityId) $q->where('commodity_id', $commodityId);
        return $q->first();
    }

    private function fetchAiLogForAnalysis(object $analysis): ?object
    {
        if ($analysis->updated_at) {
            $exactLog = DB::table('ai_analysis_logs')
                ->where('commodity_id', $analysis->commodity_id)
                ->where('updated_at', $analysis->updated_at)
                ->first();
            
            if ($exactLog) return $exactLog;
        }

        $log = DB::table('ai_analysis_logs')
            ->where('commodity_id', $analysis->commodity_id)
            ->whereBetween('updated_at', [
                Carbon::parse($analysis->updated_at)->subMinutes(1),
                Carbon::parse($analysis->updated_at)->addMinutes(1)
            ])
            ->orderByDesc('id')
            ->first();

        return $log ?? DB::table('ai_analysis_logs')
            ->where('commodity_id', $analysis->commodity_id)
            ->orderByDesc('id')
            ->first();
    }

    private function buildTimelineSeries(object $analysis)
    {
        $offsetMap = [
            -3 => 'preco_3_meses_anterior', -2 => 'preco_2_meses_anterior', -1 => 'preco_1_mes_anterior',
             0 => 'preco_mes_atual',
             1 => 'preco_1_mes_depois', 2 => 'preco_2_meses_depois', 3 => 'preco_3_meses_depois', 4 => 'preco_4_meses_depois',
        ];
        $series = [];
        $ref = Carbon::parse($analysis->referencia_mes);
        $ultimaReferencia = null;

        foreach ($offsetMap as $offset => $field) {
            $valor = $analysis->{$field} ?? null;
            if ($valor === null) continue;
            $mes = Str::ucfirst($ref->copy()->addMonths($offset)->locale('pt_BR')->translatedFormat('M/Y'));
            $variacao = ($ultimaReferencia && $ultimaReferencia > 0) ? round((($valor - $ultimaReferencia) / $ultimaReferencia) * 100, 2) : 0;
            $series[] = (object) ['mes_ano' => $mes, 'preco_medio' => (float) $valor, 'variacao_perc' => $variacao];
            $ultimaReferencia = $valor;
        }
        return collect($series);
    }

    private function buildAiSummary(object $analysis, object $commodity, ?object $aiLog): array
    {
        $parsed = ($aiLog && $aiLog->response) ? json_decode($aiLog->response, true) : [];
        return [
            'materia_prima' => $commodity->nome,
            'recomendacao' => $parsed['recomendacao'] ?? 'Sem recomendação disponível.',
            'mercados' => $parsed['mercados'] ?? [],
            'logistica' => array_merge(['custo_estimado' => $analysis->logistica_perc], $parsed['logistica'] ?? []),
            'indicadores' => array_merge(['risco' => $analysis->risco], $parsed['indicadores'] ?? [])
        ];
    }

    private function buildRegionalComparisons(object $analysis)
    {
        $nomeReferencia = DB::table('commodity_entrada')
            ->where('commodity_id', $analysis->commodity_id)
            ->value('nome');

        if (!$nomeReferencia) return collect([]);

        $dados = DB::table('commodity_entrada')
            ->join('locations', 'locations.id', '=', 'commodity_entrada.location_id')
            ->where('commodity_entrada.nome', $nomeReferencia)
            ->where('commodity_entrada.source', 'AI_RANKING') // Filtra para mostrar apenas sugestões da IA
            ->orderByDesc('commodity_entrada.last_updated')
            ->select(
                'locations.nome as local',
                'locations.regiao',
                'commodity_entrada.price',
                'commodity_entrada.currency',
                'commodity_entrada.last_updated'
            )
            ->get()
            ->unique('local')
            ->take(3); // Garante pegar apenas as top 3

        return $dados->map(function ($item) use ($analysis) {
            // Remove "(Auto)" do nome para exibição (limpeza de segurança)
            $nomeLocal = str_replace(' (Auto)', '', $item->local);

            $priceBrl = $item->price;
            switch($item->currency) {
                case 'USD': $priceBrl *= 5.00; break;
                case 'CNY': $priceBrl *= 0.70; break;
                case 'EUR': $priceBrl *= 5.40; break;
                case 'GBP': $priceBrl *= 6.30; break;
            }

            $logistica = $analysis->logistica_perc ?? 10;
            if ($item->regiao === 'Centro-Oeste') $logistica += 5;
            if ($item->regiao === 'Sul' || $item->regiao === 'Sudeste') $logistica -= 2;
            if ($item->regiao === 'EUA') $logistica -= 3;
            if ($item->regiao === 'China') $logistica += 4;

            $estabilidade = $analysis->estabilidade ?? 'Média';
            if (in_array($item->local, ['EUA', 'Brasil', 'Santos', 'Chicago'])) $estabilidade = 'Alta';
            if (in_array($item->local, ['Ucrânia', 'Rússia'])) $estabilidade = 'Baixa';

            return (object) [
                'pais' => $nomeLocal, 
                'preco_medio' => round($priceBrl, 2),
                'moeda' => $item->currency,
                'currency_orig' => $item->currency,
                'logistica_perc' => max(0, $logistica),
                'estabilidade' => $estabilidade,
                'risco' => $analysis->risco ?? 'Médio',
                
                'logistica_obs' => 'Estimativa regional',
                'estabilidade_economica' => $estabilidade,
                'estabilidade_climatica' => '-',
                'risco_geral' => $analysis->risco
            ];
        });
    }
}