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
        Schema::create('relapses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable();
            $table->text('symptoms');
            $table->boolean('is_before_diagnosis')->default(false)->index();
            $table->string('treatment_administered', 255)->nullable();
            $table->string('administration_route', 50)->nullable();
            $table->string('clinical_recovery', 50)->nullable();
            $table->text('sequelae')->nullable();
            $table->boolean('hospitalization_required')->default(false);
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
        Schema::dropIfExists('relapses');
    }
};
