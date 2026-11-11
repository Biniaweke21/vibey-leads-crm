<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('client_id');
            $table->boolean('portal_enabled')->default(true)->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('portal_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['must_change_password', 'portal_enabled', 'last_login_at']);
        });
    }
};
