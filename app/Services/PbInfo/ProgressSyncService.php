<?php

namespace App\Services\PbInfo;

use App\Models\Category;
use App\Models\Problem;
use App\Models\SyncRun;
use App\Models\User;
use App\Models\UserProblemStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgressSyncService
{
    public function __construct(private PbInfoClient $client) {}

    public function sync(User $user, SyncRun $run): void
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');

        $run->markRunning();

        try {
            $cookies = $user->pbinfo_cookies ?? [];

            try {
                $entries = $this->client->fetchJournal($user->username, $cookies);
            } catch (\Throwable) {
                $login = $this->client->login($user->username, $user->pbinfo_password);
                $user->forceFill([
                    'pbinfo_cookies' => $login['cookies'],
                    'pbinfo_cookies_at' => now(),
                ])->save();
                $entries = $this->client->fetchJournal($user->username, $login['cookies']);
            }

            $aggregated = $this->client->aggregateJournal($entries);
            $created = 0;
            $updated = 0;

            DB::transaction(function () use ($aggregated, $user, &$created, &$updated) {
                foreach ($aggregated as $row) {
                    $problem = Problem::query()->firstOrCreate(
                        ['pbinfo_id' => $row['id']],
                        [
                            'title' => $row['title'],
                            'slug' => Str::slug($row['title']) ?: null,
                            'url' => PbInfoClient::BASE_URL.'/probleme/'.$row['id'],
                        ]
                    );

                    $stat = UserProblemStat::query()->firstOrNew([
                        'user_id' => $user->id,
                        'problem_id' => $problem->id,
                    ]);

                    $wasNew = ! $stat->exists;
                    $stat->best_score = $row['best_score'];
                    $stat->attempts = $row['attempts'];
                    $stat->status = UserProblemStat::statusFromScore($row['best_score']);
                    $stat->last_submission_at = $this->parseDate($row['last_submission_at']);
                    $stat->save();

                    $wasNew ? $created++ : $updated++;
                }
            });

            $user->forceFill([
                'last_sync_status' => SyncRun::STATUS_SUCCESS,
                'last_sync_at' => now(),
                'last_sync_error' => null,
            ])->save();

            $run->markSuccess(count($aggregated), $created, $updated);
        } catch (\Throwable $e) {
            $user->forceFill([
                'last_sync_status' => SyncRun::STATUS_FAILED,
                'last_sync_at' => now(),
                'last_sync_error' => $e->getMessage(),
            ])->save();

            $run->markFailed($e->getMessage());

            throw $e;
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
