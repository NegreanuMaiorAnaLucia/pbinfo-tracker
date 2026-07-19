<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCatalogJob;
use App\Jobs\SyncUserProgressJob;
use App\Models\SyncRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function progress(Request $request): RedirectResponse
    {
        $user = $request->user();

        $running = SyncRun::query()
            ->where('user_id', $user->id)
            ->where('type', SyncRun::TYPE_PROGRESS)
            ->whereIn('status', [SyncRun::STATUS_PENDING, SyncRun::STATUS_RUNNING])
            ->exists();

        if ($running) {
            return back()->with('status', 'A progress sync is already running.');
        }

        $run = SyncRun::query()->create([
            'user_id' => $user->id,
            'type' => SyncRun::TYPE_PROGRESS,
            'status' => SyncRun::STATUS_PENDING,
        ]);

        SyncUserProgressJob::dispatch($user->id, $run->id)->afterResponse();

        return back()->with('status', 'Progress sync started.');
    }

    public function catalog(Request $request): RedirectResponse
    {
        $running = SyncRun::query()
            ->where('type', SyncRun::TYPE_CATALOG)
            ->whereIn('status', [SyncRun::STATUS_PENDING, SyncRun::STATUS_RUNNING])
            ->exists();

        if ($running) {
            return back()->with('status', 'Catalog sync is already running.');
        }

        $run = SyncRun::query()->create([
            'type' => SyncRun::TYPE_CATALOG,
            'status' => SyncRun::STATUS_PENDING,
        ]);

        SyncCatalogJob::dispatch($run->id)->afterResponse();

        return back()->with('status', 'Catalog sync started.');
    }
}
