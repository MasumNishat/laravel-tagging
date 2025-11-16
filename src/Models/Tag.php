<?php

namespace Masum\Tagging\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorHTML;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'value',
        'taggable_type',
        'taggable_id',
    ];

    /**
     * Get the parent taggable model.
     */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        $prefix = config('tagging.table_prefix', '');
        $table = config('tagging.tables.tags', 'tags');
        return $prefix . $table;
    }

    /**
     * Generate barcode as SVG
     *
     * @param string|null $type Barcode type (default: CODE_128)
     * @param int $widthFactor Width factor (default: 2)
     * @param int $height Height in pixels (default: 30)
     * @param string $color Hex color (default: #000000)
     * @return string SVG markup
     */
    public function generateBarcodeSVG(
        ?string $type = null,
        int $widthFactor = 2,
        int $height = 30,
        string $color = '#000000'
    ): string {
        $generator = new BarcodeGeneratorSVG();
        $type = $type ?? $generator::TYPE_CODE_128;

        return $generator->getBarcode($this->value, $type, $widthFactor, $height, $color);
    }

    /**
     * Generate barcode as PNG binary
     *
     * @param string|null $type Barcode type (default: CODE_128)
     * @param int $widthFactor Width factor (default: 2)
     * @param int $height Height in pixels (default: 30)
     * @param array $color RGB color array (default: [0, 0, 0])
     * @return string PNG binary data
     */
    public function generateBarcodePNG(
        ?string $type = null,
        int $widthFactor = 2,
        int $height = 30,
        array $color = [0, 0, 0]
    ): string {
        $generator = new BarcodeGeneratorPNG();
        $type = $type ?? $generator::TYPE_CODE_128;

        return $generator->getBarcode($this->value, $type, $widthFactor, $height, $color);
    }

    /**
     * Generate barcode as base64 data URL for inline display
     *
     * @param string|null $type Barcode type (default: CODE_128)
     * @param int $widthFactor Width factor (default: 2)
     * @param int $height Height in pixels (default: 30)
     * @return string Base64 data URL
     */
    public function getBarcodeBase64(?string $type = null, int $widthFactor = 2, int $height = 30): string
    {
        $png = $this->generateBarcodePNG($type, $widthFactor, $height);
        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Generate barcode as HTML
     *
     * @param string|null $type Barcode type (default: CODE_128)
     * @param int $widthFactor Width factor (default: 2)
     * @param int $height Height in pixels (default: 30)
     * @param string $color Hex color (default: #000000)
     * @return string HTML markup
     */
    public function generateBarcodeHTML(
        ?string $type = null,
        int $widthFactor = 2,
        int $height = 30,
        string $color = 'black'
    ): string {
        $generator = new BarcodeGeneratorHTML();
        $type = $type ?? $generator::TYPE_CODE_128;

        return $generator->getBarcode($this->value, $type, $widthFactor, $height, $color);
    }

    /**
     * Get available barcode types
     *
     * @return array
     */
    public static function availableBarcodeTypes(): array
    {
        $generator = new BarcodeGeneratorSVG();
        return [
            'CODE_128' => $generator::TYPE_CODE_128,
            'CODE_39' => $generator::TYPE_CODE_39,
            'EAN_13' => $generator::TYPE_EAN_13,
            'EAN_8' => $generator::TYPE_EAN_8,
            'UPC_A' => $generator::TYPE_UPC_A,
            'UPC_E' => $generator::TYPE_UPC_E,
            'ITF_14' => $generator::TYPE_ITF_14,
            'QR_CODE' => $generator::TYPE_QRCODE,
        ];
    }
}