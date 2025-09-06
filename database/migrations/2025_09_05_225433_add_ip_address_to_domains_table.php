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
        Schema::table('domains', function (Blueprint $table) {
            $table->unsignedBigInteger('ip_address_id')->nullable()->after('user_id');
            $table->foreign('ip_address_id')->references('id')->on('ip_addresses')->onDelete('set null');
            $table->index(['ip_address_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropForeign(['ip_address_id']);
            $table->dropIndex(['ip_address_id']);
            $table->dropColumn('ip_address_id');
        });
    }
};
