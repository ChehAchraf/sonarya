<?php

namespace App\Http\Controllers;

use App\Enums\ClinicalForm;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PatientStatus;
use App\Models\DiseaseProfile;
use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientImportController extends Controller
{
    /**
     * Download a preformatted CSV template for Excel / SPSS bulk patient import.
     */
    public function template(): StreamedResponse
    {
        $fileName = 'sonarya_modele_import_patients_excel.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for automatic Excel delimiter & character encoding detection
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = [
                'NUMERO_DOSSIER',
                'NOM',
                'PRENOM',
                'SEXE',
                'DATE_NAISSANCE',
                'CIN',
                'TELEPHONE',
                'VILLE',
                'ORIGINE',
                'SITUATION_FAMILIALE',
                'NOMBRE_ENFANTS',
                'PROFESSION',
                'COUVERTURE_MEDICALE',
                'TAILLE_CM',
                'POIDS_KG',
                'TABAC',
                'ALCOOL',
                'NOMBRE_GROSSESSES',
                'NOMBRE_ACCOUCHEMENTS',
                'DONNEES_POSTPARTUM',
                'ATCD_FAMILIAUX_SEP',
                'ATCD_MEDICAUX',
                'ATCD_CHIRURGICAUX',
                'FORME_CLINIQUE',
                'DATE_1ER_SYMPTOME',
                'AGE_1ER_SYMPTOME',
                'DATE_DIAGNOSTIC',
                'AGE_DIAGNOSTIC',
                'EDSS_INITIAL',
                'NOTES',
            ];

            fputcsv($handle, $headers, ';');

            // Example 1: Female patient with exact dates
            fputcsv($handle, [
                'DOS-2026-001',
                'BENANI',
                'Fatima',
                'Femme',
                '14/05/1992',
                'AB123456',
                '0612345678',
                'Casablanca',
                'Casablanca-Settat',
                'Marié(e)',
                '2',
                'Enseignante',
                'CNSS, AMO',
                'Non',
                'Non',
                '2',
                'RR',
                '10/03/2020',
                '27',
                '15/08/2020',
                '28',
                '1.5',
                'Inauguré par une NORB gauche unilatérale',
            ], ';');

            // Example 2: Male patient with age only (unknown exact dates)
            fputcsv($handle, [
                '',
                'EL AMRI',
                'Karim',
                'Homme',
                '20/11/1988',
                'CD789101',
                '0698765432',
                'Rabat',
                'Rabat-Salé-Kénitra',
                'Célibataire',
                '0',
                'Ingénieur',
                'AMO',
                '10 PA',
                'Non',
                '',
                'RR',
                '',
                '30',
                '',
                '31',
                '2.0',
                'Dossier importé depuis registre hospitalier',
            ], ';');

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Parse and import patients from an uploaded Excel/SPSS compatible CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Patient::class);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ], [
            'file.required' => 'Veuillez sélectionner un fichier CSV ou Excel à importer.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ]);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return back()->with('error', "Le fichier téléversé n'est pas valide.");
        }

        $filePath = $file->getRealPath();
        $fileHandle = fopen($filePath, 'r');
        if (! $fileHandle) {
            return back()->with('error', 'Impossible de lire le fichier importé.');
        }

        // Read first line to detect delimiter and strip potential UTF-8 BOM
        $firstLine = fgets($fileHandle);
        if ($firstLine === false) {
            fclose($fileHandle);

            return back()->with('error', 'Le fichier importé est vide.');
        }

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($fileHandle);

        // Discard BOM again if present after rewind
        $bomCheck = fread($fileHandle, 3);
        if ($bomCheck !== "\xEF\xBB\xBF") {
            rewind($fileHandle);
        }

        $rawHeaders = fgetcsv($fileHandle, 0, $delimiter);
        if (! $rawHeaders || count($rawHeaders) === 0) {
            fclose($fileHandle);

            return back()->with('error', "Impossible d'extraire les en-têtes du fichier.");
        }

        $normalizedHeaders = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rawHeaders);

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($fileHandle, 0, $delimiter)) !== false) {
                $rowNumber++;

                // Ignore empty or whitespace rows
                if (count(array_filter($row, fn ($val) => trim((string) $val) !== '')) === 0) {
                    continue;
                }

                // Map header to values
                $rowData = [];
                foreach ($normalizedHeaders as $idx => $headerKey) {
                    if ($headerKey !== '' && isset($row[$idx])) {
                        $rowData[$headerKey] = trim((string) $row[$idx]);
                    }
                }

                $lastName = $rowData['last_name'] ?? $rowData['nom'] ?? null;
                $firstName = $rowData['first_name'] ?? $rowData['prenom'] ?? null;

                if (empty($lastName) && empty($firstName)) {
                    $skippedCount++;
                    $errors[] = "Ligne {$rowNumber} : Nom ou prénom manquant.";

                    continue;
                }

                $gender = $this->parseGender($rowData['gender'] ?? $rowData['sexe'] ?? null);
                $birthDate = $this->parseDate($rowData['birth_date'] ?? $rowData['date_naissance'] ?? null);

                if (! $birthDate) {
                    // Default to fallback adult age if absent
                    $birthDate = Carbon::now()->subYears(30);
                }

                $dossierNumber = $rowData['dossier_number'] ?? $rowData['numero_dossier'] ?? null;
                if (empty($dossierNumber)) {
                    $dossierNumber = Patient::generateNextDossierNumber();
                }

                // Check if dossier already exists; if so, skip or append suffix
                if (Patient::where('dossier_number', $dossierNumber)->exists()) {
                    $dossierNumber = Patient::generateNextDossierNumber();
                }

                $maritalStatus = $this->parseMaritalStatus($rowData['marital_status'] ?? $rowData['situation_familiale'] ?? null);
                $childrenCount = isset($rowData['children_count']) && is_numeric($rowData['children_count'])
                    ? (int) $rowData['children_count']
                    : (isset($rowData['nombre_enfants']) && is_numeric($rowData['nombre_enfants']) ? (int) $rowData['nombre_enfants'] : null);

                $healthCoverage = $this->parseHealthCoverage($rowData['health_coverage'] ?? $rowData['couverture_medicale'] ?? null);

                $patient = Patient::create([
                    'dossier_number' => $dossierNumber,
                    'last_name' => $lastName ?: 'Inconnu',
                    'first_name' => $firstName ?: 'Inconnu',
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'cin' => $rowData['cin'] ?? null,
                    'phone' => $rowData['phone'] ?? $rowData['telephone'] ?? null,
                    'city' => $rowData['city'] ?? $rowData['ville'] ?? null,
                    'origin' => $rowData['origin'] ?? $rowData['origine'] ?? null,
                    'profession' => $rowData['profession'] ?? null,
                    'marital_status' => $maritalStatus,
                    'children_count' => $childrenCount,
                    'health_coverage' => $healthCoverage,
                    'height' => isset($rowData['height']) && is_numeric($rowData['height']) ? (float) $rowData['height'] : null,
                    'weight' => isset($rowData['weight']) && is_numeric($rowData['weight']) ? (float) $rowData['weight'] : null,
                    'tobacco_consumption' => $rowData['tobacco_consumption'] ?? $rowData['tabac'] ?? null,
                    'alcohol_consumption' => $rowData['alcohol_consumption'] ?? $rowData['alcool'] ?? null,
                    'pregnancies_count' => isset($rowData['pregnancies_count']) && is_numeric($rowData['pregnancies_count'])
                        ? (int) $rowData['pregnancies_count']
                        : (isset($rowData['nombre_grossesses']) && is_numeric($rowData['nombre_grossesses']) ? (int) $rowData['nombre_grossesses'] : null),
                    'deliveries_count' => isset($rowData['deliveries_count']) && is_numeric($rowData['deliveries_count'])
                        ? (int) $rowData['deliveries_count']
                        : (isset($rowData['nombre_accouchements']) && is_numeric($rowData['nombre_accouchements']) ? (int) $rowData['nombre_accouchements'] : null),
                    'postpartum_info' => $rowData['postpartum_info'] ?? $rowData['donnees_postpartum'] ?? null,
                    'family_history_sep' => isset($rowData['family_history_sep'])
                        ? in_array(strtolower(trim((string) $rowData['family_history_sep'])), ['1', 'oui', 'yes', 'true'], true)
                        : (isset($rowData['atcd_familiaux_sep']) ? in_array(strtolower(trim((string) $rowData['atcd_familiaux_sep'])), ['1', 'oui', 'yes', 'true'], true) : false),
                    'medical_history_notes' => $rowData['medical_history_notes'] ?? $rowData['atcd_medicaux'] ?? null,
                    'surgical_history_notes' => $rowData['surgical_history_notes'] ?? $rowData['atcd_chirurgicaux'] ?? null,
                    'status' => PatientStatus::Active,
                    'notes' => $rowData['notes'] ?? 'Dossier importé via fichier Excel/SPSS.',
                    'created_by' => $request->user()?->id,
                    'updated_by' => $request->user()?->id,
                ]);

                // Create DiseaseProfile if clinical information provided
                $clinicalForm = $this->parseClinicalForm($rowData['clinical_form'] ?? $rowData['forme_clinique'] ?? null);
                $symptomDate = $this->parseDate($rowData['first_symptom_date'] ?? $rowData['date_1er_symptome'] ?? null);
                $symptomAge = isset($rowData['first_symptom_age_estimated']) && is_numeric($rowData['first_symptom_age_estimated'])
                    ? (int) $rowData['first_symptom_age_estimated']
                    : (isset($rowData['age_1er_symptome']) && is_numeric($rowData['age_1er_symptome']) ? (int) $rowData['age_1er_symptome'] : null);

                if ($symptomDate && ! $symptomAge) {
                    $symptomAge = $patient->birth_date->diffInYears($symptomDate);
                }

                $diagDate = $this->parseDate($rowData['diagnosis_date'] ?? $rowData['date_diagnostic'] ?? null);
                $diagAge = isset($rowData['diagnosis_age_estimated']) && is_numeric($rowData['diagnosis_age_estimated'])
                    ? (int) $rowData['diagnosis_age_estimated']
                    : (isset($rowData['age_diagnostic']) && is_numeric($rowData['age_diagnostic']) ? (int) $rowData['age_diagnostic'] : null);

                if ($diagDate && ! $diagAge) {
                    $diagAge = $patient->birth_date->diffInYears($diagDate);
                }

                $edssInitial = isset($rowData['initial_edss']) && is_numeric($rowData['initial_edss'])
                    ? (float) $rowData['initial_edss']
                    : (isset($rowData['edss_initial']) && is_numeric($rowData['edss_initial']) ? (float) $rowData['edss_initial'] : null);

                $prediagnosticMonths = null;
                if ($symptomDate && $diagDate && $diagDate->greaterThanOrEqualTo($symptomDate)) {
                    $prediagnosticMonths = $symptomDate->diffInMonths($diagDate);
                } elseif ($symptomAge !== null && $diagAge !== null && $diagAge >= $symptomAge) {
                    $prediagnosticMonths = ($diagAge - $symptomAge) * 12;
                }

                DiseaseProfile::create([
                    'patient_id' => $patient->id,
                    'first_symptom_date' => $symptomDate,
                    'first_symptom_age_estimated' => $symptomAge,
                    'diagnosis_date' => $diagDate,
                    'diagnosis_age_estimated' => $diagAge,
                    'prediagnostic_duration_months' => $prediagnosticMonths,
                    'initial_clinical_form' => $clinicalForm,
                    'current_clinical_form' => $clinicalForm,
                    'diagnostic_criteria' => 'Critères McDonald standard (Importé)',
                    'initial_edss' => $edssInitial,
                    'notes' => 'Profil créé automatiquement lors de l\'importation.',
                ]);

                // Create MedicalHistory
                PatientMedicalHistory::create([
                    'patient_id' => $patient->id,
                    'smoking' => $patient->tobacco_consumption ?: 'Non',
                    'alcohol' => $patient->alcohol_consumption ?: 'Non',
                    'pregnancies_count' => $patient->pregnancies_count ?: 0,
                    'children_count' => $patient->children_count ?: 0,
                    'family_history_neurological' => false,
                    'family_history_autoimmune' => false,
                ]);

                $importedCount++;
            }

            fclose($fileHandle);
            DB::commit();

            if (auth()->check()) {
                activity('import_dossiers')
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'imported_count' => $importedCount,
                        'skipped_count' => $skippedCount,
                        'file_name' => $file->getClientOriginalName(),
                    ])
                    ->log("Importation de {$importedCount} dossier(s) patient(s) via Excel/CSV");
            }

            $message = "Importation réussie : {$importedCount} dossier(s) patient(s) ont été enregistrés avec succès.";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} ligne(s) ignorée(s)).";
            }

            return back()->with('success', $message);
        } catch (Exception $e) {
            DB::rollBack();
            fclose($fileHandle);

            return back()->with('error', "Une erreur est survenue lors de l'importation à la ligne {$rowNumber} : {$e->getMessage()}");
        }
    }

    /**
     * Detect CSV delimiter (; or , or \t).
     */
    protected function detectDelimiter(string $firstLine): string
    {
        $semicolons = substr_count($firstLine, ';');
        $commas = substr_count($firstLine, ',');
        $tabs = substr_count($firstLine, "\t");

        if ($semicolons >= $commas && $semicolons >= $tabs && $semicolons > 0) {
            return ';';
        }

        if ($tabs > $commas && $tabs > $semicolons) {
            return "\t";
        }

        return ',';
    }

    /**
     * Normalize header keys to lowercase snake_case.
     */
    protected function normalizeHeader(string $header): string
    {
        $clean = trim($header);
        $clean = strtolower($clean);
        $clean = str_replace([' ', '-', "'", '"', '.', '/', '\\'], '_', $clean);
        $clean = preg_replace('/_+/', '_', $clean) ?? $clean;
        $clean = trim($clean, '_');

        return match ($clean) {
            'numero_dossier', 'n_dossier', 'ipp', 'dossier', 'dossier_no' => 'dossier_number',
            'nom', 'nom_de_famille', 'last_name', 'nom_patient' => 'last_name',
            'prenom', 'first_name', 'prenom_patient' => 'first_name',
            'sexe', 'genre' => 'gender',
            'date_naissance', 'ddn', 'dob', 'date_de_naissance', 'naissance' => 'birth_date',
            'telephone', 'gsm', 'tel', 'phone_number' => 'phone',
            'ville', 'city', 'residence', 'lieu_residence' => 'city',
            'origine', 'origine_geographique', 'region_origine' => 'origin',
            'situation_familiale', 'statut_marital', 'etat_civil' => 'marital_status',
            'nombre_enfants', 'nb_enfants', 'enfants' => 'children_count',
            'couverture_medicale', 'assurance', 'mutuelle', 'couverture' => 'health_coverage',
            'taille', 'taille_cm', 'hauteur', 'height' => 'height',
            'poids', 'poids_kg', 'weight' => 'weight',
            'tabac', 'tabagisme', 'fumeur' => 'tobacco_consumption',
            'alcool', 'alcoolisme', 'boisson' => 'alcohol_consumption',
            'nombre_grossesses', 'grossesses', 'gestite' => 'pregnancies_count',
            'nombre_accouchements', 'nb_accouchements', 'accouchements', 'parite' => 'deliveries_count',
            'donnees_postpartum', 'postpartum', 'post_partum' => 'postpartum_info',
            'atcd_familiaux_sep', 'famille_sep', 'sep_famille' => 'family_history_sep',
            'atcd_medicaux', 'antecedents_medicaux' => 'medical_history_notes',
            'atcd_chirurgicaux', 'antecedents_chirurgicaux' => 'surgical_history_notes',
            'forme_clinique', 'forme_sep', 'type_sep' => 'clinical_form',
            'date_1er_symptome', 'date_debut', 'date_premier_signe', 'date_debut_symptomes' => 'first_symptom_date',
            'age_1er_symptome', 'age_debut', 'age_apparition' => 'first_symptom_age_estimated',
            'date_diagnostic', 'date_confirmation' => 'diagnosis_date',
            'age_diagnostic', 'age_au_diagnostic' => 'diagnosis_age_estimated',
            'edss_initial', 'score_edss_initial', 'edss_diag' => 'initial_edss',
            default => $clean,
        };
    }

    /**
     * Parse Gender enum from text.
     */
    protected function parseGender(?string $val): Gender
    {
        if (! $val) {
            return Gender::Female;
        }

        $v = strtolower(trim($val));
        if (in_array($v, ['homme', 'h', 'male', 'm', 'masculin'], true)) {
            return Gender::Male;
        }

        return Gender::Female;
    }

    /**
     * Parse MaritalStatus enum from text.
     */
    protected function parseMaritalStatus(?string $val): ?MaritalStatus
    {
        if (! $val) {
            return null;
        }

        $v = strtolower(trim($val));
        if (str_contains($v, 'mari') || $v === 'married') {
            return MaritalStatus::Married;
        }
        if (str_contains($v, 'celibat') || $v === 'single') {
            return MaritalStatus::Single;
        }
        if (str_contains($v, 'divorc')) {
            return MaritalStatus::Divorced;
        }
        if (str_contains($v, 'veuf') || str_contains($v, 'veuv')) {
            return MaritalStatus::Widowed;
        }

        return null;
    }

    /**
     * Parse flexible date strings.
     */
    protected function parseDate(?string $val): ?Carbon
    {
        if (! $val) {
            return null;
        }

        $v = trim($val);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'];

        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $v);
                if ($d !== false && $d->year > 1900 && $d->year <= Carbon::now()->year + 1) {
                    return $d;
                }
            } catch (Exception) {
                // Try next format
            }
        }

        try {
            return Carbon::parse($v);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Parse health coverage checkboxes/list.
     *
     * @return array<string>
     */
    protected function parseHealthCoverage(?string $val): array
    {
        if (! $val) {
            return [];
        }

        $parts = preg_split('/[,;|]+/', $val) ?: [];
        $result = [];

        foreach ($parts as $p) {
            $trimmed = trim($p);
            if ($trimmed !== '') {
                $upper = strtoupper($trimmed);
                if (str_contains($upper, 'CNSS')) {
                    $result[] = 'CNSS';
                } elseif (str_contains($upper, 'AMO')) {
                    $result[] = 'AMO';
                } elseif (str_contains($upper, 'SANS') || str_contains($upper, 'AUCUNE')) {
                    $result[] = 'Sans couverture';
                } else {
                    $result[] = $trimmed;
                }
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Parse clinical form.
     */
    protected function parseClinicalForm(?string $val): ClinicalForm
    {
        if (! $val) {
            return ClinicalForm::RelapsingRemitting;
        }

        $v = strtoupper(trim($val));
        if ($v === 'SP' || str_contains($v, 'SECONDAIRE')) {
            return ClinicalForm::SecondaryProgressive;
        }
        if ($v === 'PP' || str_contains($v, 'PRIMAIRE')) {
            return ClinicalForm::PrimaryProgressive;
        }
        if ($v === 'CIS') {
            return ClinicalForm::ClinicallyIsolatedSyndrome;
        }
        if ($v === 'RIS') {
            return ClinicalForm::RadiologicallyIsolatedSyndrome;
        }

        return ClinicalForm::RelapsingRemitting;
    }
}
