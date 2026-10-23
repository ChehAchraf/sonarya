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
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('height', 5, 1)->nullable()->after('profession')->comment('Taille en cm');
            $table->decimal('weight', 5, 1)->nullable()->after('height')->comment('Poids en kg');
            $table->decimal('imc', 4, 1)->nullable()->after('weight')->comment('Indice de Masse Corporelle calculé');
            $table->unsignedTinyInteger('deliveries_count')->nullable()->after('pregnancies_count');
            $table->text('postpartum_info')->nullable()->after('deliveries_count');
            $table->boolean('family_history_sep')->default(false)->after('postpartum_info');
            $table->boolean('family_history_neurological')->default(false)->after('family_history_sep');
            $table->boolean('family_history_autoimmune')->default(false)->after('family_history_neurological');
            $table->text('family_history_details')->nullable()->after('family_history_autoimmune');
            $table->text('medical_history_notes')->nullable()->after('family_history_details');
            $table->text('surgical_history_notes')->nullable()->after('medical_history_notes');
        });

        Schema::table('patient_medical_histories', function (Blueprint $table) {
            $table->boolean('family_history_sep')->default(false)->after('family_history_autoimmune');
            $table->text('postpartum_info')->nullable()->after('gyneco_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'height',
                'weight',
                'imc',
                'deliveries_count',
                'postpartum_info',
                'family_history_sep',
                'family_history_neurological',
                'family_history_autoimmune',
                'family_history_details',
                'medical_history_notes',
                'surgical_history_notes',
            ]);
        });

        Schema::table('patient_medical_histories', function (Blueprint $table) {
            $table->dropColumn(['family_history_sep', 'postpartum_info']);
        });
    }
};
