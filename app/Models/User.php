<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'pbinfo_password',
    'pbinfo_cookies',
    'pbinfo_cookies_at',
    'last_sync_status',
    'last_sync_at',
    'last_sync_error',
])]
#[Hidden(['password', 'remember_token', 'pbinfo_password', 'pbinfo_cookies'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pbinfo_password' => 'encrypted',
            'pbinfo_cookies' => 'encrypted:array',
            'pbinfo_cookies_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }

    public function problemStats(): HasMany
    {
        return $this->hasMany(UserProblemStat::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }
}
