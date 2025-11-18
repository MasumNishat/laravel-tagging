<?php

namespace Masum\Tagging\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Masum\Tagging\Models\TagConfig;
use Masum\Tagging\Tests\Fixtures\Equipment;
use Masum\Tagging\Tests\TestCase;

class CachingTest extends TestCase
{
    /** @test */
    public function it_caches_tag_config_lookups(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // First call - hits database
        DB::enableQueryLog();
        $result1 = TagConfig::forModel(Equipment::class);
        $queries1 = count(DB::getQueryLog());

        DB::flushQueryLog();

        // Second call - hits cache (no query)
        $result2 = TagConfig::forModel(Equipment::class);
        $queries2 = count(DB::getQueryLog());

        $this->assertEquals($config->id, $result1->id);
        $this->assertEquals($config->id, $result2->id);
        $this->assertEquals(1, $queries1); // First call queries DB
        $this->assertEquals(0, $queries2); // Second call uses cache
    }

    /** @test */
    public function it_respects_cache_enabled_setting(): void
    {
        config(['tagging.cache.enabled' => false]);

        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // Both calls should hit database when cache is disabled
        DB::enableQueryLog();
        TagConfig::forModel(Equipment::class);
        $queries1 = count(DB::getQueryLog());

        DB::flushQueryLog();
        TagConfig::forModel(Equipment::class);
        $queries2 = count(DB::getQueryLog());

        $this->assertEquals(1, $queries1);
        $this->assertEquals(1, $queries2); // Still queries DB
    }

    /** @test */
    public function it_uses_model_accessor_with_caching(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        $equipment = Equipment::create(['name' => 'Router']);

        // First access - caches config
        DB::enableQueryLog();
        $config1 = $equipment->tag_config;
        $queries1 = count(DB::getQueryLog());

        DB::flushQueryLog();

        // Second access - uses cache
        $config2 = $equipment->tag_config;
        $queries2 = count(DB::getQueryLog());

        $this->assertEquals($config->id, $config1->id);
        $this->assertEquals($config->id, $config2->id);
        $this->assertGreaterThan(0, $queries1); // First access queries
        $this->assertEquals(0, $queries2); // Second access cached
    }

    /** @test */
    public function it_generates_correct_cache_key(): void
    {
        $key = TagConfig::getCacheKey(Equipment::class);

        $this->assertStringStartsWith('tag_config:', $key);
        $this->assertEquals('tag_config:' . md5(Equipment::class), $key);
    }

    /** @test */
    public function it_clears_cache_on_config_update(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // Warm cache
        $cached1 = TagConfig::forModel(Equipment::class);
        $this->assertEquals('EQ', $cached1->prefix);

        // Update config
        $config->update(['prefix' => 'EQUIPMENT']);

        // Cache should be invalidated, get fresh data
        $cached2 = TagConfig::forModel(Equipment::class);
        $this->assertEquals('EQUIPMENT', $cached2->prefix);
    }

    /** @test */
    public function it_clears_cache_on_config_delete(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // Warm cache
        $cached = TagConfig::forModel(Equipment::class);
        $this->assertNotNull($cached);

        // Delete config
        $config->delete();

        // Cache should be cleared
        $result = TagConfig::forModel(Equipment::class);
        $this->assertNull($result);
    }
}
