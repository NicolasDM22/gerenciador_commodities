<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    /**
     * Display the authenticated dashboard.
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

        $adminData = [
            'notifications' => collect(),
        ];

        if (!empty($user->is_admin)) {
            $adminData['notifications'] = DB::table('admin_notifications')
                ->select('id', 'title', 'body', 'status', 'created_at')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(function ($notification) {
                    $createdAt = $notification->created_at ? Carbon::parse($notification->created_at) : null;
                    $notification->created_at_formatted = $createdAt ? $createdAt->format('d/m/Y H:i') : 'Nao informado';
                    return $notification;
                });
        }

        $locations = DB::table('locations')
            ->select('id', 'nome', 'estado', 'regiao')
            ->orderBy('nome')
            ->get();

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

        $locationSummaries = $priceRows->groupBy('location_id')
            ->map(function ($entries) {
                $first = $entries->first();

                if (!$first) {
                    return null;
                }

                $items = $entries->sortBy('price')
                    ->values()
                    ->map(function ($row) {
                        $lastUpdated = $row->last_updated ? Carbon::parse($row->last_updated) : null;

                        return [
                            'commodity' => $row->commodity_nome,
                            'categoria' => $row->categoria,
                            'price' => (float) $row->price,
                            'currency' => $row->currency,
                            'last_updated' => $lastUpdated ? $lastUpdated->format('d/m/Y') : null,
                            'source' => $row->source,
                            'location' => $row->location_nome,
                        ];
                    })
                    ->take(8)
                    ->all();

                return [
                    'location_id' => $first->location_id,
                    'location' => $first->location_nome,
                    'estado' => $first->estado,
                    'regiao' => $first->regiao,
                    'items' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $locationOptions = $locations->map(function ($location) {
            return [
                'id' => $location->id,
                'nome' => $location->nome,
                'estado' => $location->estado,
                'regiao' => $location->regiao,
            ];
        })
            ->values()
            ->all();

        $supportChat = $this->resolveSupportChat($userId);

        return view('home', [
            'user' => $user,
            'stats' => $stats,
            'avatarUrl' => $this->resolveAvatarUrl($user),
            'isAdmin' => (bool) ($user->is_admin ?? false),
            'adminData' => $adminData,
            'marketOverview' => $marketOverview,
            'locationSummaries' => $locationSummaries,
            'locationOptions' => $locationOptions,
            'supportChat' => $supportChat,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'usuario' => [
                'required',
                'string',
                'max:191',
                Rule::unique('users', 'usuario')->ignore($userId),
            ],
            'nome' => ['nullable', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'nova_senha' => ['nullable', 'string', 'min:6', 'confirmed'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        $updatePayload = [
            'usuario' => $data['usuario'],
            'nome' => $data['nome'] ?? null,
            'email' => $data['email'] ?? null,
            'updated_at' => now(),
        ];

        if (!empty($data['nova_senha'])) {
            $updatePayload['senha'] = Hash::make($data['nova_senha']);
        }

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $updatePayload['foto_blob'] = base64_encode(file_get_contents($file->getRealPath()));
            $updatePayload['foto_mime'] = $file->getMimeType();
        }

        DB::table('users')
            ->where('id', $userId)
            ->update($updatePayload);

        $request->session()->put('auth_usuario', $data['usuario']);

        return redirect()
            ->route('home')
            ->with('status', 'Perfil atualizado com sucesso!');
    }

    private function resolveSupportChat(int $userId): array
    {
        $chat = DB::table('support_chats')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if (!$chat) {
            return [
                'id' => null,
                'status' => 'inexistente',
                'opened_at' => null,
                'messages' => [],
            ];
        }

        $messages = DB::table('support_messages')
            ->where('chat_id', $chat->id)
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                $createdAt = $message->created_at ? Carbon::parse($message->created_at) : null;

                return [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'created_at' => $createdAt ? $createdAt->format('d/m/Y H:i') : null,
                ];
            })
            ->all();

        $openedAt = $chat->opened_at ? Carbon::parse($chat->opened_at) : null;

        return [
            'id' => $chat->id,
            'status' => $chat->status,
            'opened_at' => $openedAt ? $openedAt->format('d/m/Y H:i') : null,
            'messages' => $messages,
        ];
    }
}
