<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación y gestión de la cuenta del usuario.
 *
 * La API es stateless: cada sesión se representa con un token personal de
 * Sanctum que la app móvil envía en la cabecera Authorization.
 */
class AuthController extends Controller
{
    /**
     * Registra una cuenta nueva y la deja lista para usar.
     *
     * Además de crear el usuario, siembra los catálogos por defecto que la
     * app necesita desde el primer uso (configuración de costos, categorías
     * de materiales, tipos de costo indirecto y prestaciones legales), de
     * modo que el usuario pueda calcular sin tener que configurar nada antes.
     */
    public function register(Request $request)
    {
        // Nombre y apellido solo admiten letras (incluidas tildes y ñ) y espacios.
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

        // Configuración de costos con los valores por defecto de la migración.
        $user->configuration()->create([]);

        $defaultCategories = [
            'Cuentas y abalorios',
            'Piedras',
            'Dijes y colgantes',
            'Herrajes y accesorios',
            'Cadenas',
            'Hilos y cordones',
            'Alambres',
            'Componentes para aretes',
            'Broches',
            'Otros materiales',
        ];

        $user->materialCategories()->createMany(
            array_map(fn (string $name) => ['name' => $name], $defaultCategories)
        );

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

        // Prestaciones legales colombianas con porcentajes de referencia sobre
        // el salario. Cada una se crea con su propio tipo para que el usuario
        // pueda editarlas, eliminarlas o agregar otras desde Configuraciones.
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

    /**
     * Valida las credenciales y emite un token de acceso nuevo.
     *
     * Las credenciales inválidas y las cuentas desactivadas se reportan como
     * error de validación (422) sobre el campo email, para que la app pueda
     * mostrarlos junto al formulario.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Mismo mensaje si el correo no existe o la contraseña no coincide,
        // para no revelar qué cuentas están registradas.
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

    /**
     * Revoca solo el token de la sesión actual; las sesiones abiertas en
     * otros dispositivos siguen activas.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Devuelve el perfil del usuario autenticado.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Actualiza los datos personales de forma parcial: solo se validan y
     * guardan los campos enviados ("sometimes"). El correo debe seguir siendo
     * único, excluyendo el del propio usuario.
     */
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

    /**
     * Cambia la contraseña previa verificación de la actual, para que una
     * sesión abierta en un dispositivo ajeno no baste para tomar la cuenta.
     */
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
