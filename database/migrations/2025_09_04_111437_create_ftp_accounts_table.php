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
        Schema::create('ftp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('home_directory');
            $table->bigInteger('quota_mb')->default(1000); // Quota in MB
            $table->bigInteger('used_mb')->default(0); // Used space in MB
            $table->boolean('is_active')->default(true);
            $table->datetime('last_login')->nullable();
            $table->text('allowed_ips')->nullable(); // JSON array of allowed IPs
            $table->boolean('read_only')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ftp_accounts');
    }
};
