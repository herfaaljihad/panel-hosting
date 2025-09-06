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
        if (Schema::hasTable('email_accounts')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('email_accounts', 'domain_id')) {
                    $table->foreignId('domain_id')->nullable()->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('email_accounts', 'quota_mb')) {
                    $table->integer('quota_mb')->default(1000);
                }
                if (!Schema::hasColumn('email_accounts', 'is_active')) {
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
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['domain_id', 'quota_mb', 'is_active']);
        });
    }
};
