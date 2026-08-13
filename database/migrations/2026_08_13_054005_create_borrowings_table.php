<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('borrower_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('asset_id')
                ->constrained()
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('status', 32)->default('pending');
            $table->dateTime('requested_at');
            $table->dateTime('borrowed_at')->nullable();
            $table->dateTime('due_at');

            $table->string('borrowing_evidence_path', 255)->nullable();
            $table->text('borrower_note')->nullable();

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_note')->nullable();

            $table->foreignId('rejected_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->string('checkout_condition', 32)->nullable();

            $table->dateTime('return_submitted_at')->nullable();
            $table->string('return_evidence_path', 255)->nullable();
            $table->text('return_note')->nullable();

            $table->dateTime('returned_at')->nullable();
            $table->string('return_condition', 32)->nullable();

            $table->foreignId('return_verified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->dateTime('return_verified_at')->nullable();
            $table->text('return_verification_note')->nullable();

            $table->unsignedBigInteger('active_asset_id')->storedAs("CASE WHEN `status` IN ('approved', 'borrowed', 'return_pending_verification') THEN `asset_id` ELSE NULL END");

            $table->timestamps();

            $table->index(['borrower_user_id', 'created_at']);
            $table->index(['asset_id', 'requested_at']);
            $table->index(['status', 'due_at']);
            $table->unique('active_asset_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `borrowings` ADD CONSTRAINT `borrowings_status_check` CHECK (`status` IN ('pending', 'approved', 'rejected', 'cancelled', 'borrowed', 'return_pending_verification', 'returned'))");
            DB::statement("ALTER TABLE `borrowings` ADD CONSTRAINT `borrowings_checkout_condition_check` CHECK (`checkout_condition` IS NULL OR `checkout_condition` IN ('baik', 'rusak_ringan', 'rusak_berat'))");
            DB::statement("ALTER TABLE `borrowings` ADD CONSTRAINT `borrowings_return_condition_check` CHECK (`return_condition` IS NULL OR `return_condition` IN ('baik', 'rusak_ringan', 'rusak_berat'))");
        }
    }

    /**
     * This migration intentionally has no destructive rollback.
     */
    public function down(): void
    {
        // Borrowing history is preserved by design.
    }
};
