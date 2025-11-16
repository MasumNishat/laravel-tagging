<?php

namespace Masum\Tagging\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefix',
        'separator',
        'number_format',
        'auto_generate',
        'model',
        'description',
    ];

    protected $casts = [
        'auto_generate' => 'boolean',
        'number_format' => 'string',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        $prefix = config('tagging.table_prefix', '');
        $table = config('tagging.tables.tag_configs', 'tag_configs');
        return $prefix . $table;
    }
}