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
        Schema::create('complementary_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('exam_date')->index();
            $table->string('exam_type', 50)->index();
            $table->string('pl_result', 50)->nullable();
            $table->decimal('pl_cytorachie', 8, 2)->nullable();
            $table->decimal('pl_proteinorachie', 8, 2)->nullable();
            $table->string('pl_oligoclonal_bands', 50)->nullable();
            $table->string('biology_anti_nmo', 50)->nullable();
            $table->string('biology_anti_mog', 50)->nullable();
            $table->string('biology_anti_antinucleaire', 50)->nullable();
            $table->string('biology_anti_bgsa', 50)->nullable();
            $table->string('biology_serology_hbv', 50)->nullable();
            $table->text('biology_other')->nullable();
            $table->string('iepp_sample_type', 50)->nullable();
            $table->text('iepp_result')->nullable();
            $table->string('report_file_path', 255)->nullable();
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
        Schema::dropIfExists('complementary_exams');
    }
};
