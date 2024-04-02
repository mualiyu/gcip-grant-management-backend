<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEligibility extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'application_id',
        'nigerian_origin',
        'incorporated_for_profit_clean_tech_company',
        'years_of_existence',
        'does_your_company_possess_an_innovative_idea',
        'does_your_company_require_assistance_to_upscale',
        'to_what_extent_are_your_challenges_financial_in_nature',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, "application_id", 'id');
    }

}
