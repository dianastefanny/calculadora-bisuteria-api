<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    // La app muestra estos mensajes tal cual al usuario, por eso van en español.
    private const MESSAGES = [
        'email.required' => 'Ingresa tu correo electrónico.',
        'email.email' => 'Ingresa un correo electrónico válido.',
        'code.required' => 'Ingresa el código que enviamos a tu correo.',
        'code.size' => 'El código debe tener 6 dígitos.',
        'password.required' => 'Ingresa la nueva contraseña.',
        'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
        'password.confirmed' => 'Las contraseñas ingresadas no coinciden.',
    ];

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ], self::MESSAGES);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_codes')->updateOrInsert(
                ['email' => $user->email],
                [
                    'code' => Hash::make($code),
                    'expires_at' => Carbon::now()->addMinutes(15),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

            Mail::to($user->email)->send(new PasswordResetCodeMail($code));
        }

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un código de verificación.',
        ]);
    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ], self::MESSAGES);

        $record = DB::table('password_reset_codes')
            ->where('email', $validated['email'])
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['code'], $record->code)) {
            return response()->json([
                'message' => 'El código de verificación no es válido o ya venció. Solicita uno nuevo.',
            ], 422);
        }

        return response()->json(['message' => 'Código verificado correctamente.']);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], self::MESSAGES);

        $record = DB::table('password_reset_codes')
            ->where('email', $validated['email'])
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['code'], $record->code)) {
            return response()->json([
                'message' => 'El código de verificación no es válido o ya venció. Solicita uno nuevo.',
            ], 422);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_codes')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
