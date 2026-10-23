<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClinicalExamRequest;
use App\Http\Requests\UpdateClinicalExamRequest;
use App\Models\ClinicalExam;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;

class ClinicalExamController extends Controller
{
    /**
     * Store a newly created clinical examination in storage.
     */
    public function store(StoreClinicalExamRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $exam = $patient->clinicalExams()->create([
            'exam_date' => $validated['exam_date'],
            'edss_score' => $validated['edss_score'],
            't25fw' => $validated['t25fw'] ?? null,
            'nine_hpt' => $validated['nine_hpt'] ?? null,
            'syndrome_pyramidal' => $validated['syndrome_pyramidal'] ?? 'absent',
            'syndrome_vestibulaire' => $validated['syndrome_vestibulaire'] ?? 'absent',
            'syndrome_ataxo_spasmodique' => $validated['syndrome_ataxo_spasmodique'] ?? 'absent',
            'syndrome_medullaire' => $validated['syndrome_medullaire'] ?? 'absent',
            'norb' => $validated['norb'] ?? 'absent',
            'cranial_nerves_impairment' => $validated['cranial_nerves_impairment'] ?? 'absent',
            'cranial_nerves_details' => $validated['cranial_nerves_details'] ?? null,
            'other_signs' => $validated['other_signs'] ?? null,
            'functional_systems_scores' => $validated['functional_systems_scores'] ?? null,
            'evaluator_name' => $validated['evaluator_name'] ?? $request->user()?->name,
            'clinical_comment' => $validated['clinical_comment'] ?? null,
        ]);

        $dateFormatted = $exam->exam_date?->format('d/m/Y') ?? '';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'évaluation clinique du {$dateFormatted} (EDSS {$exam->edss_score}) a été enregistrée avec succès.");
    }

    /**
     * Update the specified clinical examination in storage.
     */
    public function update(UpdateClinicalExamRequest $request, Patient $patient, ClinicalExam $clinicalExam): RedirectResponse
    {
        abort_if($clinicalExam->patient_id !== $patient->id, 404);

        $validated = $request->validated();

        $clinicalExam->update([
            'exam_date' => $validated['exam_date'],
            'edss_score' => $validated['edss_score'],
            't25fw' => $validated['t25fw'] ?? null,
            'nine_hpt' => $validated['nine_hpt'] ?? null,
            'syndrome_pyramidal' => $validated['syndrome_pyramidal'] ?? 'absent',
            'syndrome_vestibulaire' => $validated['syndrome_vestibulaire'] ?? 'absent',
            'syndrome_ataxo_spasmodique' => $validated['syndrome_ataxo_spasmodique'] ?? 'absent',
            'syndrome_medullaire' => $validated['syndrome_medullaire'] ?? 'absent',
            'norb' => $validated['norb'] ?? 'absent',
            'cranial_nerves_impairment' => $validated['cranial_nerves_impairment'] ?? 'absent',
            'cranial_nerves_details' => $validated['cranial_nerves_details'] ?? null,
            'other_signs' => $validated['other_signs'] ?? null,
            'functional_systems_scores' => $validated['functional_systems_scores'] ?? null,
            'evaluator_name' => $validated['evaluator_name'] ?? $request->user()?->name,
            'clinical_comment' => $validated['clinical_comment'] ?? null,
        ]);

        $dateFormatted = $clinicalExam->exam_date?->format('d/m/Y') ?? '';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'évaluation clinique du {$dateFormatted} a été mise à jour avec succès.");
    }

    /**
     * Remove the specified clinical examination from storage.
     */
    public function destroy(Patient $patient, ClinicalExam $clinicalExam): RedirectResponse
    {
        abort_if($clinicalExam->patient_id !== $patient->id, 404);

        $dateFormatted = $clinicalExam->exam_date?->format('d/m/Y') ?? '';
        $clinicalExam->delete();

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'évaluation clinique du {$dateFormatted} a été supprimée avec succès.");
    }
}
