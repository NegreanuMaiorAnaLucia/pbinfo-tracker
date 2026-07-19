<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->text('pbinfo_password')->nullable()->after('password');
            $table->text('pbinfo_cookies')->nullable()->after('pbinfo_password');
            $table->timestamp('pbinfo_cookies_at')->nullable()->after('pbinfo_cookies');
            $table->string('last_sync_status')->nullable()->after('pbinfo_cookies_at');
            $table->timestamp('last_sync_at')->nullable()->after('last_sync_status');
            $table->text('last_sync_error')->nullable()->after('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'pbinfo_password',
                'pbinfo_cookies',
                'pbinfo_cookies_at',
                'last_sync_status',
                'last_sync_at',
                'last_sync_error',
            ]);
        });
    }
};
