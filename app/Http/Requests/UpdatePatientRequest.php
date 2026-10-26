<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PatientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
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
        $patientId = $this->route('patient')?->id ?? $this->route('patient');

        return [
            'dossier_number' => ['required', 'string', 'max:50', Rule::unique('patients', 'dossier_number')->ignore($patientId)],
            'cin' => ['nullable', 'string', 'max:30'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'origin' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'profession' => ['nullable', 'string', 'max:150'],
            'height' => ['nullable', 'numeric', 'min:30', 'max:260'],
            'weight' => ['nullable', 'numeric', 'min:10', 'max:350'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'education_level' => ['nullable', 'string', 'max:100'],
            'health_coverage' => ['nullable', 'array'],
            'health_coverage.*' => ['string', 'max:150'],
            'tobacco_consumption' => ['nullable', 'string', 'max:200'],
            'alcohol_consumption' => ['nullable', 'string', 'max:200'],
            'pregnancies_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'deliveries_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'postpartum_info' => ['nullable', 'string', 'max:2000'],
            'family_history_sep' => ['nullable', 'boolean'],
            'family_history_neurological' => ['nullable', 'boolean'],
            'family_history_autoimmune' => ['nullable', 'boolean'],
            'family_history_details' => ['nullable', 'string', 'max:2000'],
            'medical_history_notes' => ['nullable', 'string', 'max:2000'],
            'surgical_history_notes' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'referring_doctor' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(PatientStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dossier_number' => 'numéro de dossier',
            'cin' => 'CIN',
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'gender' => 'sexe',
            'birth_date' => 'date de naissance',
            'phone' => 'téléphone',
            'city' => 'ville',
            'origin' => 'origine géographique',
            'address' => 'adresse',
            'profession' => 'profession',
            'height' => 'taille (cm)',
            'weight' => 'poids (kg)',
            'marital_status' => 'situation familiale',
            'children_count' => "nombre d'enfants",
            'education_level' => "niveau d'instruction",
            'health_coverage' => 'couverture sociale',
            'tobacco_consumption' => 'tabagisme',
            'alcohol_consumption' => "consommation d'alcool",
            'pregnancies_count' => 'nombre de grossesses',
            'deliveries_count' => "nombre d'accouchements",
            'postpartum_info' => 'informations relatives à la grossesse et au post-partum',
            'family_history_sep' => 'antécédents familiaux de SEP',
            'emergency_contact_name' => "nom du contact d'urgence",
            'emergency_contact_phone' => "téléphone du contact d'urgence",
            'referring_doctor' => 'médecin traitant',
            'status' => 'statut',
            'notes' => 'notes',
        ];
    }
}
