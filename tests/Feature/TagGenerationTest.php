<?php

namespace Masum\Tagging\Tests\Feature;

use Masum\Tagging\Models\Tag;
use Masum\Tagging\Models\TagConfig;
use Masum\Tagging\Tests\Fixtures\Brand;
use Masum\Tagging\Tests\Fixtures\Equipment;
use Masum\Tagging\Tests\TestCase;

class TagGenerationTest extends TestCase
{
    /** @test */
    public function it_generates_sequential_tags_automatically(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
            'padding_length' => 3,
        ]);

        $equipment1 = Equipment::create(['name' => 'Router 1']);
        $equipment2 = Equipment::create(['name' => 'Router 2']);
        $equipment3 = Equipment::create(['name' => 'Router 3']);

        $this->assertEquals('EQ-001', $equipment1->tag);
        $this->assertEquals('EQ-002', $equipment2->tag);
        $this->assertEquals('EQ-003', $equipment3->tag);
    }

    /** @test */
    public function it_uses_configurable_padding_length(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
            'padding_length' => 5, // Custom padding
        ]);

        $equipment = Equipment::create(['name' => 'Router']);

        $this->assertEquals('EQ-00001', $equipment->tag);
    }

    /** @test */
    public function it_generates_random_timestamp_based_tags(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'random',
        ]);

        $equipment = Equipment::create(['name' => 'Router']);

        $this->assertStringStartsWith('EQ-', $equipment->tag);
        $this->assertMatchesRegularExpression('/^EQ-\d+$/', $equipment->tag);
    }

    /** @test */
    public function it_generates_branch_based_tags(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'branch_based',
            'padding_length' => 3,
        ]);

        $equipment1 = Equipment::create(['name' => 'Router 1', 'branch_id' => 5]);
        $equipment2 = Equipment::create(['name' => 'Router 2', 'branch_id' => 5]);
        $equipment3 = Equipment::create(['name' => 'Router 3', 'branch_id' => 10]);

        $this->assertEquals('EQ-001-5', $equipment1->tag);
        $this->assertEquals('EQ-002-5', $equipment2->tag);
        $this->assertEquals('EQ-001-10', $equipment3->tag); // Different branch, restarts at 1
    }

    /** @test */
    public function it_generates_fallback_tag_when_no_config_exists(): void
    {
        $equipment = Equipment::create(['name' => 'Router']);

        $this->assertStringStartsWith('TAG-', $equipment->tag);
        $this->assertMatchesRegularExpression('/^TAG-\d+$/', $equipment->tag);
    }

    /** @test */
    public function it_prevents_duplicate_tags_for_same_model(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        $equipment = Equipment::create(['name' => 'Router']);
        $originalTag = $equipment->tag;

        // Try to create another tag for the same model (should fail due to unique constraint)
        $this->expectException(\Illuminate\Database\QueryException::class);

        Tag::create([
            'value' => 'EQ-999',
            'taggable_type' => get_class($equipment),
            'taggable_id' => $equipment->id,
        ]);
    }

    /** @test */
    public function it_deletes_tag_when_model_is_deleted(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        $equipment = Equipment::create(['name' => 'Router']);
        $tagId = $equipment->tag()->first()->id;

        $equipment->delete();

        $this->assertNull(Tag::find($tagId));
    }

    /** @test */
    public function it_uses_tag_label_constant_for_printing(): void
    {
        TagConfig::create([
            'model' => Brand::class,
            'prefix' => 'BRD',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        $brand = Brand::create(['name' => 'Cisco']);

        $this->assertEquals('Brand: Cisco', $brand->getTagLabel());
    }

    /** @test */
    public function it_avoids_n_plus_one_queries_with_eager_loading(): void
    {
        TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        // Create 5 equipment items
        foreach (range(1, 5) as $i) {
            Equipment::create(['name' => "Router $i"]);
        }

        // Enable query log
        \DB::enableQueryLog();

        // Load equipment WITH eager loading
        $equipment = Equipment::with('tag')->get();
        $queryCount = count(\DB::getQueryLog());

        // Access all tags
        foreach ($equipment as $item) {
            $tag = $item->tag; // Should not create additional queries
        }

        $finalQueryCount = count(\DB::getQueryLog());

        // Should only have the initial queries (select equipment + select tags)
        // Not 5 additional queries (one per equipment)
        $this->assertEquals($queryCount, $finalQueryCount);
    }
}
