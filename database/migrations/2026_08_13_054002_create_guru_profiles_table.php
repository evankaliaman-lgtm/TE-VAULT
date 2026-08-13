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
        Schema::create('guru_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->string('nip', 30)->unique();
            $table->string('phone', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * This migration intentionally has no destructive rollback.
     */
    public function down(): void
    {
        // Guru profile history is preserved by design.
    }
};
