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
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_category_id')
                ->constrained()
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->string('asset_code', 64)->unique();
            $table->string('name', 150)->index();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 128)->nullable()->unique();
            $table->string('condition', 32)->default('baik')->index();
            $table->string('availability_status', 32)->default('tersedia');
            $table->string('photo_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index(['availability_status', 'asset_category_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `assets` ADD CONSTRAINT `assets_condition_check` CHECK (`condition` IN ('baik', 'rusak_ringan', 'rusak_berat'))");
            DB::statement("ALTER TABLE `assets` ADD CONSTRAINT `assets_availability_status_check` CHECK (`availability_status` IN ('tersedia', 'dipesan', 'dipinjam', 'perbaikan', 'tidak_tersedia'))");
        }
    }

    /**
     * This migration intentionally has no destructive rollback.
     */
    public function down(): void
    {
        // Asset records are preserved by design.
    }
};
