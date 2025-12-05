<?php
/**
 * HomeController.php by Nicolas Duran Munhos
 */
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    private function tableExists($tableName)
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return redirect()->route('login');

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'telefone', 'endereco', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) return redirect()->route('login');

        $isAdmin = (bool) ($user->is_admin ?? false);
        $avatarUrl = $this->resolveAvatarUrl($user);
        $hasCommoditySaidaTable = $this->tableExists('commodity_saida');

        // --- 1. CONFIGURAÇÃO PADRÃO (VAZIO) ---
        $chartData = [
            'labels' => [], 
            'prices' => [], 
            'commodityName' => 'Ainda não existem análises'
        ];
        
        $analysis = collect();

        // --- 2. BUSCA DINÂMICA (Se houver tabelas) ---
        if ($hasCommoditySaidaTable) {
            try {
                // A. Identifica a ÚLTIMA commodity analisada (ID mais alto inserido)
                $latestAnalysis = DB::table('commodity_saida')
                    ->where('tipo_analise', 'PREVISAO_MENSAL')
                    ->orderByDesc('id') // Pega a última inserção real
                    ->first();

                if ($latestAnalysis) {
                    $targetCommodityId = $latestAnalysis->commodity_id;

                    // B. Busca o NOME real dessa commodity na tabela de entrada (Sem mapa fixo)
                    $metaCommodity = DB::table('commodity_entrada')
                        ->where('commodity_id', $targetCommodityId)
                        ->select('nome')
                        ->first();
                    
                    $realName = $metaCommodity ? $metaCommodity->nome : 'Commodity #' . $targetCommodityId;

                    // C. Busca os DADOS para o gráfico desta commodity específica
                    $analysisData = DB::table('commodity_saida')
                        ->where('commodity_id', $targetCommodityId)
                        ->where('tipo_analise', 'PREVISAO_MENSAL')
                        ->select(
                            'referencia_mes',
                            'preco_3_meses_anterior', 'preco_2_meses_anterior', 'preco_1_mes_anterior',
                            'preco_mes_atual',
                            'preco_1_mes_depois', 'preco_2_meses_depois', 'preco_3_meses_depois', 'preco_4_meses_depois'
                        )
                        ->orderByDesc('referencia_mes')
                        ->first();

                    // D. Monta o gráfico se houver dados
                    if ($analysisData) {
                        $pricePoints = [
                            $analysisData->preco_3_meses_anterior,
                            $analysisData->preco_2_meses_anterior,
                            $analysisData->preco_1_mes_anterior,
                            $analysisData->preco_mes_atual,
                            $analysisData->preco_1_mes_depois,
                            $analysisData->preco_2_meses_depois,
                            $analysisData->preco_3_meses_depois,
                            $analysisData->preco_4_meses_depois,
                        ];

                        $refDate = Carbon::parse($analysisData->referencia_mes);
                        $chartLabels = [];
                        for ($i = -3; $i <= 4; $i++) {
                            $chartLabels[] = $refDate->copy()->addMonths($i)->format('M/y');
                        }

                        $chartData = [
                            'labels' => $chartLabels,
                            'prices' => array_map(fn($p) => $p ?? 0, $pricePoints),
                            'commodityName' => $realName, // Nome vindo do banco
                        ];
                    }
                }

                // E. Lista de Histórico (Para a tabela abaixo do gráfico)
                $analysis = DB::table('commodity_saida')
                    ->join('commodity_entrada', 'commodity_saida.commodity_id', '=', 'commodity_entrada.commodity_id')
                    ->where('commodity_saida.tipo_analise', 'PREVISAO_MENSAL')
                    ->orderByDesc('commodity_saida.updated_at')
                    ->select(
                        'commodity_saida.id', 
                        'commodity_saida.commodity_id', 
                        'commodity_saida.referencia_mes', 
                        'commodity_saida.created_at', 
                        'commodity_saida.updated_at',
                        'commodity_entrada.nome as commodity_nome' // Nome direto do join
                    )
                    ->get()
                    ->map(function ($item) {
                        $dataBase = $item->updated_at ?? $item->created_at ?? now();
                        $item->data_previsao = Carbon::parse($dataBase)->format('d/m/Y H:i');
                        return $item;
                    });

            } catch (\Exception $e) {
                // Silencia erro e mantém "Ainda não existem análises"
            }
        }

        // --- 3. NOTIFICAÇÕES (Mantido igual) ---
        $adminData = ['notifications' => collect()];
        if ($isAdmin) {
            $adminData['notifications'] = DB::table('admin_notifications')
                ->select('id', 'title', 'body', 'status', 'created_at')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(function ($notification) {
                    $createdAt = $notification->created_at ? Carbon::parse($notification->created_at) : null;
                    $notification->created_at_formatted = $createdAt ? $createdAt->format('d/m/Y H:i') : 'Não informado';
                    return $notification;
                });
        }

        return view('home', [
            'user' => $user,
            'avatarUrl' => $this->resolveAvatarUrl($user),
            'chartData' => $chartData,
            'isAdmin' => $isAdmin,
            'adminData' => $adminData,
            'previousAnalyses' => $analysis,
            'aiAnalyses' => $this->latestAiAnalyses($userId, $isAdmin),
        ]);
    }

    private function latestAiAnalyses(int $userId, bool $isAdmin)
    {
        $query = DB::table('ai_analysis_logs')->orderByDesc('created_at');
        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        return $query->limit(5)
            ->get()
            ->map(function ($item) {
                $item->parsed = json_decode($item->response ?? '', true) ?: null;
                $item->created_at_formatted = $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i') : '';
                return $item;
            });
    }
}