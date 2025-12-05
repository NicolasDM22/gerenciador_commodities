<!DOCTYPE html>
<!-- by Nicolas Duran Munhos -->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        :root {
            --gray-100: #f5f5f5;
            --gray-200: #e5e5e5;
            --gray-600: #4a4a4a;
            --gray-700: #3a3a3a;
            --gray-800: #2b2b2b;
            --accent: #6f6f6f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            font-family: Arial, Helvetica, sans-serif;
            color: var(--gray-800);
        }

        .login-card {
            width: min(420px, 90vw);
            padding: 2.5rem;
            background-color: #ffffff;
            border-radius: 18px;
            box-shadow: 0 25px 50px -20px rgba(0, 0, 0, 0.45);
        }

        .login-card h1 {
            margin: 0 0 1rem;
            font-size: 2rem;
            color: var(--gray-800);
            text-align: center;
        }

        .login-card p {
            margin: 0 0 2rem;
            font-size: 0.95rem;
            text-align: center;
            color: var(--gray-600);
        }

        .status,
        .alert {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .status {
            background-color: #d1f2d8;
            color: #0b5520;
        }

        .alert {
            background-color: #fcdada;
            color: #7a1f1f;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--accent);
            border-radius: 12px;
            background-color: var(--gray-100);
            font-size: 0.95rem;
            color: var(--gray-800);
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="email"]{
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--accent);
            border-radius: 12px;
            background-color: var(--gray-100);
            font-size: 0.95rem;
            color: var(--gray-800);
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--gray-700);
            box-shadow: 0 0 0 3px rgba(47, 47, 51, 0.15);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        button {
            width: 100%;
            padding: 0.9rem 1rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--gray-700), var(--gray-800));
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -12px rgba(0, 0, 0, 0.5);
        }

        .session-details {
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
            background-color: var(--gray-100);
            border-radius: 12px;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .footer-link {
            margin-top: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
            color: var(--gray-600);
        }

        .footer-link a {
            color: var(--gray-700);
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Entrar</h1>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.authenticate') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit">Acessar</button>
        </form>

        <div class="footer-link">
            Ainda não possui cadastro?
            <a href="{{ route('register') }}">Criar conta</a>
        </div>

        @if (session()->has('auth_usuario'))
            <div class="session-details">
                Usuário autenticado: <strong>{{ session('auth_usuario') }}</strong>
            </div>
        @endif
    </div>
</body>
</html>
