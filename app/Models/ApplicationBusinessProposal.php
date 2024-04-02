<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationBusinessProposal extends Model
{
    use HasFactory;

    /**
         * The attributes that are mass assignable.
         *
         * @var array<int, string>
         */
        protected $fillable = [
            'application_id',
            'the_critical_need_for_the_technology',
            'the_critical_needs_for_the_grant',
            'carried_out_market_survey',
            'survey_doc',
            'valuable_additions_that_makes_your_technology_stand_out',
            'consideration_for_direct_and_indirect_carbon_emissions_in_design',
            'acquired_authority_of_the_patent_owners',
        ];

        public function application(): BelongsTo
        {
            return $this->belongsTo(Application::class, "application_id", 'id');
        }
}
