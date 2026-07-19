<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Problem extends Model
{
    protected $fillable = [
        'pbinfo_id',
        'category_id',
        'title',
        'slug',
        'difficulty',
        'url',
        'metadata',
        'source_hash',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(UserProblemStat::class);
    }
}
