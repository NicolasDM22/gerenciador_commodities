<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FormsController extends Controller
{
    public function salvar(Request $request)
    {
        // Aqui você trata o envio do formulário
        // Exemplo simples:
        return back()->with('success', 'Dados enviados com sucesso!');
    }
}
