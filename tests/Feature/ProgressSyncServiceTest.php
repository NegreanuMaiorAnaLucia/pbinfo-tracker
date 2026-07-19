<?php

namespace Tests\Feature;

use App\Models\Problem;
use App\Models\SyncRun;
use App\Models\User;
use App\Models\UserProblemStat;
use App\Services\PbInfo\ProgressSyncService;
use App\Services\PbInfo\PbInfoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProgressSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_upserts_stats_from_journal(): void
    {
        $payload = json_decode(file_get_contents(base_path('tests/Fixtures/pbinfo/journal.json')), true);

        $client = Mockery::mock(PbInfoClient::class);
        $client->shouldReceive('fetchJournal')->once()->andReturn(collect($payload['content'])->map(fn ($row) => [
            'id' => (int) $row['id'],
            'denumire' => $row['denumire'],
            'scor' => (int) $row['scor'],
            'data' => $row['data'],
        ])->all());
        $client->shouldReceive('aggregateJournal')->once()->andReturnUsing(function (array $entries) {
            return (new PbInfoClient(requestDelayMs: 0))->aggregateJournal($entries);
        });

        $this->app->instance(PbInfoClient::class, $client);

        $user = User::factory()->create([
            'username' => 'demo_user',
            'pbinfo_password' => 'secret',
            'pbinfo_cookies' => ['SSID' => 'abc'],
        ]);

        $run = SyncRun::query()->create([
            'user_id' => $user->id,
            'type' => SyncRun::TYPE_PROGRESS,
            'status' => SyncRun::STATUS_PENDING,
        ]);

        app(ProgressSyncService::class)->sync($user, $run);

        $this->assertSame(SyncRun::STATUS_SUCCESS, $run->fresh()->status);
        $this->assertSame(3, Problem::query()->count());
        $this->assertSame(1, UserProblemStat::query()->where('status', UserProblemStat::STATUS_SOLVED)->count());
        $this->assertSame(1, UserProblemStat::query()->where('status', UserProblemStat::STATUS_ATTEMPTED)->count());
    }
}
