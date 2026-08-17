<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendModulePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_render_asset_category_asset_and_borrowing_review_pages(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/asset-categories')->assertOk()->assertSee('Asset Categories');
        $this->actingAs($this->user('admin'))
            ->get('/assets')->assertOk()->assertSee('Assets');
        $this->actingAs($this->user('admin'))
            ->get('/admin/borrowings/pending')->assertOk()->assertSee('Pending Requests');
    }

    public function test_borrower_can_render_borrowing_pages_but_not_admin_category_page(): void
    {
        $this->actingAs($this->user('guru'))
            ->get('/borrowings')->assertOk()->assertSee('My Borrowings');
        $this->actingAs($this->user('guru'))
            ->get('/asset-categories')->assertForbidden();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
