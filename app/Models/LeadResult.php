<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadResult extends Model
{
    protected $fillable = [
        'lead_request_id',
        'company_id',
        'person_id',
        'similarity_score',
        'status',
        'notes',
    ];

    protected $casts = [
        'similarity_score' => 'decimal:2',
    ];

    public function leadRequest(): BelongsTo
    {
        return $this->belongsTo(LeadRequest::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function queuedEmails(): HasMany
    {
        return $this->hasMany(QueuedEmail::class);
    }

    public function generatedEmail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GeneratedEmail::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'lead_result_tag');
    }
}
