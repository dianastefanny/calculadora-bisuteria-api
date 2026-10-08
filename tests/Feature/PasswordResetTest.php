<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // Pide el código y devuelve el que llegó al correo (falso) del usuario.
    private function requestCode(User $user): string
    {
        Mail::fake();

        $this->postJson('/api/password/forgot', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('message', 'Si el correo está registrado, te enviamos un código de verificación.');

        $code = null;
        Mail::assertSent(PasswordResetCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    public function test_user_can_reset_password_with_valid_code(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);

        $this->postJson('/api/password/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertOk()
            ->assertJsonPath('message', 'Código verificado correctamente.');

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'NuevaClave#2026',
            'password_confirmation' => 'NuevaClave#2026',
        ])->assertOk()->assertJsonPath('message', 'Contraseña actualizada correctamente.');

        $this->assertTrue(Hash::check('NuevaClave#2026', $user->fresh()->password));
    }

    public function test_code_expires_after_15_minutes(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user);

        // A los 14 minutos todavía sirve...
        $this->travel(14)->minutes();
        $this->postJson('/api/password/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertOk();

        // ...pasados los 15 ya no.
        $this->travel(2)->minutes();
        $this->postJson('/api/password/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El código de verificación no es válido o ya venció. Solicita uno nuevo.');
    }

    public function test_requesting_a_new_code_invalidates_the_previous_one(): void
    {
        $user = User::factory()->create();
        $first = $this->requestCode($user);
        $second = $this->requestCode($user);

        if ($first === $second) {
            $this->markTestSkipped('Los dos códigos aleatorios coincidieron por casualidad.');
        }

        $this->postJson('/api/password/verify-code', ['email' => $user->email, 'code' => $first])
            ->assertUnprocessable();
        $this->postJson('/api/password/verify-code', ['email' => $user->email, 'code' => $second])
            ->assertOk();
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/password/verify-code', ['email' => $user->email, 'code' => '12345'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'El código debe tener 6 dígitos.');

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'NuevaClave#2026',
            'password_confirmation' => 'OtraClave#2026',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Las contraseñas ingresadas no coinciden.');
    }
}
