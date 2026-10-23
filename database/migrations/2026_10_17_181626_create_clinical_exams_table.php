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
        Schema::create('clinical_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('exam_date')->index();
            $table->string('syndrome_pyramidal', 30)->default('absent');
            $table->string('syndrome_vestibulaire', 30)->default('absent');
            $table->string('syndrome_ataxo_spasmodique', 30)->default('absent');
            $table->string('syndrome_medullaire', 30)->default('absent');
            $table->string('norb', 30)->default('absent'); // Névrite optique rétrobulbaire
            $table->string('cranial_nerves_impairment', 30)->default('absent');
            $table->string('cranial_nerves_details', 255)->nullable();
            $table->text('other_signs')->nullable();
            $table->decimal('edss_score', 3, 1)->index();
            $table->json('functional_systems_scores')->nullable();
            $table->string('evaluator_name', 100)->nullable();
            $table->text('clinical_comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_exams');
    }
};
