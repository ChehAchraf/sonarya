<?php

namespace App\Models;

use Database\Factories\TreatmentFactory;
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
 * @property string|null $category
 * @property string|null $therapeutic_line
 * @property string $molecule_name
 * @property string|null $therapeutic_class
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string $status
 * @property string|null $dosage_regimen
 * @property string|null $administration_route
 * @property string|null $prescription_reason
 * @property bool $has_treatment_changed
 * @property string|null $switch_to_molecule
 * @property string|null $stop_reason
 * @property string|null $adverse_effects
 * @property bool $care_access_interruption
 * @property bool $post_stop_reactivation
 * @property int|null $relapses_after_two_years
 * @property bool $vitamin_d_supplementation
 * @property bool $antidepressants
 * @property bool $anti_spastic
 * @property string|null $other_symptomatic
 * @property array<string>|null $vaccinations
 * @property array<string, mixed>|null $pre_therapeutic_checklist
 * @property string|null $pregnancy_contraception_notes
 * @property string|null $estimated_price_mad
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 */
#[Fillable([
    'patient_id',
    'category',
    'therapeutic_line',
    'molecule_name',
    'therapeutic_class',
    'start_date',
    'end_date',
    'status',
    'dosage_regimen',
    'administration_route',
    'prescription_reason',
    'has_treatment_changed',
    'switch_to_molecule',
    'stop_reason',
    'adverse_effects',
    'care_access_interruption',
    'post_stop_reactivation',
    'relapses_after_two_years',
    'vitamin_d_supplementation',
    'antidepressants',
    'anti_spastic',
    'other_symptomatic',
    'vaccinations',
    'pre_therapeutic_checklist',
    'pregnancy_contraception_notes',
    'estimated_price_mad',
    'notes',
])]
class Treatment extends Model
{
    /** @use HasFactory<TreatmentFactory> */
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
            'relapses_after_two_years' => 'integer',
            'has_treatment_changed' => 'boolean',
            'care_access_interruption' => 'boolean',
            'post_stop_reactivation' => 'boolean',
            'vitamin_d_supplementation' => 'boolean',
            'antidepressants' => 'boolean',
            'anti_spastic' => 'boolean',
            'vaccinations' => 'array',
            'pre_therapeutic_checklist' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('treatment');
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
