<?php

namespace Masum\Tagging\Traits;

use Masum\Tagging\Models\Tag;
use Masum\Tagging\Models\TagConfig;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
                // Create tag relationship after model is saved and has an ID
                Tag::create([
                    'taggable_type' => get_class($model),
                    'taggable_id' => $model->id,
                    'value' => $model->generateNextTag()
                ]);
            }
        });

        // Automatically delete tags when model is deleted
        static::deleting(function ($model) {
            $model->tag()->delete();
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
     */
    public function getTagAttribute(): ?string
    {
        return $this->tag()->first()?->value;
    }

    /**
     * Get the related TagConfig model as an attribute.
     */
    public function getTagConfigAttribute(): ?TagConfig
    {
        $tagConfig = TagConfig::where('model', get_class($this))->first();

        return $tagConfig ?: $this->tagConfig()->getResults();
    }

    /**
     * Define the TagConfig relationship.
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
            $this->tag()->updateOrCreate(
                ['taggable_type' => get_class($this), 'taggable_id' => $this->id],
                ['value' => $value]
            );
        } else {
            $this->tag()->delete();
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
        $oldTag = Tag::where('taggable_type', get_class($this))
            ->latest()
            ->first()?->value;

        if ($tagConfig && $tagConfig->number_format === 'sequential') {
            return $this->generateSequentialTag($tagConfig, $oldTag);
        } elseif ($tagConfig && $tagConfig->number_format === 'random') {
            return $this->generateRandomTag($tagConfig);
        } elseif ($tagConfig && $tagConfig->number_format === 'branch_based') {
            return $this->generateBranchBasedTag($tagConfig, $oldTag);
        }

        // Fallback if no tagConfig configuration found
        return $this->generateFallbackTag();
    }

    /**
     * Generate a sequential tag.
     */
    protected function generateSequentialTag(TagConfig $tagConfig, ?string $oldTag): string
    {
        // Get all existing tags for this model type
        $allTags = Tag::where('taggable_type', get_class($this))
            ->pluck('value')
            ->toArray();

        // Extract all numbers from existing tags
        $existingNumbers = [];
        foreach ($allTags as $tagValue) {
            $parts = explode($tagConfig->separator, $tagValue);
            if (isset($parts[1])) {
                $existingNumbers[] = (int) $parts[1];
            }
        }

        // Find the maximum number and add 1
        $nextNumber = empty($existingNumbers) ? 1 : max($existingNumbers) + 1;

        return "{$tagConfig->prefix}{$tagConfig->separator}" . str_pad(
            $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Generate a random (timestamp-based) tag.
     */
    protected function generateRandomTag(TagConfig $tagConfig): string
    {
        return "{$tagConfig->prefix}{$tagConfig->separator}" . time();
    }

    /**
     * Generate a branch-based tag.
     */
    protected function generateBranchBasedTag(TagConfig $tagConfig, ?string $oldTag): string
    {
        $branchId = $this->branch_id ?? 'null';

        // Get all existing tags for this model type and branch
        $allTags = Tag::where('taggable_type', get_class($this))
            ->pluck('value')
            ->toArray();

        // Extract all numbers from existing tags for this branch
        $existingNumbers = [];
        foreach ($allTags as $tagValue) {
            $parts = explode($tagConfig->separator, $tagValue);
            // Check if this tag is for the same branch
            if (isset($parts[1]) && isset($parts[2]) && $parts[2] == $branchId) {
                $existingNumbers[] = (int) $parts[1];
            }
        }

        // Find the maximum number and add 1
        $nextNumber = empty($existingNumbers) ? 1 : max($existingNumbers) + 1;

        return "{$tagConfig->prefix}{$tagConfig->separator}" . str_pad(
            $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        ) . "{$tagConfig->separator}{$branchId}";
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