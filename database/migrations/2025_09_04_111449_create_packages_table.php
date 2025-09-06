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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annually'])->default('monthly');
            
            // Resource Limits
            $table->integer('max_domains')->default(1);
            $table->integer('max_subdomains')->default(10);
            $table->integer('max_databases')->default(1);
            $table->integer('max_email_accounts')->default(10);
            $table->integer('max_ftp_accounts')->default(5);
            $table->bigInteger('disk_quota_mb')->default(1000); // Disk space in MB
            $table->bigInteger('bandwidth_quota_mb')->default(10000); // Bandwidth in MB
            $table->integer('max_cron_jobs')->default(5);
            
            // Features
            $table->boolean('ssl_enabled')->default(true);
            $table->boolean('backup_enabled')->default(true);
            $table->boolean('dns_management')->default(true);
            $table->boolean('file_manager')->default(true);
            $table->boolean('statistics')->default(true);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
