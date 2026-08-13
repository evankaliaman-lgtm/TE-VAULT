<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DomainDatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    public function test_guru_profile_can_be_linked_to_a_user(): void
    {
        $user = User::factory()->create();

        DB::table('guru_profiles')->insert([
            'user_id' => $user->id,
            'nip' => $this->identifier('NIP'),
        ]);

        $this->assertDatabaseHas('guru_profiles', ['user_id' => $user->id]);
    }

    public function test_siswa_profile_can_be_linked_to_a_user(): void
    {
        $user = User::factory()->create();

        DB::table('siswa_profiles')->insert([
            'user_id' => $user->id,
            'nis' => $this->identifier('NIS'),
        ]);

        $this->assertDatabaseHas('siswa_profiles', ['user_id' => $user->id]);
    }

    public function test_nip_must_be_unique(): void
    {
        $nip = $this->identifier('NIP');

        DB::table('guru_profiles')->insert([
            'user_id' => User::factory()->create()->id,
            'nip' => $nip,
        ]);

        $this->expectException(QueryException::class);

        DB::table('guru_profiles')->insert([
            'user_id' => User::factory()->create()->id,
            'nip' => $nip,
        ]);
    }

    public function test_nis_must_be_unique(): void
    {
        $nis = $this->identifier('NIS');

        DB::table('siswa_profiles')->insert([
            'user_id' => User::factory()->create()->id,
            'nis' => $nis,
        ]);

        $this->expectException(QueryException::class);

        DB::table('siswa_profiles')->insert([
            'user_id' => User::factory()->create()->id,
            'nis' => $nis,
        ]);
    }

    public function test_nisn_must_be_unique_when_present(): void
    {
        $nisn = $this->identifier('NISN');

        DB::table('siswa_profiles')->insert([
            'user_id' => User::factory()->create()->id,
            'nis' => $this->identifier('NIS'),
            'nisn' => $nisn,
        ]);

        $this->expectException(QueryException::class);

        DB::table('siswa_profiles')->insert([
            'user_id' => User::factory()->create()->id,
            'nis' => $this->identifier('NIS'),
            'nisn' => $nisn,
        ]);
    }

    public function test_asset_code_and_non_null_serial_number_must_be_unique(): void
    {
        $categoryId = $this->createCategory();
        $assetCode = $this->identifier('ASSET');
        $serialNumber = $this->identifier('SERIAL');

        $this->createAsset($categoryId, $assetCode, $serialNumber);

        $this->expectException(QueryException::class);

        $this->createAsset($categoryId, $assetCode, $this->identifier('SERIAL'));
    }

    public function test_non_null_serial_number_must_be_unique_while_multiple_nulls_are_allowed(): void
    {
        $categoryId = $this->createCategory();
        $serialNumber = $this->identifier('SERIAL');

        $this->createAsset($categoryId, $this->identifier('ASSET'), $serialNumber);
        $this->createAsset($categoryId, $this->identifier('ASSET'));
        $this->createAsset($categoryId, $this->identifier('ASSET'));

        $this->expectException(QueryException::class);

        $this->createAsset($categoryId, $this->identifier('ASSET'), $serialNumber);
    }

    public function test_asset_category_cannot_be_deleted_while_referenced_by_an_asset(): void
    {
        $categoryId = $this->createCategory();
        $this->createAsset($categoryId, $this->identifier('ASSET'));

        $this->expectException(QueryException::class);

        DB::table('asset_categories')->where('id', $categoryId)->delete();
    }

    public function test_borrowing_cannot_reference_a_missing_user(): void
    {
        $assetId = $this->createAsset($this->createCategory(), $this->identifier('ASSET'));

        $this->expectException(QueryException::class);

        $this->createBorrowing(999_999, $assetId);
    }

    public function test_borrowing_cannot_reference_a_missing_asset(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        $this->createBorrowing($user->id, 999_999);
    }

    public function test_notification_log_cannot_reference_a_missing_borrowing(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('notification_logs')->insert([
            'borrowing_id' => 999_999,
            'recipient_user_id' => $user->id,
            'notification_type' => 'pengingat_h1',
            'scheduled_for' => now(),
            'idempotency_key' => $this->identifier('NOTIFICATION'),
        ]);
    }

    public function test_active_asset_constraint_prevents_two_active_borrowings_for_the_same_asset(): void
    {
        $assetId = $this->createAsset($this->createCategory(), $this->identifier('ASSET'));

        $this->createBorrowing(User::factory()->create()->id, $assetId, ['status' => 'borrowed']);

        $this->expectException(QueryException::class);

        $this->createBorrowing(User::factory()->create()->id, $assetId, ['status' => 'approved']);
    }

    public function test_historical_returned_borrowings_are_allowed_for_the_same_asset(): void
    {
        $assetId = $this->createAsset($this->createCategory(), $this->identifier('ASSET'));

        $this->createBorrowing(User::factory()->create()->id, $assetId, [
            'status' => 'returned',
            'returned_at' => now(),
        ]);
        $this->createBorrowing(User::factory()->create()->id, $assetId, [
            'status' => 'returned',
            'returned_at' => now(),
        ]);

        $this->assertSame(2, DB::table('borrowings')->where('asset_id', $assetId)->count());
    }

    public function test_audit_log_can_be_created_without_an_actor_for_a_system_event(): void
    {
        DB::table('audit_logs')->insert([
            'action' => 'borrowing.overdue_detected',
            'entity_type' => 'borrowing',
            'entity_id' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'borrowing.overdue_detected',
            'actor_user_id' => null,
        ]);
    }

    public function test_soft_deleted_asset_retains_historical_borrowings(): void
    {
        $assetId = $this->createAsset($this->createCategory(), $this->identifier('ASSET'));

        $this->createBorrowing(User::factory()->create()->id, $assetId, [
            'status' => 'returned',
            'returned_at' => now(),
        ]);

        DB::table('assets')->where('id', $assetId)->update(['deleted_at' => now()]);

        $this->assertDatabaseHas('assets', ['id' => $assetId]);
        $this->assertSame(1, DB::table('borrowings')->where('asset_id', $assetId)->count());
    }

    private function createCategory(): int
    {
        return (int) DB::table('asset_categories')->insertGetId([
            'code' => $this->identifier('CATEGORY'),
            'name' => $this->identifier('Category'),
        ]);
    }

    private function createAsset(int $categoryId, string $assetCode, ?string $serialNumber = null): int
    {
        return (int) DB::table('assets')->insertGetId([
            'asset_category_id' => $categoryId,
            'asset_code' => $assetCode,
            'name' => $this->identifier('Asset'),
            'serial_number' => $serialNumber,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createBorrowing(int $borrowerUserId, int $assetId, array $overrides = []): int
    {
        return (int) DB::table('borrowings')->insertGetId(array_merge([
            'borrower_user_id' => $borrowerUserId,
            'asset_id' => $assetId,
            'requested_at' => Carbon::now(),
            'due_at' => Carbon::now()->addDays(3),
        ], $overrides));
    }

    private function identifier(string $prefix): string
    {
        $this->sequence++;

        return sprintf('%s-%d', $prefix, $this->sequence);
    }
}
