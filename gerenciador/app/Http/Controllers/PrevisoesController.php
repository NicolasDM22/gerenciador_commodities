<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrevisoesController extends Controller
{
    /**
     * Display the forecasts page.
     */
    public function index(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return redirect()->route('login');
        }

        $memberSince = $user->created_at ? Carbon::parse($user->created_at) : null;
        $lastUpdate = $user->updated_at ? Carbon::parse($user->updated_at) : null;

        $stats = [
            'member_since' => $memberSince ? $memberSince->format('d/m/Y') : 'Nao informado',
            'account_age' => $memberSince ? $memberSince->locale('pt_BR')->diffForHumans() : 'Nao informado',
            'last_update' => $lastUpdate ? $lastUpdate->format('d/m/Y H:i') : 'Nao informado',
        ];

        $priceRows = DB::table('commodity_prices as cp')
            ->join('commodities as c', 'c.id', '=', 'cp.commodity_id')
            ->join('locations as l', 'l.id', '=', 'cp.location_id')
            ->select(
                'cp.id',
                'cp.price',
                'cp.currency',
                'cp.source',
                'cp.last_updated',
                'c.id as commodity_id',
                'c.nome as commodity_nome',
                'c.categoria',
                'l.id as location_id',
                'l.nome as location_nome',
                'l.estado',
                'l.regiao'
            )
            ->orderBy('cp.price')
            ->get();

        $marketOverview = $priceRows->groupBy('commodity_id')
            ->map(function ($entries) {
                $best = $entries->sortBy('price')->first();

                if (!$best) {
                    return null;
                }

                $lastUpdated = $best->last_updated ? Carbon::parse($best->last_updated) : null;

                return [
                    'commodity' => $best->commodity_nome,
                    'categoria' => $best->categoria,
                    'location' => $best->location_nome,
                    'price' => (float) $best->price,
                    'currency' => $best->currency,
                    'last_updated' => $lastUpdated ? $lastUpdated->format('d/m/Y') : null,
                    'source' => $best->source,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return view('previ', [
            'user' => $user,
            'stats' => $stats,
            'avatarUrl' => $this->resolveAvatarUrl($user),
            'isAdmin' => (bool) ($user->is_admin ?? false),
            'marketOverview' => $marketOverview,
        ]);
    }
}
