<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends Model
{
    protected $fillable = [
        'name', 'code', 'short_name', 'established_on', 'university_type', 'accreditation',
        'official_email', 'official_phone', 'website', 'address_line_1', 'address_line_2',
        'city', 'state', 'postal_code', 'country', 'timezone',
    ];

    protected function casts(): array
    {
        return ['established_on' => 'date'];
    }

    public function colleges(): HasMany
    {
        return $this->hasMany(College::class);
    }

    public function authorizedSignatories(): HasMany
    {
        return $this->hasMany(AuthorizedSignatory::class);
    }
}
