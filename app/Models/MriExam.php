<?php

namespace App\Models;

use Database\Factories\MriExamFactory;
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
 * @property string|null $timing
 * @property string|null $region
 * @property bool $sep_protocol_applied
 * @property string|null $sequences_available
 * @property string|null $lesions_count_estimated
 * @property string|null $lesions_locations
 * @property string|null $contrast_enhancement
 * @property int|null $new_lesions_count
 * @property string|null $radiologist_report
 * @property string|null $dicom_study_uid
 * @property string|null $dicom_storage_path
 * @property array<string, mixed>|null $key_images
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 */
#[Fillable([
    'patient_id',
    'exam_date',
    'timing',
    'region',
    'sep_protocol_applied',
    'sequences_available',
    'lesions_count_estimated',
    'lesions_locations',
    'contrast_enhancement',
    'new_lesions_count',
    'radiologist_report',
    'dicom_study_uid',
    'dicom_storage_path',
    'key_images',
    'notes',
])]
class MriExam extends Model
{
    /** @use HasFactory<MriExamFactory> */
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
            'sep_protocol_applied' => 'boolean',
            'new_lesions_count' => 'integer',
            'key_images' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('mri_exam');
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
