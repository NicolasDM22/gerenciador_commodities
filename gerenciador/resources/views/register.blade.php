<!DOCTYPE html>
<!-- by Nicolas Duran Munhos -->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
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

        .card {
            width: min(440px, 92vw);
            padding: 2.5rem;
            background-color: #ffffff;
            border-radius: 18px;
            box-shadow: 0 25px 50px -20px rgba(0, 0, 0, 0.45);
        }

        .card h1 {
            margin: 0 0 1rem;
            font-size: 2rem;
            text-align: center;
        }

        .card p {
            margin: 0 0 2rem;
            font-size: 0.95rem;
            text-align: center;
            color: var(--gray-600);
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            background-color: #fcdada;
            color: #7a1f1f;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        input[type="text"],
        input[type="tel"] {
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
            background-color: var(--gray-100);
            font-size: 0.95rem;
            color: var(--gray-800);
            transition: border 0.2s ease, box-shadow 0.2s ease;
            border-radius: 12px;

        }
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

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--gray-700);
            box-shadow: 0 0 0 3px rgba(47, 47, 51, 0.15);
        }

        .form-group {
            margin-bottom: 1.4rem;
        }

        button {
            width: 100%;
            padding: 0.95rem 1rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--gray-700), var(--gray-800));
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -12px rgba(0, 0, 0, 0.5);
        }

        .footer-link {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .footer-link a {
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Novo cadastro</h1>

        @if ($errors->any())
            <div class="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('register.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    value="{{ old('usuario') }}"
                    autocomplete="username"
                    required
                >
            </div>

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
                <label for="telefone">Telefone</label>
                <input
                    type="tel"
                    id="telefone"
                    name="telefone"
                    value="{{ old('telefone') }}"
                    pattern="[\d\s\-\(\)\+]{10,20}"
                    inputmode="tel"
                    required
                >
            </div>

            <div class="form-group">
                <label for="endereco">Endereco (opcional)</label>
                <input
                    type="text"
                    id="endereco"
                    name="endereco"
                    value="{{ old('endereco') }}"
                    maxlength="255"
                    autocomplete="street-address"
                >
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-group">
                <label for="senha_confirmation">Confirmar senha</label>
                <input
                    type="password"
                    id="senha_confirmation"
                    name="senha_confirmation"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button type="submit">Cadastrar</button>
        </form>

        <div class="footer-link">
            Já possui uma conta?
            <a href="{{ route('login') }}">Acesse aqui</a>
        </div>
    </div>
</body>
</html>
