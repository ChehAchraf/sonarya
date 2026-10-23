<?php

use App\Enums\ClinicalForm;
use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;

test('authenticated user can download the patient import template csv', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('patients.import.template'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('NUMERO_DOSSIER;NOM;PRENOM;SEXE;DATE_NAISSANCE');
});

test('user can bulk import patients from excel csv file with disease profile and clinical fields', function () {
    $user = User::factory()->create();

    $csvContent = "\xEF\xBB\xBF".
        "NUMERO_DOSSIER;NOM;PRENOM;SEXE;DATE_NAISSANCE;CIN;TELEPHONE;VILLE;ORIGINE;SITUATION_FAMILIALE;NOMBRE_ENFANTS;PROFESSION;COUVERTURE_MEDICALE;TABAC;ALCOOL;NOMBRE_GROSSESSES;FORME_CLINIQUE;DATE_1ER_SYMPTOME;AGE_1ER_SYMPTOME;DATE_DIAGNOSTIC;AGE_DIAGNOSTIC;EDSS_INITIAL;NOTES\n".
        "DOS-IMP-001;BENJELLOUN;Samira;Femme;1990-04-12;BK112233;0611223344;Fès;Fès-Meknès;Marié(e);3;Architecte;CNSS, AMO;Non;Non;3;RR;2018-05-10;28;2018-11-20;28;2.0;Import de test\n".
        ";CHRAIBI;Yassine;Homme;1985-08-25;BL445566;0655667788;Rabat;Rabat-Salé;Célibataire;0;Comptable;AMO;Fumeur;Non;;SP;;32;;35;3.5;Import sans date exacte avec age direct\n";

    $file = UploadedFile::fake()->createWithContent('patients_import.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('patients.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $patient1 = Patient::where('last_name', 'BENJELLOUN')->first();
    expect($patient1)->not->toBeNull()
        ->and($patient1->dossier_number)->toBe('DOS-IMP-001')
        ->and($patient1->first_name)->toBe('Samira')
        ->and($patient1->gender)->toBe(Gender::Female)
        ->and($patient1->origin)->toBe('Fès-Meknès')
        ->and($patient1->children_count)->toBe(3)
        ->and($patient1->health_coverage)->toContain('CNSS', 'AMO')
        ->and($patient1->pregnancies_count)->toBe(3)
        ->and($patient1->diseaseProfile)->not->toBeNull()
        ->and($patient1->diseaseProfile->first_symptom_age_estimated)->toBe(28)
        ->and($patient1->diseaseProfile->initial_clinical_form)->toBe(ClinicalForm::RelapsingRemitting);

    $patient2 = Patient::where('last_name', 'CHRAIBI')->first();
    expect($patient2)->not->toBeNull()
        ->and($patient2->dossier_number)->not->toBeEmpty()
        ->and($patient2->first_name)->toBe('Yassine')
        ->and($patient2->gender)->toBe(Gender::Male)
        ->and($patient2->tobacco_consumption)->toBe('Fumeur')
        ->and($patient2->diseaseProfile)->not->toBeNull()
        ->and($patient2->diseaseProfile->first_symptom_age_estimated)->toBe(32)
        ->and($patient2->diseaseProfile->diagnosis_age_estimated)->toBe(35)
        ->and($patient2->diseaseProfile->initial_clinical_form)->toBe(ClinicalForm::SecondaryProgressive);

    $activity = Activity::where('log_name', 'import_dossiers')->latest()->first();
    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($user->id);
});

test('it validates required file when importing patients', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('patients.import'), []);

    $response->assertSessionHasErrors(['file']);
});
