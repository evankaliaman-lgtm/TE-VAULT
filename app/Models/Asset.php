<?php

namespace App\Models;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\AssetCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'asset_category_id',
        'asset_code',
        'name',
        'brand',
        'model',
        'serial_number',
        'condition',
        'availability_status',
        'photo_path',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition' => AssetCondition::class,
            'availability_status' => AssetAvailabilityStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }
}
