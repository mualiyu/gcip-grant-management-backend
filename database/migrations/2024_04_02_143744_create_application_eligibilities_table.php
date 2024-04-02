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
        Schema::create('application_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('nigerian_origin')->nullable();
            $table->string('incorporated_for_profit_clean_tech_company')->nullable();
            $table->string('years_of_existence')->nullable();
            $table->string('does_your_company_possess_an_innovative_idea')->nullable();
            $table->string('does_your_company_require_assistance_to_upscale')->nullable();
            $table->string('to_what_extent_are_your_challenges_financial_in_nature')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_eligibilities');
    }
};
