<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Verifica si hay al menos un admin en la DB
    public function systemCheck()
    {
        $adminExists = User::where('role', 'admin')->exists();
        return response()->json(['adminExists' => $adminExists]);
    }

    // Registra al primer administrador (Setup)
    public function registerAdmin(Request $request)
    {
        if (User::where('role', 'admin')->exists()) {
            return response()->json(['error' => 'Ya existe un administrador en el sistema.'], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.'
        ]);

        $persona = Persona::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
        ]);

        $user = User::create([
            'persona_id' => $persona->id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    // Registro público para clientes
    public function registerClient(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'ci_nit' => 'nullable|string|max:20|regex:/^[0-9]{4,15}(-[a-zA-Z0-9]{1,3})?(\s[a-zA-Z]{2,3})?$/',
            'razon_social' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:15',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'ci_nit.regex' => 'El CI/NIT debe comenzar con números (Ej. 1234567, 1234567-1P, 1234567 LP).',
            'email.email' => 'Debes ingresar un correo electrónico válido.'
        ]);

        $persona = Persona::create([
            'nombre' => $request->nombre,
            'apellidos' => $request->apellidos,
            'ci_nit' => $request->ci_nit,
            'razon_social' => $request->razon_social,
            'telefono' => $request->telefono,
        ]);

        $user = User::create([
            'persona_id' => $persona->id,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client',
            'loyalty_points' => 0,
            'delivery_route_day' => $request->has('delivery_route_day') ? $request->delivery_route_day : rand(1, 7)
        ]);

        $user->load('persona');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::with('persona')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['access_token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'nombre' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:50|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'ci_nit' => 'nullable|string|max:20|regex:/^[0-9]{4,15}(-[a-zA-Z0-9]{1,3})?(\s[a-zA-Z]{2,3})?$/',
            'razon_social' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:15',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'ci_nit.regex' => 'El CI/NIT debe comenzar con números (Ej. 1234567, 1234567-1P, 1234567 LP).',
            'email.email' => 'Debes ingresar un correo electrónico válido.'
        ]);

        $user->email = $request->email;
        if ($request->has('delivery_route_day')) {
            $user->delivery_route_day = $request->delivery_route_day;
        }
        $user->save();

        if ($user->persona) {
            $user->persona->nombre = $request->nombre;
            $user->persona->apellidos = $request->apellidos;
            $user->persona->ci_nit = $request->ci_nit;
            $user->persona->razon_social = $request->razon_social;
            $user->persona->telefono = $request->telefono;
            $user->persona->save();
        }

        return response()->json(['message' => 'Perfil actualizado correctamente', 'user' => $user->load('persona')]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'La contraseña actual no es correcta'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }
}
