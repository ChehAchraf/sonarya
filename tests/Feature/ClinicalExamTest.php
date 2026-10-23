<?php

use App\Models\ClinicalExam;
use App\Models\Patient;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('unauthenticated users cannot store, update or delete clinical exams', function () {
    $patient = Patient::factory()->create();
    $exam = ClinicalExam::factory()->create(['patient_id' => $patient->id]);

    $this->post("/patients/{$patient->id}/clinical-exams", [])
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/clinical-exams/{$exam->id}", [])
        ->assertRedirect('/login');

    $this->delete("/patients/{$patient->id}/clinical-exams/{$exam->id}")
        ->assertRedirect('/login');
});

test('authenticated user can store a new clinical exam for a patient', function () {
    $user = User::factory()->create(['name' => 'Dr. Neurologiste']);
    $patient = Patient::factory()->create();

    $payload = [
        'exam_date' => '2025-01-15',
        'edss_score' => 3.5,
        'syndrome_pyramidal' => 'bilateral',
        'syndrome_vestibulaire' => 'modere',
        'syndrome_ataxo_spasmodique' => 'leger',
        'syndrome_medullaire' => 'absent',
        'norb' => 'sequellaire',
        'cranial_nerves_impairment' => 'present',
        'cranial_nerves_details' => 'Atteinte du nerf facial (VII) gauche périphérique résiduelle',
        'other_signs' => 'Signe de Lhermitte positif à la flexion de la nuque',
        'functional_systems_scores' => [
            'pyramidal' => 2,
            'cerebelleux' => 2,
            'tronc' => 1,
            'sensitif' => 1,
            'vesico_sphincterien' => 0,
            'visuel' => 1,
            'fonctions_mentales' => 0,
        ],
        'evaluator_name' => 'Dr. Neurologiste',
        'clinical_comment' => 'Évolution stable sous traitement. Autonomie à la marche conservée sans aide technique.',
    ];

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/clinical-exams", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->clinicalExams)->toHaveCount(1);

    $exam = $patient->clinicalExams->first();
    expect((float) $exam->edss_score)->toBe(3.5)
        ->and($exam->exam_date?->format('Y-m-d'))->toBe('2025-01-15')
        ->and($exam->syndrome_pyramidal)->toBe('bilateral')
        ->and($exam->cranial_nerves_impairment)->toBe('present')
        ->and($exam->functional_systems_scores)->toBeArray()
        ->and($exam->functional_systems_scores['pyramidal'])->toBe(2)
        ->and($exam->evaluator_name)->toBe('Dr. Neurologiste');

    // Verify Spatie activity log
    $activity = Activity::forSubject($exam)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('clinical_exam');
});

test('store clinical exam validates required fields', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/clinical-exams", [
        // empty payload
    ]);

    $response->assertSessionHasErrors(['exam_date', 'edss_score']);
});

test('store clinical exam validates exam date cannot be in the future', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $futureDate = now()->addDays(5)->format('Y-m-d');

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/clinical-exams", [
        'exam_date' => $futureDate,
        'edss_score' => 2.0,
    ]);

    $response->assertSessionHasErrors(['exam_date']);
});

test('store clinical exam validates edss score range between 0 and 10', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $responseNegative = $this->actingAs($user)->post("/patients/{$patient->id}/clinical-exams", [
        'exam_date' => '2025-01-10',
        'edss_score' => -1,
    ]);
    $responseNegative->assertSessionHasErrors(['edss_score']);

    $responseTooHigh = $this->actingAs($user)->post("/patients/{$patient->id}/clinical-exams", [
        'exam_date' => '2025-01-10',
        'edss_score' => 10.5,
    ]);
    $responseTooHigh->assertSessionHasErrors(['edss_score']);
});

test('authenticated user can update an existing clinical exam', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $exam = ClinicalExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-06-01',
        'edss_score' => 2.0,
        'syndrome_pyramidal' => 'absent',
    ]);

    $updatePayload = [
        'exam_date' => '2024-06-01',
        'edss_score' => 2.5,
        'syndrome_pyramidal' => 'unilateral_droit',
        'syndrome_vestibulaire' => 'leger',
        'clinical_comment' => 'Aggravation discrète sur le plan pyramidal droit.',
    ];

    $response = $this->actingAs($user)->put("/patients/{$patient->id}/clinical-exams/{$exam->id}", $updatePayload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $exam->refresh();
    expect((float) $exam->edss_score)->toBe(2.5)
        ->and($exam->syndrome_pyramidal)->toBe('unilateral_droit')
        ->and($exam->clinical_comment)->toBe('Aggravation discrète sur le plan pyramidal droit.');
});

test('user cannot update a clinical exam belonging to another patient', function () {
    $user = User::factory()->create();
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $examPatient2 = ClinicalExam::factory()->create([
        'patient_id' => $patient2->id,
        'exam_date' => '2024-01-10',
        'edss_score' => 3.0,
    ]);

    $response = $this->actingAs($user)->put("/patients/{$patient1->id}/clinical-exams/{$examPatient2->id}", [
        'exam_date' => '2024-01-10',
        'edss_score' => 4.0,
    ]);

    $response->assertNotFound();
});

test('authenticated user can delete a clinical exam', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $exam = ClinicalExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-06-15',
        'edss_score' => 1.5,
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient->id}/clinical-exams/{$exam->id}");

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    expect(ClinicalExam::find($exam->id))->toBeNull()
        ->and(ClinicalExam::withTrashed()->find($exam->id))->not->toBeNull();
});

test('user cannot delete a clinical exam belonging to another patient', function () {
    $user = User::factory()->create();
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $examPatient2 = ClinicalExam::factory()->create([
        'patient_id' => $patient2->id,
        'exam_date' => '2024-01-10',
        'edss_score' => 2.0,
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient1->id}/clinical-exams/{$examPatient2->id}");

    $response->assertNotFound();
});
