<?php

namespace App\Models;

use Database\Factories\RelapseFactory;
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
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string $symptoms
 * @property bool $is_before_diagnosis
 * @property string|null $treatment_administered
 * @property string|null $administration_route
 * @property string|null $clinical_recovery
 * @property string|null $sequelae
 * @property bool $hospitalization_required
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 */
#[Fillable([
    'patient_id',
    'start_date',
    'end_date',
    'symptoms',
    'is_before_diagnosis',
    'treatment_administered',
    'administration_route',
    'clinical_recovery',
    'sequelae',
    'hospitalization_required',
    'notes',
])]
class Relapse extends Model
{
    /** @use HasFactory<RelapseFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_before_diagnosis' => 'boolean',
            'hospitalization_required' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('relapse');
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
