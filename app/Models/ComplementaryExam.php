<?php

namespace App\Models;

use Database\Factories\ComplementaryExamFactory;
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
 * @property string $exam_type
 * @property string|null $pl_result
 * @property string|null $pl_cytorachie
 * @property string|null $pl_proteinorachie
 * @property string|null $pl_oligoclonal_bands
 * @property string|null $biology_anti_nmo
 * @property string|null $biology_anti_mog
 * @property string|null $biology_anti_antinucleaire
 * @property string|null $biology_anti_bgsa
 * @property string|null $biology_serology_hbv
 * @property string|null $biology_serology_hcv
 * @property string|null $biology_serology_vzv
 * @property string|null $biology_serology_cmv
 * @property string|null $biology_serology_ebv
 * @property string|null $biology_serology_jcv
 * @property string|null $biology_other
 * @property string|null $iepp_sample_type
 * @property string|null $iepp_result
 * @property string|null $report_file_path
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Patient $patient
 */
#[Fillable([
    'patient_id',
    'exam_date',
    'exam_type',
    'pl_result',
    'pl_cytorachie',
    'pl_proteinorachie',
    'pl_oligoclonal_bands',
    'biology_anti_nmo',
    'biology_anti_mog',
    'biology_anti_antinucleaire',
    'biology_anti_bgsa',
    'biology_serology_hbv',
    'biology_serology_hcv',
    'biology_serology_vzv',
    'biology_serology_cmv',
    'biology_serology_ebv',
    'biology_serology_jcv',
    'biology_other',
    'iepp_sample_type',
    'iepp_result',
    'iepp_kappa_index',
    'report_file_path',
    'notes',
])]
class ComplementaryExam extends Model
{
    /** @use HasFactory<ComplementaryExamFactory> */
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
            'pl_cytorachie' => 'decimal:2',
            'pl_proteinorachie' => 'decimal:2',
            'iepp_kappa_index' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('complementary_exam');
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
