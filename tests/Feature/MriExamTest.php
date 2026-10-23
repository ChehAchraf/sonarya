<?php

use App\Models\MriExam;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

test('unauthenticated users cannot store, update or delete mri exams', function () {
    $patient = Patient::factory()->create();
    $mri = MriExam::factory()->create(['patient_id' => $patient->id]);

    $this->post("/patients/{$patient->id}/mri-exams", [])
        ->assertRedirect('/login');

    $this->put("/patients/{$patient->id}/mri-exams/{$mri->id}", [])
        ->assertRedirect('/login');

    $this->delete("/patients/{$patient->id}/mri-exams/{$mri->id}")
        ->assertRedirect('/login');
});

test('authenticated user can store a new mri exam for a patient', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $payload = [
        'exam_date' => '2025-01-20',
        'timing' => 'baseline',
        'region' => 'cerebrale',
        'sep_protocol_applied' => true,
        'sequences_available' => 'T1 3D, T2 FLAIR 3D, T2 TSE, Diffusion B1000, T1 3D + Gado',
        'lesions_count_estimated' => '≥ 10',
        'lesions_locations' => 'Multiples hypersignaux périventriculaires et juxtacorticales disséminées dans l\'espace',
        'contrast_enhancement' => 'positive',
        'new_lesions_count' => 0,
        'radiologist_report' => 'Aspect d\'affection démyélinisante répondant aux critères de dissémination spatiale de McDonald 2017 avec 2 prises de contraste actives au gadolinium.',
        'dicom_study_uid' => '1.2.840.113619.2.55.3.60',
        'notes' => 'Examen réalisé sur IRM Siemens 3 Tesla, protocole SEP respecté sans artefact.',
    ];

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/mri-exams", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    expect($patient->mriExams)->toHaveCount(1);

    $mri = $patient->mriExams->first();
    expect($mri->exam_date?->format('Y-m-d'))->toBe('2025-01-20')
        ->and($mri->timing)->toBe('baseline')
        ->and($mri->region)->toBe('cerebrale')
        ->and($mri->sep_protocol_applied)->toBeTrue()
        ->and($mri->contrast_enhancement)->toBe('positive')
        ->and($mri->lesions_count_estimated)->toBe('≥ 10')
        ->and($mri->dicom_study_uid)->toBe('1.2.840.113619.2.55.3.60');

    // Verify Spatie activity log
    $activity = Activity::forSubject($mri)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('mri_exam');
});

test('store mri exam validates required fields', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/mri-exams", [
        // empty payload
    ]);

    $response->assertSessionHasErrors(['exam_date', 'timing', 'region']);
});

test('store mri exam validates exam date cannot be in the future', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $futureDate = now()->addDays(5)->format('Y-m-d');

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/mri-exams", [
        'exam_date' => $futureDate,
        'timing' => 'baseline',
        'region' => 'cerebrale',
    ]);

    $response->assertSessionHasErrors(['exam_date']);
});

test('store mri exam validates timing and region enum values', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/mri-exams", [
        'exam_date' => '2025-01-10',
        'timing' => 'invalid_timing',
        'region' => 'invalid_region',
    ]);

    $response->assertSessionHasErrors(['timing', 'region']);
});

test('authenticated user can store mri exam with file upload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $image = UploadedFile::fake()->image('coupe_flair.jpg', 600, 600);

    $payload = [
        'exam_date' => '2025-02-15',
        'timing' => 'control',
        'region' => 'medullaire',
        'sep_protocol_applied' => true,
        'images' => [$image],
    ];

    $response = $this->actingAs($user)->post("/patients/{$patient->id}/mri-exams", $payload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $patient->refresh();
    $mri = $patient->mriExams->first();

    expect($mri->key_images)->toBeArray()
        ->and(count($mri->key_images))->toBe(1)
        ->and($mri->key_images[0]['name'])->toBe('coupe_flair.jpg');

    $storedPath = str_replace('/storage/', '', $mri->key_images[0]['path']);
    Storage::disk('public')->assertExists($storedPath);
});

test('authenticated user can update an existing mri exam', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $mri = MriExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-06-01',
        'timing' => 'baseline',
        'region' => 'cerebrale',
        'contrast_enhancement' => 'negative',
    ]);

    $updatePayload = [
        'exam_date' => '2024-06-01',
        'timing' => 'rebaseline_6m',
        'region' => 'cerebrale',
        'sep_protocol_applied' => true,
        'contrast_enhancement' => 'positive',
        'new_lesions_count' => 1,
        'radiologist_report' => 'Apparition d\'une nouvelle lésion périventriculaire gauche rehaussée après injection.',
    ];

    $response = $this->actingAs($user)->put("/patients/{$patient->id}/mri-exams/{$mri->id}", $updatePayload);

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    $mri->refresh();
    expect($mri->timing)->toBe('rebaseline_6m')
        ->and($mri->contrast_enhancement)->toBe('positive')
        ->and($mri->new_lesions_count)->toBe(1)
        ->and($mri->radiologist_report)->toBe('Apparition d\'une nouvelle lésion périventriculaire gauche rehaussée après injection.');
});

