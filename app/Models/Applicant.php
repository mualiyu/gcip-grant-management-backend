<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Applicant extends Model
{
    use HasFactory, HasApiTokens;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'username',
        'email',
        'password',
        'person_incharge',
        'rc_number',
        'address',

        'has_operated',
        'has_designed',
        'cac_certificate',
        'tax_clearance_certificate',
        'isApproved',
        // 'cac_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function jvs(): HasMany
    {
        return $this->hasMany(JV::class, "applicant_id", 'id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, "applicant_id", 'id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, "applicant_id", 'id');
    }


    public function applicant_uploaded_documents(): HasMany
    {
        return $this->hasMany(ApplicantProjectDocument::class, "applicant_id", 'id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function proposal(): HasMany
    {
        return $this->hasMany(ApplicantProposal::class,  "applicant_id", 'id');
    }

}
