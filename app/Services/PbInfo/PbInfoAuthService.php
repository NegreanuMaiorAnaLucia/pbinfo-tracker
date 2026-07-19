<?php

namespace App\Services\PbInfo;

use App\Models\User;
use App\Services\PbInfo\Exceptions\PbInfoAuthException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PbInfoAuthService
{
    public function __construct(private PbInfoClient $client) {}

    /**
     * @throws PbInfoAuthException
     */
    public function authenticate(string $username, string $password): User
    {
        $result = $this->client->login($username, $password);

        // Identity comes from the credentials the user typed, not homepage scrapes.
        $canonical = $username;
        if (
            is_string($result['username'] ?? null)
            && strcasecmp($result['username'], $username) === 0
        ) {
            $canonical = $result['username'];
        }

        $user = User::query()->firstOrNew(['username' => $canonical]);

        $user->fill([
            'name' => $canonical,
            'email' => $user->email ?: Str::lower($canonical).'@pbinfo.local',
            'pbinfo_password' => $password,
            'pbinfo_cookies' => $result['cookies'],
            'pbinfo_cookies_at' => now(),
        ]);

        if (! $user->exists) {
            $user->password = Hash::make(Str::random(40));
        }

        $user->save();

        return $user;
    }
}
