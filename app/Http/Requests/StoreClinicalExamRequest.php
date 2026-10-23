<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClinicalExamRequest extends FormRequest
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
            'exam_date' => ['required', 'date', 'before_or_equal:today'],
            'edss_score' => ['required', 'numeric', 'min:0', 'max:10'],
            't25fw' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'nine_hpt' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'syndrome_pyramidal' => ['nullable', 'string', 'max:50'],
            'syndrome_vestibulaire' => ['nullable', 'string', 'max:50'],
            'syndrome_ataxo_spasmodique' => ['nullable', 'string', 'max:50'],
            'syndrome_medullaire' => ['nullable', 'string', 'max:50'],
            'norb' => ['nullable', 'string', 'max:50'],
            'cranial_nerves_impairment' => ['nullable', 'string', 'max:50'],
            'cranial_nerves_details' => ['nullable', 'string', 'max:255'],
            'other_signs' => ['nullable', 'string', 'max:2000'],
            'functional_systems_scores' => ['nullable', 'array'],
            'functional_systems_scores.*' => ['nullable', 'integer', 'min:0', 'max:5'],
            'evaluator_name' => ['nullable', 'string', 'max:100'],
            'clinical_comment' => ['nullable', 'string', 'max:2000'],
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
            'exam_date' => "date de l'examen clinique",
            'edss_score' => 'score EDSS',
            't25fw' => 'test de marche T25FW (secondes)',
            'nine_hpt' => 'test de dextérité 9HPT (secondes)',
            'syndrome_pyramidal' => 'syndrome pyramidal',
            'syndrome_vestibulaire' => 'syndrome cérébello-vestibulaire',
            'syndrome_ataxo_spasmodique' => 'syndrome ataxo-spasmodique',
            'syndrome_medullaire' => 'syndrome médullaire',
            'norb' => 'névrite optique rétrobulbaire',
            'cranial_nerves_impairment' => 'atteinte des paires crâniennes',
            'cranial_nerves_details' => 'détails des paires crâniennes',
            'other_signs' => 'autres signes cliniques',
            'functional_systems_scores' => 'systèmes fonctionnels de Kurtzke',
            'evaluator_name' => 'examinateur',
            'clinical_comment' => 'commentaire clinique',
        ];
    }
}
