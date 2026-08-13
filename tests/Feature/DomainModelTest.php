<?php

namespace Tests\Feature;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\AssetCondition;
use App\Enums\BorrowingStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AuditLog;
use App\Models\Borrowing;
use App\Models\GuruProfile;
use App\Models\NotificationLog;
use App\Models\SiswaProfile;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_domain_enums_expose_only_the_approved_values(): void
    {
        $this->assertSame(['baik', 'rusak_ringan', 'rusak_berat'], $this->enumValues(AssetCondition::class));
        $this->assertSame(['tersedia', 'dipesan', 'dipinjam', 'perbaikan', 'tidak_tersedia'], $this->enumValues(AssetAvailabilityStatus::class));
        $this->assertSame(['pending', 'approved', 'rejected', 'cancelled', 'borrowed', 'return_pending_verification', 'returned'], $this->enumValues(BorrowingStatus::class));
        $this->assertSame(['pending', 'sent', 'failed', 'skipped'], $this->enumValues(NotificationStatus::class));
        $this->assertSame(['pengingat_h1', 'overdue'], $this->enumValues(NotificationType::class));
        $this->assertSame(['email'], $this->enumValues(NotificationChannel::class));
    }

    public function test_enum_casts_return_domain_enum_instances(): void
    {
        $asset = $this->createAsset(attributes: [
            'condition' => AssetCondition::RusakRingan,
            'availability_status' => AssetAvailabilityStatus::Perbaikan,
        ]);
        $borrowing = $this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Borrowed,
            'checkout_condition' => AssetCondition::Baik,
            'return_condition' => AssetCondition::RusakRingan,
        ]);
        $notificationLog = $this->createNotificationLog($borrowing, attributes: [
            'notification_type' => NotificationType::PengingatH1,
            'channel' => NotificationChannel::Email,
            'status' => NotificationStatus::Pending,
        ]);

        $this->assertInstanceOf(AssetCondition::class, $asset->fresh()->condition);
        $this->assertInstanceOf(AssetAvailabilityStatus::class, $asset->fresh()->availability_status);
        $this->assertInstanceOf(BorrowingStatus::class, $borrowing->fresh()->status);
        $this->assertInstanceOf(AssetCondition::class, $borrowing->fresh()->checkout_condition);
        $this->assertInstanceOf(AssetCondition::class, $borrowing->fresh()->return_condition);
        $this->assertInstanceOf(NotificationType::class, $notificationLog->fresh()->notification_type);
        $this->assertInstanceOf(NotificationChannel::class, $notificationLog->fresh()->channel);
        $this->assertInstanceOf(NotificationStatus::class, $notificationLog->fresh()->status);
    }

    public function test_datetime_and_audit_json_casts_return_the_expected_php_types(): void
    {
        $user = User::factory()->create(['deactivated_at' => now()]);
        $borrowing = $this->createBorrowing($user, attributes: [
            'borrowed_at' => now(),
            'approved_at' => now(),
            'rejected_at' => now(),
            'cancelled_at' => now(),
            'return_submitted_at' => now(),
            'returned_at' => now(),
            'return_verified_at' => now(),
        ])->fresh();
        $notificationLog = $this->createNotificationLog($borrowing, $user, attributes: [
            'last_attempt_at' => now(),
            'next_attempt_at' => now(),
            'sent_at' => now(),
        ])->fresh();
        $auditLog = $this->createAuditLog($user)->fresh();

        $this->assertInstanceOf(DateTimeInterface::class, $user->fresh()->deactivated_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->requested_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->borrowed_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->due_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->approved_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->rejected_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->cancelled_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->return_submitted_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->returned_at);
        $this->assertInstanceOf(DateTimeInterface::class, $borrowing->return_verified_at);
        $this->assertInstanceOf(DateTimeInterface::class, $notificationLog->scheduled_for);
        $this->assertInstanceOf(DateTimeInterface::class, $notificationLog->last_attempt_at);
        $this->assertInstanceOf(DateTimeInterface::class, $notificationLog->next_attempt_at);
        $this->assertInstanceOf(DateTimeInterface::class, $notificationLog->sent_at);
        $this->assertIsArray($auditLog->old_values);
        $this->assertIsArray($auditLog->new_values);
        $this->assertIsArray($auditLog->metadata);
    }

    public function test_user_relationships_resolve_profiles_borrowings_notifications_and_audit_logs(): void
    {
        $user = User::factory()->create();
        $guruProfile = GuruProfile::query()->create([
            'user_id' => $user->id,
            'nip' => $this->identifier('NIP'),
        ]);
        $siswaProfile = SiswaProfile::query()->create([
            'user_id' => $user->id,
            'nis' => $this->identifier('NIS'),
        ]);
        $borrowing = $this->createBorrowing($user);
        $notificationLog = $this->createNotificationLog($borrowing, $user);
        $auditLog = $this->createAuditLog($user);

        $this->assertTrue($user->guruProfile->is($guruProfile));
        $this->assertTrue($user->siswaProfile->is($siswaProfile));
        $this->assertTrue($user->borrowings->contains($borrowing));
        $this->assertTrue($user->notificationLogs->contains($notificationLog));
        $this->assertTrue($user->auditLogs->contains($auditLog));
    }

    public function test_asset_category_and_asset_relationships_resolve_correctly(): void
    {
        $category = $this->createCategory();
        $asset = $this->createAsset($category);
        $borrowing = $this->createBorrowing(asset: $asset);

        $this->assertTrue($category->assets->contains($asset));
        $this->assertTrue($asset->category->is($category));
        $this->assertTrue($asset->borrowings->contains($borrowing));
    }

    public function test_borrowing_relationships_resolve_all_participants_and_notification_logs(): void
    {
        $borrower = User::factory()->create();
        $approver = User::factory()->create();
        $rejector = User::factory()->create();
        $canceller = User::factory()->create();
        $verifier = User::factory()->create();
        $asset = $this->createAsset();
        $borrowing = $this->createBorrowing($borrower, $asset, [
            'approved_by_user_id' => $approver->id,
            'rejected_by_user_id' => $rejector->id,
            'cancelled_by_user_id' => $canceller->id,
            'return_verified_by_user_id' => $verifier->id,
        ]);
        $notificationLog = $this->createNotificationLog($borrowing, $borrower);

        $this->assertTrue($borrowing->borrower->is($borrower));
        $this->assertTrue($borrowing->asset->is($asset));
        $this->assertTrue($borrowing->approvedBy->is($approver));
        $this->assertTrue($borrowing->rejectedBy->is($rejector));
        $this->assertTrue($borrowing->cancelledBy->is($canceller));
        $this->assertTrue($borrowing->returnVerifiedBy->is($verifier));
        $this->assertTrue($borrowing->notificationLogs->contains($notificationLog));
        $this->assertTrue($approver->approvedBorrowings->contains($borrowing));
        $this->assertTrue($rejector->rejectedBorrowings->contains($borrowing));
        $this->assertTrue($canceller->cancelledBorrowings->contains($borrowing));
        $this->assertTrue($verifier->verifiedReturns->contains($borrowing));
    }

    public function test_notification_log_relationships_resolve_borrowing_and_recipient(): void
    {
        $recipient = User::factory()->create();
        $borrowing = $this->createBorrowing();
        $notificationLog = $this->createNotificationLog($borrowing, $recipient);

        $this->assertTrue($notificationLog->borrowing->is($borrowing));
        $this->assertTrue($notificationLog->recipient->is($recipient));
    }

    public function test_audit_log_actor_relationship_resolves_correctly(): void
    {
        $actor = User::factory()->create();
        $auditLog = $this->createAuditLog($actor);

        $this->assertTrue($auditLog->actor->is($actor));
    }

    public function test_pending_borrowing_with_past_due_date_is_not_overdue(): void
    {
        $this->assertFalse($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Pending,
            'due_at' => now()->subMinute(),
        ])->isOverdue());
    }

    public function test_approved_borrowing_with_past_due_date_is_not_overdue(): void
    {
        $this->assertFalse($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Approved,
            'due_at' => now()->subMinute(),
        ])->isOverdue());
    }

    public function test_borrowed_borrowing_with_past_due_date_is_overdue(): void
    {
        $this->assertTrue($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Borrowed,
            'due_at' => now()->subMinute(),
        ])->isOverdue());
    }

    public function test_return_pending_verification_borrowing_with_past_due_date_is_overdue(): void
    {
        $this->assertTrue($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::ReturnPendingVerification,
            'due_at' => now()->subMinute(),
        ])->isOverdue());
    }

    public function test_returned_borrowing_with_past_due_date_is_not_overdue(): void
    {
        $this->assertFalse($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Returned,
            'due_at' => now()->subMinute(),
            'returned_at' => now(),
        ])->isOverdue());
    }

    public function test_borrowed_borrowing_with_future_due_date_is_not_overdue(): void
    {
        $this->assertFalse($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Borrowed,
            'due_at' => now()->addMinute(),
        ])->isOverdue());
    }

    public function test_borrowed_borrowing_with_a_recorded_return_is_not_overdue(): void
    {
        $this->assertFalse($this->createBorrowing(attributes: [
            'status' => BorrowingStatus::Borrowed,
            'due_at' => now()->subMinute(),
            'returned_at' => now(),
        ])->isOverdue());
    }

    private function createCategory(): AssetCategory
    {
        return AssetCategory::query()->create([
            'code' => $this->identifier('category'),
            'name' => $this->identifier('Category'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createAsset(?AssetCategory $category = null, array $attributes = []): Asset
    {
        $category ??= $this->createCategory();

        return Asset::query()->create(array_merge([
            'asset_category_id' => $category->id,
            'asset_code' => $this->identifier('asset'),
            'name' => $this->identifier('Asset'),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createBorrowing(?User $borrower = null, ?Asset $asset = null, array $attributes = []): Borrowing
    {
        $borrower ??= User::factory()->create();
        $asset ??= $this->createAsset();

        return Borrowing::query()->create(array_merge([
            'borrower_user_id' => $borrower->id,
            'asset_id' => $asset->id,
            'status' => BorrowingStatus::Pending,
            'requested_at' => now(),
            'due_at' => now()->addDays(3),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createNotificationLog(Borrowing $borrowing, ?User $recipient = null, array $attributes = []): NotificationLog
    {
        $recipient ??= $borrowing->borrower;

        return NotificationLog::query()->create(array_merge([
            'borrowing_id' => $borrowing->id,
            'recipient_user_id' => $recipient->id,
            'notification_type' => NotificationType::PengingatH1,
            'channel' => NotificationChannel::Email,
            'scheduled_for' => now(),
            'status' => NotificationStatus::Pending,
            'idempotency_key' => $this->identifier('notification'),
        ], $attributes));
    }

    private function createAuditLog(?User $actor = null): AuditLog
    {
        return AuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'action' => 'asset.created',
            'entity_type' => 'asset',
            'entity_id' => 1,
            'old_values' => ['availability_status' => null],
            'new_values' => ['availability_status' => 'tersedia'],
            'metadata' => ['source' => 'test'],
        ]);
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     * @return list<string>
     */
    private function enumValues(string $enum): array
    {
        return array_map(
            static fn (BackedEnum $case): string => $case->value,
            $enum::cases(),
        );
    }

    private function identifier(string $prefix): string
    {
        $this->sequence++;

        return sprintf('%s-%d', $prefix, $this->sequence);
    }
}
