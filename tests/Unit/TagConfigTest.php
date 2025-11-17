<?php

namespace Masum\Tagging\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Masum\Tagging\Models\TagConfig;
use Masum\Tagging\Tests\Fixtures\Equipment;
use Masum\Tagging\Tests\TestCase;

class TagConfigTest extends TestCase
{
    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
            'auto_generate' => true,
            'description' => 'Test config',
            'current_number' => 100,
            'padding_length' => 5,
        ]);

        $this->assertEquals('EQ', $config->prefix);
        $this->assertEquals('-', $config->separator);
        $this->assertEquals('sequential', $config->number_format);
        $this->assertTrue($config->auto_generate);
        $this->assertEquals(100, $config->current_number);
        $this->assertEquals(5, $config->padding_length);
    }

    /** @test */
    public function it_casts_attributes_correctly(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
            'auto_generate' => '1',  // String
            'current_number' => '50', // String
        ]);

        $this->assertIsBool($config->auto_generate);
        $this->assertIsInt($config->current_number);
        $this->assertTrue($config->auto_generate);
        $this->assertEquals(50, $config->current_number);
    }

    /** @test */
    public function it_requires_unique_model(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TagConfig::create([
            'model' => Equipment::class, // Duplicate!
            'prefix' => 'EQ2',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);
    }

    /** @test */
    public function it_invalidates_cache_on_save(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // Warm up cache
        $cached = TagConfig::forModel(Equipment::class);
        $this->assertNotNull($cached);

        // Update config
        $config->update(['prefix' => 'EQUIPMENT']);

        // Cache should be cleared, fetch fresh from DB
        $fresh = TagConfig::forModel(Equipment::class);
        $this->assertEquals('EQUIPMENT', $fresh->prefix);
    }

    /** @test */
    public function it_invalidates_cache_on_delete(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // Warm up cache
        TagConfig::forModel(Equipment::class);

        // Delete config
        $config->delete();

        // Cache should be cleared
        $result = TagConfig::forModel(Equipment::class);
        $this->assertNull($result);
    }

    /** @test */
    public function it_provides_for_model_helper_with_caching(): void
    {
        $config = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // First call - should hit database and cache
        $result1 = TagConfig::forModel(Equipment::class);

        // Second call - should hit cache
        $result2 = TagConfig::forModel(Equipment::class);

        $this->assertEquals($config->id, $result1->id);
        $this->assertEquals($config->id, $result2->id);
    }
}
