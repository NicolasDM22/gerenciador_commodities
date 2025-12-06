<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings/servicos de aplicacao no container (vazio por padrao).
     */
    public function register(): void
    {
        //
    }

    /**
     * Executa configuracoes de inicializacao da aplicacao (placeholder para hooks globais).
     */
    public function boot(): void
    {
        //
    }
}
