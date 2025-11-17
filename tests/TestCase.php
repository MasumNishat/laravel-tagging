<?php

namespace Masum\Tagging\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Masum\Tagging\TaggingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TaggingServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure tagging package
        $app['config']->set('tagging.table_prefix', 'tagging_');
        $app['config']->set('tagging.tables.tags', 'tags');
        $app['config']->set('tagging.tables.tag_configs', 'tag_configs');
        $app['config']->set('tagging.cache.enabled', true);
        $app['config']->set('tagging.cache.ttl', 3600);
        $app['config']->set('tagging.performance.max_retries', 3);
        $app['config']->set('tagging.performance.debug_n_plus_one', false); // Disable in tests
    }

    /**
     * Set up the database.
     *
     * @return void
     */
    protected function setUpDatabase(): void
    {
        // Run package migrations
        include_once __DIR__.'/../src/database/migrations/create_tags_table.php';
        include_once __DIR__.'/../src/database/migrations/create_tag_configs_table.php';
        include_once __DIR__.'/../src/database/migrations/add_improvements_to_tagging_tables.php';

        (new \CreateTagsTable())->up();
        (new \CreateTagConfigsTable())->up();
        (new \AddImprovementsToTaggingTables())->up();

        // Create test models tables
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serial_no')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
