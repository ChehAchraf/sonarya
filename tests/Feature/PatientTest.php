<?php

use App\Enums\ClinicalForm;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PatientStatus;
use App\Models\DiseaseProfile;
use App\Models\Patient;
use App\Models\Relapse;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('it creates a patient record with proper casts and computed attributes', function () {
    $user = User::factory()->create();

    $patient = Patient::factory()->create([
        'first_name' => 'Sara',
        'last_name' => 'El Amrani',
        'gender' => Gender::Female,
        'birth_date' => now()->subYears(32)->format('Y-m-d'),
        'status' => PatientStatus::Active,
        'created_by' => $user->id,
    ]);

    expect($patient->full_name)->toBe('Sara El Amrani')
        ->and($patient->age)->toBe(32)
        ->and($patient->gender)->toBe(Gender::Female)
        ->and($patient->status)->toBe(PatientStatus::Active)
        ->and($patient->creator->id)->toBe($user->id);
});

test('it logs activity when a patient is created or updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $patient = Patient::factory()->create([
        'first_name' => 'Karim',
        'last_name' => 'Bennani',
        'city' => 'Casablanca',
    ]);

    $creationLog = Activity::forSubject($patient)->first();
    expect($creationLog)->not->toBeNull()
        ->and($creationLog->log_name)->toBe('patient');

    $patient->update([
        'city' => 'Tanger',
    ]);

    expect(Activity::forSubject($patient)->count())->toBe(2);
});

test('it establishes clinical domain relationships cleanly without n+1 lazy loading issues', function () {
    $patient = Patient::factory()->create();

    DiseaseProfile::create([
        'patient_id' => $patient->id,
        'first_symptom_date' => '2024-01-15',
        'diagnosis_date' => '2024-06-20',
        'initial_clinical_form' => ClinicalForm::RelapsingRemitting,
        'current_clinical_form' => ClinicalForm::RelapsingRemitting,
        'initial_edss' => 1.5,
    ]);

    Relapse::create([
        'patient_id' => $patient->id,
        'start_date' => '2025-02-10',
        'symptoms' => 'Névrite optique rétrobulbaire gauche',
        'is_before_diagnosis' => false,
        'treatment_administered' => 'Bolus Solumédrol 1g x 3j',
        'clinical_recovery' => 'Complète',
    ]);

    // Test eager loading
    $loadedPatient = Patient::with(['diseaseProfile', 'relapses'])->findOrFail($patient->id);

    expect($loadedPatient->diseaseProfile)->not->toBeNull()
        ->and($loadedPatient->diseaseProfile->initial_clinical_form)->toBe(ClinicalForm::RelapsingRemitting)
        ->and($loadedPatient->diseaseProfile->initial_edss)->toBe('1.5')
        ->and($loadedPatient->relapses)->toHaveCount(1)
        ->and($loadedPatient->relapses->first()->clinical_recovery)->toBe('Complète');
});

test('it supports soft deletes for patient record safety', function () {
    $patient = Patient::factory()->create();
    $patientId = $patient->id;

    $patient->delete();

    expect(Patient::find($patientId))->toBeNull()
        ->and(Patient::withTrashed()->find($patientId))->not->toBeNull();
});

test('unauthenticated users cannot access patients index', function () {
    $this->get('/patients')
        ->assertRedirect('/login');
});

test('authenticated doctor can access patients list with search and filters', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Fatima',
        'last_name' => 'Zahra',
        'city' => 'Tanger',
        'status' => PatientStatus::Active,
    ]);

    $response = $this->actingAs($user)->get('/patients?search=Fatima');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.first_name', 'Fatima')
            ->has('statuses')
            ->has('genders')
        );
});

test('unauthenticated users cannot access patients create or store', function () {
    $this->get('/patients/create')
        ->assertRedirect('/login');

    $this->post('/patients', [])
        ->assertRedirect('/login');
});

test('authenticated user can access patients create page with suggested dossier number', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/patients/create');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Create')
            ->has('suggested_dossier_number')
            ->has('statuses')
            ->has('genders')
            ->has('marital_statuses')
        );
});

