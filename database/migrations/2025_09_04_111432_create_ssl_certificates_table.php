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
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider')->default('letsencrypt'); // letsencrypt, custom, self-signed
            $table->text('certificate')->nullable();
            $table->text('private_key')->nullable();
            $table->text('certificate_chain')->nullable();
            $table->datetime('issued_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->enum('status', ['pending', 'active', 'expired', 'error'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
