<?php

namespace Tests\Feature;

use App\Actions\Borrowings\ApproveBorrowingAction;
use App\Actions\Borrowings\CheckoutBorrowingAction;
use App\Actions\Borrowings\RequestBorrowingAction;
use App\Enums\AssetCondition;
use App\Jobs\SendBorrowingNotificationJob;
use App\Mail\BorrowingApprovedMail;
use App\Mail\BorrowingOverdueMail;
use App\Mail\BorrowingRejectedMail;
use App\Mail\BorrowingReminderMail;
use App\Mail\ReturnVerifiedMail;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Borrowing;
use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HttpLayerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/assets')->assertUnauthorized();
    }

    #[DataProvider('invalidAssetPayloads')]
    public function test_asset_creation_validates_input(array $payload): void
    {
        $this->actingAs($this->user('admin'))->postJson('/api/admin/assets', $payload)->assertUnprocessable();
    }

    public static function invalidAssetPayloads(): array
    {
        return [[[]], [['asset_code' => 'A']], [['asset_category_id' => 999, 'asset_code' => 'A', 'name' => 'Asset', 'condition' => 'invalid']], [['asset_category_id' => 999, 'asset_code' => 'A', 'name' => 'Asset', 'condition' => 'baik']], [['asset_category_id' => 1, 'asset_code' => 'A', 'name' => 'Asset', 'condition' => 'invalid']]];
    }

    public function test_admin_can_create_and_audit_asset(): void
    {
        $category = $this->category();
        $this->actingAs($admin = $this->user('admin'))->postJson('/api/admin/assets', ['asset_category_id' => $category->id, 'asset_code' => 'HTTP-1', 'name' => 'Camera', 'condition' => 'baik'])->assertSuccessful()->assertJsonPath('data.asset_code', 'HTTP-1');
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $admin->id, 'action' => 'asset.created']);
    }

    public function test_admin_can_update_asset(): void
    {
        $asset = $this->asset();
        $this->actingAs($this->user('admin'))->patchJson('/api/admin/assets/'.$asset->id, ['name' => 'Updated'])->assertSuccessful()->assertJsonPath('data.name', 'Updated');
    }

    public function test_admin_can_soft_delete_asset(): void
    {
        $asset = $this->asset();
        $this->actingAs($this->user('admin'))->deleteJson('/api/admin/assets/'.$asset->id)->assertNoContent();
        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_guru_can_request_borrowing_and_request_is_audited(): void
    {
        $asset = $this->asset();
        $guru = $this->user('guru');
        $this->actingAs($guru)->postJson('/api/borrowings', ['asset_id' => $asset->id, 'borrower_note' => 'Class'])->assertSuccessful()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $guru->id, 'action' => 'borrowing.requested']);
    }

    public function test_borrowing_request_requires_an_asset(): void
    {
        $this->actingAs($this->user('guru'))->postJson('/api/borrowings', [])->assertUnprocessable();
    }

    public function test_non_admin_cannot_approve_borrowing(): void
    {
        $borrowing = $this->pending();
        $this->actingAs($this->user('guru'))->postJson('/api/admin/borrowings/'.$borrowing->id.'/approve')->assertForbidden();
    }

    public function test_admin_approval_audits_and_queues_mail(): void
    {
        Mail::fake();
        Bus::fake();
        $borrowing = $this->pending();
        $this->actingAs($this->user('admin'))->postJson('/api/admin/borrowings/'.$borrowing->id.'/approve')->assertSuccessful();
        $this->assertDatabaseHas('audit_logs', ['action' => 'borrowing.approved']);
        Mail::assertQueued(BorrowingApprovedMail::class);
        Bus::assertDispatched(SendBorrowingNotificationJob::class);
    }

    public function test_notification_service_is_idempotent(): void
    {
        Bus::fake();
        $borrowing = $this->borrowed();
        $service = app(NotificationService::class);
        $service->scheduleReminder($borrowing);
        $service->scheduleReminder($borrowing);
        $this->assertDatabaseCount('notification_logs', 1);
        Bus::assertDispatchedTimes(SendBorrowingNotificationJob::class, 1);
    }

    public function test_notification_job_sends_email(): void
    {
        Mail::fake();
        $log = app(NotificationService::class)->scheduleOverdue($this->borrowed());
        (new SendBorrowingNotificationJob($log))->handle();
        Mail::assertSent(BorrowingOverdueMail::class);
        $this->assertSame('sent', $log->fresh()->status->value);
    }

    #[DataProvider('mailables')]
    public function test_mailable_classes_build(object $mail): void
    {
        $this->assertNotEmpty($mail->build()->subject);
    }

    public static function mailables(): array
    {
        $borrowing = new Borrowing(['id' => 1]);

        return [[new BorrowingApprovedMail($borrowing)], [new BorrowingRejectedMail($borrowing)], [new BorrowingReminderMail($borrowing)], [new BorrowingOverdueMail($borrowing)], [new ReturnVerifiedMail($borrowing)]];
    }

    #[DataProvider('apiResources')]
    public function test_api_resource_classes_exist(string $resource): void
    {
        $this->assertTrue(class_exists($resource));
    }

    public static function apiResources(): array
    {
        return array_map(static fn (string $resource): array => [$resource], ['App\\Http\\Resources\\AssetResource', 'App\\Http\\Resources\\AssetCategoryResource', 'App\\Http\\Resources\\BorrowingResource', 'App\\Http\\Resources\\NotificationLogResource', 'App\\Http\\Resources\\AuditLogResource', 'App\\Http\\Resources\\UserResource', 'App\\Http\\Resources\\GuruProfileResource', 'App\\Http\\Resources\\SiswaProfileResource']);
    }

    public function test_scheduler_registers_borrowing_tasks(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('borrowing-reminders')->expectsOutputToContain('borrowing-overdue')->assertExitCode(0);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function category(): AssetCategory
    {
        return AssetCategory::query()->create(['code' => fake()->unique()->bothify('CAT-###'), 'name' => fake()->unique()->word()]);
    }

    private function asset(): Asset
    {
        return Asset::query()->create(['asset_category_id' => $this->category()->id, 'asset_code' => fake()->unique()->bothify('AST-####'), 'name' => 'Asset']);
    }

    private function pending(): Borrowing
    {
        return app(RequestBorrowingAction::class)->execute($this->user('guru'), $this->asset());
    }

    private function borrowed(): Borrowing
    {
        $borrowing = $this->pending();
        app(ApproveBorrowingAction::class)->execute($this->user('admin'), $borrowing);

        return app(CheckoutBorrowingAction::class)->execute($this->user('admin'), $borrowing->fresh(), AssetCondition::Baik);
    }
}
