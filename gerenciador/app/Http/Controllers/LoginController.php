<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function show()
    {
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
            ->select('id', 'usuario', 'senha')
            ->where('usuario', $credentials['usuario'])
            ->first();

        if ($user && ($this->passwordMatches($credentials['senha'], $user->senha))) {
            $request->session()->regenerate();
            $request->session()->put('auth_user_id', $user->id);
            $request->session()->put('auth_usuario', $user->usuario);

            return redirect()
                ->route('login')
                ->with('status', 'Login realizado com sucesso!');
        }

        return back()
            ->withErrors([
                'usuario' => 'As credenciais fornecidas nao foram encontradas.',
            ])
            ->onlyInput('usuario');
    }


    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if (Hash::info($storedPassword)['algo'] !== null) {
            return Hash::check($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }
}
