<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->string('app_name');
            $table->string('app_version');
            $table->string('installation_path')->default('/');
            $table->string('database_name')->nullable();
            $table->string('database_user')->nullable();
            $table->string('admin_username');
            $table->string('admin_email');
            $table->string('app_url');
            $table->enum('status', ['installing', 'installed', 'failed', 'updating'])->default('installing');
            $table->text('installation_log')->nullable();
            $table->boolean('auto_update')->default(false);
            $table->boolean('backup_enabled')->default(true);
            $table->boolean('ssl_enabled')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['domain_id', 'installation_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_installations');
    }
};
