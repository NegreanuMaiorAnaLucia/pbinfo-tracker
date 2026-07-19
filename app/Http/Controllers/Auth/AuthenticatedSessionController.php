<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncUserProgressJob;
use App\Models\SyncRun;
use App\Services\PbInfo\Exceptions\PbInfoAuthException;
use App\Services\PbInfo\PbInfoAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request, PbInfoAuthService $authService): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $key = 'pbinfo-login:'.$request->ip().'|'.Str::lower($credentials['username']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        try {
            $user = $authService->authenticate($credentials['username'], $credentials['password']);
        } catch (PbInfoAuthException $e) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'username' => $e->getMessage(),
            ]);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Never fail login because progress sync failed (sync queue / Neon / PbInfo).
        try {
            $run = SyncRun::query()->create([
                'user_id' => $user->id,
                'type' => SyncRun::TYPE_PROGRESS,
                'status' => SyncRun::STATUS_PENDING,
            ]);

            SyncUserProgressJob::dispatch($user->id, $run->id)->afterResponse();
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
