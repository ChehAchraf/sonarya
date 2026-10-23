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
        Schema::table('complementary_exams', function (Blueprint $table) {
            $table->decimal('iepp_kappa_index', 8, 2)->nullable()->after('iepp_result');
        });

        Schema::table('clinical_exams', function (Blueprint $table) {
            $table->decimal('t25fw', 6, 2)->nullable()->after('edss_score');
            $table->decimal('nine_hpt', 6, 2)->nullable()->after('t25fw');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->string('consultation_type', 30)->default('suivi_rdv')->index()->after('consultation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complementary_exams', function (Blueprint $table) {
            $table->dropColumn('iepp_kappa_index');
        });

        Schema::table('clinical_exams', function (Blueprint $table) {
            $table->dropColumn(['t25fw', 'nine_hpt']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('consultation_type');
        });
    }
};
