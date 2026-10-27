<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRelapseRequest;
use App\Http\Requests\UpdateRelapseRequest;
use App\Models\Patient;
use App\Models\Relapse;
use Illuminate\Http\RedirectResponse;

class RelapseController extends Controller
{
    /**
     * Store a newly created relapse in storage.
     */
    public function store(StoreRelapseRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $relapse = $patient->relapses()->create([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'symptoms' => $validated['symptoms'],
            'severity' => $validated['severity'] ?? null,
            'is_before_diagnosis' => (bool) ($validated['is_before_diagnosis'] ?? false),
            'treatment_administered' => $validated['treatment_administered'] ?? null,
            'administration_route' => $validated['administration_route'] ?? null,
            'clinical_recovery' => $validated['clinical_recovery'] ?? null,
            'edss_before_relapse' => $validated['edss_before_relapse'] ?? null,
            'edss_after_relapse' => $validated['edss_after_relapse'] ?? null,
            'edss_evaluation_date' => $validated['edss_evaluation_date'] ?? null,
            'sequelae' => $validated['sequelae'] ?? null,
            'hospitalization_required' => (bool) ($validated['hospitalization_required'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        $dateFormatted = $relapse->start_date?->format('d/m/Y') ?? '';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "La poussée ({$dateFormatted}) a été enregistrée avec succès.");
    }

    /**
     * Update the specified relapse in storage.
     */
    public function update(UpdateRelapseRequest $request, Patient $patient, Relapse $relapse): RedirectResponse
    {
        abort_if($relapse->patient_id !== $patient->id, 404);

        $validated = $request->validated();

        $relapse->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'symptoms' => $validated['symptoms'],
            'severity' => $validated['severity'] ?? null,
            'is_before_diagnosis' => (bool) ($validated['is_before_diagnosis'] ?? false),
            'treatment_administered' => $validated['treatment_administered'] ?? null,
            'administration_route' => $validated['administration_route'] ?? null,
            'clinical_recovery' => $validated['clinical_recovery'] ?? null,
            'edss_before_relapse' => $validated['edss_before_relapse'] ?? null,
            'edss_after_relapse' => $validated['edss_after_relapse'] ?? null,
            'edss_evaluation_date' => $validated['edss_evaluation_date'] ?? null,
            'sequelae' => $validated['sequelae'] ?? null,
            'hospitalization_required' => (bool) ($validated['hospitalization_required'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        $dateFormatted = $relapse->start_date?->format('d/m/Y') ?? '';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "La poussée ({$dateFormatted}) a été mise à jour avec succès.");
    }

    /**
     * Remove the specified relapse from storage.
     */
    public function destroy(Patient $patient, Relapse $relapse): RedirectResponse
    {
        abort_if($relapse->patient_id !== $patient->id, 404);

        $dateFormatted = $relapse->start_date?->format('d/m/Y') ?? '';
        $relapse->delete();

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "La poussée ({$dateFormatted}) a été supprimée avec succès.");
    }
}
