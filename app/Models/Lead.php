<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'company', 'service', 'location', 'budget', 'website', 'message', 'source_page',
        'form_name', 'utm', 'ip', 'user_agent', 'status', 'assigned_to', 'notes', 'contacted_at',
    ];

    protected $casts = ['status' => LeadStatus::class, 'utm' => 'array', 'contacted_at' => 'datetime'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function leadNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }
}
