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
        Schema::create('update_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('comment_type', 50); // update_available, update_failed, etc.
            $table->string('title');
            $table->text('message');
            $table->tinyInteger('priority')->default(2); // 1=low, 2=medium, 3=high, 4=critical
            $table->string('status', 20)->default('pending'); // pending, acknowledged, resolved, dismissed
            $table->boolean('action_required')->default(false);
            $table->boolean('auto_resolve')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            $table->index(['plugin_id', 'status']);
            $table->index(['comment_type', 'priority']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_comments');
    }
};
