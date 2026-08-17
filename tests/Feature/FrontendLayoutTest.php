<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_dashboard_renders_the_application_shell_and_admin_navigation(): void
    {
        $this->actingAs($this->user('admin'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('TE-VAULT')
            ->assertSee('Asset Management')
            ->assertSee('Borrowing Approval');
    }

    public function test_guru_dashboard_hides_admin_navigation_and_shows_borrower_navigation(): void
    {
        $this->actingAs($this->user('guru'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Guru dashboard')
            ->assertSee('My Borrowings')
            ->assertDontSee('Asset Management');
    }

    public function test_siswa_dashboard_hides_admin_navigation_and_shows_borrower_navigation(): void
    {
        $this->actingAs($this->user('siswa'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Siswa dashboard')
            ->assertSee('My Borrowings')
            ->assertDontSee('Audit Logs');
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
