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
        if (Schema::hasTable('cron_jobs')) {
            Schema::table('cron_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('cron_jobs', 'domain_id')) {
                    $table->foreignId('domain_id')->nullable()->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('cron_jobs', 'command')) {
                    $table->text('command');
                }
                if (!Schema::hasColumn('cron_jobs', 'schedule')) {
                    $table->string('schedule');
                }
                if (!Schema::hasColumn('cron_jobs', 'email_output')) {
                    $table->boolean('email_output')->default(false);
                }
                if (!Schema::hasColumn('cron_jobs', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('cron_jobs', 'success_count')) {
                    $table->integer('success_count')->default(0);
                }
                if (!Schema::hasColumn('cron_jobs', 'failure_count')) {
                    $table->integer('failure_count')->default(0);
                }
                if (!Schema::hasColumn('cron_jobs', 'last_run_at')) {
                    $table->timestamp('last_run_at')->nullable();
                }
                if (!Schema::hasColumn('cron_jobs', 'next_run_at')) {
                    $table->timestamp('next_run_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'domain_id', 'command', 'schedule', 'email_output', 'is_active',
                'success_count', 'failure_count', 'last_run_at', 'next_run_at'
            ]);
        });
    }
};
