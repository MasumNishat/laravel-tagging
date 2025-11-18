<?php

namespace Masum\Tagging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Masum\Tagging\Models\Tag;

/**
 * Event dispatched when a tag is updated.
 */
class TagUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Tag $tag,
        public mixed $taggable,
        public ?string $oldValue = null
    ) {
        //
    }
}
