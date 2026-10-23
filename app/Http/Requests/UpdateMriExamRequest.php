<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMriExamRequest extends FormRequest
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
            'timing' => ['required', 'string', 'in:baseline,rebaseline_6m,control'],
            'region' => ['required', 'string', 'in:cerebrale,medullaire,les_deux'],
            'sep_protocol_applied' => ['boolean'],
            'sequences_available' => ['nullable', 'string', 'max:255'],
            'lesions_count_estimated' => ['nullable', 'string', 'max:50'],
            'lesions_locations' => ['nullable', 'string', 'max:1000'],
            'contrast_enhancement' => ['nullable', 'string', 'max:50'],
            'new_lesions_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'radiologist_report' => ['nullable', 'string', 'max:5000'],
            'dicom_study_uid' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'retained_images' => ['nullable'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpeg,jpg,png,webp,pdf,dcm,zip', 'max:20480'],
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
            'exam_date' => "date de l'examen IRM",
            'timing' => "timing de l'IRM (baseline, rebaseline ou contrôle)",
            'region' => 'région explorée (cérébrale ou médullaire)',
            'sep_protocol_applied' => 'protocole IRM SEP',
            'sequences_available' => 'séquences disponibles',
            'lesions_count_estimated' => 'nombre estimé de lésions',
            'lesions_locations' => 'localisation des lésions',
            'contrast_enhancement' => 'prise de contraste au gadolinium',
            'new_lesions_count' => 'nombre de nouvelles lésions',
            'radiologist_report' => 'compte rendu radiologique',
            'dicom_study_uid' => "identifiant d'étude DICOM / PACS",
            'notes' => 'notes et commentaires',
            'images' => "fichiers d'imagerie",
        ];
    }
}
