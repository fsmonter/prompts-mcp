<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            // Add source_identifier field
            $table->string('source_identifier')->nullable()->after('source_type');

            // Drop the old unique constraint
            $table->dropUnique(['name', 'source_type']);

            // Add new unique constraint with source_identifier
            $table->unique(['name', 'source_type', 'source_identifier']);

            // Add index for source_identifier
            $table->index(['source_identifier']);
        });

        // Update existing records to have source_identifier
        DB::table('prompts')
            ->where('source_type', 'fabric')
            ->update(['source_identifier' => 'fabric']);

        DB::table('prompts')
            ->where('source_type', 'manual')
            ->update(['source_identifier' => 'manual']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique(['name', 'source_type', 'source_identifier']);

            // Drop the source_identifier index
            $table->dropIndex(['source_identifier']);

            // Drop the source_identifier column
            $table->dropColumn('source_identifier');

            // Restore the old unique constraint
            $table->unique(['name', 'source_type']);
        });
    }
};
