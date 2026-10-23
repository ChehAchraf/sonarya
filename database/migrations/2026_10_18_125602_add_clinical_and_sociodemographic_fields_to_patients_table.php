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
            $table->string('origin', 150)->nullable()->after('city');
            $table->unsignedSmallInteger('children_count')->nullable()->after('marital_status');
            $table->json('health_coverage')->nullable()->after('education_level');
            $table->string('tobacco_consumption', 200)->nullable()->after('health_coverage');
            $table->string('alcohol_consumption', 200)->nullable()->after('tobacco_consumption');
            $table->unsignedSmallInteger('pregnancies_count')->nullable()->after('alcohol_consumption');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'origin',
                'children_count',
                'health_coverage',
                'tobacco_consumption',
                'alcohol_consumption',
                'pregnancies_count',
            ]);
        });
    }
};
