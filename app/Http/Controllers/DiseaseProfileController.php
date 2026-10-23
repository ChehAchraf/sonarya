<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalForm;
use App\Http\Requests\UpdateDiseaseProfileRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DiseaseProfileController extends Controller
{
    /**
     * Show the form for editing the patient's disease profile and medical history.
     */
    public function edit(Patient $patient): Response
    {
        $patient->load(['diseaseProfile', 'medicalHistory']);

        return Inertia::render('Patients/DiseaseProfile/Edit', [
            'patient' => [
                'id' => $patient->id,
                'dossier_number' => $patient->dossier_number,
                'full_name' => $patient->full_name,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'gender' => $patient->gender->value,
                'birth_date' => $patient->birth_date->format('Y-m-d'),
                'birth_date_formatted' => $patient->birth_date->format('d/m/Y'),
                'age' => $patient->age,
                'status_label' => $patient->status->label(),
                'status_color' => $patient->status->badgeColor(),
            ],
            'disease_profile' => $patient->diseaseProfile ? [
                'first_symptom_date' => $patient->diseaseProfile->first_symptom_date?->format('Y-m-d') ?? '',
                'first_symptom_age_estimated' => $patient->diseaseProfile->first_symptom_age_estimated,
                'diagnosis_date' => $patient->diseaseProfile->diagnosis_date?->format('Y-m-d') ?? '',
                'diagnosis_age_estimated' => $patient->diseaseProfile->diagnosis_age_estimated,
                'prediagnostic_duration_months' => $patient->diseaseProfile->prediagnostic_duration_months,
                'initial_clinical_form' => $patient->diseaseProfile->initial_clinical_form->value,
                'current_clinical_form' => $patient->diseaseProfile->current_clinical_form->value,
                'first_symptoms_description' => $patient->diseaseProfile->first_symptoms_description ?? '',
                'diagnostic_criteria' => $patient->diseaseProfile->diagnostic_criteria ?? '',
                'initial_edss' => $patient->diseaseProfile->initial_edss ? (string) $patient->diseaseProfile->initial_edss : '',
                'notes' => $patient->diseaseProfile->notes ?? '',
            ] : [
                'first_symptom_date' => '',
                'first_symptom_age_estimated' => null,
                'diagnosis_date' => '',
                'diagnosis_age_estimated' => null,
                'prediagnostic_duration_months' => null,
                'initial_clinical_form' => ClinicalForm::RelapsingRemitting->value,
                'current_clinical_form' => ClinicalForm::RelapsingRemitting->value,
                'first_symptoms_description' => '',
                'diagnostic_criteria' => 'Critères de McDonald 2017',
                'initial_edss' => '',
                'notes' => '',
            ],
            'medical_history' => $patient->medicalHistory ? [
                'medical_history' => $patient->medicalHistory->medical_history ?? '',
                'surgical_history' => $patient->medicalHistory->surgical_history ?? '',
                'smoking' => $patient->medicalHistory->smoking ?? 'Non',
                'alcohol' => $patient->medicalHistory->alcohol ?? 'Non',
                'toxic_other' => $patient->medicalHistory->toxic_other ?? 'Non',
                'pregnancies_count' => $patient->medicalHistory->pregnancies_count ?? 0,
                'deliveries_count' => $patient->medicalHistory->deliveries_count ?? 0,
                'children_count' => $patient->medicalHistory->children_count ?? 0,
                'gyneco_notes' => $patient->medicalHistory->gyneco_notes ?? '',
                'family_history_neurological' => (bool) $patient->medicalHistory->family_history_neurological,
                'family_history_autoimmune' => (bool) $patient->medicalHistory->family_history_autoimmune,
                'family_history_details' => $patient->medicalHistory->family_history_details ?? '',
            ] : [
                'medical_history' => '',
                'surgical_history' => '',
                'smoking' => 'Non',
                'alcohol' => 'Non',
                'toxic_other' => 'Non',
                'pregnancies_count' => 0,
                'deliveries_count' => 0,
                'children_count' => 0,
                'gyneco_notes' => '',
                'family_history_neurological' => false,
                'family_history_autoimmune' => false,
                'family_history_details' => '',
            ],
            'clinical_forms' => collect(ClinicalForm::cases())->map(fn (ClinicalForm $f): array => [
                'value' => $f->value,
                'label' => $f->shortLabel(),
                'full_label' => $f->label(),
            ])->values()->all(),
        ]);
    }

    /**
     * Update the patient's disease profile and medical history.
     */
    public function update(UpdateDiseaseProfileRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $diseaseData = [
            'first_symptom_date' => $validated['first_symptom_date'] ?? null,
            'first_symptom_age_estimated' => $validated['first_symptom_age_estimated'] ?? null,
            'diagnosis_date' => $validated['diagnosis_date'] ?? null,
            'diagnosis_age_estimated' => $validated['diagnosis_age_estimated'] ?? null,
            'prediagnostic_duration_months' => $validated['prediagnostic_duration_months'] ?? null,
            'initial_clinical_form' => $validated['initial_clinical_form'],
            'current_clinical_form' => $validated['current_clinical_form'],
            'first_symptoms_description' => $validated['first_symptoms_description'] ?? null,
            'diagnostic_criteria' => $validated['diagnostic_criteria'] ?? null,
            'initial_edss' => $validated['initial_edss'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        $historyData = [
            'medical_history' => $validated['medical_history'] ?? null,
            'surgical_history' => $validated['surgical_history'] ?? null,
            'smoking' => $validated['smoking'] ?? null,
            'alcohol' => $validated['alcohol'] ?? null,
            'toxic_other' => $validated['toxic_other'] ?? null,
            'pregnancies_count' => $validated['pregnancies_count'] ?? 0,
            'deliveries_count' => $validated['deliveries_count'] ?? 0,
            'children_count' => $validated['children_count'] ?? 0,
            'gyneco_notes' => $validated['gyneco_notes'] ?? null,
            'family_history_neurological' => (bool) ($validated['family_history_neurological'] ?? false),
            'family_history_autoimmune' => (bool) ($validated['family_history_autoimmune'] ?? false),
            'family_history_details' => $validated['family_history_details'] ?? null,
        ];

        DB::transaction(function () use ($patient, $diseaseData, $historyData): void {
            $patient->diseaseProfile()->updateOrCreate(
                ['patient_id' => $patient->id],
                $diseaseData
            );

            $patient->medicalHistory()->updateOrCreate(
                ['patient_id' => $patient->id],
                $historyData
            );
        });

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "Le profil clinique et les antécédents du dossier {$patient->dossier_number} ont été enregistrés avec succès.");
    }
}
