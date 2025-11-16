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
        $table = config('tagging.tables.tag_configs', 'tag_configs');

        Schema::create($prefix . $table, function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10);
            $table->string('separator', 5)->default('-');
            $table->enum('number_format', ['sequential', 'branch_based', 'random'])->default('sequential');
            $table->boolean('auto_generate')->default(true);
            $table->text('description')->nullable();
            $table->string('model')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('tagging.table_prefix', 'tagging_');
        $table = config('tagging.tables.tag_configs', 'tag_configs');

        Schema::dropIfExists($prefix . $table);
    }
};