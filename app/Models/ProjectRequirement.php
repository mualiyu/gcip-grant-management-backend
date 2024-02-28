<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRequirement extends Model
{
    use HasFactory;

   protected $fillable = [
       'project_id',
       'name',
       'type',
   ];

   public function project(): BelongsTo
   {
       return $this->belongsTo(Project::class, "project_id", 'id');
   }

   public function applicant_project_documents(): HasMany
    {
        return $this->hasMany(ApplicantProjectDocument::class, "project_requirement_id", 'id');
    }

}
