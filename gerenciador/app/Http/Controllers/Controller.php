<?php
/**
 * Controller.php by Nicolas Duran Munhos
 */
namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Build a usable avatar URL for the given user record.
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
     * Remove caracteres nao numericos do telefone.
     */
    protected function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Normaliza endereco removendo espacos redundantes.
     */
    protected function normalizeAddress(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
