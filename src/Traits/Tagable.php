<?php

namespace Masum\Tagging\Traits;

use Masum\Tagging\Models\Tag;
use Masum\Tagging\Models\TagConfig;
use Masum\Tagging\Events\TagCreated;
use Masum\Tagging\Events\TagUpdated;
use Masum\Tagging\Events\TagDeleted;
use Masum\Tagging\Events\TagGenerationFailed;
use Masum\Tagging\Exceptions\TagGenerationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Tagable
{
    /**
     * Boot the Tagable trait.
     */
    public static function bootTagable(): void
    {
        static::retrieved(function ($model) {
            if (defined(static::class.'::TAGABLE') && !in_array('tag', $model->appends ?? [])) {
                $model->appends[] = 'tag';
            }
        });

        // Automatically generate tags after saving (for both new and existing models)
        static::saved(function ($model) {
            // Check if tag already exists in database (not just the loaded relationship)
            $existingTag = Tag::where('taggable_type', get_class($model))
                ->where('taggable_id', $model->id)
                ->first();

            if (!$existingTag) {
                try {
                    $tagValue = $model->generateNextTag();
                    $tagConfig = $model->tag_config;

                    // Create tag relationship after model is saved and has an ID
                    $tag = Tag::create([
                        'taggable_type' => get_class($model),
                        'taggable_id' => $model->id,
                        'value' => $tagValue
                    ]);

                    // Dispatch TagCreated event
                    event(new TagCreated($tag, $model, $tagConfig));
                } catch (\Exception $e) {
                    Log::error('Tag generation failed', [
                        'model' => get_class($model),
                        'id' => $model->id,
                        'error' => $e->getMessage()
                    ]);

                    // Dispatch TagGenerationFailed event
                    event(new TagGenerationFailed($model, $e, null));

                    // Re-throw if not in production
                    if (config('app.debug')) {
                        throw $e;
                    }
                }
            }
        });

        // Automatically delete tags when model is deleted
        static::deleting(function ($model) {
            $tag = $model->tag()->first();

            if ($tag) {
                $tagValue = $tag->value;
                $tag->delete();

                // Dispatch TagDeleted event
                event(new TagDeleted(
                    $tagValue,
                    get_class($model),
                    $model->id
                ));
            }
        });
    }

    /**
     * Get the tag associated with the model.
     */
    public function tag(): MorphOne
    {
        return $this->morphOne(Tag::class, 'taggable');
    }

    /**
     * Get the tag value as an attribute.
     *
     * PERFORMANCE: This method checks if the relationship is already loaded
     * to avoid N+1 queries. Use eager loading for best performance:
     * Model::with('tag')->get()
     */
    public function getTagAttribute(): ?string
    {
        // Check if relationship is already loaded (prevents N+1 queries)
        if ($this->relationLoaded('tag')) {
            return $this->getRelation('tag')?->value;
        }

        // Warn in debug mode about missing eager loading
        if (config('app.debug') && config('tagging.performance.debug_n_plus_one', true)) {
            Log::warning('Tag relationship not eager loaded - potential N+1 query', [
                'model' => static::class,
                'id' => $this->id ?? 'unsaved',
                'tip' => 'Use Model::with("tag")->get() for better performance'
            ]);
        }

        // Fall back to querying (not optimal)
        return $this->tag()->first()?->value;
    }

    /**
     * Get the related TagConfig model as an attribute.
     *
     * PERFORMANCE: Uses caching by default to avoid repeated database queries.
     */
    public function getTagConfigAttribute(): ?TagConfig
    {
        return TagConfig::forModel(get_class($this));
    }

    /**
     * Define the TagConfig relationship.
     *
     * Note: This is kept for compatibility but not a true BelongsTo relationship.
     * Use the tag_config attribute accessor instead.
     */
    public function tagConfig(): BelongsTo
    {
        return $this->belongsTo(TagConfig::class, 'model')->withDefault(function () {
            return TagConfig::where('model', get_class($this))->first();
        });
    }

    /**
     * Get the custom label for printing on barcode labels.
     *
     * Models can define a TAG_LABEL constant to customize the label.
     * The label supports variable interpolation using {attribute} syntax.
     *
     * Example: const TAG_LABEL = 'Brand: {name}';
     *
     * @return string
     */
    public function getTagLabel(): string
    {
        // Check if model has defined TAG_LABEL constant
        if (defined(static::class.'::TAG_LABEL')) {
            $label = constant(static::class.'::TAG_LABEL');

            // Replace {attribute} with actual values
            $label = preg_replace_callback('/{(\w+(?:\.\w+)*)}/', function ($matches) {
                $attribute = $matches[1];

                // Support nested attributes like {category.name}
                if (str_contains($attribute, '.')) {
                    $parts = explode('.', $attribute);
                    $value = $this;

                    foreach ($parts as $part) {
                        if (is_object($value)) {
                            $value = $value->$part ?? null;
                        } else {
                            $value = null;
                            break;
                        }
                    }

                    return $value ?? $matches[0];
                }

                // Simple attribute access
                return $this->$attribute ?? $matches[0];
            }, $label);

            return $label;
        }

        // Default label: just the model class basename
        return class_basename(static::class);
    }

    /**
     * Set the tag value.
     */
    public function setTagAttribute($value): void
    {
        if ($value) {
            $existingTag = $this->tag()->first();
            $oldValue = $existingTag?->value;

            $tag = $this->tag()->updateOrCreate(
                ['taggable_type' => get_class($this), 'taggable_id' => $this->id],
                ['value' => $value]
            );

            // Dispatch TagUpdated event if value changed
            if ($oldValue && $oldValue !== $value) {
                event(new TagUpdated($tag, $this, $oldValue));
            } elseif (!$oldValue) {
                // If no old value, this is a creation
                event(new TagCreated($tag, $this, $this->tag_config));
            }
        } else {
            $tag = $this->tag()->first();
            if ($tag) {
                $tagValue = $tag->value;
                $tag->delete();

                // Dispatch TagDeleted event
                event(new TagDeleted($tagValue, get_class($this), $this->id));
            }
        }
    }

    /**
     * Generate the next tag for this model.
     */
    public function generateNextTag(): string
    {
        if ($this->tag) {
            return $this->tag;
        }

        $tagConfig = $this->tag_config;

        if ($tagConfig && $tagConfig->number_format === 'sequential') {
            return $this->generateSequentialTag($tagConfig);
        } elseif ($tagConfig && $tagConfig->number_format === 'random') {
            return $this->generateRandomTag($tagConfig);
        } elseif ($tagConfig && $tagConfig->number_format === 'branch_based') {
            return $this->generateBranchBasedTag($tagConfig);
        }

        // Fallback if no tagConfig configuration found
        return $this->generateFallbackTag();
    }

    /**
     * Generate a sequential tag with race condition protection.
     *
     * Uses database-level pessimistic locking and atomic counter increments
     * to prevent duplicate tags in high-concurrency scenarios.
     */
    protected function generateSequentialTag(TagConfig $tagConfig): string
    {
        $maxRetries = config('tagging.performance.max_retries', 3);
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($tagConfig) {
                    // Lock the config row to prevent concurrent tag generation
                    $config = TagConfig::where('id', $tagConfig->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$config) {
                        throw new \RuntimeException('Tag configuration not found');
                    }

                    // Atomic increment of the counter
                    $config->increment('current_number');
                    $nextNumber = $config->current_number;

                    // Use configured padding length
                    $paddingLength = $config->padding_length ?? 3;

                    return "{$config->prefix}{$config->separator}" . str_pad(
                        $nextNumber,
                        $paddingLength,
                        '0',
                        STR_PAD_LEFT
                    );
                });
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    Log::error('Failed to generate sequential tag after retries', [
                        'model' => static::class,
                        'config_id' => $tagConfig->id,
                        'attempts' => $attempt,
                        'error' => $e->getMessage()
                    ]);

                    // Fall back to timestamp-based tag
                    return $this->generateFallbackTag();
                }

                // Exponential backoff: wait 10ms * 2^attempt
                usleep(10000 * pow(2, $attempt));
            }
        }

        return $this->generateFallbackTag();
    }

    /**
     * Generate a random (timestamp-based) tag.
     */
    protected function generateRandomTag(TagConfig $tagConfig): string
    {
        return "{$tagConfig->prefix}{$tagConfig->separator}" . time();
    }

    /**
     * Generate a branch-based tag with race condition protection.
     *
     * Similar to sequential tags but scoped by branch_id.
     */
    protected function generateBranchBasedTag(TagConfig $tagConfig): string
    {
        $branchId = $this->branch_id ?? 'null';
        $maxRetries = config('tagging.performance.max_retries', 3);
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($tagConfig, $branchId) {
                    // Lock the config row
                    $config = TagConfig::where('id', $tagConfig->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$config) {
                        throw new \RuntimeException('Tag configuration not found');
                    }

                    // For branch-based, we need to find the max for this specific branch
                    // since different branches can have the same sequence number
                    $maxNumber = Tag::where('taggable_type', get_class($this))
                        ->where('value', 'LIKE', "{$config->prefix}{$config->separator}%{$config->separator}{$branchId}")
                        ->get()
                        ->map(function ($tag) use ($config) {
                            $parts = explode($config->separator, $tag->value);
                            return isset($parts[1]) ? (int) $parts[1] : 0;
                        })
                        ->max() ?? 0;

                    $nextNumber = $maxNumber + 1;
                    $paddingLength = $config->padding_length ?? 3;

                    return "{$config->prefix}{$config->separator}" . str_pad(
                        $nextNumber,
                        $paddingLength,
                        '0',
                        STR_PAD_LEFT
                    ) . "{$config->separator}{$branchId}";
                });
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    Log::error('Failed to generate branch-based tag after retries', [
                        'model' => static::class,
                        'config_id' => $tagConfig->id,
                        'branch_id' => $branchId,
                        'attempts' => $attempt,
                        'error' => $e->getMessage()
                    ]);

                    return $this->generateFallbackTag();
                }

                usleep(10000 * pow(2, $attempt));
            }
        }

        return $this->generateFallbackTag();
    }

    /**
     * Generate a fallback tag when no configuration exists.
     */
    protected function generateFallbackTag(): string
    {
        return config('tagging.fallback_prefix', 'TAG') . '-' . time();
    }

    /**
     * Ensure this model has a tag, generating one if it doesn't exist.
     */
    public function ensureTag(): void
    {
        if (!$this->tag) {
            $this->tag = $this->generateNextTag();
            $this->save();
        }
    }
}
