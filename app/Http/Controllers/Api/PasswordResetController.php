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
        public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

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
            'message' => 'If that email is registered, a verification code has been sent.',
        ]);
    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $validated['email'])
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['code'], $record->code)) {
            return response()->json([
                'message' => 'The verification code is invalid or has expired.',
            ], 422);
        }

        return response()->json(['message' => 'Code verified successfully.']);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $validated['email'])
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast() || ! Hash::check($validated['code'], $record->code)) {
            return response()->json([
                'message' => 'The verification code is invalid or has expired.',
            ], 422);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_codes')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
