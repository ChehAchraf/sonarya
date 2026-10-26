<?php

use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('unauthenticated users cannot store, update or delete treatments', function () {
    $patient = Patient::factory()->create();
    $treatment = Treatment::factory()->create(['patient_id' => $patient->id]);

    $this->post("/patients/{$patient->id}/treatments", [])
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/treatments/{$treatment->id}", [])
        ->assertRedirect('/login');

    $this->delete("/patients/{$patient->id}/treatments/{$treatment->id}")
        ->assertRedirect('/login');
});

test('authenticated user can store a new treatment for a patient', function () {
    $user = User::factory()->create(['name' => 'Dr. Neurologiste']);
    $patient = Patient::factory()->create();

    $payload = [
        'category' => 'fond',
        'molecule_name' => 'Diméthylfumarate (Tecfidera)',
        'therapeutic_class' => 'Immunomodulateur Nrf2',
        'start_date' => '2024-03-01',
        'end_date' => null,
        'status' => 'en_cours',
        'dosage_regimen' => '240 mg 2 fois par jour au milieu des repas',
        'administration_route' => 'Orale (PO)',
        'prescription_reason' => 'SEP récurrente-rémittente active en première ligne',
        'stop_reason' => null,
        'adverse_effects' => 'Flushs vasomoteurs transitoires en début de traitement',
        'relapses_after_two_years' => 0,
        'vitamin_d_supplementation' => true,
        'antidepressants' => false,
        'notes' => 'Bonne observance thérapeutique rapportée par la patiente.',
    ];

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/treatments", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->treatments)->toHaveCount(1);

    $treatment = $patient->treatments->first();
    expect($treatment->molecule_name)->toBe('Diméthylfumarate (Tecfidera)')
        ->and($treatment->category)->toBe('fond')
        ->and($treatment->start_date?->format('Y-m-d'))->toBe('2024-03-01')
        ->and($treatment->status)->toBe('en_cours')
        ->and($treatment->administration_route)->toBe('Orale (PO)')
        ->and($treatment->vitamin_d_supplementation)->toBeTrue()
        ->and($treatment->antidepressants)->toBeFalse()
        ->and($treatment->relapses_after_two_years)->toBe(0);

    // Verify Spatie activity log
    $activity = Activity::forSubject($treatment)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('treatment');
});

test('store treatment validates required fields', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/treatments", []);

    $response->assertSessionHasErrors(['molecule_name', 'category', 'start_date', 'status']);
});

test('store treatment validates start date cannot be in the future', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $futureDate = now()->addDays(10)->format('Y-m-d');

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/treatments", [
        'molecule_name' => 'Fingolimod (Gilenya)',
        'category' => 'fond',
        'start_date' => $futureDate,
        'status' => 'en_cours',
    ]);

    $response->assertSessionHasErrors(['start_date']);
});

test('store treatment validates end date cannot precede start date', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/treatments", [
        'molecule_name' => 'Fingolimod (Gilenya)',
        'category' => 'fond',
        'start_date' => '2024-06-01',
        'end_date' => '2024-01-01',
        'status' => 'arrete',
    ]);

    $response->assertSessionHasErrors(['end_date']);
});

