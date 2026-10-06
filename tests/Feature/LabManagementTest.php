<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin(): void
    {
        $this->get('/admin/labs')->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_lab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/labs', [
                'code' => 'ГМЗ-9',
                'name_ru' => 'Завод 9',
                'name_uz' => 'Zavod 9',
                'name_en' => 'Plant 9',
                'status' => 'soon',
                'url' => null,
                'sort_order' => 10,
            ])
            ->assertRedirect('/admin/labs');

        $this->assertDatabaseHas('labs', ['code' => 'ГМЗ-9']);
    }

    public function test_admin_can_update_and_delete_lab(): void
    {
        $user = User::factory()->create();
        $lab = Lab::create([
            'code' => 'ЦНИЛ', 'name_ru' => 'ЦНИЛ', 'name_uz' => 'MITL',
            'name_en' => 'CRL', 'status' => 'soon', 'url' => null, 'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put("/admin/labs/{$lab->id}", [
                'code' => 'ЦНИЛ',
                'name_ru' => 'ЦНИЛ', 'name_uz' => 'MITL', 'name_en' => 'CRL',
                'status' => 'live', 'url' => 'http://cnil.test', 'sort_order' => 1,
            ])
            ->assertRedirect('/admin/labs');

        $this->assertDatabaseHas('labs', ['code' => 'ЦНИЛ', 'status' => 'live']);

        $this->actingAs($user)->delete("/admin/labs/{$lab->id}");
        $this->assertDatabaseMissing('labs', ['id' => $lab->id]);
    }
}
