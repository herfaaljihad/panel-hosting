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
        if (Schema::hasTable('ftp_accounts')) {
            Schema::table('ftp_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('ftp_accounts', 'domain_id')) {
                    $table->foreignId('domain_id')->nullable()->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('ftp_accounts', 'username')) {
                    $table->string('username');
                }
                if (!Schema::hasColumn('ftp_accounts', 'password')) {
                    $table->string('password');
                }
                if (!Schema::hasColumn('ftp_accounts', 'directory')) {
                    $table->string('directory')->default('/');
                }
                if (!Schema::hasColumn('ftp_accounts', 'quota_mb')) {
                    $table->integer('quota_mb')->default(1000);
                }
                if (!Schema::hasColumn('ftp_accounts', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ftp_accounts', function (Blueprint $table) {
            $table->dropColumn(['domain_id', 'username', 'password', 'directory', 'quota_mb', 'is_active']);
        });
    }
};
