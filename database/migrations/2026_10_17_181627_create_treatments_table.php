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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('category', 30)->index(); // fond, poussee, symptomatique
            $table->string('molecule_name', 100)->index();
            $table->string('therapeutic_class', 100)->nullable();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('en_cours')->index(); // en_cours, arrete, remplace
            $table->string('dosage_regimen', 150)->nullable();
            $table->string('administration_route', 50)->nullable();
            $table->text('prescription_reason')->nullable();
            $table->text('stop_reason')->nullable();
            $table->text('adverse_effects')->nullable();
            $table->unsignedTinyInteger('relapses_after_two_years')->nullable();
            $table->boolean('vitamin_d_supplementation')->default(false);
            $table->boolean('antidepressants')->default(false);
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
        Schema::dropIfExists('treatments');
    }
};
