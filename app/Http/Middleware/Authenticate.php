<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request; // <-- Pastikan ini ada

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // ===================================================================
        // INI ADALAH KODE PERBAIKANNYA
        // ===================================================================
        //
        // Jika request BUKAN request web (misalnya request API dari Flutter),
        // JANGAN redirect ke route 'login' (HTML).
        // Biarkan Laravel melempar Exception, yang akan ditangkap
        // oleh Exception Handler dan diubah menjadi JSON error 401.
        
        if (! $request->expectsJson()) {
            // Ini adalah perilaku default untuk web, biarkan saja
            return route('login'); 
        }

        // Jika request adalah JSON (dari Flutter), fungsi ini akan
        // me-return null, dan Laravel akan otomatis mengirim
        // respons error JSON 401 "Unauthenticated".
    }
}