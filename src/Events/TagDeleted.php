<?php

namespace Masum\Tagging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Masum\Tagging\Models\Tag;

/**
 * Event dispatched when a tag is deleted.
 */
class TagDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $tagValue,
        public string $taggableType,
        public int $taggableId
    ) {
        //
    }
}
