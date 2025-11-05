<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'telefone', 'endereco', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return redirect()->route('login');
        }

        $avatarUrl = $this->resolveAvatarUrl($user);

        $previousAnalyses = collect([
        ]);

        $chartData = [
            'labels' => ['09/25', '10/25', '11/25', '12/25', '01/26', '02/26', '03/26', '04/26'],
            'prices' => [60, 58, 57, 57.5, 59, 62, 56, 52],
            'commodityName' => 'Exemplo'
        ];

        $isAdmin = (bool) ($user->is_admin ?? false);
        $adminData = ['notifications' => collect()];

        if ($isAdmin) {
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

        return view('home', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'previousAnalyses' => $previousAnalyses,
            'chartData' => $chartData,
            'isAdmin' => $isAdmin,
            'adminData' => $adminData,
        ]);
    }

    public function update(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return redirect()->route('login');

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) return redirect()->route('login');

        $data = $request->validate(
            [
                'usuario' => ['required', 'string', 'max:191', Rule::unique('users', 'usuario')->ignore($userId)],
                'nome' => ['nullable', 'string', 'max:191'],
                'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
                'telefone' => ['required', 'string', 'regex:/^[0-9\\-\\s\\(\\)\\+]{10,20}$/'],
                'endereco' => ['required', 'string', 'min:5', 'max:255'],
                'nova_senha' => ['nullable', 'string', 'min:6', 'confirmed'],
                'foto' => ['nullable', 'image', 'max:2048'],
            ],
            [
                'telefone.regex' => 'Informe um telefone valido (apenas numeros, +, () e -).',
            ]
        );

        $normalizedPhone = $this->normalizePhone($data['telefone']);
        if (strlen($normalizedPhone) < 10 || strlen($normalizedPhone) > 15) {
            return back()
                ->withErrors(['telefone' => 'O telefone deve conter entre 10 e 15 digitos.'])
                ->withInput($request->except('foto'));
        }

        $normalizedAddress = $this->normalizeAddress($data['endereco']);

        $updatePayload = [
            'usuario' => $data['usuario'],
            'nome' => $data['nome'] ?? null,
            'email' => $data['email'],
            'telefone' => $normalizedPhone,
            'endereco' => $normalizedAddress,
            'updated_at' => now(),
        ];

        if (!empty($data['nova_senha'])) {
            $updatePayload['senha'] = Hash::make($data['nova_senha']);
        }

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $updatePayload['foto_blob'] = file_get_contents($file->getRealPath());
            $updatePayload['foto_mime'] = $file->getMimeType();
        }

        DB::table('users')->where('id', $userId)->update($updatePayload);

        return redirect()->route('home')->with('status', 'Perfil atualizado com sucesso!');
    }
}
