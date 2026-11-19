<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalLink extends Model
{
    protected $fillable = ['target_type', 'target_id', 'url', 'anchor', 'group', 'sort_order'];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function href(): string
    {
        return $this->target?->url ?? url($this->url);
    }
}
