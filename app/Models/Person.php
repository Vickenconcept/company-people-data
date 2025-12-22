<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'full_name',
        'title',
        'email',
        'phone',
        'linkedin_url',
        'twitter_handle',
        'bio',
        'metadata',
        'data_source',
        'external_id',
        'email_verified',
    ];

    protected $casts = [
        'metadata' => 'array',
        'email_verified' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leadResults(): HasMany
    {
        return $this->hasMany(LeadResult::class);
    }

    public function queuedEmails(): HasMany
    {
        return $this->hasMany(QueuedEmail::class);
    }
}