test('authenticated user can update an existing treatment', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $treatment = Treatment::factory()->create([
        'patient_id' => $patient->id,
        'category' => 'fond',
        'molecule_name' => 'Tériflunomide (Aubagio)',
        'status' => 'en_cours',
        'start_date' => '2023-01-10',
    ]);

    $updatePayload = [
        'category' => 'fond',
        'molecule_name' => 'Tériflunomide (Aubagio)',
        'therapeutic_class' => 'Inhibiteur de la dihydro-orotate déshydrogénase (DHODH)',
        'start_date' => '2023-01-10',
        'end_date' => '2024-05-15',
        'status' => 'remplace',
        'dosage_regimen' => '14 mg 1 fois par jour',
        'administration_route' => 'Orale (PO)',
        'prescription_reason' => 'Première ligne',
        'stop_reason' => 'Échappement thérapeutique (nouvelles poussées sous traitement)',
        'adverse_effects' => 'Cytolyse hépatique modérée résolutive à l arrêt',
        'relapses_after_two_years' => 2,
        'vitamin_d_supplementation' => true,
        'antidepressants' => true,
        'notes' => 'Switch planifié vers Ocrélizumab (Ocrevus)',
    ];

    $response = $this->actingAs($user)
        ->put("/patients/{$patient->id}/treatments/{$treatment->id}", $updatePayload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $treatment->refresh();
    expect($treatment->status)->toBe('remplace')
        ->and($treatment->end_date?->format('Y-m-d'))->toBe('2024-05-15')
        ->and($treatment->stop_reason)->toBe('Échappement thérapeutique (nouvelles poussées sous traitement)')
        ->and($treatment->antidepressants)->toBeTrue()
        ->and($treatment->relapses_after_two_years)->toBe(2);
});

test('cannot update or delete a treatment belonging to another patient', function () {
    $user = User::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    $treatmentOfPatientA = Treatment::factory()->create([
        'patient_id' => $patientA->id,
        'molecule_name' => 'Interféron bêta-1a',
    ]);

    // Attempt to update treatment of patient A through patient B route
    $response = $this->actingAs($user)
        ->put("/patients/{$patientB->id}/treatments/{$treatmentOfPatientA->id}", [
            'category' => 'fond',
            'molecule_name' => 'Interféron bêta-1a',
            'start_date' => '2023-01-01',
            'status' => 'en_cours',
        ]);

    $response->assertNotFound();

    // Attempt to delete treatment of patient A through patient B route
    $deleteResponse = $this->actingAs($user)
        ->delete("/patients/{$patientB->id}/treatments/{$treatmentOfPatientA->id}");

    $deleteResponse->assertNotFound();
});

test('authenticated user can store a treatment with clinical protocol fields and therapeutic line', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $payload = [
        'category' => 'fond',
        'therapeutic_line' => 'the',
        'molecule_name' => 'Natalizumab (Tysabri 300mg)',
        'therapeutic_class' => 'Anticorps monoclonal anti-VLA-4',
        'start_date' => '2024-01-15',
        'status' => 'en_cours',
        'dosage_regimen' => '300 mg IV toutes les 4 semaines',
        'administration_route' => 'Intraveineuse (IV)',
        'has_treatment_changed' => false,
        'care_access_interruption' => false,
        'post_stop_reactivation' => false,
        'anti_spastic' => true,
        'other_symptomatic' => 'Fampridine 10mg',
        'vaccinations' => ['vhb', 'grippe', 'pneumocoque'],
        'pre_therapeutic_checklist' => ['Sérologie virus JC', 'IRM de référence'],
        'pregnancy_contraception_notes' => 'Surveillance néonatale si poursuite au 3ème trimestre.',
        'estimated_price_mad' => '14 234 DH',
        'vitamin_d_supplementation' => true,
        'antidepressants' => false,
    ];

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/treatments", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    $treatment = $patient->treatments->first();
    expect($treatment->therapeutic_line)->toBe('the')
        ->and($treatment->estimated_price_mad)->toBe('14 234 DH')
        ->and($treatment->anti_spastic)->toBeTrue()
        ->and($treatment->other_symptomatic)->toBe('Fampridine 10mg')
        ->and($treatment->vaccinations)->toBe(['vhb', 'grippe', 'pneumocoque'])
        ->and($treatment->pre_therapeutic_checklist)->toBe(['Sérologie virus JC', 'IRM de référence']);
});

test('authenticated user can delete a treatment', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $treatment = Treatment::factory()->create([
        'patient_id' => $patient->id,
        'molecule_name' => 'Méthylprednisolone (Solumédrol)',
    ]);

    $response = $this->actingAs($user)
        ->delete("/patients/{$patient->id}/treatments/{$treatment->id}");

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    expect(Treatment::find($treatment->id))->toBeNull();
    // Soft deleted
    expect(Treatment::withTrashed()->find($treatment->id))->not->toBeNull();
});
