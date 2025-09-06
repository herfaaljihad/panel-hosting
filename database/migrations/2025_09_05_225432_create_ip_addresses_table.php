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
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip_address')->unique();
            $table->enum('type', ['ipv4', 'ipv6'])->default('ipv4');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_shared')->default(false);
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->string('server_name')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->json('dns_records')->nullable(); // For NS, A, AAAA records
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['is_available', 'type']);
            $table->index(['assigned_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
