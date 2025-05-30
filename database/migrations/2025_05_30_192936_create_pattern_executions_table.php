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
        Schema::create('pattern_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabric_pattern_id')->constrained()->onDelete('cascade');
            $table->longText('input_content')->nullable();
            $table->longText('output_content')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->float('execution_time_ms')->nullable();
            $table->string('client_info')->nullable();
            $table->timestamps();

            $table->index(['fabric_pattern_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pattern_executions');
    }
};
