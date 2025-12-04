<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrevisoesController extends Controller
{
    // Mapa manual de nomes
    private $commodityMap = [
        1 => 'Soja',
        2 => 'Milho',
        3 => 'Açúcar',
        4 => 'Cacau',
    ];

    public function index(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        
        $avatarUrl = $this->resolveAvatarUrl($user);

        // --- CORREÇÃO PRINCIPAL AQUI ---
        // $id agora é tratado como o ID da ANÁLISE (commodity_saida.id), não da commodity.
        
        $saidaData = null;

        if ($id) {
            // 1. Tenta buscar a análise específica pelo ID da rota
            $saidaData = DB::table('commodity_saida')->where('id', $id)->first();
        }

        // 2. Se não passou ID ou não achou, pega a ÚLTIMA inserida no sistema (fallback)
        if (!$saidaData) {
            $saidaData = DB::table('commodity_saida')
                ->where('tipo_analise', 'PREVISAO_MENSAL')
                ->orderByDesc('id') // Pega a última inserção real
                ->first();
        }

        // Se o banco estiver vazio
        if (!$saidaData) {
            return redirect()->route('home')->withErrors('Nenhuma análise encontrada.');
        }

        // 3. Define qual é a commodity baseada no registro encontrado
        $commodityId = $saidaData->commodity_id;
        $commodityName = $this->commodityMap[$commodityId] ?? 'Produto #' . $commodityId;

        // 4. Monta o Objeto Descritivo com os dados DAQUELA análise específica
        $descriptiveData = (object) [
            'materia_prima' => $commodityName,
            'volume_compra_ton' => $saidaData->volume_compra_ton,
            'preco_medio_global' => $saidaData->preco_medio_global,
            'preco_medio_brasil' => $saidaData->preco_medio_brasil,
            'preco_alvo' => $saidaData->preco_alvo,
            'referencia_mes' => $saidaData->referencia_mes,
            // Passamos dados extras para usar na lógica interna se precisar
            'logistica_perc' => $saidaData->logistica_perc,
            'risco' => $saidaData->risco,
            'estabilidade' => $saidaData->estabilidade
        ];

        // 5. Monta a Previsão Nacional (lendo as colunas horizontais do registro encontrado)
        $refDate = Carbon::parse($saidaData->referencia_mes);
        
        $nationalForecasts = collect([
            [
                'mes_ano' => ucfirst($refDate->copy()->addMonth(1)->locale('pt_BR')->translatedFormat('F/Y')),
                'preco_medio' => $saidaData->preco_1_mes_depois,
                'variacao_perc' => $this->calcDiffPerc($saidaData->preco_mes_atual, $saidaData->preco_1_mes_depois)
            ],
            [
                'mes_ano' => ucfirst($refDate->copy()->addMonth(2)->locale('pt_BR')->translatedFormat('F/Y')),
                'preco_medio' => $saidaData->preco_2_meses_depois,
                'variacao_perc' => $this->calcDiffPerc($saidaData->preco_1_mes_depois, $saidaData->preco_2_meses_depois)
            ],
            [
                'mes_ano' => ucfirst($refDate->copy()->addMonth(3)->locale('pt_BR')->translatedFormat('F/Y')),
                'preco_medio' => $saidaData->preco_3_meses_depois,
                'variacao_perc' => $this->calcDiffPerc($saidaData->preco_2_meses_depois, $saidaData->preco_3_meses_depois)
            ],
            [
                'mes_ano' => ucfirst($refDate->copy()->addMonth(4)->locale('pt_BR')->translatedFormat('F/Y')),
                'preco_medio' => $saidaData->preco_4_meses_depois,
                'variacao_perc' => $this->calcDiffPerc($saidaData->preco_3_meses_depois, $saidaData->preco_4_meses_depois)
            ]
        ])->map(fn($item) => (object) $item);

        // 6. Comparativo Regional
        // Busca os preços atuais para essa commodity
        $regionalComparisons = DB::table('commodity_entrada')
            ->join('locations', 'locations.id', '=', 'commodity_entrada.location_id')
            ->select('locations.nome as pais', 'commodity_entrada.price as preco_medio')
            ->where('commodity_entrada.commodity_id', $commodityId)
            ->orderByDesc('commodity_entrada.last_updated')
            ->get()
            ->unique('pais')
            ->map(function ($item, $key) use ($saidaData) {
                // Usa os dados da análise ($saidaData) para preencher a estimativa de risco/logística
                $item->logistica_perc = $saidaData->logistica_perc ?? 10; 
                $item->risco = $saidaData->risco ?? 'Médio';
                $item->estabilidade = $saidaData->estabilidade ?? 'Média';
                $item->ranking = $key + 1;
                return $item;
            });

        // Objeto simples para compatibilidade da view
        $selectedCommodity = (object) ['id' => $commodityId, 'nome' => $commodityName];

        // Passamos 'currentAnalysisId' para saber qual ID estamos vendo (útil para botões de voltar/avançar)
        return view('previ', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'descriptiveData' => $descriptiveData,
            'nationalForecasts' => $nationalForecasts,
            'regionalComparisons' => $regionalComparisons,
            'selectedCommodity' => $selectedCommodity,
            'currentAnalysisId' => $saidaData->id // ID real da análise
        ]);
    }

    // --- MÉTODOS DE GRÁFICO E CONCLUSÃO TAMBÉM PRECISAM DE AJUSTE ---
    // Eles devem receber o Commodity ID, mas se você quiser navegar entre ANÁLISES específicas,
    // a lógica teria que mudar. Por enquanto, mantive eles focados na Commodity (padrão do dashboard).

    public function graficos(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        // AQUI: $id geralmente vem como ID da Commodity na rota de gráficos
        // Mas se vier da tela 'previ', pode ser confuso. 
        // Assumindo que a rota é /previsoes/graficos/{commodity_id}
        
        $commodityId = $id ?? 1;
        $commodityName = $this->commodityMap[$commodityId] ?? 'Produto';

        // Pega a análise mais recente para servir de base para os gráficos
        $saidaBase = DB::table('commodity_saida')
            ->where('commodity_id', $commodityId)
            ->orderByDesc('referencia_mes')
            ->first();

        $chartData = DB::table('commodity_entrada')
            ->join('locations', 'locations.id', '=', 'commodity_entrada.location_id')
            ->select('locations.nome as pais', 'commodity_entrada.price as preco_medio')
            ->where('commodity_entrada.commodity_id', $commodityId)
            ->orderByDesc('commodity_entrada.last_updated')
            ->get()
            ->unique('pais')
            ->map(function($item) use ($saidaBase) {
                $variation = rand(-20, 20) / 10; 
                $item->logistica_perc = ($saidaBase->logistica_perc ?? 10) + $variation;
                $item->risco = $saidaBase->risco ?? 'Médio';
                $item->estabilidade = $saidaBase->estabilidade ?? 'Média';
                return $item;
            })
            ->values();

        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId,
            'commodityName' => $commodityName,
            'chartData' => $chartData,
        ]);
    }

    public function conclusao(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        $commodityId = $id ?? 1;

        return view('conclusao', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId, 
        ]);
    }

    public function exportarPdf($id)
    {
        // ... (Mesma lógica simulada) ...
        return "PDF do ID commodity: $id";
    }

    // Auxiliares
    private function getAuthenticatedUser(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return null;
        return DB::table('users')->where('id', $userId)->first();
    }

    private function calcDiffPerc($antigo, $novo) {
        if(!$antigo || $antigo == 0) return 0;
        return (($novo - $antigo) / $antigo) * 100;
    }
}