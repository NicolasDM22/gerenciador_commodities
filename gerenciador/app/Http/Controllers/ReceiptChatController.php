<?php

//by Nicolas Duran
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptChatController extends Controller
{
    /**
     * Store a message or receipt proof from the authenticated user.
     */
    public function store(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'comprovante' => ['nullable', 'file', 'max:4096', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        if (empty($data['message']) && !$request->hasFile('comprovante')) {
            throw ValidationException::withMessages([
                'message' => 'Envie uma mensagem ou anexe um comprovante.',
            ]);
        }

        $attachment = [
            'name' => null,
            'mime' => null,
            'blob' => null,
        ];

        if ($request->hasFile('comprovante')) {
            $file = $request->file('comprovante');
            $attachment['name'] = $file->getClientOriginalName();
            $attachment['mime'] = $file->getMimeType();
            $attachment['blob'] = base64_encode(file_get_contents($file->getRealPath()));
        }

        $message = $data['message'] ?? null;
        $now = now();

        DB::table('receipt_messages')->insert([
            'user_id' => $userId,
            'sender_type' => 'user',
            'message' => $message,
            'attachment_name' => $attachment['name'],
            'attachment_mime' => $attachment['mime'],
            'attachment_blob' => $attachment['blob'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('admin_notifications')->insert([
            'user_id' => $userId,
            'type' => 'comprovante',
            'title' => 'Novo comprovante PIX enviado',
            'body' => 'O usuario enviou um novo comprovante via chat do checkout.',
            'status' => 'novo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect()
            ->route('checkout.index')
            ->with('status', 'Mensagem enviada com sucesso! A equipe sera notificada.');
    }

    /**
     * Admin can reply to a user's receipt chat thread.
     */
    public function reply(Request $request, int $userId)
    {
        $authUserId = $request->session()->get('auth_user_id');
        $isAdmin = (bool) $request->session()->get('auth_is_admin', false);

        if (!$authUserId) {
            return redirect()->route('login');
        }

        if (!$isAdmin) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            abort(404, 'Usuario nao encontrado.');
        }

        $now = now();

        DB::table('receipt_messages')->insert([
            'user_id' => $userId,
            'sender_type' => 'admin',
            'message' => $data['message'],
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_blob' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('admin_notifications')
            ->where('user_id', $userId)
            ->where('status', 'novo')
            ->update([
                'status' => 'em_atendimento',
                'updated_at' => $now,
            ]);

        return back()->with('status', 'Resposta enviada ao usuario.');
    }
}
