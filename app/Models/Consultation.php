<?php

namespace App\Models;

use Database\Factories\ConsultationFactory;
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
 * @property Carbon|null $consultation_date
 * @property int|null $doctor_id
 * @property string|null $doctor_name
 * @property string|null $intercurrent_events
 * @property int|null $relapses_since_last_visit
 * @property string|null $edss_score
 * @property string|null $current_treatment_evaluation
 * @property string|null $tolerance_and_adherence
 * @property string|null $recent_exam_results
 * @property string|null $therapeutic_decision
 * @property Carbon|null $next_appointment_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 * @property-read User|null $doctor
 */
#[Fillable([
    'patient_id',
    'consultation_date',
    'consultation_type',
    'doctor_id',
    'doctor_name',
    'intercurrent_events',
    'relapses_since_last_visit',
    'edss_score',
    'current_treatment_evaluation',
    'tolerance_and_adherence',
    'recent_exam_results',
    'therapeutic_decision',
    'next_appointment_date',
    'notes',
])]
class Consultation extends Model
{
    /** @use HasFactory<ConsultationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consultation_date' => 'date',
            'next_appointment_date' => 'date',
            'edss_score' => 'decimal:1',
            'relapses_since_last_visit' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('consultation');
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

    /**
     * The consulting doctor user.
     *
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
