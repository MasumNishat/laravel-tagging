<?php

namespace Masum\Tagging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Masum\Tagging\Models\Tag;
use Masum\Tagging\Models\TagConfig;

/**
 * Event dispatched when a new tag is created.
 */
class TagCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Tag $tag,
        public mixed $taggable,
        public ?TagConfig $config = null
    ) {
        //
    }
}
