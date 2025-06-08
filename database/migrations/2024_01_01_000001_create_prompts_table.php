<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('category')->index();
            $table->json('tags')->nullable();
            $table->enum('source_type', ['manual', 'fabric', 'github'])->default('manual')->index();
            $table->string('source_url')->nullable();
            $table->integer('estimated_tokens')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_public')->default(true)->index();
            $table->string('checksum')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Regular indexes instead of full-text for SQLite compatibility
            $table->index(['title']);
            $table->index(['description']);
            $table->index(['source_type', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->unique(['name', 'source_type']);
        });

        Schema::create('compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->onDelete('cascade');
            $table->text('input_content')->nullable();
            $table->longText('composed_content');
            $table->json('metadata')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->float('compose_time_ms')->default(0);
            $table->string('client_info')->default('unknown');
            $table->timestamps();

            $table->index(['prompt_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compositions');
        Schema::dropIfExists('prompts');
    }
};
