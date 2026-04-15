<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_managed_through_the_api(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        Category::factory()->create(['name' => 'Skola']);

        Sanctum::actingAs($user);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('categories.0.name', 'Skola');

        Sanctum::actingAs($admin);

        $storeResponse = $this->postJson('/api/categories', [
            'name' => 'Praca',
            'color' => '#FF8800',
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('category.name', 'Praca');

        $categoryId = $storeResponse->json('category.id');

        $this->getJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('category.name', 'Praca');

        $this->putJson("/api/categories/{$categoryId}", [
            'name' => 'Osobne',
            'color' => '#00AAFF',
        ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Osobne');

        $this->deleteJson("/api/categories/{$categoryId}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }
}
