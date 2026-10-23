<?php

namespace App\Http\Requests;

use App\Enums\ClinicalForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiseaseProfileRequest extends FormRequest
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
            // Disease Profile
            'first_symptom_date' => ['nullable', 'date', 'before_or_equal:today'],
            'first_symptom_age_estimated' => ['nullable', 'integer', 'min:0', 'max:120'],
            'diagnosis_date' => ['nullable', 'date', 'before_or_equal:today'],
            'diagnosis_age_estimated' => ['nullable', 'integer', 'min:0', 'max:120'],
            'prediagnostic_duration_months' => ['nullable', 'integer', 'min:0'],
            'initial_clinical_form' => ['required', Rule::enum(ClinicalForm::class)],
            'current_clinical_form' => ['required', Rule::enum(ClinicalForm::class)],
            'first_symptoms_description' => ['nullable', 'string', 'max:2000'],
            'diagnostic_criteria' => ['nullable', 'string', 'max:255'],
            'initial_edss' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Medical History
            'medical_history' => ['nullable', 'string', 'max:2000'],
            'surgical_history' => ['nullable', 'string', 'max:2000'],
            'smoking' => ['nullable', 'string', 'max:50'],
            'alcohol' => ['nullable', 'string', 'max:50'],
            'toxic_other' => ['nullable', 'string', 'max:255'],
            'pregnancies_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'deliveries_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'gyneco_notes' => ['nullable', 'string', 'max:1000'],
            'family_history_neurological' => ['boolean'],
            'family_history_autoimmune' => ['boolean'],
            'family_history_details' => ['nullable', 'string', 'max:2000'],
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
            'first_symptom_date' => 'date des premiers symptômes',
            'first_symptom_age_estimated' => 'âge au premier symptôme',
            'diagnosis_date' => 'date du diagnostic',
            'diagnosis_age_estimated' => 'âge au diagnostic',
            'prediagnostic_duration_months' => 'délai pré-diagnostique',
            'initial_clinical_form' => 'forme clinique initiale',
            'current_clinical_form' => 'forme clinique actuelle',
            'first_symptoms_description' => 'description des premiers symptômes',
            'diagnostic_criteria' => 'critères diagnostiques',
            'initial_edss' => 'score EDSS initial',
            'notes' => 'notes et remarques',
            'medical_history' => 'antécédents médicaux',
            'surgical_history' => 'antécédents chirurgicaux',
            'smoking' => 'tabagisme',
            'alcohol' => "consommation d'alcool",
            'toxic_other' => 'autres toxiques',
            'pregnancies_count' => 'nombre de grossesses',
            'deliveries_count' => "nombre d'accouchements",
            'children_count' => "nombre d'enfants",
            'family_history_neurological' => 'antécédent familial de SEP',
            'family_history_autoimmune' => 'antécédent familial de maladie auto-immune',
            'family_history_details' => 'détails familiaux',
        ];
    }
}
