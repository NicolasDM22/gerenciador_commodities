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

    /**
     * Tela principal de previsoes: valida usuario, monta payload da analise selecionada e renderiza view.
     */
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

    /**
     * Mostra graficos detalhados da analise (timeline e ranking regional) para o usuario autenticado.
     */
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

    /**
     * Exibe tela de conclusao com resumo da IA e timeline para analise escolhida.
     */
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

    public function destroy(Request $request, int $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');

        try {
            $deleted = DB::table('commodity_saida')->where('id', $id)->delete();
        } catch (\Throwable $e) {
            return back()->withErrors('Erro ao excluir a análise: ' . $e->getMessage());
        }

        if (!$deleted) {
            return back()->withErrors('Análise não encontrada ou já removida.');
        }

        return redirect()->route('home')->with('status', 'Análise removida com sucesso.');
    }

    /**
     * Gera conteudo do relatorio completo em view para exportacao em PDF.
     */
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

    /**
     * Recupera usuario autenticado a partir da sessao ou retorna null.
     */
    private function getAuthenticatedUser(Request $request) {
        $userId = $request->session()->get('auth_user_id');
        return $userId ? DB::table('users')->where('id', $userId)->first() : null;
    }

    /**
     * Consolida analise selecionada com dados de commodity, timeline, resumo IA e comparacoes regionais.
     */
    private function buildAnalysisPayload(?int $analysisId, ?int $commodityId): ?array
    {
        $analysis = $this->resolveAnalysis($analysisId, $commodityId);
        if (!$analysis) return null;

        $commodity = DB::table('commodity_entrada')
            ->where('commodity_id', $analysis->commodity_id)
            ->where('source', 'User')
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
            'regionalComparisons' => $this->buildRegionalComparisonsFromLog($analysis, $aiLog)
        ];
    }

    /**
     * Decide qual analise carregar: por id fornecido ou ultima referente a commodity.
     */
    private function resolveAnalysis(?int $analysisId, ?int $commodityId)
    {
        $q = DB::table('commodity_saida')->orderByDesc('updated_at');
        if ($analysisId) $q->where('id', $analysisId);
        elseif ($commodityId) $q->where('commodity_id', $commodityId);
        return $q->first();
    }

    /**
     * Tenta encontrar o log da IA correspondente a analise (matching exato por timestamp ou mais proximo).
     */
    private function fetchAiLogForAnalysis(object $analysis): ?object
    {
        if ($analysis->updated_at) {
            $time = Carbon::parse($analysis->updated_at);
            
            $log = DB::table('ai_analysis_logs')
                ->where('commodity_id', $analysis->commodity_id)
                ->whereBetween('created_at', [$time->copy()->subSeconds(15), $time->copy()->addSeconds(15)])
                ->orderByDesc('id')
                ->first();
            
            if ($log) return $log;
        }

        return DB::table('ai_analysis_logs')
            ->where('commodity_id', $analysis->commodity_id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Gera serie temporal com meses relativos e variacao percentual entre pontos.
     */
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

    /**
     * Monta resumo textual/estruturado da IA mesclando dados persistidos e resposta original.
     */
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

    /**
     * Monta comparativo regional lendo diretamente do JSON histórico (Log) para garantir integridade dos dados.
     */
    private function buildRegionalComparisonsFromLog(object $analysis, ?object $aiLog)
    {
        if (!$aiLog || empty($aiLog->response)) {
            return collect([]);
        }

        $dadosJson = json_decode($aiLog->response, true);
        $mercados = $dadosJson['mercados'] ?? [];

        return collect($mercados)->take(3)->map(function ($item) use ($analysis) {
            
            $nomeLocal = isset($item['nome']) ? trim(explode('(', $item['nome'])[0]) : 'Desconhecido';
            $moeda = $item['moeda'] ?? 'BRL';
            $precoOriginal = $this->toFloat($item['preco'] ?? 0);

            $priceBrl = $precoOriginal;
            if ($moeda !== 'BRL') {
                switch($moeda) {
                    case 'USD': $priceBrl *= 5.00; break;
                    case 'CNY': $priceBrl *= 0.70; break;
                    case 'EUR': $priceBrl *= 5.40; break;
                    case 'GBP': $priceBrl *= 6.30; break;
                }
            }

            $logistica = $analysis->logistica_perc ?? 10;
            if (str_contains($nomeLocal, 'China') || str_contains($nomeLocal, 'EUA')) $logistica += 5;
            if (str_contains($nomeLocal, 'Paraná') || str_contains($nomeLocal, 'Santos')) $logistica -= 2;

            $estabilidade = $item['estabilidade_economica'] ?? ($analysis->estabilidade ?? 'Média');

            return (object) [
                'pais' => $nomeLocal, 
                'preco_medio' => round($priceBrl, 2),
                'price' => round($priceBrl, 2),
                'moeda' => $moeda,
                'currency' => $moeda,
                'logistica_perc' => max(0, $logistica),
                'estabilidade' => $item['estabilidade_climatica'] ?? '-',
                'estabilidade_economica' => $estabilidade,
                'risco' => $item['risco_geral'] ?? 'Médio'
            ];
        });
    }

    private function toFloat($v) { 
        if (is_numeric($v)) return (float)$v;
        return (float) preg_replace('/[^0-9.]/', '', str_replace(['.',','], ['','.'], (string)$v)); 
    }
}