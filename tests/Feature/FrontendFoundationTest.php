<?php

namespace Tests\Feature;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\BorrowingStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Borrowing;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_view_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_sees_admin_dashboard_and_navigation(): void
    {
        $admin = $this->user('admin');
        $this->asset();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('Total assets')
            ->assertSee('Asset Management')
            ->assertSee('Audit Logs')
            ->assertDontSee('My Borrowings');
    }

    public function test_guru_sees_only_guru_navigation_and_own_summary(): void
    {
        $guru = $this->user('guru');
        $this->borrowing($guru);
        $this->borrowing($this->user('siswa'));

        $this->actingAs($guru)->get('/dashboard')
            ->assertOk()
            ->assertSee('Guru dashboard')
            ->assertSee('My Borrowings')
            ->assertDontSee('Asset Management')
            ->assertSee('>1<', false);
    }

    public function test_siswa_sees_siswa_dashboard_and_cannot_see_admin_navigation(): void
    {
        $siswa = $this->user('siswa');

        $this->actingAs($siswa)->get('/dashboard')
            ->assertOk()
            ->assertSee('Siswa dashboard')
            ->assertSee('My Borrowings')
            ->assertDontSee('Borrowing Approval')
            ->assertDontSee('Audit Logs');
    }

    public function test_sidebar_uses_asset_permission_for_asset_navigation(): void
    {
        $this->actingAs($this->user('guru'))->get('/dashboard')->assertSee('Assets');
    }

    public function test_admin_summary_uses_existing_domain_data(): void
    {
        $asset = $this->asset();
        $admin = $this->user('admin');
        Borrowing::query()->create(['borrower_user_id' => $this->user('guru')->id, 'asset_id' => $asset->id, 'status' => BorrowingStatus::Pending, 'requested_at' => now(), 'due_at' => now()->addDays(3)]);

        $this->actingAs($admin)->get('/dashboard')->assertSee('>1<', false);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function asset(): Asset
    {
        $category = AssetCategory::query()->create(['code' => fake()->unique()->bothify('CAT-###'), 'name' => fake()->unique()->word()]);

        return Asset::query()->create(['asset_category_id' => $category->id, 'asset_code' => fake()->unique()->bothify('AST-####'), 'name' => 'Asset', 'availability_status' => AssetAvailabilityStatus::Tersedia]);
    }

    private function borrowing(User $user): Borrowing
    {
        return Borrowing::query()->create(['borrower_user_id' => $user->id, 'asset_id' => $this->asset()->id, 'status' => BorrowingStatus::Pending, 'requested_at' => now(), 'due_at' => now()->addDays(3)]);
    }
}
