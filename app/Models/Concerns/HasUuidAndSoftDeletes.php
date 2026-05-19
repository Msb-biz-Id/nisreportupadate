<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasUuidAndSoftDeletes
{
    use HasUuids, SoftDeletes;
}
