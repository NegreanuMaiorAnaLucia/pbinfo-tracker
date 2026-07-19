<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\SyncRun;
use App\Models\UserProblemStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $totalProblems = Problem::query()->count();
        $solved = UserProblemStat::query()->where('user_id', $user->id)->where('status', UserProblemStat::STATUS_SOLVED)->count();
        $attempted = UserProblemStat::query()->where('user_id', $user->id)->where('status', UserProblemStat::STATUS_ATTEMPTED)->count();
        $touched = UserProblemStat::query()->where('user_id', $user->id)->count();

        $percent = $totalProblems > 0 ? round(($solved / $totalProblems) * 100, 1) : 0;

        $recent = UserProblemStat::query()
            ->with('problem:id,pbinfo_id,title,url')
            ->where('user_id', $user->id)
            ->whereNotNull('last_submission_at')
            ->orderByDesc('last_submission_at')
            ->limit(8)
            ->get()
            ->map(fn (UserProblemStat $stat) => [
                'id' => $stat->problem?->pbinfo_id,
                'title' => $stat->problem?->title,
                'url' => $stat->problem?->url,
                'score' => $stat->best_score,
                'status' => $stat->status,
                'at' => optional($stat->last_submission_at)?->toIso8601String(),
            ]);

        $latestSync = SyncRun::query()
            ->where('user_id', $user->id)
            ->where('type', SyncRun::TYPE_PROGRESS)
            ->latest('id')
            ->first();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $totalProblems,
                'solved' => $solved,
                'attempted' => $attempted,
                'untouched' => max(0, $totalProblems - $touched),
                'percent' => $percent,
            ],
            'recent' => $recent,
            'sync' => [
                'status' => $user->last_sync_status ?? $latestSync?->status,
                'at' => optional($user->last_sync_at)?->toIso8601String(),
                'error' => $user->last_sync_error,
                'run_status' => $latestSync?->status,
            ],
        ]);
    }
}
