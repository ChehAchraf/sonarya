<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplementaryExamRequest extends FormRequest
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
            'exam_type' => ['required', 'string', 'max:100'],
            'pl_result' => ['nullable', 'string', 'in:normal,pathologique,non_realise,indisponible'],
            'pl_cytorachie' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'pl_proteinorachie' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'pl_oligoclonal_bands' => ['nullable', 'string', 'max:100'],
            'biology_anti_nmo' => ['nullable', 'string', 'in:positive,negative,non_disponible'],
            'biology_anti_mog' => ['nullable', 'string', 'in:positive,negative,non_disponible'],
            'biology_anti_antinucleaire' => ['nullable', 'string', 'in:positive,negative,non_disponible'],
            'biology_anti_bgsa' => ['nullable', 'string', 'in:positive,negative,non_disponible'],
            'biology_serology_hbv' => ['nullable', 'string', 'in:positive,negative,non_disponible'],
            'biology_other' => ['nullable', 'string', 'max:1000'],
            'iepp_sample_type' => ['nullable', 'string', 'in:sang,lcr,sang_et_lcr'],
            'iepp_result' => ['nullable', 'string', 'max:2000'],
            'iepp_kappa_index' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'report_file_path' => ['nullable', 'string', 'max:255'],
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
            'exam_date' => "date de l'examen",
            'exam_type' => "type d'examen",
            'pl_result' => 'résultat global de la ponction lombaire',
            'pl_cytorachie' => 'cytorachie',
            'pl_proteinorachie' => 'protéinorachie',
            'pl_oligoclonal_bands' => 'bandes oligoclonales (BOC)',
            'biology_anti_nmo' => 'anticorps anti-NMO (Aquaporine-4)',
            'biology_anti_mog' => 'anticorps anti-MOG',
            'biology_anti_antinucleaire' => 'anticorps anti-nucléaires (AAN)',
            'biology_anti_bgsa' => 'anticorps anti-BGSA',
            'biology_serology_hbv' => 'sérologie VHB',
            'biology_other' => 'autres bilans biologiques',
            'iepp_sample_type' => "type d'échantillon IEPP",
            'iepp_result' => "résultat de l'immuno-électrophorèse",
            'iepp_kappa_index' => 'index Kappa',
            'notes' => 'commentaires et observations',
        ];
    }
}
