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
        Schema::table('treatments', function (Blueprint $table) {
            $table->string('therapeutic_line')->nullable()->after('category');
            $table->boolean('has_treatment_changed')->default(false)->after('prescription_reason');
            $table->string('switch_to_molecule')->nullable()->after('has_treatment_changed');
            $table->boolean('care_access_interruption')->default(false)->after('adverse_effects');
            $table->boolean('post_stop_reactivation')->default(false)->after('care_access_interruption');
            $table->boolean('anti_spastic')->default(false)->after('antidepressants');
            $table->string('other_symptomatic')->nullable()->after('anti_spastic');
            $table->json('vaccinations')->nullable()->after('other_symptomatic');
            $table->json('pre_therapeutic_checklist')->nullable()->after('vaccinations');
            $table->string('pregnancy_contraception_notes')->nullable()->after('pre_therapeutic_checklist');
            $table->string('estimated_price_mad')->nullable()->after('pregnancy_contraception_notes');
        });

        Schema::table('complementary_exams', function (Blueprint $table) {
            $table->string('biology_serology_hcv')->nullable()->after('biology_serology_hbv');
            $table->string('biology_serology_vzv')->nullable()->after('biology_serology_hcv');
            $table->string('biology_serology_cmv')->nullable()->after('biology_serology_vzv');
            $table->string('biology_serology_ebv')->nullable()->after('biology_serology_cmv');
            $table->string('biology_serology_jcv')->nullable()->after('biology_serology_ebv');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->text('prescribed_tests')->nullable()->after('therapeutic_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('prescribed_tests');
        });

        Schema::table('complementary_exams', function (Blueprint $table) {
            $table->dropColumn([
                'biology_serology_hcv',
                'biology_serology_vzv',
                'biology_serology_cmv',
                'biology_serology_ebv',
                'biology_serology_jcv',
            ]);
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn([
                'therapeutic_line',
                'has_treatment_changed',
                'switch_to_molecule',
                'care_access_interruption',
                'post_stop_reactivation',
                'anti_spastic',
                'other_symptomatic',
                'vaccinations',
                'pre_therapeutic_checklist',
                'pregnancy_contraception_notes',
                'estimated_price_mad',
            ]);
        });
    }
};
