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
        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('command');
            $table->string('schedule'); // cron format: * * * * *
            $table->boolean('is_active')->default(true);
            $table->datetime('last_run')->nullable();
            $table->datetime('next_run')->nullable();
            $table->text('last_output')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->boolean('email_on_failure')->default(false);
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
