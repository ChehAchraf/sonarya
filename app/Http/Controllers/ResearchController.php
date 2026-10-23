<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalForm;
use App\Models\ClinicalExam;
use App\Models\ComplementaryExam;
use App\Models\Consultation;
use App\Models\DiseaseProfile;
use App\Models\MriExam;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResearchController extends Controller
{
    /**
     * Display the multicriteria research, statistics, and cohort explorer interface.
     */
    public function index(Request $request): Response
    {
        $query = $this->buildFilteredQuery($request);

        // Calculate cohort statistics on the filtered query clone
        $statistics = $this->computeStatistics(clone $query);

        // Paginate patients for the data table
        $patients = $query
            ->with([
                'diseaseProfile',
                'clinicalExams' => fn ($q) => $q->latest('exam_date')->limit(1),
                'treatments' => fn ($q) => $q->latest('start_date')->limit(1),
                'complementaryExams' => fn ($q) => $q->latest('exam_date')->limit(1),
                'mriExams' => fn ($q) => $q->latest('exam_date')->limit(1),
            ])
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Patient $p): array {
                $dp = $p->diseaseProfile;
                $latestExam = $p->clinicalExams->first();
                $activeTreatment = $p->treatments->first();
                $latestLcr = $p->complementaryExams->first();
                $latestMri = $p->mriExams->first();

                $latestEdss = '—';
                if ($latestExam instanceof ClinicalExam && $latestExam->edss_score !== null) {
                    $latestEdss = $latestExam->edss_score;
                } elseif ($dp instanceof DiseaseProfile && $dp->initial_edss !== null) {
                    $latestEdss = $dp->initial_edss;
                }

                return [
                    'id' => $p->id,
                    'dossier_number' => $p->dossier_number,
                    'pseudonym' => 'SEP-'.substr(hash('sha256', (string) ($p->id.config('app.key'))), 0, 6),
                    'full_name' => $p->full_name,
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'gender' => $p->gender->value,
                    'gender_label' => $p->gender->label(),
                    'age' => $p->age,
                    'city' => $p->city,
                    'clinical_form_label' => $dp instanceof DiseaseProfile
                        ? $dp->current_clinical_form->label()
                        : 'Non précisée',
                    'clinical_form_code' => $dp instanceof DiseaseProfile
                        ? $dp->current_clinical_form->value
                        : null,
                    'latest_edss' => $latestEdss,
                    'relapses_count' => $p->relapses()->count(),
                    'active_treatment' => $activeTreatment instanceof Treatment
                        ? $activeTreatment->molecule_name
                        : 'Aucun',
                    'treatment_status' => $activeTreatment instanceof Treatment
                        ? $activeTreatment->status
                        : null,
                    'boc_status' => $latestLcr instanceof ComplementaryExam
                        ? $latestLcr->pl_oligoclonal_bands
                        : null,
                    'mri_contrast' => $latestMri instanceof MriExam
                        ? $latestMri->contrast_enhancement
                        : null,
                ];
            });

        // Distinct options for filters
        $availableMolecules = Treatment::query()
            ->distinct()
            ->whereNotNull('molecule_name')
            ->orderBy('molecule_name')
            ->pluck('molecule_name')
            ->toArray();

        $availableCities = Patient::query()
            ->distinct()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->orderBy('city')
            ->pluck('city')
            ->toArray();

        return Inertia::render('Research/Index', [
            'patients' => $patients,
            'statistics' => $statistics,
            'filters' => [
                'search' => $request->query('search', ''),
                'gender' => $request->query('gender', 'all'),
                'age_min' => $request->query('age_min', ''),
                'age_max' => $request->query('age_max', ''),
                'city' => $request->query('city', 'all'),
                'clinical_form' => $request->query('clinical_form', 'all'),
                'age_onset_min' => $request->query('age_onset_min', ''),
                'age_onset_max' => $request->query('age_onset_max', ''),
                'age_diagnosis_min' => $request->query('age_diagnosis_min', ''),
                'age_diagnosis_max' => $request->query('age_diagnosis_max', ''),
                'edss_min' => $request->query('edss_min', ''),
                'edss_max' => $request->query('edss_max', ''),
                'relapses_min' => $request->query('relapses_min', ''),
                'relapses_max' => $request->query('relapses_max', ''),
                'mri_region' => $request->query('mri_region', 'all'),
                'mri_contrast' => $request->query('mri_contrast', 'all'),
                'mri_sep_protocol' => $request->query('mri_sep_protocol', 'all'),
                'boc' => $request->query('boc', 'all'),
                'anti_nmo' => $request->query('anti_nmo', 'all'),
                'anti_mog' => $request->query('anti_mog', 'all'),
                'treatment_molecule' => $request->query('treatment_molecule', 'all'),
                'treatment_status' => $request->query('treatment_status', 'all'),
                'vitamin_d' => $request->query('vitamin_d', 'all'),
                'antidepressants' => $request->query('antidepressants', 'all'),
                'pseudonymize' => $request->boolean('pseudonymize', true),
            ],
            'availableMolecules' => $availableMolecules,
            'availableCities' => $availableCities,
        ]);
    }

    /**
     * Stream an Excel/SPSS/R compliant CSV export of filtered research variables.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildFilteredQuery($request);
        $pseudonymize = $request->boolean('pseudonymize', true);

        $patients = $query
            ->with([
                'diseaseProfile',
                'medicalHistory',
                'clinicalExams' => fn ($q) => $q->latest('exam_date'),
                'treatments' => fn ($q) => $q->latest('start_date'),
                'complementaryExams' => fn ($q) => $q->latest('exam_date'),
                'mriExams' => fn ($q) => $q->latest('exam_date'),
                'consultations' => fn ($q) => $q->latest('consultation_date'),
            ])
            ->orderBy('id')
            ->lazy(100);

        $fileName = sprintf(
            'SEP_%s_Export_%s.csv',
            $pseudonymize ? 'Recherche_Pseudonymise' : 'Clinique_Nominatif',
            now()->format('Ymd_His')
        );

        if (auth()->check()) {
            activity('export')
                ->causedBy(auth()->user())
                ->withProperties([
                    'mode' => $pseudonymize ? 'pseudonymise' : 'nominatif',
                    'ip' => $request->ip(),
                ])
                ->log($pseudonymize
                    ? 'Exportation de la cohorte de recherche (Mode Pseudonymisé - Conforme CNDP / Loi 09-08)'
                    : 'Exportation clinique nominative complète (Alerte Traçabilité CNDP)');
        }

        return response()->streamDownload(function () use ($patients, $pseudonymize): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 Byte Order Mark (BOM) so Excel detects UTF-8 characters automatically
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            $columns = $pseudonymize ? [
                'ID_RECHERCHE_PSEUDONYME',
                'SEXE',
                'SEXE_CODE',
                'AGE',
                'TAILLE_CM',
                'POIDS_KG',
                'IMC',
                'IMC_CATEGORIE',
                'VILLE',
                'FORME_CLINIQUE',
                'FORME_CLINIQUE_CODE',
                'AGE_DEBUT_SYMPTOMES',
                'AGE_DIAGNOSTIC',
                'DUREE_PREDIAGNOSTIQUE_MOIS',
                'CRITERES_DIAGNOSTIQUES',
                'EDSS_INITIAL',
                'EDSS_ACTUEL',
                'EDSS_EVOLUTION_DELTA',
                'TEST_MARCHE_T25FW_SEC',
                'TEST_9HPT_SEC',
                'NB_TOTAL_POUSSEES',
                'TRAITEMENT_FOND_ACTUEL',
                'TRAITEMENT_CLASSE',
                'TRAITEMENT_STATUT',
                'TRAITEMENT_POUSSEES_2ANS',
                'SUPPLEMENTATION_VITAMINE_D',
                'ANTIDEPRESSEURS',
                'TABAC',
                'ALCOOL',
                'NB_GROSSESSES',
                'NB_ACCOUCHEMENTS',
                'DONNEES_POSTPARTUM',
                'ATCD_FAMILIAUX_SEP',
                'PL_REALISEE',
                'PL_RESULTAT',
                'PL_CYTORACHIE_EL_MM3',
                'PL_PROTEINORACHIE_G_L',
                'PL_BANDES_OLIGOCLONALES_BOC',
                'INDEX_KAPPA_IEPP',
                'BIO_ANTI_NMO',
                'BIO_ANTI_MOG',
                'BIO_ANTI_NUCLEAIRES_AAN',
                'BIO_SEROLOGIE_VHB',
                'IEPP_PROFIL',
                'IRM_CEREBRALE_REALISEE',
                'IRM_MEDULLAIRE_REALISEE',
                'IRM_PRISE_CONTRASTE',
                'IRM_PROTOCOLE_SEP_RESPECTE',
                'IRM_NB_EXAMENS_TOTAL',
                'IRM_NB_PROTOCOLE_SEP',
                'NB_CONSULTATIONS_SUIVI',
                'DERNIERE_CONSULTATION_TYPE',
                'DATE_DERNIERE_CONSULTATION',
            ] : [
                'NUMERO_DOSSIER',
                'NOM',
                'PRENOM',
                'CIN',
                'TELEPHONE',
                'SEXE',
                'DATE_NAISSANCE',
                'AGE',
                'TAILLE_CM',
                'POIDS_KG',
                'IMC',
                'IMC_CATEGORIE',
                'VILLE',
                'FORME_CLINIQUE',
                'AGE_DEBUT_SYMPTOMES',
                'AGE_DIAGNOSTIC',
                'DUREE_PREDIAGNOSTIQUE_MOIS',
                'CRITERES_DIAGNOSTIQUES',
                'EDSS_INITIAL',
                'EDSS_ACTUEL',
                'TEST_MARCHE_T25FW_SEC',
                'TEST_9HPT_SEC',
                'NB_TOTAL_POUSSEES',
                'TRAITEMENT_FOND_ACTUEL',
                'TRAITEMENT_CLASSE',
                'TRAITEMENT_STATUT',
                'SUPPLEMENTATION_VITAMINE_D',
                'ANTIDEPRESSEURS',
                'TABAC',
                'ALCOOL',
                'NB_GROSSESSES',
                'NB_ACCOUCHEMENTS',
                'DONNEES_POSTPARTUM',
                'ATCD_FAMILIAUX_SEP',
                'ATCD_MEDICAUX',
                'ATCD_CHIRURGICAUX',
                'PL_RESULTAT',
                'PL_CYTORACHIE',
                'PL_PROTEINORACHIE',
                'PL_BANDES_OLIGOCLONALES',
                'INDEX_KAPPA_IEPP',
                'BIO_ANTI_NMO',
                'BIO_ANTI_MOG',
                'IRM_PRISE_CONTRASTE',
                'IRM_PROTOCOLE_SEP',
                'NB_CONSULTATIONS',
                'DERNIERE_CONSULTATION_TYPE',
                'DATE_DERNIERE_CONSULTATION',
            ];

            fputcsv($handle, $columns, ';');

            foreach ($patients as $p) {
                $dp = $p->diseaseProfile;
                $latestExam = $p->clinicalExams->first();
                $firstExam = $p->clinicalExams->last();
                $activeTreatment = $p->treatments->first();
                $latestLcr = $p->complementaryExams->first();
                $latestMri = $p->mriExams->first();
                $latestConsultation = $p->consultations->first();

                $latestEdssVal = null;
                if ($latestExam instanceof ClinicalExam && $latestExam->edss_score !== null) {
                    $latestEdssVal = (float) $latestExam->edss_score;
                } elseif ($dp instanceof DiseaseProfile && $dp->initial_edss !== null) {
                    $latestEdssVal = (float) $dp->initial_edss;
                }

                $firstEdssVal = null;
                if ($dp instanceof DiseaseProfile && $dp->initial_edss !== null) {
                    $firstEdssVal = (float) $dp->initial_edss;
                } elseif ($firstExam instanceof ClinicalExam && $firstExam->edss_score !== null) {
                    $firstEdssVal = (float) $firstExam->edss_score;
                }

                $deltaEdss = ($latestEdssVal !== null && $firstEdssVal !== null) ? round($latestEdssVal - $firstEdssVal, 1) : null;

                $clinicalFormLabel = $dp instanceof DiseaseProfile ? $dp->current_clinical_form->label() : 'Non precise';
                $clinicalFormCode = $dp instanceof DiseaseProfile ? $dp->current_clinical_form->value : '';
                $ageOnset = ($dp instanceof DiseaseProfile && $dp->first_symptom_age_estimated !== null) ? $dp->first_symptom_age_estimated : '';
                $ageDiag = ($dp instanceof DiseaseProfile && $dp->diagnosis_age_estimated !== null) ? $dp->diagnosis_age_estimated : '';
                $dureePre = ($dp instanceof DiseaseProfile && $dp->prediagnostic_duration_months !== null) ? $dp->prediagnostic_duration_months : '';
                $critDiag = ($dp instanceof DiseaseProfile && $dp->diagnostic_criteria !== null) ? $dp->diagnostic_criteria : '';
                $initEdss = ($dp instanceof DiseaseProfile && $dp->initial_edss !== null) ? $dp->initial_edss : '';

                $treatmentName = $activeTreatment instanceof Treatment ? $activeTreatment->molecule_name : 'Aucun';
                $treatmentClass = ($activeTreatment instanceof Treatment && $activeTreatment->therapeutic_class !== null) ? $activeTreatment->therapeutic_class : '';
                $treatmentStatus = $activeTreatment instanceof Treatment ? $activeTreatment->status : '';
                $relapsesAfter2Years = ($activeTreatment instanceof Treatment && $activeTreatment->relapses_after_two_years !== null) ? $activeTreatment->relapses_after_two_years : '';
                $vitDVal = ($activeTreatment instanceof Treatment && $activeTreatment->vitamin_d_supplementation) ? 1 : 0;
                $antidepVal = ($activeTreatment instanceof Treatment && $activeTreatment->antidepressants) ? 1 : 0;

                $plResultVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->pl_result !== null) ? $latestLcr->pl_result : '';
                $cytorachieVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->pl_cytorachie !== null) ? $latestLcr->pl_cytorachie : '';
                $proteinorachieVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->pl_proteinorachie !== null) ? $latestLcr->pl_proteinorachie : '';
                $bocVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->pl_oligoclonal_bands !== null) ? $latestLcr->pl_oligoclonal_bands : '';
                $antiNmoVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->biology_anti_nmo !== null) ? $latestLcr->biology_anti_nmo : '';
                $antiMogVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->biology_anti_mog !== null) ? $latestLcr->biology_anti_mog : '';
                $aanVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->biology_anti_antinucleaire !== null) ? $latestLcr->biology_anti_antinucleaire : '';
                $vhbVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->biology_serology_hbv !== null) ? $latestLcr->biology_serology_hbv : '';
                $ieppVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->iepp_result !== null) ? $latestLcr->iepp_result : '';

                $mriContrastVal = ($latestMri instanceof MriExam && $latestMri->contrast_enhancement !== null) ? $latestMri->contrast_enhancement : '';

                $consultDateVal = ($latestConsultation instanceof Consultation && $latestConsultation->consultation_date !== null) ? $latestConsultation->consultation_date->format('d/m/Y') : '';
                $consultTypeVal = ($latestConsultation instanceof Consultation && $latestConsultation->consultation_type !== null) ? $latestConsultation->consultation_type : '';

                $t25fwVal = ($latestExam instanceof ClinicalExam && $latestExam->t25fw !== null) ? $latestExam->t25fw : '';
                $nineHptVal = ($latestExam instanceof ClinicalExam && $latestExam->nine_hpt !== null) ? $latestExam->nine_hpt : '';
                $kappaVal = ($latestLcr instanceof ComplementaryExam && $latestLcr->iepp_kappa_index !== null) ? $latestLcr->iepp_kappa_index : '';
                $imcCat = $p->imcClassification()['label'] ?? '';

                $mriTotal = $p->mriExams->count();
                $mriSepCount = $p->mriExams->where('sep_protocol_applied', true)->count();
                $mriSepCompliant = $mriSepCount > 0 ? 1 : ($mriTotal > 0 ? 0 : '');
                $mriSepNominal = $mriTotal === 0 ? 'Aucune IRM' : ($mriSepCount === $mriTotal ? "Conforme ({$mriSepCount}/{$mriTotal})" : ($mriSepCount > 0 ? "Partiel ({$mriSepCount}/{$mriTotal})" : "Non conforme ({$mriTotal} IRM)"));

                if ($pseudonymize) {
                    $row = [
                        'SEP-'.substr(hash('sha256', (string) ($p->id.config('app.key'))), 0, 6),
                        $p->gender->label(),
                        $p->gender->value === 'female' ? 1 : 2,
                        $p->age,
                        $p->height ?? '',
                        $p->weight ?? '',
                        $p->imc ?? '',
                        $imcCat,
                        $p->city ?? 'Non renseigne',
                        $clinicalFormLabel,
                        $clinicalFormCode,
                        $ageOnset,
                        $ageDiag,
                        $dureePre,
                        $critDiag,
                        $initEdss,
                        $latestEdssVal ?? '',
                        $deltaEdss ?? '',
                        $t25fwVal,
                        $nineHptVal,
                        $p->relapses->count(),
                        $treatmentName,
                        $treatmentClass,
                        $treatmentStatus,
                        $relapsesAfter2Years,
                        $vitDVal,
                        $antidepVal,
                        $p->tobacco_consumption ?? '',
                        $p->alcohol_consumption ?? '',
                        $p->pregnancies_count ?? '',
                        $p->deliveries_count ?? '',
                        $p->postpartum_info ?? '',
                        $p->family_history_sep ? 1 : 0,
                        $latestLcr instanceof ComplementaryExam ? 1 : 0,
                        $plResultVal,
                        $cytorachieVal,
                        $proteinorachieVal,
                        $bocVal,
                        $kappaVal,
                        $antiNmoVal,
                        $antiMogVal,
                        $aanVal,
                        $vhbVal,
                        $ieppVal,
                        $p->mriExams->where('region', '!=', 'medullaire')->count() > 0 ? 1 : 0,
                        $p->mriExams->where('region', '!=', 'cerebrale')->count() > 0 ? 1 : 0,
                        $mriContrastVal,
                        $mriSepCompliant,
                        $mriTotal,
                        $mriSepCount,
                        $p->consultations->count(),
                        $consultTypeVal,
                        $consultDateVal,
                    ];
                } else {
                    $row = [
                        $p->dossier_number,
                        $p->last_name,
                        $p->first_name,
                        $p->cin ?? '',
                        $p->phone ?? '',
                        $p->gender->label(),
                        $p->birth_date->format('d/m/Y'),
                        $p->age,
                        $p->height ?? '',
                        $p->weight ?? '',
                        $p->imc ?? '',
                        $imcCat,
                        $p->city ?? '',
                        $clinicalFormLabel,
                        $ageOnset,
                        $ageDiag,
                        $dureePre,
                        $critDiag,
                        $initEdss,
                        $latestEdssVal ?? '',
                        $t25fwVal,
                        $nineHptVal,
                        $p->relapses->count(),
                        $treatmentName,
                        $treatmentClass,
                        $treatmentStatus,
                        $vitDVal === 1 ? 'Oui' : 'Non',
                        $antidepVal === 1 ? 'Oui' : 'Non',
                        $p->tobacco_consumption ?? '',
                        $p->alcohol_consumption ?? '',
                        $p->pregnancies_count ?? '',
                        $p->deliveries_count ?? '',
                        $p->postpartum_info ?? '',
                        $p->family_history_sep ? 'Oui' : 'Non',
                        $p->medical_history_notes ?? '',
                        $p->surgical_history_notes ?? '',
                        $plResultVal,
                        $cytorachieVal,
                        $proteinorachieVal,
                        $bocVal,
                        $kappaVal,
                        $antiNmoVal,
                        $antiMogVal,
                        $mriContrastVal,
                        $mriSepNominal,
                        $p->consultations->count(),
                        $consultTypeVal,
                        $consultDateVal,
                    ];
                }

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the filtered Eloquent query based on request parameters.
     *
     * @return Builder<Patient>
     */
    protected function buildFilteredQuery(Request $request): Builder
    {
        $query = Patient::query();

        // 1. Text search
        if ($search = $request->query('search')) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('dossier_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('cin', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // 2. Gender
        $gender = $request->query('gender');
        if ($gender && $gender !== 'all') {
            $query->where('gender', $gender);
        }

        // 3. Current Age (date math agnostic)
        $ageMin = $request->query('age_min');
        if (is_numeric($ageMin)) {
            $maxBirthDate = now()->subYears((int) $ageMin)->format('Y-m-d');
            $query->where('birth_date', '<=', $maxBirthDate);
        }

        $ageMax = $request->query('age_max');
        if (is_numeric($ageMax)) {
            $minBirthDate = now()->subYears((int) $ageMax + 1)->addDay()->format('Y-m-d');
            $query->where('birth_date', '>=', $minBirthDate);
        }

        // 4. City
        $city = $request->query('city');
        if ($city && $city !== 'all') {
            $query->where('city', $city);
        }

        // 5. Clinical Form
        $clinicalForm = $request->query('clinical_form');
        if ($clinicalForm && $clinicalForm !== 'all') {
            $enumCase = match ($clinicalForm) {
                'RR', 'remittente_recurrente' => ClinicalForm::RelapsingRemitting,
                'SP', 'secondairement_progressive' => ClinicalForm::SecondaryProgressive,
                'PP', 'primaire_progressive' => ClinicalForm::PrimaryProgressive,
                'CIS' => ClinicalForm::ClinicallyIsolatedSyndrome,
                'RIS' => ClinicalForm::RadiologicallyIsolatedSyndrome,
                default => null,
            };

            $query->whereHas('diseaseProfile', function (Builder $q) use ($clinicalForm, $enumCase): void {
                if ($enumCase) {
                    $q->where('current_clinical_form', $enumCase)
                        ->orWhere('initial_clinical_form', $enumCase)
                        ->orWhere('current_clinical_form', $clinicalForm)
                        ->orWhere('initial_clinical_form', $clinicalForm);
                } else {
                    $q->where('current_clinical_form', $clinicalForm)
                        ->orWhere('initial_clinical_form', $clinicalForm);
                }
            });
        }

        // 6. Age at onset
        $ageOnsetMin = $request->query('age_onset_min');
        if (is_numeric($ageOnsetMin)) {
            $query->whereHas('diseaseProfile', fn (Builder $q) => $q->where('first_symptom_age_estimated', '>=', (int) $ageOnsetMin));
        }

        $ageOnsetMax = $request->query('age_onset_max');
        if (is_numeric($ageOnsetMax)) {
            $query->whereHas('diseaseProfile', fn (Builder $q) => $q->where('first_symptom_age_estimated', '<=', (int) $ageOnsetMax));
        }

        // 7. Age at diagnosis
        $ageDiagMin = $request->query('age_diagnosis_min');
        if (is_numeric($ageDiagMin)) {
            $query->whereHas('diseaseProfile', fn (Builder $q) => $q->where('diagnosis_age_estimated', '>=', (int) $ageDiagMin));
        }

        $ageDiagMax = $request->query('age_diagnosis_max');
        if (is_numeric($ageDiagMax)) {
            $query->whereHas('diseaseProfile', fn (Builder $q) => $q->where('diagnosis_age_estimated', '<=', (int) $ageDiagMax));
        }

        // 8. EDSS Score
        $edssMin = $request->query('edss_min');
        if (is_numeric($edssMin)) {
            $query->where(function (Builder $q) use ($edssMin): void {
                $q->whereHas('clinicalExams', fn (Builder $sq) => $sq->where('edss_score', '>=', (float) $edssMin))
                    ->orWhereHas('diseaseProfile', fn (Builder $sq) => $sq->where('initial_edss', '>=', (float) $edssMin));
            });
        }

        $edssMax = $request->query('edss_max');
        if (is_numeric($edssMax)) {
            $query->where(function (Builder $q) use ($edssMax): void {
                $q->whereHas('clinicalExams', fn (Builder $sq) => $sq->where('edss_score', '<=', (float) $edssMax))
                    ->orWhereHas('diseaseProfile', fn (Builder $sq) => $sq->where('initial_edss', '<=', (float) $edssMax));
            });
        }

        // 9. Relapses count
        $relapsesMin = $request->query('relapses_min');
        if (is_numeric($relapsesMin)) {
            $query->has('relapses', '>=', (int) $relapsesMin);
        }

        $relapsesMax = $request->query('relapses_max');
        if (is_numeric($relapsesMax)) {
            $query->has('relapses', '<=', (int) $relapsesMax);
        }

        // 10. MRI Region, Contrast & SEP Protocol
        $mriRegion = $request->query('mri_region');
        if ($mriRegion && $mriRegion !== 'all') {
            $query->whereHas('mriExams', fn (Builder $q) => $q->where('region', $mriRegion));
        }

        $mriContrast = $request->query('mri_contrast');
        if ($mriContrast && $mriContrast !== 'all') {
            $query->whereHas('mriExams', fn (Builder $q) => $q->where('contrast_enhancement', $mriContrast === 'oui' ? 'present' : 'absent'));
        }

        $mriSepProtocol = $request->query('mri_sep_protocol');
        if ($mriSepProtocol && $mriSepProtocol !== 'all') {
            $query->whereHas('mriExams', fn (Builder $q) => $q->where('sep_protocol_applied', $mriSepProtocol === 'oui'));
        }

        // 11. BOC & Biology
        $boc = $request->query('boc');
        if ($boc && $boc !== 'all') {
            $query->whereHas('complementaryExams', fn (Builder $q) => $q->where('pl_oligoclonal_bands', $boc));
        }

        $antiNmo = $request->query('anti_nmo');
        if ($antiNmo && $antiNmo !== 'all') {
            $query->whereHas('complementaryExams', fn (Builder $q) => $q->where('biology_anti_nmo', $antiNmo));
        }

        $antiMog = $request->query('anti_mog');
        if ($antiMog && $antiMog !== 'all') {
            $query->whereHas('complementaryExams', fn (Builder $q) => $q->where('biology_anti_mog', $antiMog));
        }

        // 12. Treatments
        $treatmentMolecule = $request->query('treatment_molecule');
        if ($treatmentMolecule && $treatmentMolecule !== 'all') {
            $query->whereHas('treatments', fn (Builder $q) => $q->where('molecule_name', 'like', "%{$treatmentMolecule}%"));
        }

        $treatmentStatus = $request->query('treatment_status');
        if ($treatmentStatus && $treatmentStatus !== 'all') {
            $query->whereHas('treatments', fn (Builder $q) => $q->where('status', $treatmentStatus));
        }

        $vitaminD = $request->query('vitamin_d');
        if ($vitaminD && $vitaminD !== 'all') {
            $query->whereHas('treatments', fn (Builder $q) => $q->where('vitamin_d_supplementation', $vitaminD === 'oui'));
        }

        $antidepressants = $request->query('antidepressants');
        if ($antidepressants && $antidepressants !== 'all') {
            $query->whereHas('treatments', fn (Builder $q) => $q->where('antidepressants', $antidepressants === 'oui'));
        }

        return $query;
    }

    /**
     * Compute statistics for the filtered cohort.
     *
     * @param  Builder<Patient>  $query
     * @return array<string, mixed>
     */
    protected function computeStatistics(Builder $query): array
    {
        $total = $query->count();

        if ($total === 0) {
            return [
                'total_patients' => 0,
                'female_count' => 0,
                'male_count' => 0,
                'female_ratio' => '0:1',
                'average_age' => 0,
                'clinical_forms' => [
                    'remittente' => 0,
                    'secondaire' => 0,
                    'primaire' => 0,
                ],
                'boc_positive_rate' => 0,
                'top_treatment' => 'Aucun',
                'mri_total_count' => 0,
                'mri_sep_protocol_count' => 0,
                'mri_sep_protocol_rate' => 0,
            ];
        }

        $femaleCount = (clone $query)->where('gender', 'female')->count();
        $maleCount = (clone $query)->where('gender', 'male')->count();

        $ratio = $maleCount > 0 ? round($femaleCount / $maleCount, 1).':1' : "{$femaleCount}:0";

        // Calculate average age from birth_dates
        $patients = (clone $query)->get(['id', 'birth_date']);
        $totalAge = $patients->sum(fn (Patient $p) => $p->age);
        $averageAge = $patients->count() > 0 ? round($totalAge / $patients->count(), 1) : 0;

        // Clinical forms
        $rrCount = (clone $query)->whereHas('diseaseProfile', fn ($q) => $q->where('current_clinical_form', ClinicalForm::RelapsingRemitting)->orWhere('current_clinical_form', 'RR')->orWhere('current_clinical_form', 'remittente_recurrente'))->count();
        $spCount = (clone $query)->whereHas('diseaseProfile', fn ($q) => $q->where('current_clinical_form', ClinicalForm::SecondaryProgressive)->orWhere('current_clinical_form', 'SP')->orWhere('current_clinical_form', 'secondairement_progressive'))->count();
        $ppCount = (clone $query)->whereHas('diseaseProfile', fn ($q) => $q->where('current_clinical_form', ClinicalForm::PrimaryProgressive)->orWhere('current_clinical_form', 'PP')->orWhere('current_clinical_form', 'primaire_progressive'))->count();

        // BOC positive rate
        $bocPosCount = (clone $query)->whereHas('complementaryExams', fn ($q) => $q->where('pl_oligoclonal_bands', 'positives'))->count();
        $bocRate = round(($bocPosCount / $total) * 100, 1);

        // Top treatment
        $topTreatment = Treatment::query()
            ->whereIn('patient_id', (clone $query)->select('id'))
            ->where('status', 'en_cours')
            ->groupBy('molecule_name')
            ->selectRaw('molecule_name, count(*) as count')
            ->orderByDesc('count')
            ->value('molecule_name') ?? 'Non documenté';

        // MRI Protocol statistics (Protocole IRM SEP)
        $cohortPatientIds = (clone $query)->select('id');
        $mriTotalCount = MriExam::query()->whereIn('patient_id', $cohortPatientIds)->count();
        $mriSepProtocolCount = MriExam::query()->whereIn('patient_id', $cohortPatientIds)->where('sep_protocol_applied', true)->count();
        $mriSepProtocolRate = $mriTotalCount > 0 ? round(($mriSepProtocolCount / $mriTotalCount) * 100, 1) : 0;

        return [
            'total_patients' => $total,
            'female_count' => $femaleCount,
            'male_count' => $maleCount,
            'female_ratio' => $ratio,
            'average_age' => $averageAge,
            'clinical_forms' => [
                'remittente' => $rrCount,
                'secondaire' => $spCount,
                'primaire' => $ppCount,
            ],
            'boc_positive_rate' => $bocRate,
            'top_treatment' => $topTreatment,
            'mri_total_count' => $mriTotalCount,
            'mri_sep_protocol_count' => $mriSepProtocolCount,
            'mri_sep_protocol_rate' => $mriSepProtocolRate,
        ];
    }
}
