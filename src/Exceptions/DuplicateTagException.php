<?php

namespace Masum\Tagging\Exceptions;

/**
 * Exception thrown when attempting to create a duplicate tag.
 */
class DuplicateTagException extends TaggingException
{
    /**
     * Create a new exception for duplicate tag.
     */
    public static function forModel(string $modelClass, int $modelId): self
    {
        return new self(
            "A tag already exists for {$modelClass} with ID {$modelId}"
        );
    }

    /**
     * Create a new exception for duplicate tag value.
     */
    public static function valueExists(string $value): self
    {
        return new self("Tag value '{$value}' already exists");
    }
}
