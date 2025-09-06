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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_package_id')->nullable()->after('reseller_permissions');
            $table->foreign('reseller_package_id')->references('id')->on('reseller_packages')->onDelete('set null');
            $table->index(['reseller_package_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reseller_package_id']);
            $table->dropIndex(['reseller_package_id']);
            $table->dropColumn('reseller_package_id');
        });
    }
};
