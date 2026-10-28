<?php

use App\Models\Patient;
use App\Models\Relapse;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('unauthenticated users cannot store, update or delete relapses', function () {
    $patient = Patient::factory()->create();
    $relapse = Relapse::factory()->create(['patient_id' => $patient->id]);

    $this->post("/patients/{$patient->id}/relapses", [])
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/relapses/{$relapse->id}", [])
        ->assertRedirect('/login');

    $this->delete("/patients/{$patient->id}/relapses/{$relapse->id}")
        ->assertRedirect('/login');
});

test('authenticated user can store a new relapse for a patient', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $payload = [
        'start_date' => '2025-02-10',
        'end_date' => '2025-02-25',
        'symptoms' => 'Névrite optique rétrobulbaire gauche aiguë avec baisse visuelle à 2/10',
        'severity' => 'severe',
        'is_before_diagnosis' => false,
        'treatment_administered' => 'Bolus Solumédrol 1g/j IV x 3 jours',
        'administration_route' => 'Intraveineuse (IV)',
        'clinical_recovery' => 'Complète',
        'edss_before_relapse' => 1.5,
        'edss_before_date' => '2025-01-15',
        'edss_during_relapse' => 4.0,
        'edss_after_relapse' => 2.0,
        'edss_evaluation_date' => '2025-03-01',
        'sequelae' => null,
        'hospitalization_required' => true,
        'notes' => 'Excellente tolérance aux corticoïdes, récupération visuelle ad integrum.',
    ];

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/relapses", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->relapses)->toHaveCount(1);

    $relapse = $patient->relapses->first();
    expect($relapse->symptoms)->toBe('Névrite optique rétrobulbaire gauche aiguë avec baisse visuelle à 2/10')
        ->and($relapse->severity)->toBe('severe')
        ->and((float) $relapse->edss_before_relapse)->toBe(1.5)
        ->and($relapse->edss_before_date?->format('Y-m-d'))->toBe('2025-01-15')
        ->and((float) $relapse->edss_during_relapse)->toBe(4.0)
        ->and((float) $relapse->edss_after_relapse)->toBe(2.0)
        ->and($relapse->edss_evaluation_date?->format('Y-m-d'))->toBe('2025-03-01')
        ->and($relapse->start_date?->format('Y-m-d'))->toBe('2025-02-10')
        ->and($relapse->end_date?->format('Y-m-d'))->toBe('2025-02-25')
        ->and($relapse->treatment_administered)->toBe('Bolus Solumédrol 1g/j IV x 3 jours')
        ->and($relapse->clinical_recovery)->toBe('Complète')
        ->and($relapse->hospitalization_required)->toBeTrue();

    // Verify Spatie activity log
    $activity = Activity::forSubject($relapse)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('relapse');
});

test('store relapse validates required fields', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/relapses", [
        // empty payload
    ]);

    $response->assertSessionHasErrors(['start_date', 'symptoms']);
});

test('store relapse validates start date cannot be in the future', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $futureDate = now()->addDays(5)->format('Y-m-d');

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/relapses", [
        'start_date' => $futureDate,
        'symptoms' => 'Trouble de la marche',
    ]);

    $response->assertSessionHasErrors(['start_date']);
});

test('store relapse validates end date cannot precede start date', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/relapses", [
        'start_date' => '2025-03-10',
        'end_date' => '2025-03-01',
        'symptoms' => 'Ataxie cérébelleuse',
    ]);

    $response->assertSessionHasErrors(['end_date']);
});

test('store relapse validates edss range and severity values', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/relapses", [
        'start_date' => '2025-03-10',
        'symptoms' => 'Myélite aiguë',
        'severity' => 'invalide_statut',
        'edss_before_relapse' => 12.5,
        'edss_after_relapse' => -1.0,
    ]);

    $response->assertSessionHasErrors(['severity', 'edss_before_relapse', 'edss_after_relapse']);
});

test('authenticated user can update an existing relapse', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $relapse = Relapse::create([
        'patient_id' => $patient->id,
        'start_date' => '2024-05-01',
        'symptoms' => 'Paresthésies des membres inférieurs',
        'is_before_diagnosis' => true,
        'treatment_administered' => 'Corticothérapie orale',
        'clinical_recovery' => 'Incomplète',
        'sequelae' => 'Dysesthésies persistantes',
        'hospitalization_required' => false,
    ]);

    $updatePayload = [
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-20',
        'symptoms' => 'Paresthésies et déficit moteur modéré réévalué',
        'is_before_diagnosis' => true,
        'treatment_administered' => 'Bolus Solumédrol 1g x 5j',
        'administration_route' => 'Intraveineuse (IV)',
        'clinical_recovery' => 'Complète',
        'edss_during_relapse' => 3.0,
        'edss_after_relapse' => 1.0,
        'sequelae' => null,
        'hospitalization_required' => true,
        'notes' => 'Récupération différée constatée lors du bilan de contrôle.',
    ];

    $response = $this->actingAs($user)->put("/patients/{$patient->id}/relapses/{$relapse->id}", $updatePayload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $relapse->refresh();
    expect($relapse->symptoms)->toBe('Paresthésies et déficit moteur modéré réévalué')
        ->and($relapse->treatment_administered)->toBe('Bolus Solumédrol 1g x 5j')
        ->and($relapse->clinical_recovery)->toBe('Complète')
        ->and((float) $relapse->edss_during_relapse)->toBe(3.0)
        ->and((float) $relapse->edss_after_relapse)->toBe(1.0)
        ->and($relapse->hospitalization_required)->toBeTrue()
        ->and($relapse->sequelae)->toBeNull();
});

test('user cannot update a relapse belonging to another patient', function () {
    $user = User::factory()->create();
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $relapsePatient2 = Relapse::create([
        'patient_id' => $patient2->id,
        'start_date' => '2024-01-10',
        'symptoms' => 'Diplopie binoculaire',
    ]);

    $response = $this->actingAs($user)->put("/patients/{$patient1->id}/relapses/{$relapsePatient2->id}", [
        'start_date' => '2024-01-10',
        'symptoms' => 'Tentative de modification non autorisée',
    ]);

    $response->assertNotFound();
});

test('authenticated user can delete a relapse', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $relapse = Relapse::create([
        'patient_id' => $patient->id,
        'start_date' => '2024-06-15',
        'symptoms' => 'Faiblesse musculaire membre supérieur droit',
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient->id}/relapses/{$relapse->id}");

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    expect(Relapse::find($relapse->id))->toBeNull()
        ->and(Relapse::withTrashed()->find($relapse->id))->not->toBeNull();
});

test('user cannot delete a relapse belonging to another patient', function () {
    $user = User::factory()->create();
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $relapsePatient2 = Relapse::create([
        'patient_id' => $patient2->id,
        'start_date' => '2024-01-10',
        'symptoms' => 'Trouble de la déglutition',
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient1->id}/relapses/{$relapsePatient2->id}");

    $response->assertNotFound();
});
