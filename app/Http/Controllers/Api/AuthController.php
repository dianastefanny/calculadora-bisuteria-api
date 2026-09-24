<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'email' => 'required|string|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
        ]);

        // Crea la configuración por defecto automáticamente
        $user->configuration()->create([]);

        // Crea el catálogo de categorías de materiales por defecto
        $defaultCategories = [
            'Cuentas y abalorios',
            'Piedras',
            'Dijes y colgantes',
            'Herrajes y accesorios',
            'Cadenas',
            'Hilos y cordones',
            'Alambres',
            'Componentes para aretes',
            'Otros materiales',
        ];

        $user->materialCategories()->createMany(
            array_map(fn (string $name) => ['name' => $name], $defaultCategories)
        );

        // Crea el catálogo de tipos de costo indirecto por defecto
        $defaultCostTypes = [
            'Arriendo',
            'Servicios públicos',
            'Transporte',
            'Publicidad y marketing',
            'Otros gastos',
        ];

        $user->costTypes()->createMany(
            array_map(fn (string $name) => ['name' => $name], $defaultCostTypes)
        );

        // Crea el catálogo de prestaciones legales por defecto (porcentajes
        // de referencia aproximados, no editables por ahora — el usuario solo
        // decide si incluirlas o no al calcular, desde el interruptor de
        // Cálculos).
        $defaultBenefits = [
            'ARL' => 0.52,
            'Vacaciones' => 4.17,
            'Cesantías' => 8.33,
            'Salud' => 8.5,
            'Pensión' => 12,
        ];

        foreach ($defaultBenefits as $name => $percentage) {
            $benefitType = $user->benefitTypes()->create(['name' => $name]);

            $user->benefits()->create([
                'benefit_type_id' => $benefitType->id,
                'name' => $name,
                'percentage' => $percentage,
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is inactive.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[\pL\s]+$/u'],
            'email' => [
                'sometimes', 'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
