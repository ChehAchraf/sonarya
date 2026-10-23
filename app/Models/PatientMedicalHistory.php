<?php

namespace App\Models;

use Database\Factories\PatientMedicalHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'patient_id',
    'medical_history',
    'surgical_history',
    'smoking',
    'alcohol',
    'toxic_other',
    'pregnancies_count',
    'deliveries_count',
    'children_count',
    'gyneco_notes',
    'postpartum_info',
    'family_history_sep',
    'family_history_neurological',
    'family_history_autoimmune',
    'family_history_details',
    'has_unspecified_history',
])]
class PatientMedicalHistory extends Model
{
    /** @use HasFactory<PatientMedicalHistoryFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'family_history_sep' => 'boolean',
            'family_history_neurological' => 'boolean',
            'family_history_autoimmune' => 'boolean',
            'has_unspecified_history' => 'boolean',
            'pregnancies_count' => 'integer',
            'deliveries_count' => 'integer',
            'children_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('medical_history');
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
