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
            'therapeutic_line' => ['nullable', 'string', 'in:plateforme,the,autre'],
            'has_treatment_changed' => ['nullable', 'boolean'],
            'switch_to_molecule' => ['nullable', 'string', 'max:100'],
            'care_access_interruption' => ['nullable', 'boolean'],
            'post_stop_reactivation' => ['nullable', 'boolean'],
            'anti_spastic' => ['nullable', 'boolean'],
            'other_symptomatic' => ['nullable', 'string', 'max:500'],
            'vaccinations' => ['nullable', 'array'],
            'pre_therapeutic_checklist' => ['nullable', 'array'],
            'pregnancy_contraception_notes' => ['nullable', 'string', 'max:1000'],
            'estimated_price_mad' => ['nullable', 'string', 'max:100'],
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
            'therapeutic_line' => 'ligne thérapeutique',
            'has_treatment_changed' => 'changement de traitement',
            'switch_to_molecule' => 'molécule de relais',
            'care_access_interruption' => "interruption liée à l'accès aux soins",
            'post_stop_reactivation' => 'réactivation après arrêt',
            'anti_spastic' => 'traitement anti-spastique',
            'other_symptomatic' => 'autres traitements symptomatiques',
            'vaccinations' => 'statut vaccinal',
            'pre_therapeutic_checklist' => 'bilan pré-thérapeutique',
            'pregnancy_contraception_notes' => 'grossesse et contraception',
            'estimated_price_mad' => 'prix estimé (MAD)',
            'notes' => 'remarques et observations',
        ];
    }
}
