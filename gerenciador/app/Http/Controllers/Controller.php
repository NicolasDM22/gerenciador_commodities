<?php
/**
 * Controller.php by Nicolas Duran Munhos
 *
 * Controlador base com helpers reutilizados pelos demais controladores.
 */
namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Monta a URL de avatar para um usuario:
     * - Se houver blob de foto + mime type, devolve um data URL convertendo o blob em base64.
     * - Caso contrario, usa o servico ui-avatars com o nome/usuario ou "User" como fallback.
     */
    protected function resolveAvatarUrl(object $user): string
    {
        if (!empty($user->foto_blob) && !empty($user->foto_mime)) {
            return 'data:' . $user->foto_mime . ';base64,' . base64_encode($user->foto_blob); // ADICIONE base64_encode()
        }

        $displayName = $user->nome ?? $user->usuario ?? 'User';

        return 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=d4d4d4&color=2b2b2b';
    }

    /**
     * Remove tudo que nao for numero para padronizar o armazenamento do telefone.
     */
    protected function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Normaliza endereco eliminando espacos duplicados e aparando o inicio/fim.
     */
    protected function normalizeAddress(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
