<?php

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('unauthenticated users cannot store, update or delete consultations', function () {
    $patient = Patient::factory()->create();
    $consultation = Consultation::factory()->create(['patient_id' => $patient->id]);

    $this->post("/patients/{$patient->id}/consultations", [])
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/consultations/{$consultation->id}", [])
        ->assertRedirect('/login');

    $this->delete("/patients/{$patient->id}/consultations/{$consultation->id}")
        ->assertRedirect('/login');
});

test('authenticated user can store a new consultation for a patient', function () {
    $doctor = User::factory()->create(['name' => 'Dr. Leila Benjelloun']);
    $patient = Patient::factory()->create();

    $payload = [
        'consultation_date' => '2025-02-10',
        'doctor_name' => 'Dr. Leila Benjelloun',
        'intercurrent_events' => 'Aucun épisode infectieux intercurrent. Bonne forme générale.',
        'relapses_since_last_visit' => 0,
        'edss_score' => 2.0,
        'current_treatment_evaluation' => 'Tecfidera 240mg x2/j bien suivi sans flush ni intolérance digestive.',
        'tolerance_and_adherence' => 'Bonne tolérance clinique et biologique, excellente observance.',
        'recent_exam_results' => 'IRM cérébrale de contrôle stable sans nouvelle prise de contraste.',
        'therapeutic_decision' => 'Maintien du traitement de fond actuel',
        'prescribed_tests' => 'NFS, plaquettes & bilan hépatique complet',
        'next_appointment_date' => '2025-08-10',
        'notes' => 'Patient rassuré. Prochain contrôle semestriel programmé.',
    ];

    $response = $this->actingAs($doctor)
        ->post("/patients/{$patient->id}/consultations", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->consultations)->toHaveCount(1);

    $c = $patient->consultations->first();
    expect($c->consultation_date?->format('Y-m-d'))->toBe('2025-02-10')
        ->and($c->doctor_name)->toBe('Dr. Leila Benjelloun')
        ->and((float) $c->edss_score)->toBe(2.0)
        ->and($c->relapses_since_last_visit)->toBe(0)
        ->and($c->therapeutic_decision)->toBe('Maintien du traitement de fond actuel')
        ->and($c->prescribed_tests)->toBe('NFS, plaquettes & bilan hépatique complet')
        ->and($c->next_appointment_date?->format('Y-m-d'))->toBe('2025-08-10');

    // Verify Spatie activity log
    $activity = Activity::forSubject($c)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('consultation');
});

test('store consultation validates required consultation_date', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)
        ->post("/patients/{$patient->id}/consultations", [
            'doctor_name' => 'Dr. Test',
        ]);

    $response->assertSessionHasErrors(['consultation_date']);
});

test('store consultation validates consultation_date cannot be in the future', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $futureDate = now()->addDays(5)->format('Y-m-d');

    $response = $this->actingAs($user)
        ->post("/patients/{$patient->id}/consultations", [
            'consultation_date' => $futureDate,
        ]);

    $response->assertSessionHasErrors(['consultation_date']);
});

test('store consultation validates edss_score between 0 and 10', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)
        ->post("/patients/{$patient->id}/consultations", [
            'consultation_date' => '2025-01-01',
            'edss_score' => 12.5,
        ]);

    $response->assertSessionHasErrors(['edss_score']);
});

test('authenticated user can update an existing consultation', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $consultation = Consultation::factory()->create([
        'patient_id' => $patient->id,
        'consultation_date' => '2025-01-15',
        'edss_score' => 3.0,
        'therapeutic_decision' => 'Maintien',
    ]);

    $updatePayload = [
        'consultation_date' => '2025-01-15',
        'doctor_name' => 'Dr. Updated',
        'edss_score' => 3.5,
        'relapses_since_last_visit' => 1,
        'intercurrent_events' => 'Poussée sensitive il y a 2 mois résolutive sous corticoïdes',
        'current_treatment_evaluation' => 'Échappement thérapeutique sous Aubagio',
        'tolerance_and_adherence' => 'Observance correcte mais efficacité insuffisante',
        'recent_exam_results' => 'IRM montre 2 nouvelles lésions en T2',
        'therapeutic_decision' => 'Switch thérapeutique vers Ocrevus programmé',
        'next_appointment_date' => '2025-04-15',
        'notes' => 'Bilan pré-thérapeutique prescrit.',
    ];

    $response = $this->actingAs($user)
        ->put("/patients/{$patient->id}/consultations/{$consultation->id}", $updatePayload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $consultation->refresh();
    expect((float) $consultation->edss_score)->toBe(3.5)
        ->and($consultation->relapses_since_last_visit)->toBe(1)
        ->and($consultation->therapeutic_decision)->toBe('Switch thérapeutique vers Ocrevus programmé')
        ->and($consultation->doctor_name)->toBe('Dr. Updated');
});

test('cannot update or delete a consultation belonging to another patient', function () {
    $user = User::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    $consultationA = Consultation::factory()->create([
        'patient_id' => $patientA->id,
        'consultation_date' => '2025-01-01',
    ]);

    // Try updating A's consultation through B's route
    $response = $this->actingAs($user)
        ->put("/patients/{$patientB->id}/consultations/{$consultationA->id}", [
            'consultation_date' => '2025-01-01',
            'edss_score' => 2.0,
        ]);

    $response->assertNotFound();

    // Try deleting A's consultation through B's route
    $deleteResponse = $this->actingAs($user)
        ->delete("/patients/{$patientB->id}/consultations/{$consultationA->id}");

    $deleteResponse->assertNotFound();
});

test('authenticated user can delete a consultation', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $consultation = Consultation::factory()->create([
        'patient_id' => $patient->id,
    ]);

    $response = $this->actingAs($user)
        ->delete("/patients/{$patient->id}/consultations/{$consultation->id}");

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    expect(Consultation::find($consultation->id))->toBeNull();
    expect(Consultation::withTrashed()->find($consultation->id))->not->toBeNull();
});
