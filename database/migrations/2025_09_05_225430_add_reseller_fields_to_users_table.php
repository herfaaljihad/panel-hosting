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
            $table->enum('user_type', ['admin', 'reseller', 'user'])->default('user')->after('role');
            $table->unsignedBigInteger('reseller_id')->nullable()->after('user_type');
            $table->integer('reseller_quota_disk_mb')->default(0)->after('reseller_id');
            $table->integer('reseller_quota_bandwidth_mb')->default(0)->after('reseller_quota_disk_mb');
            $table->integer('reseller_max_users')->default(0)->after('reseller_quota_bandwidth_mb');
            $table->json('reseller_permissions')->nullable()->after('reseller_max_users');
            $table->boolean('can_create_resellers')->default(false)->after('reseller_permissions');
            
            $table->foreign('reseller_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_type', 'reseller_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->dropIndex(['user_type', 'reseller_id']);
            $table->dropColumn([
                'user_type',
                'reseller_id', 
                'reseller_quota_disk_mb',
                'reseller_quota_bandwidth_mb',
                'reseller_max_users',
                'reseller_permissions',
                'can_create_resellers'
            ]);
        });
    }
};
