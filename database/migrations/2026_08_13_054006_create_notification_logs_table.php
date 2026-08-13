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
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('borrowing_id')
                ->constrained()
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreignId('recipient_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('notification_type', 50);
            $table->string('channel', 32)->default('email');
            $table->dateTime('scheduled_for');
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 191)->unique();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->dateTime('next_attempt_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['status', 'scheduled_for']);
            $table->index(['status', 'next_attempt_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `notification_logs` ADD CONSTRAINT `notification_logs_type_check` CHECK (`notification_type` IN ('pengingat_h1', 'overdue'))");
            DB::statement("ALTER TABLE `notification_logs` ADD CONSTRAINT `notification_logs_status_check` CHECK (`status` IN ('pending', 'sent', 'failed', 'skipped'))");
        }
    }

    /**
     * This migration intentionally has no destructive rollback.
     */
    public function down(): void
    {
        // Notification delivery history is preserved by design.
    }
};
