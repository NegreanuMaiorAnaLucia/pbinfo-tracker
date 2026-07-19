<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    public const TYPE_CATALOG = 'catalog';

    public const TYPE_PROGRESS = 'progress';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'processed_count',
        'created_count',
        'updated_count',
        'error_log',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_count' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
            'error_log' => null,
        ]);
    }

    public function markSuccess(int $processed = 0, int $created = 0, int $updated = 0): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'processed_count' => $processed,
            'created_count' => $created,
            'updated_count' => $updated,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_log' => mb_substr($error, 0, 4000),
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark abandoned pending/running runs as failed so they cannot block Sync now forever
     * (e.g. database queue with no worker, or a crashed request).
     */
    public static function expireStale(int $olderThanSeconds = 120, ?int $userId = null, ?string $type = null): int
    {
        $query = static::query()
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_RUNNING])
            ->where('created_at', '<', now()->subSeconds($olderThanSeconds));

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        $count = 0;
        foreach ($query->get() as $run) {
            $run->markFailed('Sync timed out or was abandoned (no queue worker / interrupted). Try Sync again.');
            $count++;
        }

        return $count;
    }
}
