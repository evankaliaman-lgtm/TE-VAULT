<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
            $table->dateTime('deactivated_at')->nullable();
        });
    }

    /**
     * This migration intentionally has no destructive rollback.
     */
    public function down(): void
    {
        // Account lifecycle data is preserved by design.
    }
};
