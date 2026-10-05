<?php

namespace Tests\Feature;

use App\Models\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesignImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_replace_design_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $design = Design::factory()->for($user)->create();

        $first = $this->actingAs($user)->postJson("/api/designs/{$design->id}/image", [
            'image' => UploadedFile::fake()->image('collar.png', 800, 800),
        ]);

        $first->assertOk()->assertJsonPath('id', $design->id);
        $firstPath = $design->fresh()->image_path;
        Storage::disk('public')->assertExists($firstPath);
        $this->assertStringEndsWith('/storage/' . $firstPath, $first->json('image_url'));

        // Al reemplazar la foto, el archivo anterior se borra
        $this->actingAs($user)->postJson("/api/designs/{$design->id}/image", [
            'image' => UploadedFile::fake()->image('collar-nuevo.png', 800, 800),
        ])->assertOk();

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($design->fresh()->image_path);
    }

    public function test_user_can_delete_design_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $design = Design::factory()->for($user)->create();

        $this->actingAs($user)->postJson("/api/designs/{$design->id}/image", [
            'image' => UploadedFile::fake()->image('collar.png'),
        ]);
        $path = $design->fresh()->image_path;

        $this->actingAs($user)->deleteJson("/api/designs/{$design->id}/image")
            ->assertOk()
            ->assertJsonPath('image_url', null);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($design->fresh()->image_path);
    }

    public function test_rejects_files_that_are_not_images_or_too_large(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $design = Design::factory()->for($user)->create();

        $this->actingAs($user)->postJson("/api/designs/{$design->id}/image", [
            'image' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable();

        $this->actingAs($user)->postJson("/api/designs/{$design->id}/image", [
            'image' => UploadedFile::fake()->image('enorme.png')->size(3000),
        ])->assertUnprocessable();

        $this->assertNull($design->fresh()->image_path);
    }

    public function test_user_cannot_change_image_of_another_users_design(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $design = Design::factory()->for($owner)->create();

        $this->actingAs($other)->postJson("/api/designs/{$design->id}/image", [
            'image' => UploadedFile::fake()->image('collar.png'),
        ])->assertForbidden();

        $this->actingAs($other)->deleteJson("/api/designs/{$design->id}/image")
            ->assertForbidden();
    }
}
