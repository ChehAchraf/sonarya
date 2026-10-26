<?php

use App\Models\ComplementaryExam;
use App\Models\Patient;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('unauthenticated users cannot store, update or delete complementary exams', function () {
    $patient = Patient::factory()->create();
    $exam = ComplementaryExam::factory()->create(['patient_id' => $patient->id]);

    $this->post("/patients/{$patient->id}/complementary-exams", [])
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/complementary-exams/{$exam->id}", [])
        ->assertRedirect('/login');

    $this->delete("/patients/{$patient->id}/complementary-exams/{$exam->id}")
        ->assertRedirect('/login');
});

test('authenticated user can store a new complementary exam for a patient', function () {
    $doctor = User::factory()->create(['name' => 'Dr. Neuro']);
    $patient = Patient::factory()->create();

    $payload = [
        'exam_date' => '2024-05-20',
        'exam_type' => 'Ponction Lombaire & Biologie',
        'pl_result' => 'pathologique',
        'pl_cytorachie' => 4.5,
        'pl_proteinorachie' => 0.48,
        'pl_oligoclonal_bands' => 'positives',
        'biology_anti_nmo' => 'negative',
        'biology_anti_mog' => 'negative',
        'biology_anti_antinucleaire' => 'negative',
        'biology_anti_bgsa' => 'negative',
        'biology_serology_hbv' => 'negative',
        'biology_serology_hcv' => 'negative',
        'biology_serology_vzv' => 'positive',
        'biology_serology_cmv' => 'negative',
        'biology_serology_ebv' => 'positive',
        'biology_serology_jcv' => 'negative',
        'biology_other' => 'Sérologie VIH et TPHA-VDRL négatives',
        'iepp_sample_type' => 'sang_et_lcr',
        'iepp_result' => 'Profil de type 2 : Synthèse intrathécale d\'IgG exclusive',
        'notes' => 'Critère de dissémination temporelle biologique validé.',
    ];

    $response = $this->actingAs($doctor)
        ->post("/patients/{$patient->id}/complementary-exams", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->complementaryExams)->toHaveCount(1);

    $exam = $patient->complementaryExams->first();
    expect($exam->exam_type)->toBe('Ponction Lombaire & Biologie')
        ->and($exam->exam_date?->format('Y-m-d'))->toBe('2024-05-20')
        ->and($exam->pl_result)->toBe('pathologique')
        ->and((float) $exam->pl_cytorachie)->toBe(4.5)
        ->and((float) $exam->pl_proteinorachie)->toBe(0.48)
        ->and($exam->pl_oligoclonal_bands)->toBe('positives')
        ->and($exam->biology_anti_nmo)->toBe('negative')
        ->and($exam->biology_anti_mog)->toBe('negative')
        ->and($exam->biology_serology_hcv)->toBe('negative')
        ->and($exam->biology_serology_vzv)->toBe('positive')
        ->and($exam->biology_serology_jcv)->toBe('negative')
        ->and($exam->iepp_sample_type)->toBe('sang_et_lcr');

    // Verify Spatie activity log
    $activity = Activity::forSubject($exam)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('complementary_exam');
});

test('store complementary exam validates required fields', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)
        ->post("/patients/{$patient->id}/complementary-exams", []);

    $response->assertSessionHasErrors(['exam_date', 'exam_type']);
});

test('store complementary exam validates exam date cannot be in the future', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $futureDate = now()->addDays(5)->format('Y-m-d');

    $response = $this->actingAs($user)
        ->post("/patients/{$patient->id}/complementary-exams", [
            'exam_date' => $futureDate,
            'exam_type' => 'Ponction Lombaire',
        ]);

    $response->assertSessionHasErrors(['exam_date']);
});

test('authenticated user can update an existing complementary exam', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $exam = ComplementaryExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-01-10',
        'biology_anti_nmo' => 'non_disponible',
    ]);

    $updatePayload = [
        'exam_date' => '2024-01-10',
        'exam_type' => 'Biologie Spécifique',
        'pl_result' => 'pathologique',
        'pl_cytorachie' => 2.0,
        'pl_proteinorachie' => 0.35,
        'pl_oligoclonal_bands' => 'positives',
        'biology_anti_nmo' => 'negative',
        'biology_anti_mog' => 'negative',
        'biology_anti_antinucleaire' => 'negative',
        'biology_anti_bgsa' => 'negative',
        'biology_serology_hbv' => 'negative',
        'biology_other' => 'Mise à jour des résultats sérologiques reçus du laboratoire',
        'iepp_sample_type' => 'sang_et_lcr',
        'iepp_result' => 'Profil 2 confirmé',
        'notes' => 'Dossier biologique complété.',
    ];

    $response = $this->actingAs($user)
        ->put("/patients/{$patient->id}/complementary-exams/{$exam->id}", $updatePayload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $exam->refresh();
    expect($exam->biology_anti_nmo)->toBe('negative')
        ->and($exam->exam_type)->toBe('Biologie Spécifique')
        ->and($exam->notes)->toBe('Dossier biologique complété.');
});

test('cannot update or delete a complementary exam belonging to another patient', function () {
    $user = User::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    $examA = ComplementaryExam::factory()->create([
        'patient_id' => $patientA->id,
        'exam_type' => 'Ponction Lombaire',
    ]);

    // Try updating A's exam through B's route
    $response = $this->actingAs($user)
        ->put("/patients/{$patientB->id}/complementary-exams/{$examA->id}", [
            'exam_date' => '2024-01-01',
            'exam_type' => 'Ponction Lombaire',
        ]);

    $response->assertNotFound();

    // Try deleting A's exam through B's route
    $deleteResponse = $this->actingAs($user)
        ->delete("/patients/{$patientB->id}/complementary-exams/{$examA->id}");

    $deleteResponse->assertNotFound();
});

test('authenticated user can delete a complementary exam', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $exam = ComplementaryExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_type' => 'Biologie Spécifique',
    ]);

    $response = $this->actingAs($user)
        ->delete("/patients/{$patient->id}/complementary-exams/{$exam->id}");

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    expect(ComplementaryExam::find($exam->id))->toBeNull();
    expect(ComplementaryExam::withTrashed()->find($exam->id))->not->toBeNull();
});
