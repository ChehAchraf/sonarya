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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('consultation_date')->index();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('doctor_name', 100)->nullable();
            $table->text('intercurrent_events')->nullable();
            $table->unsignedTinyInteger('relapses_since_last_visit')->default(0);
            $table->decimal('edss_score', 3, 1)->nullable();
            $table->text('current_treatment_evaluation')->nullable();
            $table->text('tolerance_and_adherence')->nullable();
            $table->text('recent_exam_results')->nullable();
            $table->text('therapeutic_decision')->nullable();
            $table->date('next_appointment_date')->nullable();
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
        Schema::dropIfExists('consultations');
    }
};
