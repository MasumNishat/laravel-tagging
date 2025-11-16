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
        $table = config('tagging.tables.tags', 'tags');

        Schema::create($prefix . $table, function (Blueprint $table) {
            $table->id();
            $table->string('value');
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
            $table->index(['taggable_type', 'taggable_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('tagging.table_prefix', 'tagging_');
        $table = config('tagging.tables.tags', 'tags');

        Schema::dropIfExists($prefix . $table);
    }
};