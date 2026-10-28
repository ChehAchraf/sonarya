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
            $table->date('edss_before_date')->nullable()->after('edss_before_relapse');
            $table->decimal('edss_during_relapse', 3, 1)->nullable()->after('edss_before_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relapses', function (Blueprint $table) {
            $table->dropColumn([
                'edss_before_date',
                'edss_during_relapse',
            ]);
        });
    }
};
