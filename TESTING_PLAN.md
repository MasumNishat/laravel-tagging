# Testing Plan for Laravel Tagging Package

## Overview

This document outlines the comprehensive testing strategy for the Laravel Tagging package improvements.

**Target Coverage:** 80%+
**Testing Framework:** PHPUnit 10+
**Test Environment:** Orchestra Testbench

## Test Structure

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── TagTest.php
│   │   └── TagConfigTest.php
│   ├── Traits/
│   │   └── TagableTraitTest.php
│   ├── Events/
│   │   ├── TagCreatedTest.php
│   │   ├── TagUpdatedTest.php
│   │   └── TagDeletedTest.php
│   └── Validators/
│       └── TagValidatorTest.php
├── Feature/
│   ├── Api/
│   │   ├── TagConfigApiTest.php
│   │   ├── TagApiTest.php
│   │   └── BarcodeApiTest.php
│   ├── TagGeneration/
│   │   ├── SequentialTagGenerationTest.php
│   │   ├── RandomTagGenerationTest.php
│   │   └── BranchBasedTagGenerationTest.php
│   ├── ConcurrencyTest.php
│   ├── CachingTest.php
│   └── PerformanceTest.php
├── Integration/
│   ├── DatabaseTest.php
│   └── EventsTest.php
└── TestCase.php
```

## Unit Tests

### TagTest.php

Tests for the Tag model:

```php
<?php

namespace Masum\Tagging\Tests\Unit\Models;

class TagTest extends TestCase
{
    /** @test */
    public function it_has_fillable_attributes()
    {
        // Verify fillable array
    }

    /** @test */
    public function it_belongs_to_taggable_model()
    {
        // Test polymorphic relationship
    }

    /** @test */
    public function it_generates_barcode_svg()
    {
        // Test SVG generation
    }

    /** @test */
    public function it_generates_barcode_png()
    {
        // Test PNG generation
    }

    /** @test */
    public function it_generates_barcode_base64()
    {
        // Test base64 generation
    }

    /** @test */
    public function it_returns_available_barcode_types()
    {
        // Test static method
    }

    /** @test */
    public function it_uses_custom_table_name()
    {
        // Test table prefix configuration
    }
}
```

### TagConfigTest.php

Tests for the TagConfig model:

```php
<?php

namespace Masum\Tagging\Tests\Unit\Models;

class TagConfigTest extends TestCase
{
    /** @test */
    public function it_has_fillable_attributes()
    {
        // Verify all fillable fields
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        // Test boolean and enum casts
    }

    /** @test */
    public function it_requires_unique_model()
    {
        // Test unique constraint
    }

    /** @test */
    public function it_has_default_values()
    {
        // Test default separator, format, etc.
    }

    /** @test */
    public function it_validates_number_format_enum()
    {
        // Test only valid formats accepted
    }

    /** @test */
    public function it_invalidates_cache_on_save()
    {
        // Test cache clearing
    }

    /** @test */
    public function it_invalidates_cache_on_delete()
    {
        // Test cache clearing
    }
}
```

### TagableTraitTest.php

Tests for the Tagable trait:

```php
<?php

namespace Masum\Tagging\Tests\Unit\Traits;

class TagableTraitTest extends TestCase
{
    /** @test */
    public function it_adds_tag_to_appends_on_retrieved()
    {
        // Test boot method
    }

    /** @test */
    public function it_generates_tag_on_save()
    {
        // Test automatic tag generation
    }

    /** @test */
    public function it_deletes_tag_on_model_delete()
    {
        // Test cascade delete
    }

    /** @test */
    public function it_has_tag_relationship()
    {
        // Test morphOne relationship
    }

    /** @test */
    public function it_gets_tag_value_as_attribute()
    {
        // Test accessor
    }

    /** @test */
    public function it_gets_tag_config_with_caching()
    {
        // Test cached config retrieval
    }

    /** @test */
    public function it_sets_tag_value()
    {
        // Test mutator
    }

    /** @test */
    public function it_generates_tag_label()
    {
        // Test label interpolation
    }

    /** @test */
    public function it_generates_tag_label_with_nested_attributes()
    {
        // Test relationship.attribute syntax
    }

    /** @test */
    public function it_generates_sequential_tag()
    {
        // Test sequential format
    }

    /** @test */
    public function it_generates_random_tag()
    {
        // Test random format
    }

    /** @test */
    public function it_generates_branch_based_tag()
    {
        // Test branch-based format
    }

    /** @test */
    public function it_generates_fallback_tag()
    {
        // Test fallback when no config
    }

    /** @test */
    public function it_ensures_tag_exists()
    {
        // Test ensureTag method
    }
}
```

## Feature Tests

### TagConfigApiTest.php

Tests for TagConfig API endpoints:

```php
<?php

namespace Masum\Tagging\Tests\Feature\Api;

