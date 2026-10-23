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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('dossier_number', 50)->unique();
            $table->string('cin', 30)->nullable()->index();
            $table->string('first_name', 100)->index();
            $table->string('last_name', 100)->index();
            $table->string('gender', 20)->index();
            $table->date('birth_date')->index();
            $table->string('phone', 30)->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('profession', 150)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('education_level', 100)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('referring_doctor', 150)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
