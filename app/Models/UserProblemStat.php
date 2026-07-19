<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProblemStat extends Model
{
    public const STATUS_UNSOLVED = 'unsolved';

    public const STATUS_ATTEMPTED = 'attempted';

    public const STATUS_SOLVED = 'solved';

    protected $fillable = [
        'user_id',
        'problem_id',
        'best_score',
        'attempts',
        'status',
        'last_submission_at',
    ];

    protected function casts(): array
    {
        return [
            'best_score' => 'integer',
            'attempts' => 'integer',
            'last_submission_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }

    public static function statusFromScore(int $score): string
    {
        if ($score >= 100) {
            return self::STATUS_SOLVED;
        }

        if ($score > 0) {
            return self::STATUS_ATTEMPTED;
        }

        return self::STATUS_UNSOLVED;
    }
}
