<?php

namespace Tests\Feature\Auth;

use App\Jobs\SyncUserProgressJob;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\PbInfo\Exceptions\PbInfoAuthException;
use App\Services\PbInfo\Exceptions\PbInfoRequestException;
use App\Services\PbInfo\PbInfoAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class PbInfoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_redirects_and_queues_progress_sync(): void
    {
        Bus::fake([SyncUserProgressJob::class]);

        $user = User::factory()->create([
            'username' => 'demo_user',
            'email' => 'demo_user@pbinfo.local',
        ]);

        $auth = Mockery::mock(PbInfoAuthService::class);
        $auth->shouldReceive('authenticate')
            ->once()
            ->with('demo_user', 'secret')
            ->andReturn($user);
        $this->app->instance(PbInfoAuthService::class, $auth);

        $response = $this->post('/login', [
            'username' => 'demo_user',
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('sync_runs', [
            'user_id' => $user->id,
            'type' => SyncRun::TYPE_PROGRESS,
            'status' => SyncRun::STATUS_PENDING,
        ]);
        Bus::assertDispatched(SyncUserProgressJob::class);
    }

    public function test_failed_pbinfo_auth_does_not_log_in(): void
    {
        $auth = Mockery::mock(PbInfoAuthService::class);
        $auth->shouldReceive('authenticate')
            ->once()
            ->andThrow(new PbInfoAuthException('Invalid PbInfo credentials.'));
        $this->app->instance(PbInfoAuthService::class, $auth);

        $response = $this->from('/login')->post('/login', [
            'username' => 'bad',
            'password' => 'bad',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_pbinfo_network_errors_show_form_error_not_500(): void
    {
        $auth = Mockery::mock(PbInfoAuthService::class);
        $auth->shouldReceive('authenticate')
            ->once()
            ->andThrow(new PbInfoRequestException('PbInfo returned HTTP 403 for POST /'));
        $this->app->instance(PbInfoAuthService::class, $auth);

        $response = $this->from('/login')->post('/login', [
            'username' => 'demo_user',
            'password' => 'secret',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