class TagConfigApiTest extends TestCase
{
    /** @test */
    public function it_lists_tag_configurations()
    {
        // GET /api/tag-configs
    }

    /** @test */
    public function it_filters_by_search_query()
    {
        // GET /api/tag-configs?search=Equipment
    }

    /** @test */
    public function it_filters_by_number_format()
    {
        // GET /api/tag-configs?number_format=sequential
    }

    /** @test */
    public function it_paginates_results()
    {
        // Test pagination metadata
    }

    /** @test */
    public function it_creates_tag_configuration()
    {
        // POST /api/tag-configs
    }

    /** @test */
    public function it_validates_required_fields()
    {
        // Test validation errors
    }

    /** @test */
    public function it_prevents_duplicate_model()
    {
        // Test unique constraint
    }

    /** @test */
    public function it_shows_single_tag_configuration()
    {
        // GET /api/tag-configs/{id}
    }

    /** @test */
    public function it_updates_tag_configuration()
    {
        // PUT /api/tag-configs/{id}
    }

    /** @test */
    public function it_deletes_tag_configuration()
    {
        // DELETE /api/tag-configs/{id}
    }

    /** @test */
    public function it_returns_number_format_options()
    {
        // GET /api/tag-configs/meta/number-formats
    }

    /** @test */
    public function it_returns_available_models()
    {
        // GET /api/tag-configs/meta/available-models
    }

    /** @test */
    public function it_only_returns_models_with_tagable_trait()
    {
        // Test filtering logic
    }

    /** @test */
    public function it_returns_proper_error_on_not_found()
    {
        // Test 404 response
    }
}
```

### ConcurrencyTest.php

Critical tests for race conditions:

```php
<?php

namespace Masum\Tagging\Tests\Feature;

class ConcurrencyTest extends TestCase
{
    /** @test */
    public function it_prevents_duplicate_sequential_tags_under_concurrency()
    {
        $equipment = [];
        $iterations = 100;

        // Simulate 100 concurrent tag generations
        for ($i = 0; $i < $iterations; $i++) {
            $equipment[] = Equipment::create(['name' => "Item $i"]);
        }

        $tags = array_map(fn($e) => $e->tag, $equipment);

        // All tags should be unique
        $this->assertCount($iterations, array_unique($tags));
    }

    /** @test */
    public function it_handles_deadlock_with_retry()
    {
        // Test retry logic when deadlock occurs
    }

    /** @test */
    public function it_maintains_sequential_order()
    {
        // Test that sequential tags are truly sequential
    }

    /** @test */
    public function it_prevents_duplicate_taggable_relationships()
    {
        // Test unique constraint on polymorphic relationship
    }
}
```

### SequentialTagGenerationTest.php

```php
<?php

namespace Masum\Tagging\Tests\Feature\TagGeneration;

class SequentialTagGenerationTest extends TestCase
{
    /** @test */
    public function it_generates_first_sequential_tag()
    {
        $this->assertEquals('EQ-001', $equipment->tag);
    }

    /** @test */
    public function it_increments_sequential_tags()
    {
        // EQ-001, EQ-002, EQ-003
    }

    /** @test */
    public function it_pads_numbers_correctly()
    {
        // Test padding: EQ-001, EQ-099, EQ-100, EQ-1000
    }

    /** @test */
    public function it_respects_custom_padding_length()
    {
        // Test configurable padding
    }

    /** @test */
    public function it_uses_custom_separator()
    {
        // Test: EQ_001, EQ.001, etc.
    }

    /** @test */
    public function it_continues_sequence_after_deletion()
    {
        // Delete EQ-002, next should be EQ-004
    }

    /** @test */
    public function it_finds_max_number_correctly()
    {
        // Test with gaps: 1, 3, 5 -> next is 6
    }
}
```

### CachingTest.php

```php
<?php

namespace Masum\Tagging\Tests\Feature;

class CachingTest extends TestCase
{
    /** @test */
    public function it_caches_tag_config_lookups()
    {
        // First call hits DB, second hits cache
    }

    /** @test */
    public function it_invalidates_cache_on_config_update()
    {
        // Update config, cache should clear
    }

    /** @test */
    public function it_invalidates_cache_on_config_delete()
    {
        // Delete config, cache should clear
    }

    /** @test */
    public function it_respects_cache_ttl_setting()
    {
        // Test configurable TTL
    }

    /** @test */
    public function it_can_disable_caching()
    {
        // Test cache_enabled = false
    }

    /** @test */
    public function it_uses_correct_cache_keys()
    {
        // Verify cache key format
    }
}
```

### PerformanceTest.php

```php
<?php

namespace Masum\Tagging\Tests\Feature;

class PerformanceTest extends TestCase
{
    /** @test */
    public function it_generates_single_tag_quickly()
    {
        $start = microtime(true);
        Equipment::create(['name' => 'Test']);
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(100, $duration, 'Tag generation took too long');
    }

