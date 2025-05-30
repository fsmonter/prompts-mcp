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
        Schema::create('fabric_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->string('category')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('source_url')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fabric_patterns');
    }
};
