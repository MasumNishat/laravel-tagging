<?php

namespace Masum\Tagging\Exceptions;

/**
 * Exception thrown when tag generation fails.
 */
class TagGenerationException extends TaggingException
{
    /**
     * Create a new exception for failed tag generation.
     */
    public static function configNotFound(string $modelClass): self
    {
        return new self("Tag configuration not found for model: {$modelClass}");
    }

    /**
     * Create a new exception for concurrent generation failures.
     */
    public static function concurrencyFailure(string $modelClass, int $attempts): self
    {
        return new self(
            "Failed to generate tag for {$modelClass} after {$attempts} attempts due to concurrency conflicts"
        );
    }

    /**
     * Create a new exception for invalid configuration.
     */
    public static function invalidConfig(string $reason): self
    {
        return new self("Invalid tag configuration: {$reason}");
    }
}
