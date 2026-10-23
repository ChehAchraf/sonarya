<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;

class TreatmentController extends Controller
{
    /**
     * Store a newly created treatment record in storage.
     */
    public function store(StoreTreatmentRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $treatment = $patient->treatments()->create([
            'category' => $validated['category'],
            'molecule_name' => $validated['molecule_name'],
            'therapeutic_class' => $validated['therapeutic_class'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'dosage_regimen' => $validated['dosage_regimen'] ?? null,
            'administration_route' => $validated['administration_route'] ?? null,
            'prescription_reason' => $validated['prescription_reason'] ?? null,
            'stop_reason' => $validated['stop_reason'] ?? null,
            'adverse_effects' => $validated['adverse_effects'] ?? null,
            'relapses_after_two_years' => $validated['relapses_after_two_years'] ?? null,
            'vitamin_d_supplementation' => $request->boolean('vitamin_d_supplementation', false),
            'antidepressants' => $request->boolean('antidepressants', false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "Le traitement ({$treatment->molecule_name}) a été enregistré avec succès.");
    }

    /**
     * Update the specified treatment record in storage.
     */
    public function update(UpdateTreatmentRequest $request, Patient $patient, Treatment $treatment): RedirectResponse
    {
        abort_if($treatment->patient_id !== $patient->id, 404);

        $validated = $request->validated();

        $treatment->update([
            'category' => $validated['category'],
            'molecule_name' => $validated['molecule_name'],
            'therapeutic_class' => $validated['therapeutic_class'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'dosage_regimen' => $validated['dosage_regimen'] ?? null,
            'administration_route' => $validated['administration_route'] ?? null,
            'prescription_reason' => $validated['prescription_reason'] ?? null,
            'stop_reason' => $validated['stop_reason'] ?? null,
            'adverse_effects' => $validated['adverse_effects'] ?? null,
            'relapses_after_two_years' => $validated['relapses_after_two_years'] ?? null,
            'vitamin_d_supplementation' => $request->boolean('vitamin_d_supplementation', false),
            'antidepressants' => $request->boolean('antidepressants', false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "Le traitement ({$treatment->molecule_name}) a été mis à jour avec succès.");
    }

    /**
     * Remove the specified treatment record from storage.
     */
    public function destroy(Patient $patient, Treatment $treatment): RedirectResponse
    {
        abort_if($treatment->patient_id !== $patient->id, 404);

        $name = $treatment->molecule_name;
        $treatment->delete();

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "Le traitement ({$name}) a été supprimé avec succès.");
    }
}
