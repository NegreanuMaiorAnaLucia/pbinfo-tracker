<?php

use App\Jobs\SyncCatalogJob;
use App\Jobs\SyncUserProgressJob;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $running = SyncRun::query()
        ->where('type', SyncRun::TYPE_CATALOG)
        ->whereIn('status', [SyncRun::STATUS_PENDING, SyncRun::STATUS_RUNNING])
        ->exists();

    if ($running) {
        return;
    }

    $run = SyncRun::query()->create([
        'type' => SyncRun::TYPE_CATALOG,
        'status' => SyncRun::STATUS_PENDING,
    ]);

    SyncCatalogJob::dispatch($run->id);
})->dailyAt('03:30')->name('pbinfo-catalog-sync')->withoutOverlapping();

Schedule::call(function () {
    User::query()->whereNotNull('pbinfo_password')->each(function (User $user) {
        $running = SyncRun::query()
            ->where('user_id', $user->id)
            ->where('type', SyncRun::TYPE_PROGRESS)
            ->whereIn('status', [SyncRun::STATUS_PENDING, SyncRun::STATUS_RUNNING])
            ->exists();

        if ($running) {
            return;
        }

        $run = SyncRun::query()->create([
            'user_id' => $user->id,
            'type' => SyncRun::TYPE_PROGRESS,
            'status' => SyncRun::STATUS_PENDING,
        ]);

        SyncUserProgressJob::dispatch($user->id, $run->id);
    });
})->everyFourHours()->name('pbinfo-progress-sync')->withoutOverlapping();
