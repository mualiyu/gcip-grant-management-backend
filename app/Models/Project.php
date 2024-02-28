<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'lot_name',
        'name_of_community',
        'description',
        'state',
        'lga',
        'coordinate',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, "program_id", 'id');
    }

    public function project_documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, "project_id", 'id');
    }

    public function project_requirements(): HasMany
    {
        return $this->hasMany(ProjectRequirement::class, "project_id", 'id');
    }

    public function assigned_applicants(): BelongsToMany
    {
        return $this->belongsToMany(Applicant::class);
    }

}
