<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRelapseRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'symptoms' => ['required', 'string', 'max:2000'],
            'severity' => ['nullable', 'string', 'in:legere,moderee,severe'],
            'is_before_diagnosis' => ['boolean'],
            'treatment_administered' => ['nullable', 'string', 'max:255'],
            'administration_route' => ['nullable', 'string', 'max:50'],
            'clinical_recovery' => ['nullable', 'string', 'max:50'],
            'edss_before_relapse' => ['nullable', 'numeric', 'between:0,10'],
            'edss_after_relapse' => ['nullable', 'numeric', 'between:0,10'],
            'edss_evaluation_date' => ['nullable', 'date'],
            'sequelae' => ['nullable', 'string', 'max:2000'],
            'hospitalization_required' => ['boolean'],
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
            'start_date' => 'date de début de la poussée',
            'end_date' => 'date de fin de la poussée',
            'symptoms' => 'symptomatologie de la poussée',
            'severity' => 'sévérité de la poussée',
            'is_before_diagnosis' => 'moment par rapport au diagnostic',
            'treatment_administered' => 'traitement administré',
            'administration_route' => "voie d'administration",
            'clinical_recovery' => 'récupération clinique',
            'edss_before_relapse' => 'score EDSS avant la poussée',
            'edss_after_relapse' => 'score EDSS après la poussée',
            'edss_evaluation_date' => "date d'évaluation de l'EDSS",
            'sequelae' => 'séquelles résiduelles',
            'hospitalization_required' => 'hospitalisation requise',
            'notes' => 'notes et remarques',
        ];
    }
}