test('user cannot update an mri exam belonging to another patient', function () {
    $user = User::factory()->create();
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $mriPatient2 = MriExam::factory()->create([
        'patient_id' => $patient2->id,
        'exam_date' => '2024-01-10',
        'timing' => 'baseline',
        'region' => 'cerebrale',
    ]);

    $response = $this->actingAs($user)->put("/patients/{$patient1->id}/mri-exams/{$mriPatient2->id}", [
        'exam_date' => '2024-01-10',
        'timing' => 'control',
        'region' => 'cerebrale',
    ]);

    $response->assertNotFound();
});

test('authenticated user can delete an mri exam', function () {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $mri = MriExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-06-15',
        'timing' => 'control',
        'region' => 'cerebrale',
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient->id}/mri-exams/{$mri->id}");

    $response->assertRedirect("/patients/{$patient->id}")
        ->assertSessionHas('success');

    expect(MriExam::find($mri->id))->toBeNull()
        ->and(MriExam::withTrashed()->find($mri->id))->not->toBeNull();
});

test('user cannot delete an mri exam belonging to another patient', function () {
    $user = User::factory()->create();
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $mriPatient2 = MriExam::factory()->create([
        'patient_id' => $patient2->id,
        'exam_date' => '2024-01-10',
        'timing' => 'control',
        'region' => 'medullaire',
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient1->id}/mri-exams/{$mriPatient2->id}");

    $response->assertNotFound();
});

test('authenticated user can update mri exam and remove deleted images from storage', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Storage::disk('public')->put("mri_files/{$patient->id}/slice_1.jpg", 'slice 1 content');
    Storage::disk('public')->put("mri_files/{$patient->id}/slice_2.jpg", 'slice 2 content');

    $mri = MriExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-06-01',
        'timing' => 'baseline',
        'region' => 'cerebrale',
        'key_images' => [
            [
                'name' => 'slice_1.jpg',
                'path' => "/storage/mri_files/{$patient->id}/slice_1.jpg",
                'size' => 1234,
            ],
            [
                'name' => 'slice_2.jpg',
                'path' => "/storage/mri_files/{$patient->id}/slice_2.jpg",
                'size' => 5678,
            ],
        ],
    ]);

    $retained = [
        [
            'name' => 'slice_2.jpg',
            'path' => "/storage/mri_files/{$patient->id}/slice_2.jpg",
            'size' => 5678,
        ],
    ];

    $response = $this->actingAs($user)->put("/patients/{$patient->id}/mri-exams/{$mri->id}", [
        'exam_date' => '2024-06-01',
        'timing' => 'baseline',
        'region' => 'cerebrale',
        'retained_images' => json_encode($retained),
    ]);

    $response->assertRedirect("/patients/{$patient->id}");

    $mri->refresh();
    expect($mri->key_images)->toHaveCount(1)
        ->and($mri->key_images[0]['name'])->toBe('slice_2.jpg');

    Storage::disk('public')->assertMissing("mri_files/{$patient->id}/slice_1.jpg");
    Storage::disk('public')->assertExists("mri_files/{$patient->id}/slice_2.jpg");
});

test('authenticated user can destroy a single image from mri exam directly', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    Storage::disk('public')->put("mri_files/{$patient->id}/slice_a.jpg", 'slice A');
    Storage::disk('public')->put("mri_files/{$patient->id}/slice_b.jpg", 'slice B');

    $mri = MriExam::factory()->create([
        'patient_id' => $patient->id,
        'exam_date' => '2024-06-01',
        'timing' => 'baseline',
        'region' => 'cerebrale',
        'key_images' => [
            ['name' => 'slice_a.jpg', 'path' => "/storage/mri_files/{$patient->id}/slice_a.jpg"],
            ['name' => 'slice_b.jpg', 'path' => "/storage/mri_files/{$patient->id}/slice_b.jpg"],
        ],
    ]);

    $response = $this->actingAs($user)->delete("/patients/{$patient->id}/mri-exams/{$mri->id}/images/0");

    $response->assertRedirect()
        ->assertSessionHas('success');

    $mri->refresh();
    expect($mri->key_images)->toHaveCount(1)
        ->and($mri->key_images[0]['name'])->toBe('slice_b.jpg');

    Storage::disk('public')->assertMissing("mri_files/{$patient->id}/slice_a.jpg");
    Storage::disk('public')->assertExists("mri_files/{$patient->id}/slice_b.jpg");
});
