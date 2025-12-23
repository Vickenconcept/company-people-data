<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedEmail extends Model
{
    protected $fillable = [
        'lead_result_id',
        'person_id',
        'subject',
        'body',
        'custom_context',
    ];

    public function leadResult(): BelongsTo
    {
        return $this->belongsTo(LeadResult::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
