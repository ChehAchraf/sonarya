<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplementaryExamRequest;
use App\Http\Requests\UpdateComplementaryExamRequest;
use App\Models\ComplementaryExam;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;

class ComplementaryExamController extends Controller
{
    /**
     * Store a newly created complementary exam record in storage.
     */
    public function store(StoreComplementaryExamRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $exam = $patient->complementaryExams()->create([
            'exam_date' => $validated['exam_date'],
            'exam_type' => $validated['exam_type'],
            'pl_result' => $validated['pl_result'] ?? null,
            'pl_cytorachie' => $validated['pl_cytorachie'] ?? null,
            'pl_proteinorachie' => $validated['pl_proteinorachie'] ?? null,
            'pl_oligoclonal_bands' => $validated['pl_oligoclonal_bands'] ?? null,
            'biology_anti_nmo' => $validated['biology_anti_nmo'] ?? null,
            'biology_anti_mog' => $validated['biology_anti_mog'] ?? null,
            'biology_anti_antinucleaire' => $validated['biology_anti_antinucleaire'] ?? null,
            'biology_anti_bgsa' => $validated['biology_anti_bgsa'] ?? null,
            'biology_serology_hbv' => $validated['biology_serology_hbv'] ?? null,
            'biology_other' => $validated['biology_other'] ?? null,
            'iepp_sample_type' => $validated['iepp_sample_type'] ?? null,
            'iepp_result' => $validated['iepp_result'] ?? null,
            'iepp_kappa_index' => $validated['iepp_kappa_index'] ?? null,
            'report_file_path' => $validated['report_file_path'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $formattedDate = $exam->exam_date?->format('d/m/Y') ?? 'cette date';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'examen complémentaire ({$exam->exam_type}) du {$formattedDate} a été enregistré avec succès.");
    }

    /**
     * Update the specified complementary exam record in storage.
     */
    public function update(UpdateComplementaryExamRequest $request, Patient $patient, ComplementaryExam $complementaryExam): RedirectResponse
    {
        abort_if($complementaryExam->patient_id !== $patient->id, 404);

        $validated = $request->validated();

        $complementaryExam->update([
            'exam_date' => $validated['exam_date'],
            'exam_type' => $validated['exam_type'],
            'pl_result' => $validated['pl_result'] ?? null,
            'pl_cytorachie' => $validated['pl_cytorachie'] ?? null,
            'pl_proteinorachie' => $validated['pl_proteinorachie'] ?? null,
            'pl_oligoclonal_bands' => $validated['pl_oligoclonal_bands'] ?? null,
            'biology_anti_nmo' => $validated['biology_anti_nmo'] ?? null,
            'biology_anti_mog' => $validated['biology_anti_mog'] ?? null,
            'biology_anti_antinucleaire' => $validated['biology_anti_antinucleaire'] ?? null,
            'biology_anti_bgsa' => $validated['biology_anti_bgsa'] ?? null,
            'biology_serology_hbv' => $validated['biology_serology_hbv'] ?? null,
            'biology_other' => $validated['biology_other'] ?? null,
            'iepp_sample_type' => $validated['iepp_sample_type'] ?? null,
            'iepp_result' => $validated['iepp_result'] ?? null,
            'iepp_kappa_index' => $validated['iepp_kappa_index'] ?? null,
            'report_file_path' => $validated['report_file_path'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $formattedDate = $complementaryExam->exam_date?->format('d/m/Y') ?? 'cette date';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'examen complémentaire ({$complementaryExam->exam_type}) du {$formattedDate} a été mis à jour avec succès.");
    }

    /**
     * Remove the specified complementary exam record from storage.
     */
    public function destroy(Patient $patient, ComplementaryExam $complementaryExam): RedirectResponse
    {
        abort_if($complementaryExam->patient_id !== $patient->id, 404);

        $type = $complementaryExam->exam_type;
        $formattedDate = $complementaryExam->exam_date?->format('d/m/Y') ?? 'cette date';
        $complementaryExam->delete();

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'examen complémentaire ({$type}) du {$formattedDate} a été supprimé avec succès.");
    }
}
