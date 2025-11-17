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
        $prefix = config('tagging.table_prefix', 'tagging_');
        $tagsTable = $prefix . config('tagging.tables.tags', 'tags');
        $configsTable = $prefix . config('tagging.tables.tag_configs', 'tag_configs');

        // Add improvements to tags table
        Schema::table($tagsTable, function (Blueprint $table) {
            // Add unique constraint on polymorphic relationship
            // This prevents duplicate tags for the same model instance
            $table->unique(['taggable_type', 'taggable_id'], 'unique_taggable');

            // Add index on value column for faster searches
            $table->index('value', 'idx_tags_value');
        });

        // Add improvements to tag_configs table
        Schema::table($configsTable, function (Blueprint $table) {
            // Add atomic counter for race-free sequential tag generation
            $table->unsignedBigInteger('current_number')->default(0)->after('auto_generate');

            // Add configurable padding length (default 3 for backward compatibility)
            $table->unsignedTinyInteger('padding_length')->default(3)->after('current_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('tagging.table_prefix', 'tagging_');
        $tagsTable = $prefix . config('tagging.tables.tags', 'tags');
        $configsTable = $prefix . config('tagging.tables.tag_configs', 'tag_configs');

        // Remove improvements from tags table
        Schema::table($tagsTable, function (Blueprint $table) {
            $table->dropUnique('unique_taggable');
            $table->dropIndex('idx_tags_value');
        });

        // Remove improvements from tag_configs table
        Schema::table($configsTable, function (Blueprint $table) {
            $table->dropColumn(['current_number', 'padding_length']);
        });
    }
};
