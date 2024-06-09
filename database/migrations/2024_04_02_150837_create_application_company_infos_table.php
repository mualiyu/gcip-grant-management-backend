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
        Schema::create('application_company_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->longText('profile')->nullable();
            $table->longText('description_of_products')->nullable();
            $table->longText('short_term_objectives')->nullable();
            $table->longText('medium_term_objectives')->nullable();
            $table->longText('long_term_objectives')->nullable();
            $table->string('number_of_staff')->nullable();
            $table->string('organizational_chart')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_company_infos');
    }
};
