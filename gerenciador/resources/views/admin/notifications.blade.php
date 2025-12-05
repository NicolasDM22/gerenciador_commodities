<!-- by Matias Amma -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificacoes administrativas</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-600: #4b5563;
            --gray-900: #111827;
            --accent: #0f766e;
            --accent-dark: #0d4d47;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            font-family: Arial, Helvetica, sans-serif;
            color: var(--gray-900);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 3rem;
        }

        main {
            flex: 1;
            padding: 1.5rem 3rem 3rem;
            display: grid;
            gap: 1.5rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.6rem;
            border-radius: 12px;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            text-decoration: none;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 35px -22px rgba(0, 0, 0, 0.45);
        }

        .button-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #ffffff;
        }

        .button-secondary {
            background: #ffffff;
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
        }

        .status {
            padding: 1rem 1.25rem;
            border-radius: 14px;
            background: rgba(15, 118, 110, 0.15);
            color: var(--accent-dark);
            font-size: 0.95rem;
        }

        .layout {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: 1.2fr 1fr;
        }

        .card {
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 28px 55px -28px rgba(0, 0, 0, 0.5);
            padding: 1.75rem;
            display: grid;
            gap: 1.1rem;
        }

        .card h2 {
            margin: 0;
            font-size: 1.45rem;
        }

        .notifications-list {
            display: grid;
            gap: 1rem;
        }

        .notification {
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 1rem;
            display: grid;
            gap: 0.5rem;
        }

        .notification header {
            padding: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification header h3 {
            margin: 0;
            font-size: 1.05rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.65rem;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.12);
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-danger {
            background: rgba(220, 38, 38, 0.12);
            color: var(--danger);
        }

        .threads {
            display: grid;
            gap: 0.8rem;
        }

        .thread {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 14px;
            border: 1px solid var(--gray-200);
            background: #ffffff;
        }

        .thread a {
            color: var(--accent-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .chat-window {
            display: grid;
            gap: 0.8rem;
            max-height: 360px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .chat-message {
            display: inline-flex;
            flex-direction: column;
            gap: 0.35rem;
            max-width: 80%;
            padding: 0.9rem 1.1rem;
            border-radius: 16px;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .chat-message.user {
            align-self: flex-start;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
        }

        .chat-message.admin {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #ffffff;
        }

        .chat-message small {
            font-size: 0.75rem;
            opacity: 0.75;
        }

        .empty-state {
            padding: 1rem;
            border-radius: 16px;
            border: 1px dashed var(--gray-300);
            color: var(--gray-500);
            text-align: center;
            font-size: 0.9rem;
        }

        textarea {
            width: 100%;
            min-height: 110px;
            border-radius: 14px;
            border: 1px solid var(--gray-300);
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            resize: vertical;
        }

        @media (max-width: 1080px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            main {
                padding: 1.5rem;
            }

            .layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1 style="margin: 0; font-size: 1.8rem;">Central administrativa</h1>
            <p style="margin: 0; color: var(--gray-600); font-size: 0.9rem;">Gerencie notificacoes geradas por pedidos e comprovantes enviados.</p>
        </div>
        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
            <a class="button button-secondary" href="{{ route('home') }}">Voltar para home</a>
        </div>
    </header>

    <main>
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <section class="layout">
            <article class="card">
                <h2>Notificacoes recentes</h2>
                @if ($notifications->isEmpty())
                    <div class="empty-state">Nenhuma notificacao cadastrada no momento.</div>
                @else
                    <div class="notifications-list">
                        @foreach ($notifications as $notification)
                            <div class="notification">
                                <header>
                                    <h3>{{ $notification->title }}</h3>
                                    <span class="badge {{ $notification->status !== 'novo' ? 'badge-danger' : '' }}">{{ strtoupper($notification->status) }}</span>
                                </header>
                                <p style="margin: 0; color: var(--gray-600); font-size: 0.9rem;">{{ $notification->body }}</p>
                                <small style="color: var(--gray-400);">Criada em {{ $notification->created_at_formatted }}</small>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button class="button button-secondary" type="submit">Marcar como lida</button>
                                    </form>
                                    <a class="button button-secondary" href="{{ route('admin.notifications', ['user_id' => $notification->user_id]) }}">Abrir chat</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="card">
                <h2>Conversas de comprovantes</h2>
                @if ($threads->isEmpty())
                    <div class="empty-state">Nenhum comprovante enviado ate agora.</div>
                @else
                    <div class="threads">
                        @foreach ($threads as $thread)
                            <div class="thread">
                                <div>
                                    <strong>{{ $thread->nome ?? $thread->usuario }}</strong>
                                    <div style="font-size: 0.8rem; color: var(--gray-400);">Ultima mensagem {{ $thread->last_message_formatted }}</div>
                                </div>
                                <a href="{{ route('admin.notifications', ['user_id' => $thread->user_id]) }}">Abrir conversa</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="card">
            <h2>Chat selecionado</h2>
            @if (!$selectedUser)
                <div class="empty-state">Selecione um usuario para visualizar as mensagens e responder.</div>
            @else
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>{{ $selectedUser->nome ?? $selectedUser->usuario }}</strong>
                        <div style="font-size: 0.85rem; color: var(--gray-400);">{{ $selectedUser->email ?? 'Email nao informado' }}</div>
                    </div>
                    <span class="badge">Usuario #{{ $selectedUser->id }}</span>
                </div>

                @if ($chatMessages->isEmpty())
                    <div class="empty-state" style="margin-top: 1rem;">Sem mensagens registradas para este usuario.</div>
                @else
                    <div class="chat-window" style="margin-top: 1rem;">
                        @foreach ($chatMessages as $message)
                            <div class="chat-message {{ $message->sender_type === 'admin' ? 'admin' : 'user' }}">
                                @if ($message->message)
                                    <span>{{ $message->message }}</span>
                                @endif
                                @if ($message->attachment_url)
                                    <a class="chat-attachment" href="{{ $message->attachment_url }}" target="_blank" rel="noopener">Anexo: {{ $message->attachment_name ?? 'Arquivo' }}</a>
                                @endif
                                <small>{{ ucfirst($message->sender_type) }} - {{ $message->created_at_formatted }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('receipt-chat.reply', $selectedUser->id) }}" method="POST" style="margin-top: 1.5rem; display: grid; gap: 0.75rem;">
                    @csrf
                    <label for="reply" style="font-size: 0.85rem; color: var(--gray-500);">Responder ao usuario</label>
                    <textarea id="reply" name="message" placeholder="Escreva orientacoes ou confirme o recebimento do comprovante..." required>{{ old('message') }}</textarea>
                    <button class="button button-primary" type="submit">Enviar resposta</button>
                </form>
            @endif
        </section>
    </main>
</body>
</html>
