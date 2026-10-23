<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentRequest extends FormRequest
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
            'category' => ['required', 'string', 'in:fond,poussee,symptomatique'],
            'molecule_name' => ['required', 'string', 'max:100'],
            'therapeutic_class' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'string', 'in:en_cours,arrete,remplace'],
            'dosage_regimen' => ['nullable', 'string', 'max:150'],
            'administration_route' => ['nullable', 'string', 'max:50'],
            'prescription_reason' => ['nullable', 'string', 'max:1000'],
            'stop_reason' => ['nullable', 'string', 'max:1000'],
            'adverse_effects' => ['nullable', 'string', 'max:1000'],
            'relapses_after_two_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'vitamin_d_supplementation' => ['boolean'],
            'antidepressants' => ['boolean'],
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
            'category' => 'catégorie du traitement',
            'molecule_name' => 'nom de la molécule',
            'therapeutic_class' => 'classe thérapeutique',
            'start_date' => 'date de début du traitement',
            'end_date' => 'date de fin du traitement',
            'status' => 'statut du traitement',
            'dosage_regimen' => 'posologie et schéma posologique',
            'administration_route' => "voie d'administration",
            'prescription_reason' => 'motif de prescription',
            'stop_reason' => "motif d'arrêt ou de changement",
            'adverse_effects' => 'effets indésirables et tolérance',
            'relapses_after_two_years' => 'nombre de poussées après deux ans',
            'vitamin_d_supplementation' => 'supplémentation en vitamine D',
            'antidepressants' => 'traitement antidépresseur',
            'notes' => 'remarques et observations',
        ];
    }
}
