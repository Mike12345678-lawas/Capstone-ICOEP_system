<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /**
     * Canonical list of allowed academic programs for the institution.
     */
    public const PROGRAMS = [
        'BSIT'    => 'BSIT - Bachelor of Science in Information Technology',
        'BSHM'    => 'BSHM - Bachelor of Science in Hospitality Management',
        'BSBA-FM' => 'BSBA-FM - Bachelor of Science in Business Administration major in Financial Management',
        'BSEd'    => 'BSEd - Bachelor of Secondary Education',
        'BEEd'    => 'BEEd - Bachelor of Elementary Education',
    ];

    protected $fillable = ['name', 'code'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
