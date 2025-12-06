<?php
//by Nicolas Duran
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportChatController extends Controller
{
    /**
     * Abre um novo chamado de suporte para o usuario logado: remove threads antigas,
     * cria chat limpo e devolve o payload inicial da conversa.
     */
    public function open(Request $request): JsonResponse
    {
        $userId = $request->session()->get('auth_user_id');
        $isAdmin = (bool) $request->session()->get('auth_is_admin', false);

        if (!$userId) {
            return response()->json([
                'error' => 'Sessao expirada. Faca login novamente.',
            ], 401);
        }

        if ($isAdmin) {
            return response()->json([
                'error' => 'Apenas usuarios comuns podem abrir chamados de suporte.',
            ], 403);
        }

        $now = now();
        $chatId = null;

        DB::transaction(function () use ($userId, $now, &$chatId) {
            $chatIds = DB::table('support_chats')
                ->where('user_id', $userId)
                ->pluck('id');

            if ($chatIds->isNotEmpty()) {
                DB::table('support_messages')
                    ->whereIn('chat_id', $chatIds)
                    ->delete();

                DB::table('support_chats')
                    ->whereIn('id', $chatIds)
                    ->delete();
            }

            $chatId = DB::table('support_chats')->insertGetId([
                'user_id' => $userId,
                'status' => 'aberto',
                'opened_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return response()->json([
            'chat' => $this->formatChatPayload($chatId),
            'message' => 'Novo chamado aberto com sucesso.',
        ]);
    }

    /**
     * Armazena uma nova mensagem do usuario no chat ativo, validando sessao e conteudo.
     */
    public function send(Request $request): JsonResponse
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return response()->json([
                'error' => 'Sessao expirada. Faca login novamente.',
            ], 401);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $chat = DB::table('support_chats')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if (!$chat) {
            return response()->json([
                'error' => 'Nenhum chamado ativo encontrado. Abra um novo chamado para enviar mensagens.',
            ], 404);
        }

        $now = now();

        DB::table('support_messages')->insert([
            'chat_id' => $chat->id,
            'user_id' => $userId,
            'sender_type' => 'user',
            'message' => $data['message'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('support_chats')
            ->where('id', $chat->id)
            ->update(['updated_at' => $now]);

        return response()->json([
            'chat' => $this->formatChatPayload($chat->id),
            'message' => 'Mensagem enviada.',
        ]);
    }

    /**
     * Encerra o chat ativo do usuario e limpa historico de mensagens.
     */
    public function close(Request $request): JsonResponse
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return response()->json([
                'error' => 'Sessao expirada. Faca login novamente.',
            ], 401);
        }

        $chat = DB::table('support_chats')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if (!$chat) {
            return response()->json([
                'message' => 'Nenhum chamado encontrado.',
            ]);
        }

        DB::transaction(function () use ($chat) {
            DB::table('support_messages')
                ->where('chat_id', $chat->id)
                ->delete();

            DB::table('support_chats')
                ->where('id', $chat->id)
                ->delete();
        });

        return response()->json([
            'message' => 'Chamado encerrado e historico removido.',
        ]);
    }

    /**
     * Monta payload normalizado do chat (status, horario e mensagens formatadas).
     */
    private function formatChatPayload(int $chatId): array
    {
        $chat = DB::table('support_chats')
            ->where('id', $chatId)
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
