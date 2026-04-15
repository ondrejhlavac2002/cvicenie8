<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_program',
        'faculty',
        'year_of_study',
        'expected_graduation_year',
        'bio',
        'skills',
        'portfolio_url',
        'linkedin_url',
        'github_url',
        'academic_average',
        'has_failed_courses',
        'eligibility_notes',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'has_failed_courses' => 'boolean',
            'academic_average' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
