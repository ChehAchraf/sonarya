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
        Schema::create('disease_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $table->date('first_symptom_date')->nullable();
            $table->unsignedTinyInteger('first_symptom_age_estimated')->nullable();
            $table->date('diagnosis_date')->nullable();
            $table->unsignedTinyInteger('diagnosis_age_estimated')->nullable();
            $table->unsignedSmallInteger('prediagnostic_duration_months')->nullable();
            $table->string('initial_clinical_form', 30)->default('RR')->index();
            $table->string('current_clinical_form', 30)->default('RR')->index();
            $table->text('first_symptoms_description')->nullable();
            $table->string('diagnostic_criteria', 100)->nullable();
            $table->decimal('initial_edss', 3, 1)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_profiles');
    }
};
