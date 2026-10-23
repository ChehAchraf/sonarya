<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalForm;
use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Models\ClinicalExam;
use App\Models\DiseaseProfile;
use App\Models\MriExam;
use App\Models\Patient;
use App\Models\Relapse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the clinical neurology department dashboard.
     */
    public function index(): Response
    {
        $totalPatients = Patient::count();
        $activePatients = Patient::where('status', PatientStatus::Active)->count();
        $newThisYear = Patient::where('created_at', '>=', now()->startOfYear())->count();

        // Calculate average age
        $averageAge = (int) round(Patient::all()->avg('age') ?? 0);

        $totalRelapses = Relapse::count();
        $totalMriExams = MriExam::count();

        // Sex Distribution
        $femaleCount = Patient::where('gender', Gender::Female)->count();
        $maleCount = Patient::where('gender', Gender::Male)->count();
        $sexRatio = $maleCount > 0 ? round($femaleCount / $maleCount, 1) : (float) $femaleCount;

        // Clinical Forms Distribution
        $formsCounts = DiseaseProfile::query()
            ->selectRaw('current_clinical_form, count(*) as count')
            ->groupBy('current_clinical_form')
            ->pluck('count', 'current_clinical_form')
            ->all();

        $clinicalFormsData = collect(ClinicalForm::cases())
            ->filter(fn (ClinicalForm $form): bool => ! in_array($form, [ClinicalForm::Other, ClinicalForm::Unspecified], true))
            ->map(function (ClinicalForm $form) use ($formsCounts, $totalPatients): array {
                $count = (int) ($formsCounts[$form->value] ?? 0);
                $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100, 1) : 0.0;

                return [
                    'form' => $form->value,
                    'label' => $form->shortLabel(),
                    'full_label' => $form->label(),
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            })
            ->values()
            ->all();

        // Top Disease Modifying Treatments (DMT)
        $topTreatments = DB::table('treatments')
            ->where('status', 'en_cours')
            ->whereNull('deleted_at')
            ->selectRaw('molecule_name, count(*) as count')
            ->groupBy('molecule_name')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(fn (object $t): array => [
                'molecule' => (string) $t->molecule_name,
                'count' => (int) $t->count,
                'percentage' => $totalPatients > 0 ? round(((int) $t->count / $totalPatients) * 100, 1) : 0.0,
            ])
            ->all();

        // EDSS Distribution Categories (Kurtzke)
        $edssScores = ClinicalExam::pluck('edss_score');
        $edssBuckets = [
            'mild' => 0,
            'moderate' => 0,
            'severe' => 0,
            'advanced' => 0,
        ];

        foreach ($edssScores as $score) {
            $val = (float) $score;
            if ($val <= 2.5) {
                $edssBuckets['mild']++;
            } elseif ($val <= 5.5) {
                $edssBuckets['moderate']++;
            } elseif ($val <= 6.5) {
                $edssBuckets['severe']++;
            } else {
                $edssBuckets['advanced']++;
            }
        }

        // Recent Patients
        $recentPatients = Patient::query()
            ->with(['diseaseProfile:id,patient_id,current_clinical_form'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Patient $p): array => [
                'id' => $p->id,
                'dossier_number' => $p->dossier_number,
                'full_name' => $p->full_name,
                'gender' => $p->gender->value,
                'age' => $p->age,
                'city' => $p->city,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'status_color' => $p->status->badgeColor(),
                'clinical_form' => $p->diseaseProfile instanceof DiseaseProfile
                    ? $p->diseaseProfile->current_clinical_form->shortLabel()
                    : null,
                'created_at' => $p->created_at?->format('d/m/Y'),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total_patients' => $totalPatients,
                'active_patients' => $activePatients,
                'new_this_year' => $newThisYear,
                'average_age' => $averageAge,
                'total_relapses' => $totalRelapses,
                'total_mri_exams' => $totalMriExams,
                'sex_ratio' => [
                    'female_count' => $femaleCount,
                    'female_percentage' => $totalPatients > 0 ? (int) round(($femaleCount / $totalPatients) * 100) : 0,
                    'male_count' => $maleCount,
                    'male_percentage' => $totalPatients > 0 ? (int) round(($maleCount / $totalPatients) * 100) : 0,
                    'ratio' => $sexRatio,
                ],
            ],
            'clinical_forms' => $clinicalFormsData,
            'top_treatments' => $topTreatments,
            'edss_distribution' => [
                [
                    'label' => 'EDSS 0 - 2.5 (Minime)',
                    'description' => 'Autonomie complète sans limitation fonctionnelle',
                    'count' => $edssBuckets['mild'],
                ],
                [
                    'label' => 'EDSS 3.0 - 5.5 (Modéré)',
                    'description' => 'Périmètre de marche préservé sans aide',
                    'count' => $edssBuckets['moderate'],
                ],
                [
                    'label' => 'EDSS 6.0 - 6.5 (Aide requise)',
                    'description' => 'Aide unilatérale ou bilatérale nécessaire',
                    'count' => $edssBuckets['severe'],
                ],
                [
                    'label' => 'EDSS 7.0+ (Fauteuil)',
                    'description' => 'Incapacité à marcher au-delà de 5 mètres',
                    'count' => $edssBuckets['advanced'],
                ],
            ],
            'recent_patients' => $recentPatients,
        ]);
    }
}