test('authenticated user can store patient with complete valid data', function () {
    $user = User::factory()->create();

    $payload = [
        'dossier_number' => 'SEP-2026-9999',
        'first_name' => 'Kawtar',
        'last_name' => 'El Fassi',
        'cin' => 'KB123456',
        'gender' => Gender::Female->value,
        'birth_date' => '1995-05-12',
        'marital_status' => MaritalStatus::Single->value,
        'phone' => '0612345678',
        'city' => 'Tanger',
        'address' => 'Boulevard Mohamed V, Résidence Al Amal',
        'profession' => 'Ingénieur Réseaux',
        'education_level' => 'Supérieur / Universitaire',
        'emergency_contact_name' => 'Ahmed El Fassi',
        'emergency_contact_phone' => '0698765432',
        'referring_doctor' => 'Dr. Benjelloun',
        'status' => PatientStatus::Active->value,
        'notes' => 'Première consultation SEP.',
    ];

    $response = $this->actingAs($user)->post('/patients', $payload);

    $response->assertRedirect('/patients')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('patients', [
        'dossier_number' => 'SEP-2026-9999',
        'first_name' => 'Kawtar',
        'last_name' => 'El Fassi',
        'cin' => 'KB123456',
        'gender' => Gender::Female->value,
        'city' => 'Tanger',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
});

test('store patient auto-generates dossier number when left blank', function () {
    $user = User::factory()->create();

    $payload = [
        'first_name' => 'Youssef',
        'last_name' => 'Mansouri',
        'gender' => Gender::Male->value,
        'birth_date' => '1988-03-20',
        'status' => PatientStatus::Active->value,
    ];

    $response = $this->actingAs($user)->post('/patients', $payload);

    $response->assertRedirect('/patients');

    $patient = Patient::where('first_name', 'Youssef')->where('last_name', 'Mansouri')->first();
    expect($patient)->not->toBeNull()
        ->and($patient->dossier_number)->toStartWith('SEP-'.date('Y').'-');
});

test('store patient fails validation when required fields are missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/patients', []);

    $response->assertSessionHasErrors([
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'status',
    ]);
});

test('store patient fails validation on duplicate dossier number', function () {
    $user = User::factory()->create();
    $existing = Patient::factory()->create([
        'dossier_number' => 'SEP-2026-0001',
    ]);

    $response = $this->actingAs($user)->post('/patients', [
        'dossier_number' => 'SEP-2026-0001',
        'first_name' => 'Nadia',
        'last_name' => 'Tazi',
        'gender' => Gender::Female->value,
        'birth_date' => '1992-07-15',
        'status' => PatientStatus::Active->value,
    ]);

    $response->assertSessionHasErrors(['dossier_number']);
});

test('unauthenticated users cannot view patient details', function () {
    $patient = Patient::factory()->create();

    $this->get("/patients/{$patient->id}")
        ->assertRedirect('/login');
});

test('authenticated user can view complete patient dossier with clinical summary and relationships', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Sara',
        'last_name' => 'El Amrani',
        'dossier_number' => 'SEP-2026-0042',
    ]);

    DiseaseProfile::create([
        'patient_id' => $patient->id,
        'first_symptom_date' => '2023-04-10',
        'diagnosis_date' => '2023-11-20',
        'initial_clinical_form' => ClinicalForm::RelapsingRemitting,
        'current_clinical_form' => ClinicalForm::RelapsingRemitting,
        'initial_edss' => 2.0,
        'first_symptoms_description' => 'NORB droite typique',
    ]);

    Relapse::create([
        'patient_id' => $patient->id,
        'start_date' => '2024-02-15',
        'symptoms' => 'Paresthésies hémicorps gauche',
        'is_before_diagnosis' => false,
        'treatment_administered' => 'Bolus Solumédrol 1g',
        'clinical_recovery' => 'Complète',
    ]);

    $response = $this->actingAs($user)->get("/patients/{$patient->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/Show')
            ->where('patient.dossier_number', 'SEP-2026-0042')
            ->where('patient.full_name', 'Sara El Amrani')
            ->has('patient.disease_profile')
            ->has('patient.relapses', 1)
            ->where('summary.relapses_count', 1)
            ->where('summary.clinical_form_short', 'SEP-RR')
        );
});

test('unauthenticated users cannot access disease profile edit or update', function () {
    $patient = Patient::factory()->create();

    $this->get("/patients/{$patient->id}/disease-profile")
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/disease-profile", [])
        ->assertRedirect('/login');
});

test('authenticated user can access disease profile edit form with clinical options', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Kawtar',
        'last_name' => 'El Fassi',
        'gender' => Gender::Female,
    ]);

    $response = $this->actingAs($user)->get("/patients/{$patient->id}/disease-profile");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Patients/DiseaseProfile/Edit')
            ->where('patient.id', $patient->id)
            ->where('patient.full_name', 'Kawtar El Fassi')
            ->has('clinical_forms')
            ->has('disease_profile')
            ->has('medical_history')
        );
});

