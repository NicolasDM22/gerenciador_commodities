<?php

//by Nicolas
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    /**
     * Display notifications dashboard for admins.
     */
    public function index(Request $request)
    {
        // Exibe painel com notificações e conversas de usuários para administradores autenticados.
        $authUserId = $request->session()->get('auth_user_id');
        $isAdmin = (bool) $request->session()->get('auth_is_admin', false);

        if (!$authUserId) {
            return redirect()->route('login');
        }

        if (!$isAdmin) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $notifications = DB::table('admin_notifications')
            ->select('id', 'user_id', 'type', 'title', 'body', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                $createdAt = $notification->created_at ? Carbon::parse($notification->created_at) : null;
                $notification->created_at_formatted = $createdAt ? $createdAt->format('d/m/Y H:i') : 'Nao informado';
                return $notification;
            });

        $threads = DB::table('receipt_messages as rm')
            ->join('users as u', 'u.id', '=', 'rm.user_id')
            ->select(
                'u.id as user_id',
                'u.usuario',
                'u.nome',
                DB::raw('MAX(rm.created_at) as last_message_at')
            )
            ->groupBy('u.id', 'u.usuario', 'u.nome')
            ->orderByDesc('last_message_at')
            ->limit(20)
            ->get()
            ->map(function ($thread) {
                $lastMessage = $thread->last_message_at ? Carbon::parse($thread->last_message_at) : null;
                $thread->last_message_formatted = $lastMessage ? $lastMessage->diffForHumans() : 'Nunca';
                return $thread;
            });

        $selectedUserId = (int) $request->query('user_id', 0);
        $chatMessages = collect();
        $selectedUser = null;

        if ($selectedUserId > 0) {
            $selectedUser = DB::table('users')
                ->select('id', 'usuario', 'nome', 'email')
                ->where('id', $selectedUserId)
                ->first();

            if ($selectedUser) {
                $chatMessages = DB::table('receipt_messages')
                    ->select('id', 'sender_type', 'message', 'attachment_name', 'attachment_mime', 'attachment_blob', 'created_at')
                    ->where('user_id', $selectedUserId)
                    ->orderBy('created_at')
                    ->get()
                    ->map(function ($message) {
                        $createdAt = $message->created_at ? Carbon::parse($message->created_at) : null;
                        $message->created_at_formatted = $createdAt ? $createdAt->format('d/m/Y H:i') : 'Nao informado';
                        $message->attachment_url = (!empty($message->attachment_blob) && !empty($message->attachment_mime))
                            ? 'data:'.$message->attachment_mime.';base64,'.$message->attachment_blob
                            : null;

                        return $message;
                    });
            }
        }

        return view('admin.notifications', [
            'notifications' => $notifications,
            'threads' => $threads,
            'selectedUser' => $selectedUser,
            'chatMessages' => $chatMessages,
        ]);
    }

    /**
     * Mark a notification as processed.
     */
    public function markAsRead(Request $request, int $notificationId)
    {
        // Marca uma notificação específica como lida para administradores autenticados.
        $authUserId = $request->session()->get('auth_user_id');
        $isAdmin = (bool) $request->session()->get('auth_is_admin', false);

        if (!$authUserId) {
            return redirect()->route('login');
        }

        if (!$isAdmin) {
            abort(403, 'Acesso restrito a administradores.');
        }

        DB::table('admin_notifications')
            ->where('id', $notificationId)
            ->update([
                'status' => 'lido',
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Notificacao marcada como lida.');
    }
}
