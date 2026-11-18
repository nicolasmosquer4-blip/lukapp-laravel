<?php

namespace Tests;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function seedCategories(): void
    {
        $this->seed(CategorySeeder::class);
    }

    protected function category(string $name, string $type = 'gasto'): Category
    {
        return Category::where('name', $name)->where('type', $type)->firstOrFail();
    }

    /** Usuario autenticado por token Sanctum (como un cliente Postman). */
    protected function apiUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->settingsOrDefault();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function apiAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $admin->settingsOrDefault();
        Sanctum::actingAs($admin);

        return $admin;
    }
}