test('authenticated user can update disease profile and medical history successfully', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create([
        'first_name' => 'Ahmed',
        'last_name' => 'Chraibi',
        'gender' => Gender::Male,
        'birth_date' => '1990-01-15',
    ]);

    $payload = [
        'first_symptom_date' => '2023-03-01',
        'first_symptom_age_estimated' => 33,
        'diagnosis_date' => '2023-09-15',
        'diagnosis_age_estimated' => 33,
        'prediagnostic_duration_months' => 6,
        'initial_clinical_form' => ClinicalForm::RelapsingRemitting->value,
        'current_clinical_form' => ClinicalForm::SecondaryProgressive->value,
        'first_symptoms_description' => 'Névrite optique rétrobulbaire et paresthésies',
        'diagnostic_criteria' => 'Critères de McDonald 2017 (DIS + DIT)',
        'initial_edss' => 2.5,
        'notes' => 'Transition progressive observée.',
        'medical_history' => 'Appendicectomie en 2010',
        'surgical_history' => 'Aucune autre chirurgie',
        'smoking' => 'Non',
        'alcohol' => 'Non',
        'toxic_other' => 'Néant',
        'family_history_neurological' => true,
        'family_history_autoimmune' => false,
        'family_history_details' => 'Cousin germain atteint de SEP',
    ];

    $response = $this->actingAs($user)->put("/patients/{$patient->id}/disease-profile", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->diseaseProfile)->not->toBeNull()
        ->and($patient->diseaseProfile->first_symptom_date?->format('Y-m-d'))->toBe('2023-03-01')
        ->and($patient->diseaseProfile->diagnosis_date?->format('Y-m-d'))->toBe('2023-09-15')
        ->and($patient->diseaseProfile->initial_clinical_form)->toBe(ClinicalForm::RelapsingRemitting)
        ->and($patient->diseaseProfile->current_clinical_form)->toBe(ClinicalForm::SecondaryProgressive)
        ->and($patient->diseaseProfile->prediagnostic_duration_months)->toBe(6)
        ->and($patient->diseaseProfile->diagnostic_criteria)->toBe('Critères de McDonald 2017 (DIS + DIT)')
        ->and($patient->medicalHistory)->not->toBeNull()
        ->and($patient->medicalHistory->smoking)->toBe('Non')
        ->and($patient->medicalHistory->family_history_neurological)->toBeTrue()
        ->and($patient->medicalHistory->family_history_autoimmune)->toBeFalse()
        ->and($patient->medicalHistory->family_history_details)->toBe('Cousin germain atteint de SEP');
});

test('authenticated user can store and update patient with updated health coverage including custom private insurance', function () {
    $user = User::factory()->create();

    $payload = [
        'first_name' => 'Khadija',
        'last_name' => 'Benali',
        'gender' => Gender::Female->value,
        'birth_date' => '1995-04-12',
        'status' => PatientStatus::Active->value,
        'health_coverage' => ['CNSS', 'AMO', 'Autre: Saham Assurance'],
    ];

    $response = $this->actingAs($user)->post('/patients', $payload);
    $response->assertRedirect('/patients');

    $patient = Patient::where('first_name', 'Khadija')->first();
    expect($patient)->not->toBeNull()
        ->and($patient->health_coverage)->toBe(['CNSS', 'AMO', 'Autre: Saham Assurance']);

    // Now update with Payant direct
    $updatePayload = array_merge($payload, [
        'dossier_number' => $patient->dossier_number,
        'health_coverage' => ['Sans assurance (Payant direct)', 'Autre: RMA Watanya'],
    ]);

    $updateResponse = $this->actingAs($user)->put("/patients/{$patient->id}", $updatePayload);
    $updateResponse->assertRedirect("/patients/{$patient->id}");

    $patient->refresh();
    expect($patient->health_coverage)->toBe(['Sans assurance (Payant direct)', 'Autre: RMA Watanya']);
});

test('disease profile update validates required fields like clinical forms', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->put("/patients/{$patient->id}/disease-profile", [
        // empty payload omitting required clinical forms
    ]);

    $response->assertSessionHasErrors([
        'initial_clinical_form',
        'current_clinical_form',
    ]);
});
