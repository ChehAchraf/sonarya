<?php

use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\ClinicalExamController;
use App\Http\Controllers\ComplementaryExamController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiseaseProfileController;
use App\Http\Controllers\MriExamController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientImportController;
use App\Http\Controllers\RelapseController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('patients/import/template', [PatientImportController::class, 'template'])->name('patients.import.template');
    Route::post('patients/import', [PatientImportController::class, 'import'])->name('patients.import');
    Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    Route::get('patients/{patient}/disease-profile', [DiseaseProfileController::class, 'edit'])->name('patients.disease-profile.edit');
    Route::put('patients/{patient}/disease-profile', [DiseaseProfileController::class, 'update'])->name('patients.disease-profile.update');

    Route::post('patients/{patient}/relapses', [RelapseController::class, 'store'])->name('patients.relapses.store');
    Route::put('patients/{patient}/relapses/{relapse}', [RelapseController::class, 'update'])->name('patients.relapses.update');
    Route::delete('patients/{patient}/relapses/{relapse}', [RelapseController::class, 'destroy'])->name('patients.relapses.destroy');

    Route::post('patients/{patient}/clinical-exams', [ClinicalExamController::class, 'store'])->name('patients.clinical-exams.store');
    Route::put('patients/{patient}/clinical-exams/{clinicalExam}', [ClinicalExamController::class, 'update'])->name('patients.clinical-exams.update');
    Route::delete('patients/{patient}/clinical-exams/{clinicalExam}', [ClinicalExamController::class, 'destroy'])->name('patients.clinical-exams.destroy');

    Route::post('patients/{patient}/mri-exams', [MriExamController::class, 'store'])->name('patients.mri-exams.store');
    Route::put('patients/{patient}/mri-exams/{mriExam}', [MriExamController::class, 'update'])->name('patients.mri-exams.update');
    Route::delete('patients/{patient}/mri-exams/{mriExam}', [MriExamController::class, 'destroy'])->name('patients.mri-exams.destroy');
    Route::delete('patients/{patient}/mri-exams/{mriExam}/images/{imageIndex}', [MriExamController::class, 'destroyImage'])->name('patients.mri-exams.images.destroy');

    Route::post('patients/{patient}/treatments', [TreatmentController::class, 'store'])->name('patients.treatments.store');
    Route::put('patients/{patient}/treatments/{treatment}', [TreatmentController::class, 'update'])->name('patients.treatments.update');
    Route::delete('patients/{patient}/treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('patients.treatments.destroy');

    Route::post('patients/{patient}/consultations', [ConsultationController::class, 'store'])->name('patients.consultations.store');
    Route::put('patients/{patient}/consultations/{consultation}', [ConsultationController::class, 'update'])->name('patients.consultations.update');
    Route::delete('patients/{patient}/consultations/{consultation}', [ConsultationController::class, 'destroy'])->name('patients.consultations.destroy');

    Route::post('patients/{patient}/complementary-exams', [ComplementaryExamController::class, 'store'])->name('patients.complementary-exams.store');
    Route::put('patients/{patient}/complementary-exams/{complementaryExam}', [ComplementaryExamController::class, 'update'])->name('patients.complementary-exams.update');
    Route::delete('patients/{patient}/complementary-exams/{complementaryExam}', [ComplementaryExamController::class, 'destroy'])->name('patients.complementary-exams.destroy');

    Route::get('research', [ResearchController::class, 'index'])->name('research.index');
    Route::get('research/export', [ResearchController::class, 'export'])->name('research.export');

    // Audit Trail & CNDP Traceability (Admin & Authorized Medical Personnel)
    Route::get('audit-trail', [AuditTrailController::class, 'index'])
        ->middleware(['role_or_permission:admin|view activity logs'])
        ->name('audit-trail.index');

    // Medical Staff & User Administration (Admin only)
    Route::middleware(['role_or_permission:admin|manage users'])->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

require __DIR__.'/settings.php';
