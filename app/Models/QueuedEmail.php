<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuedEmail extends Model
{
    protected $fillable = [
        'lead_result_id',
        'person_id',
        'to_email',
        'subject',
        'body',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'replied_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'replied_at' => 'datetime',
        'metadata' => 'array',
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
