<?php
/**
 * LoginController.php by Nicolas Duran Munhos e Joao Pedro de Moura
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    /**
     * Exibe o formulario de login; se ja houver sessao autenticada redireciona para home.
     */
    public function show(Request $request)
    {
        if ($request->session()->has('auth_user_id')) {
            return redirect()->route('home');
        }

        return view('login');
    }

    /**
     * Autentica o usuario: valida credenciais, compara senha (hash ou texto puro),
     * cria sessao e sinaliza permissoes de admin; retorna erro se nao corresponder.
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate(
            [
                'email' => ['required', 'email', 'max:191'],
                'senha' => ['required', 'string'],
            ]
        );

        $user = DB::table('users')
            ->select('id', 'usuario', 'senha', 'is_admin', 'email')
            ->where('email', $credentials['email'])
            ->first();

        if (
            $user
            && ($this->passwordMatches($credentials['senha'], $user->senha))
        ) {
            $request->session()->regenerate();
            $request->session()->put('auth_user_id', $user->id);
            $request->session()->put('auth_usuario', $user->usuario);
            $request->session()->put('auth_is_admin', (bool) ($user->is_admin ?? false));

            return redirect()
                ->route('home')
                ->with('status', 'Login realizado com sucesso!');
        }

        return back()
            ->withErrors([
                'email' => 'As credenciais fornecidas não foram encontradas.',
            ])
            ->withInput($request->except('senha'));
    }

    /**
     * Exibe o formulario de cadastro; se ja autenticado, volta para home.
     */
    public function create(Request $request)
    {
        if ($request->session()->has('auth_user_id')) {
            return redirect()->route('home');
        }

        return view('register');
    }

    /**
     * Registra novo usuario: valida campos, normaliza telefone/endereco,
     * salva no banco com senha hash e abre sessao logada.
     */
    public function store(Request $request)
    {
        $data = $request->validate(
            [
                'usuario' => [
                    'required',
                    'string',
                    'max:191',
                    Rule::unique('users', 'usuario'),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:191',
                    Rule::unique('users', 'email'),
                ],
                'telefone' => ['required', 'string', 'regex:/^[0-9\\-\\s\\(\\)\\+]{10,20}$/'],
                'endereco' => ['nullable', 'string', 'max:255'],
                'senha' => ['required', 'string', 'min:6', 'confirmed'],
            ],
            [
                'telefone.regex' => 'Informe um telefone valido (apenas numeros, +, () e -).',
            ]
        );

        $normalizedPhone = $this->normalizePhone($data['telefone']);
        if (strlen($normalizedPhone) < 10 || strlen($normalizedPhone) > 15) {
            return back()
                ->withErrors(['telefone' => 'O telefone deve conter entre 10 e 15 digitos.'])
                ->withInput($request->except('senha', 'senha_confirmation'));
        }

        $normalizedAddress = null;
        if (!empty($data['endereco'])) {
            $normalizedAddress = $this->normalizeAddress($data['endereco']);
        }

        $userId = DB::table('users')->insertGetId([
            'usuario' => $data['usuario'],
            'email' => trim($data['email']),
            'nome' => $data['usuario'],
            'foto_blob' => null,
            'foto_mime' => null,
            'telefone' => $normalizedPhone,
            'endereco' => $normalizedAddress ?? '',
            'senha' => Hash::make($data['senha']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request->session()->regenerate();
        $request->session()->put('auth_user_id', $userId);
        $request->session()->put('auth_usuario', $data['usuario']);
        $request->session()->put('auth_is_admin', false);

        return redirect()
            ->route('home')
            ->with('status', 'Cadastro realizado com sucesso!');
    }

    /**
     * Encerra a sessao autenticada, invalida CSRF e redireciona ao login.
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Sessao encerrada com sucesso.');
    }

    /**
     * Compara senha informada com o hash armazenado (ou texto plano legado) de forma segura.
     */
    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (Hash::info($storedPassword)['algo'] !== null) {
            return Hash::check($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
