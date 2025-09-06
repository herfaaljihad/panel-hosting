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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version');
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('status', 20)->default('inactive'); // active, inactive, error, updating
            $table->string('type', 20)->default('extension'); // core, extension, theme, module
            $table->string('file_path')->nullable();
            $table->string('config_file')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('requirements')->nullable();
            $table->timestamp('install_date')->nullable();
            $table->timestamp('last_update_check')->nullable();
            $table->string('available_version')->nullable();
            $table->string('download_url')->nullable();
            $table->text('changelog')->nullable();
            $table->boolean('auto_update')->default(false);
            $table->boolean('is_core')->default(false);
            $table->boolean('update_available')->default(false);
            $table->integer('priority')->default(5); // 1=critical, 5=low
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('update_available');
            $table->index(['is_core', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
