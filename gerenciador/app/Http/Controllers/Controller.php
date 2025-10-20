<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Build a usable avatar URL for the given user record.
     */
    protected function resolveAvatarUrl(object $user): string
    {
        if (!empty($user->foto_blob) && !empty($user->foto_mime)) {
            return 'data:' . $user->foto_mime . ';base64,' . $user->foto_blob;
        }

        $displayName = $user->nome ?? $user->usuario ?? 'User';

        return 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=d4d4d4&color=2b2b2b';
    }
}
