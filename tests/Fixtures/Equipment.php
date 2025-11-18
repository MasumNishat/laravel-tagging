<?php

namespace Masum\Tagging\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Masum\Tagging\Traits\Tagable;

class Equipment extends Model
{
    use Tagable;

    const TAGABLE = 'Equipment';

    protected $table = 'equipment';

    protected $fillable = ['name', 'serial_no', 'branch_id'];
}
