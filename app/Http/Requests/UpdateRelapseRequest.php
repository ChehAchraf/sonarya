<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRelapseRequest extends FormRequest
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
            'is_before_diagnosis' => ['boolean'],
            'treatment_administered' => ['nullable', 'string', 'max:255'],
            'administration_route' => ['nullable', 'string', 'max:50'],
            'clinical_recovery' => ['nullable', 'string', 'max:50'],
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
            'is_before_diagnosis' => 'moment par rapport au diagnostic',
            'treatment_administered' => 'traitement administré',
            'administration_route' => "voie d'administration",
            'clinical_recovery' => 'récupération clinique',
            'sequelae' => 'séquelles résiduelles',
            'hospitalization_required' => 'hospitalisation requise',
            'notes' => 'notes et remarques',
        ];
    }
}
