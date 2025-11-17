<?php

namespace Masum\Tagging\Exceptions;

/**
 * Exception thrown when a tag format is invalid.
 */
class InvalidTagFormatException extends TaggingException
{
    /**
     * Create a new exception for invalid tag format.
     */
    public static function create(string $value, string $reason): self
    {
        return new self("Invalid tag format '{$value}': {$reason}");
    }

    /**
     * Create a new exception for tag length validation.
     */
    public static function lengthExceeded(string $value, int $maxLength): self
    {
        return new self(
            "Tag value '{$value}' exceeds maximum length of {$maxLength} characters"
        );
    }

    /**
     * Create a new exception for invalid characters.
     */
    public static function invalidCharacters(string $value, string $allowedPattern): self
    {
        return new self(
            "Tag value '{$value}' contains invalid characters. Allowed pattern: {$allowedPattern}"
        );
    }
}
