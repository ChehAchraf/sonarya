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
        Schema::table('relapses', function (Blueprint $table) {
            $table->string('severity', 20)->nullable()->after('symptoms');
            $table->decimal('edss_before_relapse', 3, 1)->nullable()->after('clinical_recovery');
            $table->decimal('edss_after_relapse', 3, 1)->nullable()->after('edss_before_relapse');
            $table->date('edss_evaluation_date')->nullable()->after('edss_after_relapse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relapses', function (Blueprint $table) {
            $table->dropColumn([
                'severity',
                'edss_before_relapse',
                'edss_after_relapse',
                'edss_evaluation_date',
            ]);
        });
    }
};
