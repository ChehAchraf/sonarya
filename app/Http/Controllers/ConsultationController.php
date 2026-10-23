<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;

class ConsultationController extends Controller
{
    /**
     * Store a newly created consultation record in storage.
     */
    public function store(StoreConsultationRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $consultation = $patient->consultations()->create([
            'consultation_date' => $validated['consultation_date'],
            'consultation_type' => $validated['consultation_type'] ?? 'suivi_rdv',
            'doctor_id' => $validated['doctor_id'] ?? $request->user()?->id,
            'doctor_name' => $validated['doctor_name'] ?? $request->user()?->name,
            'intercurrent_events' => $validated['intercurrent_events'] ?? null,
            'relapses_since_last_visit' => $validated['relapses_since_last_visit'] ?? 0,
            'edss_score' => $validated['edss_score'] ?? null,
            'current_treatment_evaluation' => $validated['current_treatment_evaluation'] ?? null,
            'tolerance_and_adherence' => $validated['tolerance_and_adherence'] ?? null,
            'recent_exam_results' => $validated['recent_exam_results'] ?? null,
            'therapeutic_decision' => $validated['therapeutic_decision'] ?? null,
            'next_appointment_date' => $validated['next_appointment_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $formattedDate = $consultation->consultation_date?->format('d/m/Y') ?? 'cette date';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "La consultation du {$formattedDate} a été enregistrée avec succès.");
    }

    /**
     * Update the specified consultation record in storage.
     */
    public function update(UpdateConsultationRequest $request, Patient $patient, Consultation $consultation): RedirectResponse
    {
        abort_if($consultation->patient_id !== $patient->id, 404);

        $validated = $request->validated();

        $consultation->update([
            'consultation_date' => $validated['consultation_date'],
            'consultation_type' => $validated['consultation_type'] ?? $consultation->consultation_type ?? 'suivi_rdv',
            'doctor_id' => $validated['doctor_id'] ?? $consultation->doctor_id ?? $request->user()?->id,
            'doctor_name' => $validated['doctor_name'] ?? $consultation->doctor_name ?? $request->user()?->name,
            'intercurrent_events' => $validated['intercurrent_events'] ?? null,
            'relapses_since_last_visit' => $validated['relapses_since_last_visit'] ?? 0,
            'edss_score' => $validated['edss_score'] ?? null,
            'current_treatment_evaluation' => $validated['current_treatment_evaluation'] ?? null,
            'tolerance_and_adherence' => $validated['tolerance_and_adherence'] ?? null,
            'recent_exam_results' => $validated['recent_exam_results'] ?? null,
            'therapeutic_decision' => $validated['therapeutic_decision'] ?? null,
            'next_appointment_date' => $validated['next_appointment_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $formattedDate = $consultation->consultation_date?->format('d/m/Y') ?? 'cette date';

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "La consultation du {$formattedDate} a été mise à jour avec succès.");
    }

    /**
     * Remove the specified consultation record from storage.
     */
    public function destroy(Patient $patient, Consultation $consultation): RedirectResponse
    {
        abort_if($consultation->patient_id !== $patient->id, 404);

        $formattedDate = $consultation->consultation_date?->format('d/m/Y') ?? 'cette date';
        $consultation->delete();

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "La consultation du {$formattedDate} a été supprimée avec succès.");
    }
}
