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
        Schema::create('mri_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('exam_date')->index();
            $table->string('timing', 30)->index(); // baseline, rebaseline_6m, control
            $table->string('region', 30)->index(); // cerebrale, medullaire, les_deux
            $table->boolean('sep_protocol_applied')->default(true);
            $table->string('sequences_available', 255)->nullable();
            $table->string('lesions_count_estimated', 50)->nullable();
            $table->text('lesions_locations')->nullable();
            $table->string('contrast_enhancement', 50)->nullable();
            $table->unsignedSmallInteger('new_lesions_count')->nullable();
            $table->text('radiologist_report')->nullable();
            $table->string('dicom_study_uid', 150)->nullable()->index();
            $table->string('dicom_storage_path', 255)->nullable();
            $table->json('key_images')->nullable();
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
        Schema::dropIfExists('mri_exams');
    }
};
