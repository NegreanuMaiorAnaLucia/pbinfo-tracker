<?php

namespace App\Jobs;

use App\Models\SyncRun;
use App\Models\User;
use App\Services\PbInfo\ProgressSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncUserProgressJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $userId, public int $syncRunId) {}

    public function handle(ProgressSyncService $service): void
    {
        $user = User::query()->findOrFail($this->userId);
        $run = SyncRun::query()->findOrFail($this->syncRunId);

        try {
            $service->sync($user, $run);
        } catch (\Throwable $e) {
            Log::error('Progress sync failed', [
                'user' => $this->userId,
                'run' => $this->syncRunId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
