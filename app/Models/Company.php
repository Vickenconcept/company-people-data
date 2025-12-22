<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'website',
        'industry',
        'country',
        'city',
        'state',
        'description',
        'employee_count',
        'revenue',
        'founded_year',
        'technologies',
        'keywords',
        'icp_profile',
        'metadata',
        'data_source',
        'external_id',
    ];

    protected $casts = [
        'technologies' => 'array',
        'keywords' => 'array',
        'icp_profile' => 'array',
        'metadata' => 'array',
        'employee_count' => 'integer',
        'revenue' => 'decimal:2',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function leadResults(): HasMany
    {
        return $this->hasMany(LeadResult::class);
    }
}
