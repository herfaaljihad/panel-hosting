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
        Schema::create('reseller_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('bandwidth_limit')->default(0); // in MB
            $table->integer('disk_space_limit')->default(0); // in MB
            $table->integer('domain_limit')->default(0);
            $table->integer('subdomain_limit')->default(0);
            $table->integer('email_account_limit')->default(0);
            $table->integer('database_limit')->default(0);
            $table->integer('ftp_account_limit')->default(0);
            $table->integer('reseller_users_limit')->default(0);
            $table->decimal('monthly_price', 8, 2)->default(0);
            $table->decimal('yearly_price', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // For additional features like SSL, backups, etc.
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_packages');
    }
};
