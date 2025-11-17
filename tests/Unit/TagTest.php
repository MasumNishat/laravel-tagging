<?php

namespace Masum\Tagging\Tests\Unit;

use Masum\Tagging\Models\Tag;
use Masum\Tagging\Models\TagConfig;
use Masum\Tagging\Tests\Fixtures\Equipment;
use Masum\Tagging\Tests\TestCase;

class TagTest extends TestCase
{
    /** @test */
    public function it_belongs_to_a_taggable_model(): void
    {
        $tagConfig = TagConfig::create([
            'model' => Equipment::class,
            'prefix' => 'EQ',
            'separator' => '-',
            'number_format' => 'sequential',
        ]);

        $equipment = Equipment::create(['name' => 'Router']);
        $tag = $equipment->tag()->first();

        $this->assertInstanceOf(Equipment::class, $tag->taggable);
        $this->assertEquals($equipment->id, $tag->taggable->id);
    }

    /** @test */
    public function it_generates_barcode_svg(): void
    {
        $tag = Tag::create([
            'value' => 'TEST-001',
            'taggable_type' => Equipment::class,
            'taggable_id' => 1,
        ]);

        $svg = $tag->generateBarcodeSVG();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    /** @test */
    public function it_generates_barcode_base64(): void
    {
        $tag = Tag::create([
            'value' => 'TEST-001',
            'taggable_type' => Equipment::class,
            'taggable_id' => 1,
        ]);

        $base64 = $tag->getBarcodeBase64();

        $this->assertStringStartsWith('data:image/png;base64,', $base64);
    }

    /** @test */
    public function it_returns_available_barcode_types(): void
    {
        $types = Tag::availableBarcodeTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('CODE_128', $types);
        $this->assertArrayHasKey('QR_CODE', $types);
    }

    /** @test */
    public function it_uses_custom_table_name(): void
    {
        $tag = new Tag();

        $this->assertEquals('tagging_tags', $tag->getTable());
    }
}
