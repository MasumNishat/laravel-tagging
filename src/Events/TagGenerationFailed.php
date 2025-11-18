<?php

namespace Masum\Tagging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Event dispatched when tag generation fails.
 */
class TagGenerationFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public mixed $taggable,
        public Throwable $exception,
        public ?string $fallbackTag = null
    ) {
        //
    }
}
