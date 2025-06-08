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
        Schema::create('prompt_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // fabric, personal, team-laravel
            $table->string('type'); // git, fabric, manual
            $table->text('repository_url')->nullable(); // git URL
            $table->string('branch')->default('main'); // git branch
            $table->string('path_pattern')->default('**/*.md'); // which files to sync
            $table->string('file_pattern')->default('system.md'); // specific file name pattern
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('pending'); // pending, syncing, completed, failed
            $table->text('sync_error')->nullable();
            $table->json('metadata')->nullable(); // additional configuration
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_sources');
    }
};
