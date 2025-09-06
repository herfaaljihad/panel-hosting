<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null')->after('role');
            $table->bigInteger('disk_used_mb')->default(0)->after('package_id');
            $table->bigInteger('bandwidth_used_mb')->default(0)->after('disk_used_mb');
            $table->datetime('last_login')->nullable()->after('bandwidth_used_mb');
            $table->string('status')->default('active')->after('last_login'); // active, suspended, terminated
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['package_id', 'disk_used_mb', 'bandwidth_used_mb', 'last_login', 'status']);
        });
    }
};
