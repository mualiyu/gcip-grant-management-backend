<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'applicant_id',
        'program_id',
        'status',
        'pre_qualification_status',
    ];

    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, "applicant_id", 'id');
    }

     public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, "program_id", 'id');
    }

    public function app_decisions(): HasMany
    {
        return $this->hasMany(ApplicationDecision::class, "application_id", 'id');
    }

    public function app_document(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, "application_id", 'id');
    }


}
