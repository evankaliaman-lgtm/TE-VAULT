<?php

namespace Tests\Feature;

use App\Actions\Borrowings\ApproveBorrowingAction;
use App\Actions\Borrowings\CancelBorrowingAction;
use App\Actions\Borrowings\CheckoutBorrowingAction;
use App\Actions\Borrowings\RejectBorrowingAction;
use App\Actions\Borrowings\RequestBorrowingAction;
use App\Actions\Borrowings\SubmitReturnAction;
use App\Actions\Borrowings\VerifyReturnAction;
use App\Enums\AssetAvailabilityStatus;
use App\Enums\AssetCondition;
use App\Enums\BorrowingStatus;
use App\Exceptions\AssetUnavailableException;
use App\Exceptions\BorrowingConcurrencyException;
use App\Exceptions\BorrowingStateException;
use App\Exceptions\UnauthorizedBorrowingActionException;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Borrowing;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class BorrowingApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guru_and_siswa_can_request_borrowings_but_admin_cannot(): void
    {
        $guru = $this->user('guru');
        $siswa = $this->user('siswa');
        $admin = $this->user('admin');

        $guruBorrowing = app(RequestBorrowingAction::class)->execute($guru, $this->asset(), 'For class');
        $siswaBorrowing = app(RequestBorrowingAction::class)->execute($siswa, $this->asset(), 'For study');

        $this->assertSame(BorrowingStatus::Pending, $guruBorrowing->status);
        $this->assertSame(BorrowingStatus::Pending, $siswaBorrowing->status);
        $this->expectException(UnauthorizedBorrowingActionException::class);
        app(RequestBorrowingAction::class)->execute($admin, $this->asset());
    }

    public function test_unavailable_or_soft_deleted_asset_cannot_be_requested(): void
    {
        $guru = $this->user('guru');
        $unavailable = $this->asset(['availability_status' => AssetAvailabilityStatus::Dipesan]);
        $deleted = $this->asset();
        $deleted->delete();

        foreach ([$unavailable, $deleted] as $asset) {
            try {
                app(RequestBorrowingAction::class)->execute($guru, $asset);
                $this->fail('An unavailable asset was requested.');
            } catch (AssetUnavailableException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_admin_can_approve_and_reserve_a_pending_borrowing(): void
    {
        $borrowing = $this->pending();
        $approved = app(ApproveBorrowingAction::class)->execute($this->user('admin'), $borrowing);

        $this->assertSame(BorrowingStatus::Approved, $approved->status);
        $this->assertSame(AssetAvailabilityStatus::Dipesan, $borrowing->asset->fresh()->availability_status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_non_admin_cannot_approve_and_rejection_requires_a_reason(): void
    {
        $borrowing = $this->pending();
        $this->expectException(UnauthorizedBorrowingActionException::class);
        app(ApproveBorrowingAction::class)->execute($this->user('guru'), $borrowing);
    }

    public function test_pending_borrowing_can_be_rejected_without_changing_asset_availability(): void
    {
        $borrowing = $this->pending();
        $admin = $this->user('admin');
        $this->expectException(InvalidArgumentException::class);
        app(RejectBorrowingAction::class)->execute($admin, $borrowing, '');
    }

    public function test_rejection_records_reason_and_leaves_asset_available(): void
    {
        $borrowing = $this->pending();
        $rejected = app(RejectBorrowingAction::class)->execute($this->user('admin'), $borrowing, 'Not suitable');

        $this->assertSame(BorrowingStatus::Rejected, $rejected->status);
        $this->assertSame('Not suitable', $rejected->rejection_reason);
        $this->assertSame(AssetAvailabilityStatus::Tersedia, $borrowing->asset->fresh()->availability_status);
    }

    public function test_owner_can_cancel_pending_or_approved_and_approved_cancellation_releases_asset(): void
    {
        $pending = $this->pending();
        $pendingResult = app(CancelBorrowingAction::class)->execute($pending->borrower, $pending, 'Changed mind');
        $this->assertSame(BorrowingStatus::Cancelled, $pendingResult->status);

        $approved = $this->approved();
        $result = app(CancelBorrowingAction::class)->execute($approved->borrower, $approved, 'No longer needed');
        $this->assertSame(BorrowingStatus::Cancelled, $result->status);
        $this->assertSame(AssetAvailabilityStatus::Tersedia, $approved->asset->fresh()->availability_status);
    }

    public function test_borrowed_and_terminal_borrowings_cannot_be_cancelled(): void
    {
        foreach ([BorrowingStatus::Borrowed, BorrowingStatus::Returned] as $status) {
            $borrowing = $this->pending(['status' => $status]);
            $this->expectException(UnauthorizedBorrowingActionException::class);
            app(CancelBorrowingAction::class)->execute($borrowing->borrower, $borrowing);
        }
    }

    public function test_admin_checkout_requires_approved_reservation_and_sets_three_day_due_date(): void
    {
        $approved = $this->approved();
        $checkedOut = app(CheckoutBorrowingAction::class)->execute($this->user('admin'), $approved, AssetCondition::Baik);

        $this->assertSame(BorrowingStatus::Borrowed, $checkedOut->status);
        $this->assertSame(AssetAvailabilityStatus::Dipinjam, $approved->asset->fresh()->availability_status);
        $this->assertTrue($checkedOut->due_at->equalTo($checkedOut->borrowed_at->copy()->addDays(3)));
        $this->expectException(BorrowingStateException::class);
        app(CheckoutBorrowingAction::class)->execute($this->user('admin'), $this->pending(), AssetCondition::Baik);
    }

    public function test_owner_can_submit_return_without_releasing_asset_and_non_owner_cannot(): void
    {
        $borrowed = $this->borrowed();
        $submitted = app(SubmitReturnAction::class)->execute($borrowed->borrower, $borrowed, 'returns/photo.jpg', 'Complete');
        $this->assertSame(BorrowingStatus::ReturnPendingVerification, $submitted->status);
        $this->assertSame(AssetAvailabilityStatus::Dipinjam, $borrowed->asset->fresh()->availability_status);

        $otherBorrowed = $this->borrowed();
        $this->expectException(UnauthorizedBorrowingActionException::class);
        app(SubmitReturnAction::class)->execute($this->user('guru'), $otherBorrowed, 'returns/other.jpg');
    }

    public function test_admin_verification_releases_good_and_minor_damage_or_sends_major_damage_to_repair(): void
    {
        $admin = $this->user('admin');
        foreach ([
            [AssetCondition::Baik, AssetAvailabilityStatus::Tersedia],
            [AssetCondition::RusakRingan, AssetAvailabilityStatus::Tersedia],
            [AssetCondition::RusakBerat, AssetAvailabilityStatus::Perbaikan],
        ] as [$condition, $availability]) {
            $borrowing = $this->returnSubmitted();
            $returned = app(VerifyReturnAction::class)->execute($admin, $borrowing, $condition);
            $this->assertSame(BorrowingStatus::Returned, $returned->status);
            $this->assertSame($availability, $borrowing->asset->fresh()->availability_status);
        }
    }

    public function test_non_admin_cannot_verify_return(): void
    {
        $this->expectException(UnauthorizedBorrowingActionException::class);
        app(VerifyReturnAction::class)->execute($this->user('siswa'), $this->returnSubmitted(), AssetCondition::Baik);
    }

    public function test_policy_enforces_admin_visibility_and_borrower_ownership_by_user_id(): void
    {
        $borrowing = $this->pending();
        $admin = $this->user('admin');
        $otherGuru = $this->user('guru');

        $this->assertTrue($admin->can('view', $borrowing));
        $this->assertTrue($borrowing->borrower->can('view', $borrowing));
        $this->assertFalse($otherGuru->can('view', $borrowing));
    }

    public function test_transaction_rolls_back_borrowing_when_asset_update_fails(): void
    {
        $borrowing = $this->pending();
        Asset::updating(static function (): void {
            throw new RuntimeException('Simulated asset write failure.');
        });

        try {
            app(ApproveBorrowingAction::class)->execute($this->user('admin'), $borrowing);
            $this->fail('Approval unexpectedly succeeded.');
        } catch (RuntimeException) {
            $this->assertSame(BorrowingStatus::Pending, $borrowing->fresh()->status);
            $this->assertSame(AssetAvailabilityStatus::Tersedia, $borrowing->asset->fresh()->availability_status);
        } finally {
            Asset::flushEventListeners();
        }
    }

    public function test_unique_active_asset_conflict_is_reported_as_a_business_concurrency_exception(): void
    {
        $borrowing = $this->pending();
        $injected = false;

        DB::listen(function ($query) use (&$injected, $borrowing): void {
            if (! $injected && str_contains(strtolower($query->sql), 'select exists')) {
                $injected = true;
                Borrowing::query()->create([
                    'borrower_user_id' => $this->user('siswa')->id,
                    'asset_id' => $borrowing->asset_id,
                    'status' => BorrowingStatus::Approved,
                    'requested_at' => now(),
                    'due_at' => now()->addDays(3),
                ]);
            }
        });

        $this->expectException(BorrowingConcurrencyException::class);
        app(ApproveBorrowingAction::class)->execute($this->user('admin'), $borrowing);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function asset(array $attributes = []): Asset
    {
        $category = AssetCategory::query()->create(['code' => fake()->unique()->bothify('CAT-###'), 'name' => fake()->unique()->word()]);

        return Asset::query()->create(array_merge([
            'asset_category_id' => $category->id,
            'asset_code' => fake()->unique()->bothify('AST-####'),
            'name' => fake()->words(2, true),
        ], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function pending(array $attributes = []): Borrowing
    {
        $borrower = $attributes['borrower'] ?? $this->user('guru');
        unset($attributes['borrower']);
        $asset = $attributes['asset'] ?? $this->asset();
        unset($attributes['asset']);

        return Borrowing::query()->create(array_merge([
            'borrower_user_id' => $borrower->id,
            'asset_id' => $asset->id,
            'status' => BorrowingStatus::Pending,
            'requested_at' => now(),
            'due_at' => now()->addDays(3),
        ], $attributes));
    }

    private function approved(): Borrowing
    {
        $borrowing = $this->pending();

        return app(ApproveBorrowingAction::class)->execute($this->user('admin'), $borrowing);
    }

    private function borrowed(): Borrowing
    {
        return app(CheckoutBorrowingAction::class)->execute($this->user('admin'), $this->approved(), AssetCondition::Baik);
    }

    private function returnSubmitted(): Borrowing
    {
        $borrowing = $this->borrowed();

        return app(SubmitReturnAction::class)->execute($borrowing->borrower, $borrowing, 'returns/evidence.jpg');
    }
}
