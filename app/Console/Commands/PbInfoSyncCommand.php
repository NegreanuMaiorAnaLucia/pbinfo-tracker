<?php

namespace App\Console\Commands;

use App\Jobs\SyncCatalogJob;
use App\Jobs\SyncUserProgressJob;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Console\Command;

class PbInfoSyncCommand extends Command
{
    protected $signature = 'pbinfo:sync {type : catalog|progress} {--user= : Username for progress sync} {--sync : Run inline instead of queue}';

    protected $description = 'Dispatch or run a PbInfo catalog/progress sync';

    public function handle(): int
    {
        $type = $this->argument('type');

        if ($type === 'catalog') {
            $run = SyncRun::query()->create([
                'type' => SyncRun::TYPE_CATALOG,
                'status' => SyncRun::STATUS_PENDING,
            ]);

            if ($this->option('sync')) {
                SyncCatalogJob::dispatchSync($run->id);
            } else {
                SyncCatalogJob::dispatch($run->id);
            }

            $this->info("Catalog sync #{$run->id} started.");

            return self::SUCCESS;
        }

        if ($type === 'progress') {
            $username = $this->option('user');
            $query = User::query()->whereNotNull('pbinfo_password');
            if ($username) {
                $query->where('username', $username);
            }

            $users = $query->get();
            if ($users->isEmpty()) {
                $this->error('No matching users found.');

                return self::FAILURE;
            }

            foreach ($users as $user) {
                $run = SyncRun::query()->create([
                    'user_id' => $user->id,
                    'type' => SyncRun::TYPE_PROGRESS,
                    'status' => SyncRun::STATUS_PENDING,
                ]);

                if ($this->option('sync')) {
                    SyncUserProgressJob::dispatchSync($user->id, $run->id);
                } else {
                    SyncUserProgressJob::dispatch($user->id, $run->id);
                }

                $this->info("Progress sync #{$run->id} for @{$user->username} started.");
            }

            return self::SUCCESS;
        }

        $this->error('Type must be catalog or progress.');

        return self::FAILURE;
    }
}
