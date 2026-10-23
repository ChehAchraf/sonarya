<?php

namespace App\Models;

use App\Enums\ClinicalForm;
use Database\Factories\DiseaseProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $patient_id
 * @property Carbon|null $first_symptom_date
 * @property int|null $first_symptom_age_estimated
 * @property Carbon|null $diagnosis_date
 * @property int|null $diagnosis_age_estimated
 * @property int|null $prediagnostic_duration_months
 * @property ClinicalForm $initial_clinical_form
 * @property ClinicalForm $current_clinical_form
 * @property string|null $first_symptoms_description
 * @property string|null $diagnostic_criteria
 * @property string|null $initial_edss
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'patient_id',
    'first_symptom_date',
    'first_symptom_age_estimated',
    'diagnosis_date',
    'diagnosis_age_estimated',
    'prediagnostic_duration_months',
    'initial_clinical_form',
    'current_clinical_form',
    'first_symptoms_description',
    'diagnostic_criteria',
    'initial_edss',
    'notes',
])]
class DiseaseProfile extends Model
{
    /** @use HasFactory<DiseaseProfileFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_symptom_date' => 'date',
            'diagnosis_date' => 'date',
            'initial_clinical_form' => ClinicalForm::class,
            'current_clinical_form' => ClinicalForm::class,
            'initial_edss' => 'decimal:1',
            'prediagnostic_duration_months' => 'integer',
            'first_symptom_age_estimated' => 'integer',
            'diagnosis_age_estimated' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('disease_profile');
    }

    /**
     * The associated patient.
     *
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
