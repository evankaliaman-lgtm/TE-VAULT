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
        Schema::create('siswa_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete()
                ->restrictOnUpdate();
            $table->string('nis', 30)->unique();
            $table->string('nisn', 30)->nullable()->unique();
            $table->string('class_name', 100)->nullable()->index();
            $table->string('phone', 32)->nullable();
            $table->timestamps();
        });
    }

    /**
     * This migration intentionally has no destructive rollback.
     */
    public function down(): void
    {
        // Siswa profile history is preserved by design.
    }
};
