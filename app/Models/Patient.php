<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PatientStatus;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $dossier_number
 * @property string|null $cin
 * @property string $first_name
 * @property string $last_name
 * @property Gender $gender
 * @property Carbon $birth_date
 * @property string|null $phone
 * @property string|null $city
 * @property string|null $origin
 * @property string|null $address
 * @property string|null $profession
 * @property MaritalStatus|null $marital_status
 * @property int|null $children_count
 * @property string|null $education_level
 * @property array<string>|null $health_coverage
 * @property string|null $tobacco_consumption
 * @property string|null $alcohol_consumption
 * @property int|null $pregnancies_count
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $referring_doctor
 * @property PatientStatus $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int|null $age
 * @property-read string $full_name
 * @property-read DiseaseProfile|null $diseaseProfile
 */
#[Fillable([
    'dossier_number',
    'cin',
    'first_name',
    'last_name',
    'gender',
    'birth_date',
    'phone',
    'city',
    'origin',
    'address',
    'profession',
    'height',
    'weight',
    'imc',
    'marital_status',
    'children_count',
    'education_level',
    'health_coverage',
    'tobacco_consumption',
    'alcohol_consumption',
    'pregnancies_count',
    'deliveries_count',
    'postpartum_info',
    'family_history_sep',
    'family_history_neurological',
    'family_history_autoimmune',
    'family_history_details',
    'medical_history_notes',
    'surgical_history_notes',
    'emergency_contact_name',
    'emergency_contact_phone',
    'referring_doctor',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Bootstrap the model and its events.
     */
    protected static function booted(): void
    {
        static::saving(function (Patient $patient): void {
            if ($patient->height && $patient->weight && (float) $patient->height > 0) {
                $patient->imc = round((float) $patient->weight / (((float) $patient->height / 100) ** 2), 1);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => PatientStatus::class,
            'marital_status' => MaritalStatus::class,
            'birth_date' => 'date',
            'children_count' => 'integer',
            'pregnancies_count' => 'integer',
            'deliveries_count' => 'integer',
            'height' => 'float',
            'weight' => 'float',
            'imc' => 'float',
            'family_history_sep' => 'boolean',
            'family_history_neurological' => 'boolean',
            'family_history_autoimmune' => 'boolean',
            'health_coverage' => 'array',
        ];
    }

    /**
     * Get the clinical BMI classification.
     *
     * @return array{label: string, color: string, category: string}|null
     */
    public function imcClassification(): ?array
    {
        if (! $this->imc) {
            return null;
        }

        return match (true) {
            $this->imc < 18.5 => ['label' => 'Insuffisance pondérale (< 18.5)', 'color' => 'amber', 'category' => 'underweight'],
            $this->imc < 25.0 => ['label' => 'Corpulence normale (18.5 - 24.9)', 'color' => 'emerald', 'category' => 'normal'],
            $this->imc < 30.0 => ['label' => 'Surpoids (25.0 - 29.9)', 'color' => 'amber', 'category' => 'overweight'],
            $this->imc < 35.0 => ['label' => 'Obésité modérée Classe I (30.0 - 34.9)', 'color' => 'rose', 'category' => 'obesity_1'],
            $this->imc < 40.0 => ['label' => 'Obésité sévère Classe II (35.0 - 39.9)', 'color' => 'rose', 'category' => 'obesity_2'],
            default => ['label' => 'Obésité morbide Classe III (≥ 40.0)', 'color' => 'rose', 'category' => 'obesity_3'],
        };
    }

    /**
     * Activitylog options for medical audit trail.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('patient');
    }

    /**
     * Generate the next sequential dossier number (SEP-YYYY-XXXX).
     */
    public static function generateNextDossierNumber(): string
    {
        $year = (int) date('Y');
        $prefix = "SEP-{$year}-";

        $latestDossier = static::withTrashed()
            ->where('dossier_number', 'like', "{$prefix}%")
            ->orderByDesc('dossier_number')
            ->value('dossier_number');

        $nextSeq = 1;
        if ($latestDossier && preg_match('/SEP-\d{4}-(\d+)/', $latestDossier, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        } else {
            $nextSeq = static::withTrashed()->where('dossier_number', 'like', "{$prefix}%")->count() + 1;
        }

        $candidate = sprintf('%s%04d', $prefix, $nextSeq);

        while (static::withTrashed()->where('dossier_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = sprintf('%s%04d', $prefix, $nextSeq);
        }

        return $candidate;
    }

    /**
     * Get the patient's full name.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->first_name} {$this->last_name}",
        );
    }

    /**
     * Calculate patient's current age.
     *
     * @return Attribute<int, never>
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->birth_date->age,
        );
    }

    /**
     * The user who created the patient file.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who last updated the patient file.
     *
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Medical, surgical, toxic, and family history.
     *
     * @return HasOne<PatientMedicalHistory, $this>
     */
    public function medicalHistory(): HasOne
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    /**
     * Disease onset and diagnostic characteristics.
     *
     * @return HasOne<DiseaseProfile, $this>
     */
    public function diseaseProfile(): HasOne
    {
        return $this->hasOne(DiseaseProfile::class);
    }

    /**
     * Relapses (Poussées).
     *
     * @return HasMany<Relapse, $this>
     */
    public function relapses(): HasMany
    {
        return $this->hasMany(Relapse::class)->latest('start_date');
    }

    /**
     * Complementary exams (PL, Biologie, IEPP).
     *
     * @return HasMany<ComplementaryExam, $this>
     */
    public function complementaryExams(): HasMany
    {
        return $this->hasMany(ComplementaryExam::class)->latest('exam_date');
    }

    /**
     * MRI examinations (Cerebral and Spinal).
     *
     * @return HasMany<MriExam, $this>
     */
    public function mriExams(): HasMany
    {
        return $this->hasMany(MriExam::class)->latest('exam_date');
    }

    /**
     * Clinical examinations and EDSS scores.
     *
     * @return HasMany<ClinicalExam, $this>
     */
    public function clinicalExams(): HasMany
    {
        return $this->hasMany(ClinicalExam::class)->latest('exam_date');
    }

    /**
     * Disease modifying and acute treatments.
     *
     * @return HasMany<Treatment, $this>
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class)->latest('start_date');
    }

    /**
     * Longitudinal follow-up consultations.
     *
     * @return HasMany<Consultation, $this>
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class)->latest('consultation_date');
    }
}
