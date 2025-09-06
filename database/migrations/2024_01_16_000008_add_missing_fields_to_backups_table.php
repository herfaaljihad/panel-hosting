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
        if (Schema::hasTable('backups')) {
            Schema::table('backups', function (Blueprint $table) {
                if (!Schema::hasColumn('backups', 'domain_id')) {
                    $table->foreignId('domain_id')->nullable()->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('backups', 'backup_type')) {
                    $table->string('backup_type')->default('full');
                }
                if (!Schema::hasColumn('backups', 'filename')) {
                    $table->string('filename')->nullable();
                }
                if (!Schema::hasColumn('backups', 'file_path')) {
                    $table->string('file_path')->nullable();
                }
                if (!Schema::hasColumn('backups', 'size_mb')) {
                    $table->decimal('size_mb', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('backups', 'status')) {
                    $table->string('status')->default('pending');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn(['domain_id', 'backup_type', 'filename', 'file_path', 'size_mb', 'status']);
        });
    }
};
