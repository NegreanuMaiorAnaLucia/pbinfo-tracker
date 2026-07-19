<?php

namespace App\Jobs;

use App\Models\SyncRun;
use App\Services\PbInfo\CatalogSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCatalogJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(public int $syncRunId) {}

    public function handle(CatalogSyncService $service): void
    {
        $run = SyncRun::query()->findOrFail($this->syncRunId);

        try {
            $service->sync($run);
        } catch (\Throwable $e) {
            Log::error('Catalog sync failed', ['error' => $e->getMessage(), 'run' => $this->syncRunId]);
            throw $e;
        }
    }
}
