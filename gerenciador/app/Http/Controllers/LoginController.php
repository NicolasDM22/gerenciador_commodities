<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function show(Request $request)
    {
        if ($request->session()->has('auth_user_id')) {
            return redirect()->route('home');
        }

        return view('login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'usuario' => ['required', 'string'],
            'senha' => ['required', 'string'],
        ]);

        $user = DB::table('users')
            ->select('id', 'usuario', 'senha', 'is_admin')
            ->where('usuario', $credentials['usuario'])
            ->first();

        if ($user && ($this->passwordMatches($credentials['senha'], $user->senha))) {
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
                'usuario' => 'As credenciais fornecidas nao foram encontradas.',
            ])
            ->onlyInput('usuario');
    }

    /**
     * Display the registration form.
     */
    public function create(Request $request)
    {
        if ($request->session()->has('auth_user_id')) {
            return redirect()->route('home');
        }

        return view('register');
    }

    /**
     * Handle a new user registration.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario' => [
                'required',
                'string',
                'max:191',
                Rule::unique('users', 'usuario'),
            ],
            'senha' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $userId = DB::table('users')->insertGetId([
            'usuario' => $data['usuario'],
            'nome' => null,
            'email' => null,
            'foto_blob' => null,
            'foto_mime' => null,
            'senha' => Hash::make($data['senha']),
            'is_admin' => false,
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
     * Destroy the authenticated session.
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Sessao encerrada com sucesso.');
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (Hash::info($storedPassword)['algo'] !== null) {
            return Hash::check($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
