<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_business_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->longText('the_critical_need_for_the_technology')->nullable();
            $table->longText('the_critical_needs_for_the_grant')->nullable();
            $table->string('carried_out_market_survey')->nullable();
            $table->longText('survey_doc')->nullable();
            $table->longText('valuable_additions_that_makes_your_technology_stand_out')->nullable();
            $table->longText('consideration_for_direct_and_indirect_carbon_emissions_in_design')->nullable();
            $table->string('acquired_authority_of_the_patent_owners')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_business_proposals');
    }
};
