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
            'therapeutic_line' => $validated['therapeutic_line'] ?? null,
            'has_treatment_changed' => $request->boolean('has_treatment_changed', false),
            'switch_to_molecule' => $validated['switch_to_molecule'] ?? null,
            'care_access_interruption' => $request->boolean('care_access_interruption', false),
            'post_stop_reactivation' => $request->boolean('post_stop_reactivation', false),
            'anti_spastic' => $request->boolean('anti_spastic', false),
            'other_symptomatic' => $validated['other_symptomatic'] ?? null,
            'vaccinations' => $validated['vaccinations'] ?? null,
            'pre_therapeutic_checklist' => $validated['pre_therapeutic_checklist'] ?? null,
            'pregnancy_contraception_notes' => $validated['pregnancy_contraception_notes'] ?? null,
            'estimated_price_mad' => $validated['estimated_price_mad'] ?? null,
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
            'therapeutic_line' => $validated['therapeutic_line'] ?? null,
            'has_treatment_changed' => $request->boolean('has_treatment_changed', false),
            'switch_to_molecule' => $validated['switch_to_molecule'] ?? null,
            'care_access_interruption' => $request->boolean('care_access_interruption', false),
            'post_stop_reactivation' => $request->boolean('post_stop_reactivation', false),
            'anti_spastic' => $request->boolean('anti_spastic', false),
            'other_symptomatic' => $validated['other_symptomatic'] ?? null,
            'vaccinations' => $validated['vaccinations'] ?? null,
            'pre_therapeutic_checklist' => $validated['pre_therapeutic_checklist'] ?? null,
            'pregnancy_contraception_notes' => $validated['pregnancy_contraception_notes'] ?? null,
            'estimated_price_mad' => $validated['estimated_price_mad'] ?? null,
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