    /** @test */
    public function it_handles_batch_generation_efficiently()
    {
        // Generate 100 tags in < 5 seconds
    }

    /** @test */
    public function it_avoids_n_plus_one_queries()
    {
        // Load 50 models with tags in < 10 queries
    }

    /** @test */
    public function it_uses_indexes_for_searches()
    {
        // Verify EXPLAIN shows index usage
    }
}
```

## Integration Tests

### EventsTest.php

```php
<?php

namespace Masum\Tagging\Tests\Integration;

class EventsTest extends TestCase
{
    /** @test */
    public function it_dispatches_tag_created_event()
    {
        Event::fake();

        Equipment::create(['name' => 'Test']);

        Event::assertDispatched(TagCreated::class);
    }

    /** @test */
    public function it_dispatches_tag_updated_event()
    {
        Event::fake();

        $equipment->tag = 'NEW-001';

        Event::assertDispatched(TagUpdated::class);
    }

    /** @test */
    public function it_dispatches_tag_deleted_event()
    {
        Event::fake();

        $equipment->delete();

        Event::assertDispatched(TagDeleted::class);
    }

    /** @test */
    public function it_dispatches_tag_generation_failed_event()
    {
        Event::fake();

        // Force failure

        Event::assertDispatched(TagGenerationFailed::class);
    }
}
```

## Test Utilities

### TestCase.php

Base test case with helpers:

```php
<?php

namespace Masum\Tagging\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Masum\Tagging\TaggingServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../src/database/migrations/create_tags_table.php';
        include_once __DIR__.'/../src/database/migrations/create_tag_configs_table.php';

        (new \CreateTagsTable())->up();
        (new \CreateTagConfigsTable())->up();
    }

    protected function createTagConfig($model, $attributes = [])
    {
        return TagConfig::create(array_merge([
            'model' => $model,
            'prefix' => 'TEST',
            'separator' => '-',
            'number_format' => 'sequential',
            'auto_generate' => true,
        ], $attributes));
    }
}
```

### Fixtures/TestModel.php

Test models for testing:

```php
<?php

namespace Masum\Tagging\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Masum\Tagging\Traits\Tagable;

class Equipment extends Model
{
    use Tagable;

    const TAGABLE = 'Equipment';

    protected $fillable = ['name', 'serial_no', 'branch_id'];
}

class Brand extends Model
{
    use Tagable;

    const TAGABLE = 'Brand';
    const TAG_LABEL = 'Brand: {name}';

    protected $fillable = ['name'];
}
```

## Running Tests

### Local Development

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/Models/TagTest.php

# Run specific test method
vendor/bin/phpunit --filter test_it_generates_sequential_tag

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run only unit tests
vendor/bin/phpunit --testsuite Unit

# Run only feature tests
vendor/bin/phpunit --testsuite Feature
```

### CI/CD (GitHub Actions)

```bash
# Test matrix
PHP: 8.1, 8.2, 8.3
Laravel: 10.x, 11.x, 12.x
Database: MySQL, PostgreSQL, SQLite

# Run on every push and PR
# Require all tests pass before merge
```

## Coverage Requirements

### Minimum Coverage

- **Overall:** 80%
- **Critical paths:** 95%
  - Tag generation
  - Race condition prevention
  - Unique constraints
- **API endpoints:** 90%
- **Models:** 85%
- **Traits:** 90%

### Coverage Reports

Generate with:
```bash
vendor/bin/phpunit --coverage-html coverage/
vendor/bin/phpunit --coverage-text
```

## Test Data

### Database Seeding

```php
// For performance tests
Tag::factory()->count(10000)->create();

// For search tests
TagConfig::factory()->count(50)->create();
```

### Factories

```php
// TagFactory.php
Tag::factory()->define(function () {
    return [
        'value' => 'TEST-' . $this->faker->unique()->numberBetween(1, 9999),
        'taggable_type' => Equipment::class,
        'taggable_id' => Equipment::factory(),
    ];
});

// TagConfigFactory.php
TagConfig::factory()->define(function () {
    return [
        'model' => Equipment::class,
        'prefix' => $this->faker->unique()->lexify('???'),
        'separator' => '-',
        'number_format' => $this->faker->randomElement(['sequential', 'random', 'branch_based']),
        'auto_generate' => true,
    ];
});
```

## Continuous Testing

### Watch Mode

```bash
# Use phpunit-watcher for continuous testing
composer require spatie/phpunit-watcher --dev
vendor/bin/phpunit-watcher watch
```

### Pre-commit Hook

```bash
#!/bin/sh
# .git/hooks/pre-commit

vendor/bin/phpunit --stop-on-failure
if [ $? -ne 0 ]; then
    echo "Tests failed! Commit aborted."
    exit 1
fi
```

---

**Last Updated:** 2025-11-17
**Status:** Ready for implementation
**Estimated Test Development Time:** 2-3 days
