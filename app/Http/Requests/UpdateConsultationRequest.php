<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'consultation_date' => ['required', 'date', 'before_or_equal:today'],
            'consultation_type' => ['nullable', 'string', 'in:suivi_rdv,suivi_poussee'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'doctor_name' => ['nullable', 'string', 'max:150'],
            'intercurrent_events' => ['nullable', 'string', 'max:2000'],
            'relapses_since_last_visit' => ['nullable', 'integer', 'min:0', 'max:50'],
            'edss_score' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'current_treatment_evaluation' => ['nullable', 'string', 'max:2000'],
            'tolerance_and_adherence' => ['nullable', 'string', 'max:2000'],
            'recent_exam_results' => ['nullable', 'string', 'max:2000'],
            'therapeutic_decision' => ['nullable', 'string', 'max:1000'],
            'prescribed_tests' => ['nullable', 'string', 'max:2000'],
            'next_appointment_date' => ['nullable', 'date', 'after:consultation_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Custom French attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'consultation_date' => 'date de la consultation',
            'consultation_type' => 'type de consultation',
            'doctor_name' => 'nom du médecin',
            'intercurrent_events' => 'événements intercurrents',
            'relapses_since_last_visit' => 'nombre de poussées depuis la dernière visite',
            'edss_score' => 'score EDSS',
            'current_treatment_evaluation' => 'évaluation du traitement actuel',
            'tolerance_and_adherence' => 'tolérance et observance',
            'recent_exam_results' => 'résultats des examens récents',
            'therapeutic_decision' => 'décision thérapeutique',
            'prescribed_tests' => 'bilan à demander',
            'next_appointment_date' => 'date du prochain rendez-vous',
            'notes' => 'notes de consultation',
        ];
    }
}
