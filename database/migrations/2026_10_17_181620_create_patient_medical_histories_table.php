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
        Schema::create('patient_medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $table->text('medical_history')->nullable();
            $table->text('surgical_history')->nullable();
            $table->string('smoking', 50)->nullable();
            $table->string('alcohol', 50)->nullable();
            $table->text('toxic_other')->nullable();
            $table->unsignedTinyInteger('pregnancies_count')->nullable();
            $table->unsignedTinyInteger('deliveries_count')->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->text('gyneco_notes')->nullable();
            $table->boolean('family_history_neurological')->default(false);
            $table->boolean('family_history_autoimmune')->default(false);
            $table->text('family_history_details')->nullable();
            $table->boolean('has_unspecified_history')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_histories');
    }
};
