<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PatientStatus;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\ClinicalExam;
use App\Models\ComplementaryExam;
use App\Models\Consultation;
use App\Models\DiseaseProfile;
use App\Models\MriExam;
use App\Models\Patient;
use App\Models\Relapse;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /**
     * Display a listing of patients with search and filtering.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $gender = $request->string('gender')->trim()->value();

        $patients = Patient::query()
            ->with(['diseaseProfile:id,patient_id,current_clinical_form'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('dossier_number', 'like', "%{$search}%")
                        ->orWhere('cin', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($gender !== '', function ($query) use ($gender): void {
                $query->where('gender', $gender);
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Patient $patient): array => [
                'id' => $patient->id,
                'dossier_number' => $patient->dossier_number,
                'full_name' => $patient->full_name,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'cin' => $patient->cin,
                'gender' => $patient->gender->value,
                'gender_label' => $patient->gender->label(),
                'birth_date' => $patient->birth_date->format('d/m/Y'),
                'age' => $patient->age,
                'phone' => $patient->phone,
                'city' => $patient->city,
                'status' => $patient->status->value,
                'status_label' => $patient->status->label(),
                'status_color' => $patient->status->badgeColor(),
                'clinical_form' => $patient->diseaseProfile instanceof DiseaseProfile
                    ? $patient->diseaseProfile->current_clinical_form->shortLabel()
                    : null,
                'created_at' => $patient->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'gender' => $gender,
            ],
            'statuses' => collect(PatientStatus::cases())->map(fn (PatientStatus $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->values()->all(),
            'genders' => collect(Gender::cases())->map(fn (Gender $g): array => [
                'value' => $g->value,
                'label' => $g->label(),
            ])->values()->all(),
        ]);
    }

    /**
     * Show the form for creating a new patient record.
     */
    public function create(): Response
    {
        Gate::authorize('create', Patient::class);

        return Inertia::render('Patients/Create', [
            'suggested_dossier_number' => Patient::generateNextDossierNumber(),
            'genders' => collect(Gender::cases())->map(fn (Gender $g): array => [
                'value' => $g->value,
                'label' => $g->label(),
            ])->values()->all(),
            'statuses' => collect(PatientStatus::cases())->map(fn (PatientStatus $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->values()->all(),
            'marital_statuses' => collect(MaritalStatus::cases())->map(fn (MaritalStatus $m): array => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->values()->all(),
        ]);
    }

    /**
     * Store a newly created patient record in storage.
     */
    public function store(StorePatientRequest $request): RedirectResponse
    {
        Gate::authorize('create', Patient::class);

        $validated = $request->validated();

        if (empty($validated['dossier_number'])) {
            $validated['dossier_number'] = Patient::generateNextDossierNumber();
        }

        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        $patient = Patient::create($validated);

        // Sync medical history record
        $patient->medicalHistory()->updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'smoking' => $validated['tobacco_consumption'] ?? null,
                'alcohol' => $validated['alcohol_consumption'] ?? null,
                'pregnancies_count' => $validated['pregnancies_count'] ?? null,
                'deliveries_count' => $validated['deliveries_count'] ?? null,
                'children_count' => $validated['children_count'] ?? null,
                'postpartum_info' => $validated['postpartum_info'] ?? null,
                'family_history_sep' => (bool) ($validated['family_history_sep'] ?? false),
                'family_history_neurological' => (bool) ($validated['family_history_neurological'] ?? false),
                'family_history_autoimmune' => (bool) ($validated['family_history_autoimmune'] ?? false),
                'family_history_details' => $validated['family_history_details'] ?? null,
                'medical_history' => $validated['medical_history_notes'] ?? null,
                'surgical_history' => $validated['surgical_history_notes'] ?? null,
            ]
        );

        return redirect()
            ->route('patients.index')
            ->with('success', "Le dossier patient {$patient->dossier_number} ({$patient->full_name}) a été créé avec succès.");
    }

    /**
     * Display the specified patient medical record.
     */
    public function show(Patient $patient): Response
    {
        Gate::authorize('view', $patient);

        if (auth()->check()) {
            activity('consultation_dossier')
                ->performedOn($patient)
                ->causedBy(auth()->user())
                ->log("Consultation du dossier médical {$patient->dossier_number}");
        }

        $patient->load([
            'creator:id,name,email',
            'editor:id,name,email',
            'medicalHistory',
            'diseaseProfile',
            'relapses',
            'clinicalExams',
            'mriExams',
            'treatments',
            'consultations.doctor:id,name,email',
            'complementaryExams',
        ]);

        $latestClinicalExam = $patient->clinicalExams->first();
        $currentTreatment = $patient->treatments->firstWhere('status', 'en_cours')
            ?? $patient->treatments->first();

        return Inertia::render('Patients/Show', [
            'patient' => [
                'id' => $patient->id,
                'dossier_number' => $patient->dossier_number,
                'full_name' => $patient->full_name,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'cin' => $patient->cin,
                'gender' => $patient->gender->value,
                'gender_label' => $patient->gender->label(),
                'birth_date' => $patient->birth_date->format('Y-m-d'),
                'birth_date_formatted' => $patient->birth_date->format('d/m/Y'),
                'age' => $patient->age,
                'phone' => $patient->phone,
                'city' => $patient->city,
                'origin' => $patient->origin,
                'address' => $patient->address,
                'profession' => $patient->profession,
                'height' => $patient->height,
                'weight' => $patient->weight,
                'imc' => $patient->imc,
                'imc_classification' => $patient->imcClassification(),
                'marital_status' => $patient->marital_status?->value,
                'marital_status_label' => $patient->marital_status?->label(),
                'children_count' => $patient->children_count,
                'education_level' => $patient->education_level,
                'health_coverage' => $patient->health_coverage ?? [],
                'tobacco_consumption' => $patient->tobacco_consumption,
                'alcohol_consumption' => $patient->alcohol_consumption,
                'pregnancies_count' => $patient->pregnancies_count,
                'deliveries_count' => $patient->deliveries_count,
                'postpartum_info' => $patient->postpartum_info,
                'family_history_sep' => $patient->family_history_sep,
                'family_history_neurological' => $patient->family_history_neurological,
                'family_history_autoimmune' => $patient->family_history_autoimmune,
                'family_history_details' => $patient->family_history_details,
                'medical_history_notes' => $patient->medical_history_notes,
                'surgical_history_notes' => $patient->surgical_history_notes,
                'emergency_contact_name' => $patient->emergency_contact_name,
                'emergency_contact_phone' => $patient->emergency_contact_phone,
                'referring_doctor' => $patient->referring_doctor,
                'status' => $patient->status->value,
                'status_label' => $patient->status->label(),
                'status_color' => $patient->status->badgeColor(),
                'notes' => $patient->notes,
                'creator' => $patient->creator?->only(['id', 'name', 'email']),
                'editor' => $patient->editor?->only(['id', 'name', 'email']),
                'created_at_formatted' => $patient->created_at?->format('d/m/Y H:i'),
                'updated_at_formatted' => $patient->updated_at?->format('d/m/Y H:i'),
                'medical_history' => $patient->medicalHistory ? [
                    'id' => $patient->medicalHistory->id,
                    'medical_history' => $patient->medicalHistory->medical_history,
                    'surgical_history' => $patient->medicalHistory->surgical_history,
                    'smoking' => $patient->medicalHistory->smoking,
                    'alcohol' => $patient->medicalHistory->alcohol,
                    'toxic_other' => $patient->medicalHistory->toxic_other,
                    'pregnancies_count' => $patient->medicalHistory->pregnancies_count,
                    'deliveries_count' => $patient->medicalHistory->deliveries_count,
                    'children_count' => $patient->medicalHistory->children_count,
                    'gyneco_notes' => $patient->medicalHistory->gyneco_notes,
                    'family_history_neurological' => $patient->medicalHistory->family_history_neurological,
                    'family_history_autoimmune' => $patient->medicalHistory->family_history_autoimmune,
                    'family_history_details' => $patient->medicalHistory->family_history_details,
                    'has_unspecified_history' => $patient->medicalHistory->has_unspecified_history,
                ] : null,
                'disease_profile' => $patient->diseaseProfile ? [
                    'id' => $patient->diseaseProfile->id,
                    'first_symptom_date' => $patient->diseaseProfile->first_symptom_date?->format('d/m/Y'),
                    'first_symptom_age_estimated' => $patient->diseaseProfile->first_symptom_age_estimated,
                    'diagnosis_date' => $patient->diseaseProfile->diagnosis_date?->format('d/m/Y'),
                    'diagnosis_age_estimated' => $patient->diseaseProfile->diagnosis_age_estimated,
                    'prediagnostic_duration_months' => $patient->diseaseProfile->prediagnostic_duration_months,
                    'initial_clinical_form' => $patient->diseaseProfile->initial_clinical_form->value,
                    'initial_clinical_form_label' => $patient->diseaseProfile->initial_clinical_form->label(),
                    'current_clinical_form' => $patient->diseaseProfile->current_clinical_form->value,
                    'current_clinical_form_label' => $patient->diseaseProfile->current_clinical_form->label(),
                    'first_symptoms_description' => $patient->diseaseProfile->first_symptoms_description,
                    'diagnostic_criteria' => $patient->diseaseProfile->diagnostic_criteria,
                    'initial_edss' => $patient->diseaseProfile->initial_edss,
                    'notes' => $patient->diseaseProfile->notes,
                ] : null,
                'relapses' => $patient->relapses->map(fn (Relapse $r): array => [
                    'id' => $r->id,
                    'start_date' => $r->start_date?->format('d/m/Y'),
                    'start_date_raw' => $r->start_date?->format('Y-m-d'),
                    'end_date' => $r->end_date?->format('d/m/Y'),
                    'end_date_raw' => $r->end_date?->format('Y-m-d'),
                    'duration_days' => ($r->start_date && $r->end_date) ? $r->start_date->diffInDays($r->end_date) : null,
                    'symptoms' => $r->symptoms,
                    'severity' => $r->severity,
                    'is_before_diagnosis' => $r->is_before_diagnosis,
                    'treatment_administered' => $r->treatment_administered,
                    'administration_route' => $r->administration_route,
                    'clinical_recovery' => $r->clinical_recovery,
                    'edss_before_relapse' => $r->edss_before_relapse,
                    'edss_before_date' => $r->edss_before_date?->format('d/m/Y'),
                    'edss_before_date_raw' => $r->edss_before_date?->format('Y-m-d'),
                    'edss_during_relapse' => $r->edss_during_relapse,
                    'edss_after_relapse' => $r->edss_after_relapse,
                    'edss_evaluation_date' => $r->edss_evaluation_date?->format('d/m/Y'),
                    'edss_evaluation_date_raw' => $r->edss_evaluation_date?->format('Y-m-d'),
                    'sequelae' => $r->sequelae,
                    'hospitalization_required' => $r->hospitalization_required,
                    'notes' => $r->notes,
                ]),
                'clinical_exams' => $patient->clinicalExams->map(fn (ClinicalExam $e): array => [
                    'id' => $e->id,
                    'exam_date' => $e->exam_date?->format('d/m/Y'),
                    'exam_date_raw' => $e->exam_date?->format('Y-m-d'),
                    'edss_score' => $e->edss_score,
                    't25fw' => $e->t25fw,
                    'nine_hpt' => $e->nine_hpt,
                    'syndrome_pyramidal' => $e->syndrome_pyramidal,
                    'syndrome_vestibulaire' => $e->syndrome_vestibulaire,
                    'syndrome_ataxo_spasmodique' => $e->syndrome_ataxo_spasmodique,
                    'syndrome_medullaire' => $e->syndrome_medullaire,
                    'norb' => $e->norb,
                    'cranial_nerves_impairment' => $e->cranial_nerves_impairment,
                    'cranial_nerves_details' => $e->cranial_nerves_details,
                    'other_signs' => $e->other_signs,
                    'functional_systems_scores' => $e->functional_systems_scores,
                    'evaluator_name' => $e->evaluator_name,
                    'clinical_comment' => $e->clinical_comment,
                ]),
                'mri_exams' => $patient->mriExams->map(fn (MriExam $m): array => [
                    'id' => $m->id,
                    'exam_date' => $m->exam_date?->format('d/m/Y'),
                    'exam_date_raw' => $m->exam_date?->format('Y-m-d'),
                    'timing' => $m->timing,
                    'region' => $m->region,
                    'sep_protocol_applied' => $m->sep_protocol_applied,
                    'sequences_available' => $m->sequences_available,
                    'lesions_count_estimated' => $m->lesions_count_estimated,
                    'lesions_locations' => $m->lesions_locations,
                    'contrast_enhancement' => $m->contrast_enhancement,
                    'new_lesions_count' => $m->new_lesions_count,
                    'radiologist_report' => $m->radiologist_report,
                    'dicom_study_uid' => $m->dicom_study_uid,
                    'dicom_storage_path' => $m->dicom_storage_path,
                    'key_images' => $m->key_images,
                    'notes' => $m->notes,
                ]),
                'treatments' => $patient->treatments->map(fn (Treatment $t): array => [
                    'id' => $t->id,
                    'molecule_name' => $t->molecule_name,
                    'category' => $t->category,
                    'therapeutic_class' => $t->therapeutic_class,
                    'start_date' => $t->start_date?->format('d/m/Y'),
                    'start_date_raw' => $t->start_date?->format('Y-m-d'),
                    'end_date' => $t->end_date?->format('d/m/Y'),
                    'end_date_raw' => $t->end_date?->format('Y-m-d'),
                    'status' => $t->status,
                    'dosage_regimen' => $t->dosage_regimen,
                    'administration_route' => $t->administration_route,
                    'prescription_reason' => $t->prescription_reason,
                    'stop_reason' => $t->stop_reason,
                    'adverse_effects' => $t->adverse_effects,
                    'relapses_after_two_years' => $t->relapses_after_two_years,
                    'vitamin_d_supplementation' => $t->vitamin_d_supplementation,
                    'antidepressants' => $t->antidepressants,
                    'therapeutic_line' => $t->therapeutic_line,
                    'has_treatment_changed' => (bool) $t->has_treatment_changed,
                    'switch_to_molecule' => $t->switch_to_molecule,
                    'care_access_interruption' => (bool) $t->care_access_interruption,
                    'post_stop_reactivation' => (bool) $t->post_stop_reactivation,
                    'anti_spastic' => (bool) $t->anti_spastic,
                    'other_symptomatic' => $t->other_symptomatic,
                    'vaccinations' => $t->vaccinations ?? [],
                    'pre_therapeutic_checklist' => $t->pre_therapeutic_checklist ?? [],
                    'pregnancy_contraception_notes' => $t->pregnancy_contraception_notes,
                    'estimated_price_mad' => $t->estimated_price_mad,
                    'notes' => $t->notes,
                ]),
                'consultations' => $patient->consultations->map(fn (Consultation $c): array => [
                    'id' => $c->id,
                    'consultation_date' => $c->consultation_date?->format('d/m/Y'),
                    'consultation_date_raw' => $c->consultation_date?->format('Y-m-d'),
                    'consultation_type' => $c->consultation_type ?? 'suivi_rdv',
                    'doctor_name' => $c->doctor_name ?? $c->doctor?->name,
                    'edss_score' => $c->edss_score,
                    'intercurrent_events' => $c->intercurrent_events,
                    'relapses_since_last_visit' => $c->relapses_since_last_visit,
                    'current_treatment_evaluation' => $c->current_treatment_evaluation,
                    'tolerance_and_adherence' => $c->tolerance_and_adherence,
                    'recent_exam_results' => $c->recent_exam_results,
                    'therapeutic_decision' => $c->therapeutic_decision,
                    'prescribed_tests' => $c->prescribed_tests,
                    'next_appointment_date' => $c->next_appointment_date?->format('d/m/Y'),
                    'next_appointment_date_raw' => $c->next_appointment_date?->format('Y-m-d'),
                    'notes' => $c->notes,
                ]),
                'complementary_exams' => $patient->complementaryExams->map(fn (ComplementaryExam $ce): array => [
                    'id' => $ce->id,
                    'exam_date' => $ce->exam_date?->format('d/m/Y'),
                    'exam_date_raw' => $ce->exam_date?->format('Y-m-d'),
                    'exam_type' => $ce->exam_type,
                    'pl_result' => $ce->pl_result,
                    'pl_cytorachie' => $ce->pl_cytorachie,
                    'pl_proteinorachie' => $ce->pl_proteinorachie,
                    'pl_oligoclonal_bands' => $ce->pl_oligoclonal_bands,
                    'biology_anti_nmo' => $ce->biology_anti_nmo,
                    'biology_anti_mog' => $ce->biology_anti_mog,
                    'biology_anti_antinucleaire' => $ce->biology_anti_antinucleaire,
                    'biology_anti_bgsa' => $ce->biology_anti_bgsa,
                    'biology_serology_hbv' => $ce->biology_serology_hbv,
                    'biology_serology_hcv' => $ce->biology_serology_hcv,
                    'biology_serology_vzv' => $ce->biology_serology_vzv,
                    'biology_serology_cmv' => $ce->biology_serology_cmv,
                    'biology_serology_ebv' => $ce->biology_serology_ebv,
                    'biology_serology_jcv' => $ce->biology_serology_jcv,
                    'biology_other' => $ce->biology_other,
                    'iepp_sample_type' => $ce->iepp_sample_type,
                    'iepp_result' => $ce->iepp_result,
                    'iepp_kappa_index' => $ce->iepp_kappa_index,
                    'report_file_path' => $ce->report_file_path,
                    'notes' => $ce->notes,
                ]),
            ],
            'summary' => [
                'latest_edss' => $latestClinicalExam instanceof ClinicalExam
                    ? $latestClinicalExam->edss_score
                    : $patient->diseaseProfile?->initial_edss,
                'relapses_count' => $patient->relapses->count(),
                'mri_count' => $patient->mriExams->count(),
                'current_treatment_name' => $currentTreatment?->molecule_name,
                'clinical_form_label' => $patient->diseaseProfile instanceof DiseaseProfile
                    ? $patient->diseaseProfile->current_clinical_form->label()
                    : null,
                'clinical_form_short' => $patient->diseaseProfile instanceof DiseaseProfile
                    ? $patient->diseaseProfile->current_clinical_form->shortLabel()
                    : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the patient record.
     */
    public function edit(Patient $patient): Response
    {
        Gate::authorize('update', $patient);

        return Inertia::render('Patients/Edit', [
            'patient' => [
                'id' => $patient->id,
                'dossier_number' => $patient->dossier_number,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'cin' => $patient->cin,
                'gender' => $patient->gender->value,
                'birth_date' => $patient->birth_date->format('Y-m-d'),
                'phone' => $patient->phone,
                'city' => $patient->city,
                'origin' => $patient->origin,
                'address' => $patient->address,
                'profession' => $patient->profession,
                'height' => $patient->height,
                'weight' => $patient->weight,
                'marital_status' => $patient->marital_status?->value,
                'children_count' => $patient->children_count,
                'education_level' => $patient->education_level,
                'health_coverage' => $patient->health_coverage ?? [],
                'tobacco_consumption' => $patient->tobacco_consumption,
                'alcohol_consumption' => $patient->alcohol_consumption,
                'pregnancies_count' => $patient->pregnancies_count,
                'deliveries_count' => $patient->deliveries_count,
                'postpartum_info' => $patient->postpartum_info,
                'family_history_sep' => (bool) $patient->family_history_sep,
                'family_history_neurological' => (bool) $patient->family_history_neurological,
                'family_history_autoimmune' => (bool) $patient->family_history_autoimmune,
                'family_history_details' => $patient->family_history_details,
                'medical_history_notes' => $patient->medical_history_notes,
                'surgical_history_notes' => $patient->surgical_history_notes,
                'emergency_contact_name' => $patient->emergency_contact_name,
                'emergency_contact_phone' => $patient->emergency_contact_phone,
                'referring_doctor' => $patient->referring_doctor,
                'status' => $patient->status->value,
                'notes' => $patient->notes,
            ],
            'genders' => collect(Gender::cases())->map(fn (Gender $g): array => [
                'value' => $g->value,
                'label' => $g->label(),
            ])->values()->all(),
            'statuses' => collect(PatientStatus::cases())->map(fn (PatientStatus $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->values()->all(),
            'marital_statuses' => collect(MaritalStatus::cases())->map(fn (MaritalStatus $m): array => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->values()->all(),
        ]);
    }

    /**
     * Update the specified patient record in storage.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        Gate::authorize('update', $patient);

        $validated = $request->validated();
        $validated['updated_by'] = $request->user()?->id;

        $patient->update($validated);

        // Sync medical history record
        $patient->medicalHistory()->updateOrCreate(
            ['patient_id' => $patient->id],
            [
                'smoking' => $validated['tobacco_consumption'] ?? null,
                'alcohol' => $validated['alcohol_consumption'] ?? null,
                'pregnancies_count' => $validated['pregnancies_count'] ?? null,
                'deliveries_count' => $validated['deliveries_count'] ?? null,
                'children_count' => $validated['children_count'] ?? null,
                'postpartum_info' => $validated['postpartum_info'] ?? null,
                'family_history_sep' => (bool) ($validated['family_history_sep'] ?? false),
                'family_history_neurological' => (bool) ($validated['family_history_neurological'] ?? false),
                'family_history_autoimmune' => (bool) ($validated['family_history_autoimmune'] ?? false),
                'family_history_details' => $validated['family_history_details'] ?? null,
                'medical_history' => $validated['medical_history_notes'] ?? null,
                'surgical_history' => $validated['surgical_history_notes'] ?? null,
            ]
        );

        activity('patient')
            ->performedOn($patient)
            ->causedBy($request->user())
            ->log("Mise à jour des informations administratives du dossier {$patient->dossier_number}");

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', "Le dossier patient {$patient->dossier_number} a été mis à jour avec succès.");
    }

    /**
     * Soft-delete the specified patient record.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        Gate::authorize('delete', $patient);

        $dossierNumber = $patient->dossier_number;
        $fullName = $patient->full_name;

        $patient->delete();

        activity('patient')
            ->performedOn($patient)
            ->log("Archivage / Suppression du dossier patient {$dossierNumber} ({$fullName})");

        return redirect()
            ->route('patients.index')
            ->with('success', "Le dossier patient {$dossierNumber} a été archivé avec succès.");
    }
}
