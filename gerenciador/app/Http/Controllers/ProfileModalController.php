<?php
//by Gustavo Cavalheiro
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class ProfileModalController extends Controller
{
    public function update(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return redirect()->route('login');
        }

        $data = $request->validate(
            [
                'usuario' => ['required', 'string', 'max:191', Rule::unique('users', 'usuario')->ignore($userId)],
                'nome' => ['nullable', 'string', 'max:191'],
                'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
                'telefone' => ['required', 'string', 'regex:/^[0-9\-\s\(\)\+]{10,20}$/'],
                'endereco' => ['nullable', 'string', 'min:5', 'max:255'],
                'nova_senha' => ['nullable', 'string', 'min:6', 'confirmed'],
                'foto' => ['nullable', 'image', 'max:2048'],
            ],
            [
                'telefone.regex' => 'Informe um telefone valido (apenas numeros, +, () e -).',
            ]
        );

        $normalizedPhone = $this->limparTelefone($data['telefone']);

        if (strlen($normalizedPhone) < 10 || strlen($normalizedPhone) > 15) {
            return back()
                ->withErrors(['telefone' => 'O telefone deve conter entre 10 e 15 digitos.'])
                ->withInput($request->except('foto'));
        }

        $normalizedAddress = ''; 
        
        if (!empty($data['endereco'])) {
            $normalizedAddress = $this->limparEndereco($data['endereco']);
        }

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
            $fotoFile = $request->file('foto');
            
            // CORREÇÃO AQUI: Adicionado base64_encode
            $fotoBlob = base64_encode(file_get_contents($fotoFile->getRealPath()));
            $fotoMime = $fotoFile->getClientMimeType();

            $updatePayload['foto_blob'] = $fotoBlob;
            $updatePayload['foto_mime'] = $fotoMime;
        }

        DB::table('users')->where('id', $userId)->update($updatePayload);

        return redirect()->route('home')->with('status', 'Perfil atualizado com sucesso!');
    }

    private function limparTelefone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    private function limparEndereco($address)
    {
        return trim(mb_strtoupper($address, 'UTF-8'));
    }
}