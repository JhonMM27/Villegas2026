<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request){
        // Validar los datos del formulario
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 🔐 Clave por email + IP
        $key = Str::lower($request->email) . '|' . $request->ip();

        // 🚫 Límite: 5 intentos por minuto
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()
                ->view('autenticacion.login', [
                    'error' => "Demasiados intentos. Intente nuevamente en {$seconds} segundos."
                ], 429);
        }

        RateLimiter::hit($key, 60);

        // Verificar si el usuario existe y está activo
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) { 
            RateLimiter::clear($key);  

            $user = Auth::user();
            if ($user->activo) {
                return redirect()->route('dashboard'); // Redirigir al dashboard si es correcto
            } else {
                Auth::logout();
                return response()
                ->view('autenticacion.login', [
                    'error' => 'Su cuenta está inactiva. Contacte al administrador.'
                ], 403);
            }
        }
        return response()
        ->view('autenticacion.login', [
            'error' => 'Las credenciales no son correctas.'
        ], 401);
    }
}
