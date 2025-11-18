<?php

namespace Masum\Tagging\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        'current_number',
        'padding_length',
    ];

    protected $casts = [
        'auto_generate' => 'boolean',
        'number_format' => 'string',
        'current_number' => 'integer',
        'padding_length' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Invalidate cache when tag config is saved
        static::saved(function ($config) {
            if (config('tagging.cache.enabled', true)) {
                Cache::forget(self::getCacheKey($config->model));
            }
        });

        // Invalidate cache when tag config is deleted
        static::deleted(function ($config) {
            if (config('tagging.cache.enabled', true)) {
                Cache::forget(self::getCacheKey($config->model));
            }
        });
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        $prefix = config('tagging.table_prefix', '');
        $table = config('tagging.tables.tag_configs', 'tag_configs');
        return $prefix . $table;
    }

    /**
     * Get the cache key for a model.
     */
    public static function getCacheKey(string $modelClass): string
    {
        return 'tag_config:' . md5($modelClass);
    }

    /**
     * Get or create a tag config for a model with caching.
     */
    public static function forModel(string $modelClass): ?self
    {
        if (!config('tagging.cache.enabled', true)) {
            return self::where('model', $modelClass)->first();
        }

        $cacheKey = self::getCacheKey($modelClass);
        $ttl = config('tagging.cache.ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($modelClass) {
            return self::where('model', $modelClass)->first();
        });
    }
}