<?php

namespace App\Models;

use Database\Factories\ClinicalExamFactory;
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
 * @property Carbon|null $exam_date
 * @property string|null $syndrome_pyramidal
 * @property string|null $syndrome_vestibulaire
 * @property string|null $syndrome_ataxo_spasmodique
 * @property string|null $syndrome_medullaire
 * @property string|null $norb
 * @property string|null $cranial_nerves_impairment
 * @property string|null $cranial_nerves_details
 * @property string|null $other_signs
 * @property string|null $edss_score
 * @property array<string, mixed>|null $functional_systems_scores
 * @property string|null $evaluator_name
 * @property string|null $clinical_comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 */
#[Fillable([
    'patient_id',
    'exam_date',
    'syndrome_pyramidal',
    'syndrome_vestibulaire',
    'syndrome_ataxo_spasmodique',
    'syndrome_medullaire',
    'norb',
    'cranial_nerves_impairment',
    'cranial_nerves_details',
    'other_signs',
    'edss_score',
    't25fw',
    'nine_hpt',
    'functional_systems_scores',
    'evaluator_name',
    'clinical_comment',
])]
class ClinicalExam extends Model
{
    /** @use HasFactory<ClinicalExamFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'edss_score' => 'decimal:1',
            't25fw' => 'decimal:2',
            'nine_hpt' => 'decimal:2',
            'functional_systems_scores' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('clinical_exam');
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
