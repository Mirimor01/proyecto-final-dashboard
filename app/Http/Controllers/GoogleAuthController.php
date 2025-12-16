<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            // 1. Recibimos el usuario de Google
            $googleUser = Socialite::driver('google')->user();

            // 2. Buscamos si ya existe por su ID de Google
            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                // Si no existe, buscamos por correo (por si ya se había registrado antes)
                $user = User::where('email', $googleUser->email)->first();
                
                if ($user) {
                    // Si existe el correo, le agregamos el ID de Google
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                    ]);
                } else {
                    // Si no existe ni correo ni ID, creamos uno nuevo
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'password' => bcrypt('123456dummy'), // Contraseña temporal
                    ]);
                }
            }

            // 3. ¡AQUÍ ESTÁ LA MAGIA! Iniciamos sesión manualmente
            Auth::login($user);

            // 4. Lo mandamos al Dashboard
            return redirect('/dashboard');

        } catch (\Exception $e) {
            return "Error en el login: " . $e->getMessage();
        }
    }
}