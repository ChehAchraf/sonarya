<?php

use App\Enums\ClinicalForm;
use App\Enums\Gender;
use App\Models\ClinicalExam;
use App\Models\ComplementaryExam;
use App\Models\DiseaseProfile;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users cannot access research or export', function () {
    $this->get('/research')->assertRedirect('/login');
    $this->get('/research/export')->assertRedirect('/login');
});

test('authenticated user can view research index with cohort statistics', function () {
    $user = User::factory()->create();

    $femalePatient = Patient::factory()->create([
        'gender' => Gender::Female,
        'city' => 'Tanger',
    ]);
    DiseaseProfile::factory()->create([
        'patient_id' => $femalePatient->id,
        'current_clinical_form' => ClinicalForm::RelapsingRemitting,
    ]);
    ClinicalExam::factory()->create([
        'patient_id' => $femalePatient->id,
        'edss_score' => 2.5,
    ]);
    Treatment::factory()->create([
        'patient_id' => $femalePatient->id,
        'molecule_name' => 'Diméthylfumarate (Tecfidera)',
        'status' => 'en_cours',
    ]);
    ComplementaryExam::factory()->create([
        'patient_id' => $femalePatient->id,
        'pl_oligoclonal_bands' => 'positives',
    ]);

    $malePatient = Patient::factory()->create([
        'gender' => Gender::Male,
        'city' => 'Tétouan',
    ]);
    DiseaseProfile::factory()->create([
        'patient_id' => $malePatient->id,
        'current_clinical_form' => ClinicalForm::SecondaryProgressive,
    ]);
    ClinicalExam::factory()->create([
        'patient_id' => $malePatient->id,
        'edss_score' => 5.0,
    ]);

    $response = $this->actingAs($user)->get('/research');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Research/Index')
            ->has('patients.data', 2)
            ->where('statistics.total_patients', 2)
            ->where('statistics.female_count', 1)
            ->where('statistics.male_count', 1)
            ->where('statistics.clinical_forms.remittente', 1)
            ->where('statistics.clinical_forms.secondaire', 1)
            ->has('statistics.relapse_edss_correlation')
            ->has('statistics.cohort_arr')
            ->has('availableMolecules')
            ->has('availableCities')
        );
});

test('research index filters patients by gender and clinical form', function () {
    $user = User::factory()->create();

    $patientA = Patient::factory()->create(['gender' => Gender::Female]);
    DiseaseProfile::factory()->create([
        'patient_id' => $patientA->id,
        'current_clinical_form' => ClinicalForm::RelapsingRemitting,
    ]);

    $patientB = Patient::factory()->create(['gender' => Gender::Male]);
    DiseaseProfile::factory()->create([
        'patient_id' => $patientB->id,
        'current_clinical_form' => ClinicalForm::PrimaryProgressive,
    ]);

    // Filter female only
    $responseA = $this->actingAs($user)->get('/research?gender=female');
    $responseA->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Research/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.id', $patientA->id)
        );

    // Filter PP only
    $responseB = $this->actingAs($user)->get('/research?clinical_form=primaire_progressive');
    $responseB->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Research/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.id', $patientB->id)
        );
});

test('research index filters patients by treatment molecule and BOC status', function () {
    $user = User::factory()->create();

    $patientA = Patient::factory()->create();
    Treatment::factory()->create([
        'patient_id' => $patientA->id,
        'molecule_name' => 'Ocrélizumab (Ocrevus)',
        'status' => 'en_cours',
    ]);
    ComplementaryExam::factory()->create([
        'patient_id' => $patientA->id,
        'pl_oligoclonal_bands' => 'positives',
    ]);

    $patientB = Patient::factory()->create();
    Treatment::factory()->create([
        'patient_id' => $patientB->id,
        'molecule_name' => 'Tériflunomide (Aubagio)',
        'status' => 'en_cours',
    ]);
    ComplementaryExam::factory()->create([
        'patient_id' => $patientB->id,
        'pl_oligoclonal_bands' => 'negatives',
    ]);

    $response = $this->actingAs($user)->get('/research?treatment_molecule=Ocrevus&boc=positives');
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Research/Index')
            ->has('patients.data', 1)
            ->where('patients.data.0.id', $patientA->id)
        );
});

test('authenticated user can stream export in pseudonymized mode (Loi 09-08)', function () {
    $user = User::factory()->create();

    $patient = Patient::factory()->create([
        'first_name' => 'Fatima',
        'last_name' => 'Zahra',
        'city' => 'Tanger',
    ]);
    DiseaseProfile::factory()->create([
        'patient_id' => $patient->id,
        'current_clinical_form' => ClinicalForm::RelapsingRemitting,
    ]);

    $response = $this->actingAs($user)->get('/research/export?pseudonymize=1');

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    // Capture streamed content
    ob_start();
    $response->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('ID_RECHERCHE_PSEUDONYME')
        ->and($csvContent)->toContain('SEP-')
        ->and($csvContent)->toContain('TAILLE_CM')
        ->and($csvContent)->toContain('POIDS_KG')
        ->and($csvContent)->toContain('IMC')
        ->and($csvContent)->toContain('TAUX_ANNUALISE_POUSSEES_ARR')
        ->and($csvContent)->toContain('EDSS_AVANT_DERNIERE_POUSSEE')
        ->and($csvContent)->toContain('INDEX_KAPPA_IEPP')
        ->and($csvContent)->toContain('TEST_MARCHE_T25FW_SEC')
        ->and($csvContent)->toContain('DERNIERE_CONSULTATION_TYPE')
        ->and($csvContent)->not->toContain('Fatima')
        ->and($csvContent)->not->toContain('Zahra');
});

test('authenticated user can stream export in nominative clinical mode', function () {
    $user = User::factory()->create();

    $patient = Patient::factory()->create([
        'first_name' => 'Mohammed',
        'last_name' => 'El Idrissi',
        'cin' => 'KB123456',
    ]);

    $response = $this->actingAs($user)->get('/research/export?pseudonymize=0');

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    ob_start();
    $response->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('NUMERO_DOSSIER')
        ->and($csvContent)->toContain('Mohammed')
        ->and($csvContent)->toContain('El Idrissi')
        ->and($csvContent)->toContain('KB123456');
});
