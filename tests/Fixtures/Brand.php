<?php

namespace Masum\Tagging\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Masum\Tagging\Traits\Tagable;

class Brand extends Model
{
    use Tagable;

    const TAGABLE = 'Brand';
    const TAG_LABEL = 'Brand: {name}';

    protected $fillable = ['name'];
}
