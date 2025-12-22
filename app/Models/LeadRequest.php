<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadRequest extends Model
{
    protected $fillable = [
        'user_id',
        'reference_company_name',
        'reference_company_url',
        'reference_company_content',
        'icp_profile',
        'search_criteria',
        'target_count',
        'target_job_titles',
        'country',
        'status',
        'error_message',
        'companies_found',
        'contacts_found',
    ];

    protected $casts = [
        'icp_profile' => 'array',
        'search_criteria' => 'array',
        'target_job_titles' => 'array',
        'companies_found' => 'integer',
        'contacts_found' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leadResults(): HasMany
    {
        return $this->hasMany(LeadResult::class);
    }
}
