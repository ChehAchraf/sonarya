<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowLeft,
    Brain,
    Calendar,
    CheckCircle2,
    Clock,
    Columns2,
    Crosshair,
    Dna,
    Eye,
    FileCheck,
    FileSpreadsheet,
    FileText,
    FlaskConical,
    HeartPulse,
    AlertTriangle,
    History,
    Image as ImageIcon,
    Info,
    Layers,
    MapPin,
    Pencil,
    Pill,
    Plus,
    Scale,
    ShieldAlert,
    Sparkles,
    Stethoscope,
    TestTube,
    Trash2,
    User,
    UserCheck,
    Users,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import ClinicalExamDialog, { type ClinicalExamFormData } from '@/pages/Patients/Partials/ClinicalExamDialog.vue';
import ComplementaryExamDialog, { type ComplementaryExamFormData } from '@/pages/Patients/Partials/ComplementaryExamDialog.vue';
import ConsultationDialog, { type ConsultationFormData } from '@/pages/Patients/Partials/ConsultationDialog.vue';
import MriExamDialog, { type MriExamFormData } from '@/pages/Patients/Partials/MriExamDialog.vue';
import MriViewerDialog from '@/pages/Patients/Partials/MriViewerDialog.vue';
import PatientDiseaseTimeline from '@/pages/Patients/Partials/PatientDiseaseTimeline.vue';
import PrognosticScoreCard from '@/pages/Patients/Partials/PrognosticScoreCard.vue';
import RelapseChart from '@/pages/Patients/Partials/RelapseChart.vue';
import RelapseDialog, { type RelapseFormData } from '@/pages/Patients/Partials/RelapseDialog.vue';
import TreatmentDialog, { type TreatmentFormData } from '@/pages/Patients/Partials/TreatmentDialog.vue';
import { dashboard } from '@/routes';
import { edit as patientsEdit, index as patientsIndex, show as patientsShow } from '@/routes/patients';
import { edit as patientDiseaseProfileEdit } from '@/routes/patients/disease-profile';

type ClinicalSummary = {
    latest_edss: string | number | null;
    relapses_count: number;
    mri_count: number;
    current_treatment_name: string | null;
    clinical_form_label: string | null;
    clinical_form_short: string | null;
};

type MedicalHistory = {
    id: number;
    medical_history: string | null;
    surgical_history: string | null;
    smoking: string | null;
    alcohol: string | null;
    toxic_other: string | null;
    pregnancies_count: number | null;
    deliveries_count: number | null;
    children_count: number | null;
    postpartum_info?: string | null;
    gyneco_notes: string | null;
    family_history_sep?: boolean;
    family_history_neurological: boolean;
    family_history_autoimmune: boolean;
    family_history_details: string | null;
    has_unspecified_history: boolean;
};

type DiseaseProfile = {
    id: number;
    first_symptom_date: string | null;
    first_symptom_age_estimated: number | null;
    diagnosis_date: string | null;
    diagnosis_age_estimated: number | null;
    prediagnostic_duration_months: number | null;
    initial_clinical_form: string | null;
    initial_clinical_form_label: string | null;
    current_clinical_form: string | null;
    current_clinical_form_label: string | null;
    first_symptoms_description: string | null;
    diagnostic_criteria: string | null;
    initial_edss: string | null;
    notes: string | null;
};

type Relapse = {
    id: number;
    start_date: string | null;
    start_date_raw?: string | null;
    end_date: string | null;
    end_date_raw?: string | null;
    duration_days?: number | null;
    symptoms: string;
    is_before_diagnosis: boolean;
    treatment_administered: string | null;
    administration_route: string | null;
    clinical_recovery: string | null;
    sequelae: string | null;
    hospitalization_required: boolean;
    notes: string | null;
};

type ClinicalExam = {
    id: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    edss_score: string | number;
    t25fw?: number | string | null;
    nine_hpt?: number | string | null;
    syndrome_pyramidal: string | null;
    syndrome_vestibulaire: string | null;
    syndrome_ataxo_spasmodique: string | null;
    syndrome_medullaire: string | null;
    norb: string | null;
    cranial_nerves_impairment: string | null;
    cranial_nerves_details: string | null;
    other_signs: string | null;
    functional_systems_scores?: Record<string, number | null> | null;
    evaluator_name: string | null;
    clinical_comment: string | null;
};

type MriExam = {
    id: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    timing: string | null;
    region: string | null;
    sep_protocol_applied: boolean;
    sequences_available: string | null;
    lesions_count_estimated: string | null;
    lesions_locations: string | null;
    contrast_enhancement: string | null;
    new_lesions_count: number | null;
    radiologist_report: string | null;
    dicom_study_uid?: string | null;
    dicom_storage_path?: string | null;
    key_images?: Array<{ name: string; path: string; size?: number; mime?: string }> | null;
    notes: string | null;
};

type Treatment = {
    id: number;
    molecule_name: string;
    category: string | null;
    therapeutic_class: string | null;
    start_date: string | null;
    start_date_raw?: string | null;
    end_date: string | null;
    end_date_raw?: string | null;
    status: string;
    dosage_regimen: string | null;
    administration_route: string | null;
    prescription_reason: string | null;
    stop_reason: string | null;
    adverse_effects: string | null;
    relapses_after_two_years?: number | null;
    vitamin_d_supplementation: boolean;
    antidepressants: boolean;
    notes: string | null;
};

type Consultation = {
    id: number;
    consultation_date: string | null;
    consultation_date_raw?: string | null;
    consultation_type?: string | null;
    doctor_name: string | null;
    edss_score: string | number | null;
    intercurrent_events: string | null;
    relapses_since_last_visit: number | null;
    current_treatment_evaluation: string | null;
    tolerance_and_adherence: string | null;
    recent_exam_results?: string | null;
    therapeutic_decision: string | null;
    next_appointment_date: string | null;
    next_appointment_date_raw?: string | null;
    notes: string | null;
};

type ComplementaryExam = {
    id: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    exam_type: string;
    pl_result: string | null;
    pl_cytorachie?: string | number | null;
    pl_proteinorachie: string | number | null;
    pl_oligoclonal_bands: string | null;
    biology_anti_nmo: string | null;
    biology_anti_mog: string | null;
    biology_anti_antinucleaire?: string | null;
    biology_anti_bgsa?: string | null;
    biology_serology_hbv?: string | null;
    biology_other?: string | null;
    iepp_sample_type?: string | null;
    iepp_result?: string | null;
    iepp_kappa_index?: number | string | null;
    report_file_path?: string | null;
    notes: string | null;
};

type PatientDetails = {
    id: number;
    dossier_number: string;
    full_name: string;
    first_name: string;
    last_name: string;
    cin: string | null;
    gender: string;
    gender_label: string;
    birth_date: string;
    birth_date_formatted: string;
    age: number | null;
    height?: number | null;
    weight?: number | null;
    imc?: number | null;
    imc_classification?: { label: string; class: string } | null;
    phone: string | null;
    city: string | null;
    origin?: string | null;
    address: string | null;
    profession: string | null;
    marital_status: string | null;
    marital_status_label: string | null;
    children_count?: number | null;
    education_level: string | null;
    health_coverage?: string[] | null;
    tobacco_consumption?: string | null;
    alcohol_consumption?: string | null;
    pregnancies_count?: number | null;
    deliveries_count?: number | null;
    postpartum_info?: string | null;
    family_history_sep?: boolean;
    family_history_neurological?: boolean;
    family_history_autoimmune?: boolean;
    family_history_details?: string | null;
    medical_history_notes?: string | null;
    surgical_history_notes?: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    referring_doctor: string | null;
    status: string;
    status_label: string;
    status_color: string;
    notes: string | null;
    creator: { id: number; name: string; email: string } | null;
    editor: { id: number; name: string; email: string } | null;
    created_at_formatted: string | null;
    updated_at_formatted: string | null;
    medical_history: MedicalHistory | null;
    disease_profile: DiseaseProfile | null;
    relapses: Relapse[];
    clinical_exams: ClinicalExam[];
    mri_exams: MriExam[];
    treatments: Treatment[];
    consultations: Consultation[];
    complementary_exams: ComplementaryExam[];
};

type Props = {
    patient: PatientDetails;
    summary: ClinicalSummary;
};

const props = defineProps<Props>();

const activeTab = ref('general');
const isRelapseDialogOpen = ref(false);
const selectedRelapseToEdit = ref<RelapseFormData | null>(null);

const openNewRelapseDialog = () => {
    selectedRelapseToEdit.value = null;
    isRelapseDialogOpen.value = true;
};

const openEditRelapseDialog = (relapse: Relapse) => {
    selectedRelapseToEdit.value = {
        id: relapse.id,
        start_date: relapse.start_date,
        start_date_raw: relapse.start_date_raw,
        end_date: relapse.end_date,
        end_date_raw: relapse.end_date_raw,
        duration_days: relapse.duration_days,
        symptoms: relapse.symptoms,
        is_before_diagnosis: relapse.is_before_diagnosis,
        treatment_administered: relapse.treatment_administered,
        administration_route: relapse.administration_route,
        clinical_recovery: relapse.clinical_recovery,
        sequelae: relapse.sequelae,
        hospitalization_required: relapse.hospitalization_required,
        notes: relapse.notes,
    };
    isRelapseDialogOpen.value = true;
};

const deleteRelapse = (relapse: Relapse) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la poussée du ${relapse.start_date || 'cette date'} ? Cette action est irréversible.`)) {
        router.delete(`/patients/${props.patient.id}/relapses/${relapse.id}`, {
            preserveScroll: true,
        });
    }
};

const isClinicalExamDialogOpen = ref(false);
const selectedExamToEdit = ref<ClinicalExamFormData | null>(null);

const openNewClinicalExamDialog = () => {
    selectedExamToEdit.value = null;
    isClinicalExamDialogOpen.value = true;
};

const openEditClinicalExamDialog = (exam: ClinicalExam) => {
    selectedExamToEdit.value = {
        id: exam.id,
        exam_date: exam.exam_date,
        exam_date_raw: exam.exam_date_raw,
        edss_score: exam.edss_score,
        t25fw: exam.t25fw,
        nine_hpt: exam.nine_hpt,
        syndrome_pyramidal: exam.syndrome_pyramidal,
        syndrome_vestibulaire: exam.syndrome_vestibulaire,
        syndrome_ataxo_spasmodique: exam.syndrome_ataxo_spasmodique,
        syndrome_medullaire: exam.syndrome_medullaire,
        norb: exam.norb,
        cranial_nerves_impairment: exam.cranial_nerves_impairment,
        cranial_nerves_details: exam.cranial_nerves_details,
        other_signs: exam.other_signs,
        functional_systems_scores: exam.functional_systems_scores,
        evaluator_name: exam.evaluator_name,
        clinical_comment: exam.clinical_comment,
    };
    isClinicalExamDialogOpen.value = true;
};

const deleteClinicalExam = (exam: ClinicalExam) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'évaluation clinique du ${exam.exam_date || 'cette date'} ? Cette action est irréversible.`)) {
        router.delete(`/patients/${props.patient.id}/clinical-exams/${exam.id}`, {
            preserveScroll: true,
        });
    }
};

// MRI Exam & Viewer State
const isMriDialogOpen = ref(false);
const selectedMriToEdit = ref<MriExamFormData | null>(null);
const isMriViewerOpen = ref(false);
const mriViewerInitialExamId = ref<number | null>(null);
const mriRegionFilter = ref<'all' | 'cerebrale' | 'medullaire'>('all');

const openNewMriDialog = () => {
    selectedMriToEdit.value = null;
    isMriDialogOpen.value = true;
};

const openEditMriDialog = (mri: MriExam) => {
    selectedMriToEdit.value = {
        id: mri.id,
        exam_date: mri.exam_date || '',
        exam_date_raw: mri.exam_date_raw ?? null,
        timing: mri.timing || 'baseline',
        region: mri.region || 'cerebrale',
        sep_protocol_applied: mri.sep_protocol_applied,
        sequences_available: mri.sequences_available,
        lesions_count_estimated: mri.lesions_count_estimated,
        lesions_locations: mri.lesions_locations,
        contrast_enhancement: mri.contrast_enhancement,
        new_lesions_count: mri.new_lesions_count,
        radiologist_report: mri.radiologist_report,
        dicom_study_uid: mri.dicom_study_uid ?? null,
        notes: mri.notes,
        key_images: mri.key_images ?? null,
    };
    isMriDialogOpen.value = true;
};

const deleteMri = (mri: MriExam) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'examen IRM du ${mri.exam_date || 'cette date'} ? Cette action est irréversible.`)) {
        router.delete(`/patients/${props.patient.id}/mri-exams/${mri.id}`, {
            preserveScroll: true,
        });
    }
};

const openMriViewer = (examId?: number) => {
    mriViewerInitialExamId.value = examId ?? null;
    isMriViewerOpen.value = true;
};

const filteredMriExams = computed(() => {
    if (mriRegionFilter.value === 'all') {
        return props.patient.mri_exams;
    }
    return props.patient.mri_exams.filter((m) => {
        if (mriRegionFilter.value === 'cerebrale') {
            return m.region === 'cerebrale' || m.region === 'les_deux';
        }
        if (mriRegionFilter.value === 'medullaire') {
            return m.region === 'medullaire' || m.region === 'les_deux';
        }
        return true;
    });
});

const getMriTimingBadge = (timing: string | null) => {
    switch (timing) {
        case 'baseline':
            return { label: 'Baseline (Initiale)', class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/30' };
        case 'rebaseline_6m':
            return { label: 'Rebaseline (6 mois)', class: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-500/30' };
        default:
            return { label: 'Suivi / Contrôle', class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30' };
    }
};

const getMriRegionLabel = (region: string | null) => {
    switch (region) {
        case 'cerebrale':
            return 'IRM Cérébrale';
        case 'medullaire':
            return 'IRM Médullaire';
        case 'les_deux':
            return 'Cérébro-Médullaire';
        default:
            return 'IRM';
    }
};

// Treatment State & Handlers
const isTreatmentDialogOpen = ref(false);
const selectedTreatmentToEdit = ref<TreatmentFormData | null>(null);

const openNewTreatmentDialog = () => {
    selectedTreatmentToEdit.value = null;
    isTreatmentDialogOpen.value = true;
};

const openEditTreatmentDialog = (treatment: Treatment) => {
    selectedTreatmentToEdit.value = {
        id: treatment.id,
        category: treatment.category || 'fond',
        molecule_name: treatment.molecule_name,
        therapeutic_class: treatment.therapeutic_class,
        start_date: treatment.start_date || '',
        start_date_raw: treatment.start_date_raw ?? null,
        end_date: treatment.end_date,
        end_date_raw: treatment.end_date_raw ?? null,
        status: treatment.status,
        dosage_regimen: treatment.dosage_regimen,
        administration_route: treatment.administration_route,
        prescription_reason: treatment.prescription_reason,
        stop_reason: treatment.stop_reason,
        adverse_effects: treatment.adverse_effects,
        relapses_after_two_years: treatment.relapses_after_two_years ?? null,
        vitamin_d_supplementation: treatment.vitamin_d_supplementation,
        antidepressants: treatment.antidepressants,
        notes: treatment.notes,
    };
    isTreatmentDialogOpen.value = true;
};

const deleteTreatment = (treatment: Treatment) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le traitement (${treatment.molecule_name}) ? Cette action est irréversible.`)) {
        router.delete(`/patients/${props.patient.id}/treatments/${treatment.id}`, {
            preserveScroll: true,
        });
    }
};

const activeDiseaseModifyingTreatment = computed(() => {
    return props.patient.treatments.find((t) => t.status === 'en_cours' && t.category === 'fond')
        || props.patient.treatments.find((t) => t.status === 'en_cours')
        || null;
});

const chronologicalTreatments = computed(() => {
    return [...props.patient.treatments].sort((a, b) => {
        const da = a.start_date_raw ? new Date(a.start_date_raw).getTime() : 0;
        const db = b.start_date_raw ? new Date(b.start_date_raw).getTime() : 0;
        return da - db;
    });
});

const getTreatmentStatusBadge = (status: string) => {
    switch (status) {
        case 'en_cours':
            return { label: 'En cours', class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30 font-semibold' };
        case 'remplace':
            return { label: 'Remplacé (Switch)', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' };
        case 'arrete':
            return { label: 'Arrêté', class: 'bg-muted text-muted-foreground border-border' };
        default:
            return { label: status, class: 'bg-muted text-muted-foreground border-border' };
    }
};

const getTreatmentCategoryLabel = (category: string | null) => {
    switch (category) {
        case 'fond':
            return 'Traitement de Fond (DMT)';
        case 'poussee':
            return 'Traitement de Poussée';
        case 'symptomatique':
            return 'Symptomatique';
        default:
            return category || 'Traitement';
    }
};

// Consultation State & Handlers
const isConsultationDialogOpen = ref(false);
const selectedConsultationToEdit = ref<ConsultationFormData | null>(null);

const openNewConsultationDialog = () => {
    selectedConsultationToEdit.value = null;
    isConsultationDialogOpen.value = true;
};

const openEditConsultationDialog = (c: Consultation) => {
    selectedConsultationToEdit.value = {
        id: c.id,
        consultation_date: c.consultation_date || '',
        consultation_date_raw: c.consultation_date_raw ?? null,
        consultation_type: c.consultation_type ?? 'suivi_rdv',
        doctor_name: c.doctor_name,
        intercurrent_events: c.intercurrent_events,
        relapses_since_last_visit: c.relapses_since_last_visit ?? 0,
        edss_score: c.edss_score,
        current_treatment_evaluation: c.current_treatment_evaluation,
        tolerance_and_adherence: c.tolerance_and_adherence,
        recent_exam_results: c.recent_exam_results ?? null,
        therapeutic_decision: c.therapeutic_decision,
        next_appointment_date: c.next_appointment_date,
        next_appointment_date_raw: c.next_appointment_date_raw ?? null,
        notes: c.notes,
    };
    isConsultationDialogOpen.value = true;
};

const deleteConsultation = (c: Consultation) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la consultation du ${c.consultation_date || 'cette date'} ? Cette action est irréversible.`)) {
        router.delete(`/patients/${props.patient.id}/consultations/${c.id}`, {
            preserveScroll: true,
        });
    }
};

// Complementary Exam State & Handlers
const isComplementaryExamDialogOpen = ref(false);
const selectedComplementaryExamToEdit = ref<ComplementaryExamFormData | null>(null);

const openNewComplementaryExamDialog = () => {
    selectedComplementaryExamToEdit.value = null;
    isComplementaryExamDialogOpen.value = true;
};

const openEditComplementaryExamDialog = (exam: ComplementaryExam) => {
    selectedComplementaryExamToEdit.value = {
        id: exam.id,
        exam_date: exam.exam_date || '',
        exam_date_raw: exam.exam_date_raw ?? null,
        exam_type: exam.exam_type,
        pl_result: exam.pl_result,
        pl_cytorachie: exam.pl_cytorachie ?? null,
        pl_proteinorachie: exam.pl_proteinorachie ?? null,
        pl_oligoclonal_bands: exam.pl_oligoclonal_bands,
        biology_anti_nmo: exam.biology_anti_nmo,
        biology_anti_mog: exam.biology_anti_mog,
        biology_anti_antinucleaire: exam.biology_anti_antinucleaire ?? null,
        biology_anti_bgsa: exam.biology_anti_bgsa ?? null,
        biology_serology_hbv: exam.biology_serology_hbv ?? null,
        biology_other: exam.biology_other ?? null,
        iepp_sample_type: exam.iepp_sample_type ?? null,
        iepp_result: exam.iepp_result ?? null,
        iepp_kappa_index: exam.iepp_kappa_index ?? null,
        report_file_path: exam.report_file_path ?? null,
        notes: exam.notes,
    };
    isComplementaryExamDialogOpen.value = true;
};

const deleteComplementaryExam = (exam: ComplementaryExam) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer cet examen complémentaire (${exam.exam_type}) ? Cette action est irréversible.`)) {
        router.delete(`/patients/${props.patient.id}/complementary-exams/${exam.id}`, {
            preserveScroll: true,
        });
    }
};

const latestConsultation = computed(() => {
    if (props.patient.consultations.length === 0) return null;
    return [...props.patient.consultations].sort((a, b) => {
        const da = a.consultation_date_raw ? new Date(a.consultation_date_raw).getTime() : 0;
        const db = b.consultation_date_raw ? new Date(b.consultation_date_raw).getTime() : 0;
        return db - da;
    })[0];
});

const nextScheduledAppointment = computed(() => {
    const dates = props.patient.consultations
        .map((c) => c.next_appointment_date_raw)
        .filter((d): d is string => !!d && new Date(d).getTime() >= new Date().setHours(0, 0, 0, 0))
        .sort();
    return dates.length > 0 ? dates[0] : null;
});

const latestLcrExam = computed(() => {
    return props.patient.complementary_exams.find((e) => e.pl_result || e.pl_oligoclonal_bands) || null;
});

const latestBiologyExam = computed(() => {
    return props.patient.complementary_exams.find((e) => e.biology_anti_nmo || e.biology_anti_mog) || null;
});

// Longitudinal EDSS Evolution
const edssEvolution = computed(() => {
    const exams = [...props.patient.clinical_exams].sort((a, b) => {
        const da = a.exam_date_raw ? new Date(a.exam_date_raw).getTime() : 0;
        const db = b.exam_date_raw ? new Date(b.exam_date_raw).getTime() : 0;
        return da - db;
    });

    if (exams.length === 0) return null;

    const firstScore = Number(exams[0].edss_score);
    const latestScore = Number(exams[exams.length - 1].edss_score);
    const delta = latestScore - firstScore;

    let status = 'Stabilité clinique';
    let statusColor = 'text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/30';

    if (delta >= 1.0) {
        status = 'Progression confirmée (Aggravation)';
        statusColor = 'text-rose-700 dark:text-rose-400 bg-rose-500/10 border-rose-500/30';
    } else if (delta <= -1.0) {
        status = 'Amélioration clinique significative';
        statusColor = 'text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/30';
    } else if (delta > 0) {
        status = 'Légère tendance évolutive (+0.5)';
        statusColor = 'text-amber-700 dark:text-amber-400 bg-amber-500/10 border-amber-500/30';
    }

    return {
        firstScore,
        latestScore,
        delta: delta > 0 ? `+${delta.toFixed(1)}` : delta.toFixed(1),
        status,
        statusColor,
        examsChronological: exams,
    };
});

type EdssChartPoint = {
    id: number;
    x: number;
    y: number;
    score: number;
    date: string;
};

const edssChartData = computed(() => {
    if (!edssEvolution.value || edssEvolution.value.examsChronological.length < 2) {
        return null;
    }
    const exams = edssEvolution.value.examsChronological;
    const len = exams.length;
    const points: EdssChartPoint[] = exams.map((e, idx) => {
        const x = 50 + idx * (510 / (len - 1));
        const y = 100 - (Number(e.edss_score) / 10) * 80;
        return {
            id: e.id,
            x,
            y,
            score: Number(e.edss_score),
            date: e.exam_date || '',
        };
    });

    const polylineString = points.map((p) => `${p.x},${p.y}`).join(' ');

    return {
        points,
        polylineString,
    };
});

// Module 6 & 7 : Alertes Décisionnelles (Transition SP & NEDA-3)
const spTransitionWarning = computed(() => {
    // 1. Check if patient has DMT treatment for >= 6 months (>= 180 days)
    const dmtTreatments = props.patient.treatments.filter(t => t.category === 'fond' || t.status === 'en_cours');
    if (dmtTreatments.length === 0) return null;

    const now = new Date().getTime();
    let maxTreatmentDays = 0;
    let treatedWithMolecule = '';

    dmtTreatments.forEach(t => {
        if (!t.start_date_raw) return;
        const start = new Date(t.start_date_raw).getTime();
        const end = t.end_date_raw ? new Date(t.end_date_raw).getTime() : now;
        const days = Math.floor((end - start) / (1000 * 60 * 60 * 24));
        if (days > maxTreatmentDays) {
            maxTreatmentDays = days;
            treatedWithMolecule = t.molecule_name;
        }
    });

    if (maxTreatmentDays < 180) {
        return null;
    }

    // 2. EDSS progression evaluation
    const exams = [...props.patient.clinical_exams].sort((a, b) => {
        const da = a.exam_date_raw ? new Date(a.exam_date_raw).getTime() : 0;
        const db = b.exam_date_raw ? new Date(b.exam_date_raw).getTime() : 0;
        return da - db;
    });

    const baselineEdss = props.patient.disease_profile?.initial_edss !== null && props.patient.disease_profile?.initial_edss !== undefined
        ? Number(props.patient.disease_profile.initial_edss)
        : (exams.length > 0 ? Number(exams[0].edss_score) : null);

    const latestEdss = exams.length > 0 ? Number(exams[exams.length - 1].edss_score) : null;

    if (baselineEdss === null || latestEdss === null) return null;

    const delta = latestEdss - baselineEdss;
    let isWarning = false;
    let threshold = 1.0;

    if (baselineEdss < 6.0 && delta >= 1.0) {
        isWarning = true;
        threshold = 1.0;
    } else if (baselineEdss >= 6.0 && delta >= 0.5) {
        isWarning = true;
        threshold = 0.5;
    }

    if (!isWarning) return null;

    // 3. PIRA check: Has there been any relapse in the last 12 months?
    const oneYearAgo = now - (365 * 24 * 60 * 60 * 1000);
    const recentRelapses = props.patient.relapses.filter(r => {
        if (!r.start_date_raw) return false;
        return new Date(r.start_date_raw).getTime() >= oneYearAgo;
    });

    const isPira = recentRelapses.length === 0;

    return {
        baselineEdss,
        latestEdss,
        delta: delta > 0 ? `+${delta.toFixed(1)}` : delta.toFixed(1),
        threshold,
        treatedWithMolecule,
        treatmentDays: maxTreatmentDays,
        isPira,
    };
});

// Module 6 & 7 : Évaluation NEDA-3 (No Evidence of Disease Activity)
const neda3Evaluation = computed(() => {
    // 1. Zéro poussée
    const relapsesCount = props.patient.relapses.length;
    const noRelapses = relapsesCount === 0;

    // 2. Progression du handicap (delta EDSS <= 0)
    const exams = [...props.patient.clinical_exams].sort((a, b) => {
        const da = a.exam_date_raw ? new Date(a.exam_date_raw).getTime() : 0;
        const db = b.exam_date_raw ? new Date(b.exam_date_raw).getTime() : 0;
        return da - db;
    });

    let noProgression = true;
    let edssDelta = 0;
    if (exams.length >= 2) {
        const first = Number(exams[0].edss_score);
        const latest = Number(exams[exams.length - 1].edss_score);
        edssDelta = latest - first;
        if (edssDelta > 0) {
            noProgression = false;
        }
    }

    // 3. Activité IRM
    let noMriActivity = true;
    let mriActivityReason = '';

    props.patient.mri_exams.forEach(m => {
        if (m.new_lesions_count && m.new_lesions_count > 0) {
            noMriActivity = false;
            mriActivityReason = `${m.new_lesions_count} nouvelle(s) lésion(s) T2`;
        }
        if (m.contrast_enhancement && !['aucun', 'aucune', 'non', 'absent'].includes(m.contrast_enhancement.toLowerCase())) {
            noMriActivity = false;
            mriActivityReason = mriActivityReason ? `${mriActivityReason} + prise Gd` : 'Prise de contraste Gd';
        }
    });

    const isNeda3 = noRelapses && noProgression && noMriActivity;

    return {
        isNeda3,
        noRelapses,
        relapsesCount,
        noProgression,
        edssDelta: edssDelta > 0 ? `+${edssDelta.toFixed(1)}` : edssDelta.toFixed(1),
        noMriActivity,
        mriActivityReason,
    };
});

// Module 8 : Données pour Fiche Pronostique
const prognosticPatientData = computed(() => {
    const hasBoc = props.patient.complementary_exams.some(e => e.pl_oligoclonal_bands === 'positives');
    const hasKappa = props.patient.complementary_exams.some(e => Number(e.iepp_kappa_index || 0) >= 5.8);
    const hasSequelae = props.patient.relapses.some(r => r.clinical_recovery === 'sequelles');

    return {
        age: props.patient.age,
        gender: props.patient.gender,
        first_symptom_age: props.patient.disease_profile?.first_symptom_age_estimated,
        initial_edss: props.patient.disease_profile?.initial_edss,
        boc_positive: hasBoc,
        kappa_high: hasKappa,
        relapses_count: props.patient.relapses.length,
        first_relapse_sequelae: hasSequelae,
        short_relapse_interval: props.patient.relapses.length >= 2,
        mri_high_lesion_load: props.patient.mri_exams.some(m => (m.lesions_count_estimated && m.lesions_count_estimated.includes('> 9')) || (m.new_lesions_count && m.new_lesions_count >= 5)),
        mri_infratentorial: props.patient.mri_exams.some(m => m.lesions_locations && (m.lesions_locations.toLowerCase().includes('tronc') || m.lesions_locations.toLowerCase().includes('cervelet'))),
        mri_spinal: props.patient.mri_exams.some(m => m.region === 'medullaire' || (m.lesions_locations && m.lesions_locations.toLowerCase().includes('moelle'))),
        mri_gadolinium: props.patient.mri_exams.some(m => m.contrast_enhancement && !['aucun', 'aucune', 'non', 'absent'].includes(m.contrast_enhancement.toLowerCase())),
    };
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: dashboard(),
            },
            {
                title: 'Dossiers Patients',
                href: patientsIndex(),
            },
            {
                title: 'Dossier Clinique',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Dossier ${patient.dossier_number} — ${patient.full_name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Top Navigation Bar -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button
                    as-child
                    variant="ghost"
                    size="icon"
                    class="size-8 text-muted-foreground hover:text-foreground shrink-0"
                >
                    <Link :href="patientsIndex.url()" title="Retour aux dossiers">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            {{ patient.full_name }}
                        </h1>
                        <span class="font-mono text-xs font-semibold px-2.5 py-0.5 rounded-md bg-muted text-muted-foreground border border-border">
                            {{ patient.dossier_number }}
                        </span>
                        <span
                            :class="[
                                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                patient.status_color,
                            ]"
                        >
                            {{ patient.status_label }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        {{ patient.gender === 'female' ? 'Femme' : 'Homme' }}
                        <span v-if="patient.age"> • {{ patient.age }} ans (Né(e) le {{ patient.birth_date_formatted }})</span>
                        <span v-if="patient.cin"> • CIN: <span class="font-mono font-medium text-foreground">{{ patient.cin }}</span></span>
                        <span v-if="patient.city"> • {{ patient.city }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                >
                    <Link :href="patientsIndex.url()">
                        <ArrowLeft class="size-3.5" />
                        <span>Liste des patients</span>
                    </Link>
                </Button>

                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                >
                    <Link :href="patientsEdit.url(patient.id)">
                        <Pencil class="size-3.5" />
                        <span>Modifier Dossier</span>
                    </Link>
                </Button>

                <Button
                    as-child
                    size="sm"
                    class="gap-1.5"
                >
                    <Link :href="patientDiseaseProfileEdit.url(patient.id)">
                        <Brain class="size-3.5" />
                        <span>{{ patient.disease_profile ? 'Modifier Profil' : 'Renseigner Profil' }}</span>
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Clinical Synthesis Cards (Key Metrics) -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <!-- Forme SEP -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-medium">Forme SEP</span>
                    <Brain class="size-4 text-primary/70" />
                </div>
                <div class="mt-2 text-lg font-bold tracking-tight text-foreground">
                    {{ summary.clinical_form_short || 'N/P' }}
                </div>
                <p class="mt-0.5 text-[11px] text-muted-foreground truncate" :title="summary.clinical_form_label || 'Non précisée'">
                    {{ summary.clinical_form_label || 'Forme clinique non définie' }}
                </p>
            </div>

            <!-- Dernier EDSS -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-medium">Score EDSS</span>
                    <Activity class="size-4 text-primary/70" />
                </div>
                <div class="mt-2 text-lg font-bold tracking-tight text-foreground font-mono">
                    {{ summary.latest_edss !== null ? summary.latest_edss : '—' }}
                </div>
                <p class="mt-0.5 text-[11px] text-muted-foreground">
                    {{ summary.latest_edss !== null ? 'Dernière évaluation' : 'Aucune évaluation' }}
                </p>
            </div>

            <!-- Poussées -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-medium">Poussées</span>
                    <HeartPulse class="size-4 text-primary/70" />
                </div>
                <div class="mt-2 text-lg font-bold tracking-tight text-foreground font-mono">
                    {{ summary.relapses_count }}
                </div>
                <p class="mt-0.5 text-[11px] text-muted-foreground">
                    {{ summary.relapses_count > 1 ? 'Poussées répertoriées' : 'Poussée répertoriée' }}
                </p>
            </div>

            <!-- Traitement de fond -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-medium">Traitement de fond</span>
                    <Pill class="size-4 text-primary/70" />
                </div>
                <div class="mt-2 text-base font-bold tracking-tight text-foreground truncate" :title="summary.current_treatment_name || 'Aucun'">
                    {{ summary.current_treatment_name || 'Aucun (Naïf)' }}
                </div>
                <p class="mt-0.5 text-[11px] text-muted-foreground">
                    {{ summary.current_treatment_name ? 'DMT en cours' : 'Sans traitement de fond' }}
                </p>
            </div>

            <!-- Examens IRM -->
            <div class="rounded-xl border border-border bg-card p-4 shadow-2xs col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-medium">Imagerie IRM</span>
                    <Crosshair class="size-4 text-primary/70" />
                </div>
                <div class="mt-2 text-lg font-bold tracking-tight text-foreground font-mono">
                    {{ summary.mri_count }}
                </div>
                <p class="mt-0.5 text-[11px] text-muted-foreground">
                    {{ summary.mri_count > 1 ? 'IRM enregistrées' : 'IRM enregistrée' }}
                </p>
            </div>
        </div>

        <!-- MODULE 6 & 7 : ALERTES DÉCISIONNELLES & NEDA-3 -->
        <!-- 1. SP Transition Warning Banner (Alerte Suspicion SEP Secondairement Progressive) -->
        <div
            v-if="spTransitionWarning"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4 text-destructive flex items-start gap-3 shadow-xs"
        >
            <AlertTriangle class="size-5 shrink-0 mt-0.5 text-destructive" />
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-sm">
                        Alerte Clinique : Suspicion de transition vers une SEP Secondairement Progressive (SP)
                    </span>
                    <Badge variant="destructive" class="text-[10px]">Alerte Décisionnelle</Badge>
                    <Badge v-if="spTransitionWarning.isPira" class="text-[10px] bg-rose-600/20 text-rose-800 dark:text-rose-200 border-rose-500/40">
                        Progression Indépendante des Poussées (PIRA)
                    </Badge>
                </div>
                <p class="text-xs text-destructive/90">
                    Augmentation confirmée du score EDSS de <strong class="font-mono">{{ spTransitionWarning.delta }}</strong> point(s) (EDSS initial: {{ spTransitionWarning.baselineEdss }} → actuel: {{ spTransitionWarning.latestEdss }}, seuil de progression ≥ {{ spTransitionWarning.threshold }}) après plus de 6 mois sous traitement de fond ({{ spTransitionWarning.treatedWithMolecule }}).
                </p>
            </div>
        </div>

        <!-- 2. NEDA-3 Status Card & Metrics Breakdown -->
        <div class="rounded-xl border border-border bg-card p-3.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div :class="['flex size-8 items-center justify-center rounded-lg border', neda3Evaluation.isNeda3 ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30' : 'bg-amber-500/10 text-amber-600 border-amber-500/30']">
                    <CheckCircle2 v-if="neda3Evaluation.isNeda3" class="size-4 text-emerald-600 dark:text-emerald-400" />
                    <AlertCircle v-else class="size-4 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Statut NEDA-3 (No Evidence of Disease Activity)</span>
                        <Badge :class="['text-[11px] font-semibold border', neda3Evaluation.isNeda3 ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30']">
                            {{ neda3Evaluation.isNeda3 ? 'NEDA-3 Atteint' : 'Perte du NEDA-3' }}
                        </Badge>
                    </div>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        {{ neda3Evaluation.isNeda3 ? 'Absence totale de rechute clinique, de progression du handicap et de nouvelle activité IRM.' : 'Activité de la maladie objectivée lors du suivi.' }}
                    </p>
                </div>
            </div>

            <!-- 3 Pillars Breakdown -->
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <!-- Pillar 1: Relapses -->
                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-md border font-medium text-[11px]', neda3Evaluation.noRelapses ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20']">
                    <CheckCircle2 v-if="neda3Evaluation.noRelapses" class="size-3" />
                    <AlertCircle v-else class="size-3" />
                    <span>{{ neda3Evaluation.noRelapses ? '0 Poussée' : `${neda3Evaluation.relapsesCount} Poussée(s)` }}</span>
                </span>

                <!-- Pillar 2: Disability Progression -->
                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-md border font-medium text-[11px]', neda3Evaluation.noProgression ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20']">
                    <CheckCircle2 v-if="neda3Evaluation.noProgression" class="size-3" />
                    <AlertCircle v-else class="size-3" />
                    <span>{{ neda3Evaluation.noProgression ? 'Stabilité EDSS' : `Progression EDSS (${neda3Evaluation.edssDelta})` }}</span>
                </span>

                <!-- Pillar 3: MRI Activity -->
                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-md border font-medium text-[11px]', neda3Evaluation.noMriActivity ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20']">
                    <CheckCircle2 v-if="neda3Evaluation.noMriActivity" class="size-3" />
                    <AlertCircle v-else class="size-3" />
                    <span>{{ neda3Evaluation.noMriActivity ? 'IRM Stable' : 'Activité IRM' }}</span>
                </span>
            </div>
        </div>

        <!-- Clinical Tabs Navigation -->
        <Tabs v-model="activeTab" class="w-full space-y-4">
            <div class="border-b border-border pb-px overflow-x-auto">
                <TabsList class="h-10 bg-transparent p-0 gap-1 sm:gap-2">
                    <TabsTrigger
                        value="general"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <User class="size-3.5 mr-1.5" />
                        <span>État Civil & Fiche</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="timeline"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none font-semibold text-primary"
                    >
                        <Activity class="size-3.5 mr-1.5" />
                        <span>Chronologie (Module 5)</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="disease"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <History class="size-3.5 mr-1.5" />
                        <span>Histoire & Antécédents</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="relapses"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <HeartPulse class="size-3.5 mr-1.5" />
                        <span>Poussées ({{ patient.relapses.length }})</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="clinical"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <Stethoscope class="size-3.5 mr-1.5" />
                        <span>Clinique & EDSS ({{ patient.clinical_exams.length }})</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="mri"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <Crosshair class="size-3.5 mr-1.5" />
                        <span>IRM ({{ patient.mri_exams.length }})</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="treatments"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <Pill class="size-3.5 mr-1.5" />
                        <span>Traitements ({{ patient.treatments.length }})</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="consultations"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <Calendar class="size-3.5 mr-1.5" />
                        <span>Suivi & Examens ({{ patient.consultations.length }})</span>
                    </TabsTrigger>

                    <TabsTrigger
                        value="prognostic"
                        class="rounded-none border-b-2 border-transparent px-3 py-2 text-xs sm:text-sm font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground data-[state=active]:shadow-none"
                    >
                        <ShieldAlert class="size-3.5 mr-1.5" />
                        <span>Pronostic (Module 8)</span>
                    </TabsTrigger>
                </TabsList>
            </div>

            <!-- TAB 1: ÉTAT CIVIL & COORDONNÉES -->
            <TabsContent value="general" class="space-y-6 pt-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Identification & État Civil -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <User class="size-4 text-primary" />
                                <span>Données Démographiques & État Civil</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">N° de Dossier</span>
                                <span class="col-span-2 font-mono font-semibold text-foreground">{{ patient.dossier_number }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Nom & Prénom</span>
                                <span class="col-span-2 font-medium text-foreground">{{ patient.full_name }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">CIN</span>
                                <span class="col-span-2 font-mono text-foreground">{{ patient.cin || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Sexe</span>
                                <span class="col-span-2 text-foreground">{{ patient.gender_label }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Date de Naissance</span>
                                <span class="col-span-2 text-foreground">
                                    {{ patient.birth_date_formatted }}
                                    <span v-if="patient.age" class="text-muted-foreground ml-1">({{ patient.age }} ans)</span>
                                </span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Situation Familiale</span>
                                <span class="col-span-2 text-foreground">
                                    {{ patient.marital_status_label || '—' }}
                                    <span v-if="patient.children_count !== null && patient.children_count !== undefined" class="text-muted-foreground ml-1">
                                        ({{ patient.children_count }} {{ patient.children_count > 1 ? 'enfants' : 'enfant' }})
                                    </span>
                                </span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5">
                                <span class="text-muted-foreground font-medium">Origine Géographique</span>
                                <span class="col-span-2 text-foreground">{{ patient.origin || '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Coordonnées & Milieu Socio-Professionnel -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <MapPin class="size-4 text-primary" />
                                <span>Coordonnées & Socio-Professionnel</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Téléphone</span>
                                <span class="col-span-2 font-mono text-foreground">{{ patient.phone || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Ville de Résidence</span>
                                <span class="col-span-2 text-foreground">{{ patient.city || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Adresse</span>
                                <span class="col-span-2 text-foreground">{{ patient.address || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Profession</span>
                                <span class="col-span-2 text-foreground">{{ patient.profession || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Couverture Médicale</span>
                                <div class="col-span-2 flex flex-wrap gap-1">
                                    <template v-if="patient.health_coverage && patient.health_coverage.length > 0">
                                        <Badge
                                            v-for="cov in patient.health_coverage"
                                            :key="cov"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ cov }}
                                        </Badge>
                                    </template>
                                    <span v-else class="text-muted-foreground">Non précisée</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 py-1.5">
                                <span class="text-muted-foreground font-medium">Niveau d'Instruction</span>
                                <span class="col-span-2 text-foreground">{{ patient.education_level || '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Paramètres Anthropométriques (Module 1) -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <Scale class="size-4 text-primary" />
                                <span>Paramètres Anthropométriques & IMC</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Taille</span>
                                <span class="col-span-2 text-foreground font-mono font-medium">
                                    {{ patient.height ? `${patient.height} cm` : '—' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Poids</span>
                                <span class="col-span-2 text-foreground font-mono font-medium">
                                    {{ patient.weight ? `${patient.weight} kg` : '—' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5">
                                <span class="text-muted-foreground font-medium">IMC</span>
                                <div class="col-span-2 flex items-center gap-2 flex-wrap">
                                    <span v-if="patient.imc" class="font-mono font-bold text-foreground">
                                        {{ patient.imc }} kg/m²
                                    </span>
                                    <Badge
                                        v-if="patient.imc_classification"
                                        :class="['text-[11px] font-semibold border', patient.imc_classification.class]"
                                    >
                                        {{ patient.imc_classification.label }}
                                    </Badge>
                                    <span v-if="!patient.imc" class="text-muted-foreground">Non calculé</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Antécédents Toxiques & Gynécologiques (Module 2) -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <FlaskConical class="size-4 text-primary" />
                                <span>Antécédents Toxiques & Gynéco-Obstétricaux</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Tabac</span>
                                <span class="col-span-2 text-foreground">
                                    {{ patient.tobacco_consumption || patient.medical_history?.smoking || 'Non renseigné' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5" :class="patient.gender === 'female' ? 'border-b border-border/60' : ''">
                                <span class="text-muted-foreground font-medium">Alcool</span>
                                <span class="col-span-2 text-foreground">
                                    {{ patient.alcohol_consumption || patient.medical_history?.alcohol || 'Non renseigné' }}
                                </span>
                            </div>
                            <template v-if="patient.gender === 'female'">
                                <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                    <span class="text-muted-foreground font-medium">Grossesses / Accouchements</span>
                                    <span class="col-span-2 text-foreground font-medium">
                                        {{ patient.pregnancies_count !== null && patient.pregnancies_count !== undefined ? patient.pregnancies_count : (patient.medical_history?.pregnancies_count ?? '—') }} grossesses
                                        •
                                        {{ patient.deliveries_count !== null && patient.deliveries_count !== undefined ? patient.deliveries_count : (patient.medical_history?.deliveries_count ?? '—') }} accouchements
                                    </span>
                                </div>
                                <div v-if="patient.postpartum_info || patient.medical_history?.postpartum_info" class="grid grid-cols-3 py-1.5">
                                    <span class="text-muted-foreground font-medium">Post-Partum</span>
                                    <span class="col-span-2 text-foreground text-xs">
                                        {{ patient.postpartum_info || patient.medical_history?.postpartum_info }}
                                    </span>
                                </div>
                            </template>
                        </CardContent>
                    </Card>

                    <!-- Référencement & Contact d'Urgence -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <HeartPulse class="size-4 text-primary" />
                                <span>Réseau de Soins & Contact d'Urgence</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Médecin Référent</span>
                                <span class="col-span-2 text-foreground font-medium">{{ patient.referring_doctor || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5 border-b border-border/60">
                                <span class="text-muted-foreground font-medium">Contact d'Urgence</span>
                                <span class="col-span-2 text-foreground">{{ patient.emergency_contact_name || '—' }}</span>
                            </div>
                            <div class="grid grid-cols-3 py-1.5">
                                <span class="text-muted-foreground font-medium">Téléphone d'Urgence</span>
                                <span class="col-span-2 font-mono text-foreground">{{ patient.emergency_contact_phone || '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Traçabilité & Remarques Médicales -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <FileText class="size-4 text-primary" />
                                <span>Remarques Médicales & Audit</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div>
                                <span class="text-xs font-medium text-muted-foreground block mb-1">Notes Cliniques Initiales :</span>
                                <p class="text-foreground bg-muted/40 p-2.5 rounded-lg border border-border/50 text-xs">
                                    {{ patient.notes || 'Aucune observation enregistrée lors de la création du dossier.' }}
                                </p>
                            </div>
                            <Separator />
                            <div class="text-[11px] text-muted-foreground space-y-1">
                                <div v-if="patient.creator">
                                    Dossier ouvert par : <span class="font-medium text-foreground">{{ patient.creator.name }}</span> ({{ patient.created_at_formatted }})
                                </div>
                                <div v-if="patient.editor">
                                    Dernière mise à jour par : <span class="font-medium text-foreground">{{ patient.editor.name }}</span> ({{ patient.updated_at_formatted }})
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <!-- TAB 2: HISTOIRE DE LA MALADIE & ANTÉCÉDENTS -->
            <TabsContent value="disease" class="space-y-6 pt-2">
                <!-- Profil SEP -->
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <Brain class="size-4 text-primary" />
                                <span>Profil Clinique & Diagnostic de la SEP</span>
                            </CardTitle>
                            <div class="flex items-center gap-2">
                                <Badge v-if="patient.disease_profile?.current_clinical_form_label" variant="outline">
                                    {{ patient.disease_profile.current_clinical_form_label }}
                                </Badge>
                                <Button
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="gap-1.5"
                                >
                                    <Link :href="patientDiseaseProfileEdit.url(patient.id)">
                                        <Pencil class="size-3.5" />
                                        <span>{{ patient.disease_profile ? 'Modifier Profil & Antécédents' : 'Renseigner Profil' }}</span>
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="patient.disease_profile" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-3 rounded-lg border border-border bg-muted/20">
                                <span class="text-xs text-muted-foreground block">1er Symptôme</span>
                                <span class="text-sm font-semibold text-foreground">
                                    {{ patient.disease_profile.first_symptom_date || 'Non renseignée' }}
                                </span>
                                <span v-if="patient.disease_profile.first_symptom_age_estimated" class="text-xs text-muted-foreground block">
                                    À l'âge de {{ patient.disease_profile.first_symptom_age_estimated }} ans
                                </span>
                            </div>

                            <div class="p-3 rounded-lg border border-border bg-muted/20">
                                <span class="text-xs text-muted-foreground block">Date du Diagnostic</span>
                                <span class="text-sm font-semibold text-foreground">
                                    {{ patient.disease_profile.diagnosis_date || 'Non renseignée' }}
                                </span>
                                <span v-if="patient.disease_profile.diagnosis_age_estimated" class="text-xs text-muted-foreground block">
                                    À l'âge de {{ patient.disease_profile.diagnosis_age_estimated }} ans
                                </span>
                            </div>

                            <div class="p-3 rounded-lg border border-border bg-muted/20">
                                <span class="text-xs text-muted-foreground block">Délai Diagnostique</span>
                                <span class="text-sm font-semibold text-foreground font-mono">
                                    {{ patient.disease_profile.prediagnostic_duration_months !== null ? `${patient.disease_profile.prediagnostic_duration_months} mois` : '—' }}
                                </span>
                                <span class="text-[11px] text-muted-foreground block">Entre 1er signe et confirmation</span>
                            </div>

                            <div class="p-3 rounded-lg border border-border bg-muted/20">
                                <span class="text-xs text-muted-foreground block">EDSS Initial</span>
                                <span class="text-sm font-semibold text-foreground font-mono">
                                    {{ patient.disease_profile.initial_edss !== null ? patient.disease_profile.initial_edss : '—' }}
                                </span>
                                <span class="text-[11px] text-muted-foreground block">Au moment du diagnostic</span>
                            </div>
                        </div>

                        <!-- Symptômes initiaux & Critères -->
                        <div v-if="patient.disease_profile" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Symptomatologie inaugurale</span>
                                <p class="text-sm text-foreground bg-muted/30 p-3 rounded-lg border border-border/60">
                                    {{ patient.disease_profile.first_symptoms_description || 'Aucune description enregistrée.' }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Critères Diagnostiques Appliqués</span>
                                <p class="text-sm text-foreground bg-muted/30 p-3 rounded-lg border border-border/60">
                                    {{ patient.disease_profile.diagnostic_criteria || 'Critères de McDonald standard.' }}
                                </p>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-muted-foreground text-sm space-y-3">
                            <Brain class="size-10 mx-auto text-muted-foreground/40" />
                            <p>Aucun profil de maladie SEP n'est encore enregistré pour ce dossier.</p>
                            <Button as-child size="sm" class="gap-1.5">
                                <Link :href="patientDiseaseProfileEdit.url(patient.id)">
                                    <Plus class="size-3.5" />
                                    <span>Renseigner le Profil & Antécédents</span>
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Antécédents Personnels & Familiaux -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Antécédents Médicaux & Chirurgicaux -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <History class="size-4 text-primary" />
                                <span>Antécédents Personnels & Habitudes</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-3">
                            <div>
                                <span class="text-xs text-muted-foreground font-medium block">Antécédents Médicaux :</span>
                                <p class="text-foreground text-xs mt-0.5">{{ patient.medical_history_notes || patient.medical_history?.medical_history || 'Aucun antécédent médical notable rapporté.' }}</p>
                            </div>
                            <Separator />
                            <div>
                                <span class="text-xs text-muted-foreground font-medium block">Antécédents Chirurgicaux :</span>
                                <p class="text-foreground text-xs mt-0.5">{{ patient.surgical_history_notes || patient.medical_history?.surgical_history || 'Néant.' }}</p>
                            </div>
                            <Separator />
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-muted-foreground block">Tabagisme</span>
                                    <span class="font-medium text-foreground">{{ patient.tobacco_consumption || patient.medical_history?.smoking || 'Non' }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block">Alcool</span>
                                    <span class="font-medium text-foreground">{{ patient.alcohol_consumption || patient.medical_history?.alcohol || 'Non' }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block">Autres toxiques</span>
                                    <span class="font-medium text-foreground">{{ patient.medical_history?.toxic_other || 'Non' }}</span>
                                </div>
                            </div>

                            <!-- Profil gynéco si femme -->
                            <div v-if="patient.gender === 'female'" class="pt-2 border-t border-border/60">
                                <span class="text-xs font-semibold text-muted-foreground block mb-1">Gynéco-Obstétrique :</span>
                                <div class="grid grid-cols-3 gap-2 text-xs bg-muted/20 p-2 rounded-md">
                                    <div>Grossesses : <span class="font-semibold">{{ patient.pregnancies_count !== null && patient.pregnancies_count !== undefined ? patient.pregnancies_count : (patient.medical_history?.pregnancies_count ?? '0') }}</span></div>
                                    <div>Accouchements : <span class="font-semibold">{{ patient.deliveries_count !== null && patient.deliveries_count !== undefined ? patient.deliveries_count : (patient.medical_history?.deliveries_count ?? '0') }}</span></div>
                                    <div>Enfants : <span class="font-semibold">{{ patient.children_count !== null && patient.children_count !== undefined ? patient.children_count : (patient.medical_history?.children_count ?? '0') }}</span></div>
                                </div>
                                <div v-if="patient.postpartum_info || patient.medical_history?.postpartum_info" class="mt-2 text-xs text-muted-foreground">
                                    <span class="font-medium text-foreground">Post-partum :</span> {{ patient.postpartum_info || patient.medical_history?.postpartum_info }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Antécédents Familiaux -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <Users class="size-4 text-primary" />
                                <span>Antécédents Familiaux & Génétiques</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm space-y-4">
                            <div class="flex items-center justify-between p-3 rounded-lg border border-border bg-card">
                                <div>
                                    <span class="font-medium text-foreground block">SEP au 1er degré</span>
                                    <span class="text-xs text-muted-foreground">Parents, fratrie ou enfants atteints de SEP</span>
                                </div>
                                <Badge :variant="(patient.family_history_sep || patient.medical_history?.family_history_sep) ? 'destructive' : 'outline'">
                                    {{ (patient.family_history_sep || patient.medical_history?.family_history_sep) ? 'Oui (Positif)' : 'Non' }}
                                </Badge>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-lg border border-border bg-card">
                                <div>
                                    <span class="font-medium text-foreground block">Autre Maladie Neurologique</span>
                                    <span class="text-xs text-muted-foreground">Épilepsie, neuropathies, Parkinson...</span>
                                </div>
                                <Badge :variant="(patient.family_history_neurological || patient.medical_history?.family_history_neurological) ? 'destructive' : 'outline'">
                                    {{ (patient.family_history_neurological || patient.medical_history?.family_history_neurological) ? 'Oui (Positif)' : 'Non' }}
                                </Badge>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-lg border border-border bg-card">
                                <div>
                                    <span class="font-medium text-foreground block">Maladies Auto-immunes Familiales</span>
                                    <span class="text-xs text-muted-foreground">Lupus, Gougerot, Thyroïdite de Hashimoto, Diabète T1...</span>
                                </div>
                                <Badge :variant="(patient.family_history_autoimmune || patient.medical_history?.family_history_autoimmune) ? 'destructive' : 'outline'">
                                    {{ (patient.family_history_autoimmune || patient.medical_history?.family_history_autoimmune) ? 'Oui (Positif)' : 'Non' }}
                                </Badge>
                            </div>

                            <div v-if="patient.family_history_details || patient.medical_history?.family_history_details" class="p-3 rounded-lg bg-muted/20 border border-border/60 text-xs">
                                <span class="font-medium text-muted-foreground block mb-0.5">Détails familiaux :</span>
                                <p class="text-foreground">{{ patient.family_history_details || patient.medical_history?.family_history_details }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <!-- TAB 3: POUSSÉES (RELAPSES) -->
            <TabsContent value="relapses" class="space-y-4 pt-2">
                <!-- Top Action Header -->
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-foreground flex items-center gap-2">
                            <HeartPulse class="size-4 text-primary" />
                            <span>Historique Chronologique des Poussées</span>
                            <Badge variant="outline" class="font-mono text-xs">
                                {{ patient.relapses.length }} {{ patient.relapses.length > 1 ? 'poussées' : 'poussée' }}
                            </Badge>
                        </h3>
                        <p class="text-xs text-muted-foreground">Registre des poussées aiguës, protocoles de corticothérapie et mode de récupération</p>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        @click="openNewRelapseDialog"
                        class="gap-1.5"
                    >
                        <Plus class="size-3.5" />
                        <span>Nouvelle Poussée</span>
                    </Button>
                </div>

                <!-- Relapse Synthesis Metrics -->
                <div v-if="patient.relapses.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="p-3 rounded-lg border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Total Poussées</span>
                        <span class="text-lg font-bold text-foreground font-mono">{{ patient.relapses.length }}</span>
                    </div>

                    <div class="p-3 rounded-lg border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Poussées Inaugurales</span>
                        <span class="text-lg font-bold text-foreground font-mono">
                            {{ patient.relapses.filter(r => r.is_before_diagnosis).length }}
                        </span>
                    </div>

                    <div class="p-3 rounded-lg border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Récupérations Complètes</span>
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ patient.relapses.filter(r => r.clinical_recovery === 'Complète').length }}
                        </span>
                    </div>

                    <div class="p-3 rounded-lg border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Hospitalisations</span>
                        <span class="text-lg font-bold text-amber-600 dark:text-amber-400 font-mono">
                            {{ patient.relapses.filter(r => r.hospitalization_required).length }}
                        </span>
                    </div>
                </div>

                <!-- Healthcare Relapse Dynamic Chart -->
                <RelapseChart
                    v-if="patient.relapses.length > 0"
                    :relapses="patient.relapses"
                    :diagnosis-date="patient.disease_profile?.diagnosis_date"
                />

                <!-- Relapses Table -->
                <div v-if="patient.relapses.length > 0" class="rounded-xl border border-border bg-card overflow-hidden shadow-2xs">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="w-[140px] font-semibold">Date & Durée</TableHead>
                                <TableHead class="font-semibold min-w-[200px]">Symptômes Cliniques</TableHead>
                                <TableHead class="font-semibold">Moment</TableHead>
                                <TableHead class="font-semibold">Traitement & Voie</TableHead>
                                <TableHead class="font-semibold">Récupération</TableHead>
                                <TableHead class="font-semibold">Hospitalisation</TableHead>
                                <TableHead class="w-[90px] text-right font-semibold">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="relapse in patient.relapses" :key="relapse.id">
                                <TableCell>
                                    <div class="font-mono text-xs font-semibold text-foreground">
                                        {{ relapse.start_date || '—' }}
                                    </div>
                                    <div v-if="relapse.duration_days !== null && relapse.duration_days !== undefined" class="text-[11px] text-muted-foreground font-mono">
                                        {{ relapse.duration_days }} jours
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-foreground">
                                    <div class="font-medium">{{ relapse.symptoms }}</div>
                                    <div v-if="relapse.sequelae" class="text-xs text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1">
                                        <AlertCircle class="size-3 shrink-0" />
                                        <span>Séquelles : {{ relapse.sequelae }}</span>
                                    </div>
                                    <div v-if="relapse.notes" class="text-[11px] text-muted-foreground mt-0.5">
                                        {{ relapse.notes }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="relapse.is_before_diagnosis ? 'outline' : 'secondary'" class="text-[11px]">
                                        {{ relapse.is_before_diagnosis ? 'Inaugurale' : 'Sous suivi' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-xs">
                                    <div v-if="relapse.treatment_administered" class="font-medium text-foreground">
                                        {{ relapse.treatment_administered }}
                                    </div>
                                    <div v-if="relapse.administration_route && relapse.administration_route !== 'Néant'" class="text-[11px] text-muted-foreground">
                                        Voie : {{ relapse.administration_route }}
                                    </div>
                                    <span v-if="!relapse.treatment_administered" class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium',
                                            relapse.clinical_recovery === 'Complète'
                                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                                                : relapse.clinical_recovery === 'Incomplète'
                                                ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30'
                                                : relapse.clinical_recovery === 'Nulle'
                                                ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30'
                                                : 'bg-muted text-muted-foreground border-border'
                                        ]"
                                    >
                                        {{ relapse.clinical_recovery || 'Inconnue' }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span :class="relapse.hospitalization_required ? 'text-amber-600 dark:text-amber-400 font-semibold text-xs' : 'text-muted-foreground text-xs'">
                                        {{ relapse.hospitalization_required ? 'Hospitalisation' : 'Ambulatoire' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-foreground"
                                            title="Modifier cette poussée"
                                            @click="openEditRelapseDialog(relapse)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive"
                                            title="Supprimer cette poussée"
                                            @click="deleteRelapse(relapse)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-xl border border-dashed border-border bg-card/50 p-12 text-center space-y-3">
                    <HeartPulse class="size-10 mx-auto text-muted-foreground/40" />
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">Aucune poussée enregistrée</p>
                        <p class="text-xs text-muted-foreground max-w-sm mx-auto">
                            Ce dossier ne comporte actuellement aucun épisode de poussée aiguë répertorié.
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        @click="openNewRelapseDialog"
                        class="gap-1.5"
                    >
                        <Plus class="size-3.5" />
                        <span>Enregistrer la première poussée</span>
                    </Button>
                </div>
            </TabsContent>

            <!-- TAB 4: ÉVALUATIONS CLINIQUES & EDSS -->
            <TabsContent value="clinical" class="space-y-5 pt-2">
                <!-- Top Action Header -->
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-foreground flex items-center gap-2">
                            <Stethoscope class="size-4 text-primary" />
                            <span>Évaluations Cliniques & Scores EDSS</span>
                            <Badge variant="outline" class="font-mono text-xs">
                                {{ patient.clinical_exams.length }} {{ patient.clinical_exams.length > 1 ? 'évaluations' : 'évaluation' }}
                            </Badge>
                        </h3>
                        <p class="text-xs text-muted-foreground">Suivi des atteintes systémiques fonctionnelles et du score d'invalidité de Kurtzke (EDSS)</p>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        @click="openNewClinicalExamDialog"
                        class="gap-1.5"
                    >
                        <Plus class="size-3.5" />
                        <span>Nouvelle Évaluation</span>
                    </Button>
                </div>

                <!-- Longitudinal EDSS Analysis & KPIs -->
                <div v-if="edssEvolution" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <!-- Dernier EDSS -->
                    <div class="p-3.5 rounded-xl border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Dernier Score EDSS</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-2xl font-bold font-mono text-primary">
                                {{ edssEvolution.latestScore.toFixed(1) }}
                            </span>
                            <span class="text-xs text-muted-foreground font-mono">/ 10</span>
                        </div>
                        <p class="text-[11px] text-muted-foreground truncate mt-0.5">
                            {{ patient.clinical_exams[0]?.exam_date ? `Évalué le ${patient.clinical_exams[0].exam_date}` : '' }}
                        </p>
                    </div>

                    <!-- Évolution / Delta -->
                    <div class="p-3.5 rounded-xl border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Évolution Globale (Δ)</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span :class="['text-xl font-bold font-mono', edssEvolution.delta.startsWith('+') && Number(edssEvolution.delta) >= 1.0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400']">
                                {{ edssEvolution.delta }}
                            </span>
                            <span class="text-xs text-muted-foreground">pts</span>
                        </div>
                        <span :class="['inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium mt-1 truncate max-w-full', edssEvolution.statusColor]">
                            {{ edssEvolution.status }}
                        </span>
                    </div>

                    <!-- Score Initial de Référence -->
                    <div class="p-3.5 rounded-xl border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">EDSS Initial de Référence</span>
                        <div class="mt-1 text-2xl font-bold font-mono text-foreground">
                            {{ patient.disease_profile?.initial_edss ? Number(patient.disease_profile.initial_edss).toFixed(1) : edssEvolution.firstScore.toFixed(1) }}
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-0.5">
                            Au diagnostic initial
                        </p>
                    </div>

                    <!-- Nombre d'évaluations -->
                    <div class="p-3.5 rounded-xl border border-border bg-card">
                        <span class="text-xs text-muted-foreground block">Total Évaluations</span>
                        <div class="mt-1 text-2xl font-bold font-mono text-foreground">
                            {{ patient.clinical_exams.length }}
                        </div>
                        <p class="text-[11px] text-muted-foreground mt-0.5">
                            Examens neurologiques
                        </p>
                    </div>
                </div>

                <!-- Longitudinal EDSS Trajectory Curve (Native SVG) -->
                <div v-if="edssChartData" class="p-4 rounded-xl border border-border bg-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground">
                            <Activity class="size-3.5 text-primary" />
                            <span>Courbe d'Évolution Longitudinale du Score EDSS</span>
                        </div>
                        <span class="text-[11px] text-muted-foreground">Chronologie des consultations</span>
                    </div>

                    <div class="w-full overflow-x-auto py-2">
                        <svg viewBox="0 0 600 130" class="w-full min-w-[500px] h-32 overflow-visible">
                            <!-- Background reference lines -->
                            <line x1="40" y1="20" x2="560" y2="20" stroke="currentColor" stroke-dasharray="3 3" class="text-border/40" />
                            <text x="35" y="24" text-anchor="end" class="text-[10px] fill-muted-foreground font-mono">10</text>

                            <line x1="40" y1="52" x2="560" y2="52" stroke="currentColor" stroke-dasharray="3 3" class="text-amber-500/30" />
                            <text x="35" y="56" text-anchor="end" class="text-[10px] fill-amber-500 font-mono">6.0</text>

                            <line x1="40" y1="76" x2="560" y2="76" stroke="currentColor" stroke-dasharray="3 3" class="text-border/40" />
                            <text x="35" y="80" text-anchor="end" class="text-[10px] fill-muted-foreground font-mono">3.0</text>

                            <line x1="40" y1="100" x2="560" y2="100" stroke="currentColor" class="text-border" />
                            <text x="35" y="104" text-anchor="end" class="text-[10px] fill-muted-foreground font-mono">0</text>

                            <!-- Trajectory Polyline -->
                            <polyline
                                fill="none"
                                stroke="var(--primary)"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :points="edssChartData.polylineString"
                            />

                            <!-- Points & Value Labels -->
                            <g v-for="pt in edssChartData.points" :key="pt.id">
                                <!-- Point Circle -->
                                <circle
                                    :cx="pt.x"
                                    :cy="pt.y"
                                    r="4.5"
                                    class="fill-background stroke-primary"
                                    stroke-width="2.5"
                                />
                                <!-- Score Text Badge -->
                                <text
                                    :x="pt.x"
                                    :y="pt.y - 8"
                                    text-anchor="middle"
                                    class="text-[10px] font-mono font-bold fill-foreground"
                                >
                                    {{ pt.score.toFixed(1) }}
                                </text>
                                <!-- Date Label -->
                                <text
                                    :x="pt.x"
                                    y="120"
                                    text-anchor="middle"
                                    class="text-[9px] fill-muted-foreground font-mono"
                                >
                                    {{ pt.date }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- Clinical Examinations Table -->
                <div v-if="patient.clinical_exams.length > 0" class="rounded-xl border border-border bg-card overflow-hidden shadow-2xs">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="w-[120px] font-semibold">Date</TableHead>
                                <TableHead class="w-[100px] font-semibold">Score EDSS</TableHead>
                                <TableHead class="font-semibold min-w-[220px]">Syndromes Objectivés</TableHead>
                                <TableHead class="font-semibold">Examinateur</TableHead>
                                <TableHead class="font-semibold">Synthèse / Décision</TableHead>
                                <TableHead class="w-[90px] text-right font-semibold">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="exam in patient.clinical_exams" :key="exam.id">
                                <TableCell class="font-mono text-xs font-semibold text-foreground">
                                    {{ exam.exam_date || '—' }}
                                </TableCell>
                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center justify-center rounded-md px-2.5 py-1 font-mono text-xs font-bold border',
                                            Number(exam.edss_score) <= 2.5
                                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                                                : Number(exam.edss_score) <= 5.5
                                                ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30'
                                                : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30'
                                        ]"
                                    >
                                        EDSS {{ Number(exam.edss_score).toFixed(1) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-xs">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-if="exam.t25fw" variant="secondary" class="text-[10px] font-mono">
                                            T25FW: {{ exam.t25fw }}s
                                        </Badge>
                                        <Badge v-if="exam.nine_hpt" variant="secondary" class="text-[10px] font-mono">
                                            9HPT: {{ exam.nine_hpt }}s
                                        </Badge>
                                        <Badge v-if="exam.syndrome_pyramidal && exam.syndrome_pyramidal !== 'absent'" variant="outline" class="text-[10px] bg-muted/40">
                                            Pyramidal : {{ exam.syndrome_pyramidal }}
                                        </Badge>
                                        <Badge v-if="exam.syndrome_vestibulaire && exam.syndrome_vestibulaire !== 'absent'" variant="outline" class="text-[10px] bg-muted/40">
                                            Céréb./Vestib. : {{ exam.syndrome_vestibulaire }}
                                        </Badge>
                                        <Badge v-if="exam.syndrome_ataxo_spasmodique && exam.syndrome_ataxo_spasmodique !== 'absent'" variant="outline" class="text-[10px] bg-muted/40">
                                            Ataxo-spasm. : {{ exam.syndrome_ataxo_spasmodique }}
                                        </Badge>
                                        <Badge v-if="exam.syndrome_medullaire && exam.syndrome_medullaire !== 'absent'" variant="outline" class="text-[10px] bg-muted/40">
                                            Médullaire : {{ exam.syndrome_medullaire }}
                                        </Badge>
                                        <Badge v-if="exam.norb && exam.norb !== 'absent'" variant="outline" class="text-[10px] bg-muted/40">
                                            NORB : {{ exam.norb }}
                                        </Badge>
                                        <Badge v-if="exam.cranial_nerves_impairment && exam.cranial_nerves_impairment !== 'absent'" variant="outline" class="text-[10px] bg-muted/40">
                                            Paires : {{ exam.cranial_nerves_impairment }}
                                        </Badge>
                                        <span v-if="(!exam.syndrome_pyramidal || exam.syndrome_pyramidal === 'absent') && (!exam.syndrome_vestibulaire || exam.syndrome_vestibulaire === 'absent') && (!exam.norb || exam.norb === 'absent')" class="text-muted-foreground text-[11px]">
                                            Examen neurologique sans syndrome majeur
                                        </span>
                                    </div>
                                    <div v-if="exam.other_signs" class="text-[11px] text-muted-foreground mt-1">
                                        {{ exam.other_signs }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    {{ exam.evaluator_name || '—' }}
                                </TableCell>
                                <TableCell class="text-xs text-foreground max-w-xs truncate" :title="exam.clinical_comment || ''">
                                    {{ exam.clinical_comment || '—' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-foreground"
                                            title="Modifier cette évaluation"
                                            @click="openEditClinicalExamDialog(exam)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive"
                                            title="Supprimer cette évaluation"
                                            @click="deleteClinicalExam(exam)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-xl border border-dashed border-border bg-card/50 p-12 text-center space-y-3">
                    <Stethoscope class="size-10 mx-auto text-muted-foreground/40" />
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">Aucun examen clinique enregistré</p>
                        <p class="text-xs text-muted-foreground max-w-sm mx-auto">
                            Ce dossier ne comporte actuellement aucune évaluation clinique ni calcul d'EDSS.
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        @click="openNewClinicalExamDialog"
                        class="gap-1.5"
                    >
                        <Plus class="size-3.5" />
                        <span>Enregistrer la première évaluation</span>
                    </Button>
                </div>
            </TabsContent>

            <!-- TAB 5: IMAGERIE & IRM -->
            <TabsContent value="mri" class="space-y-4 pt-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-foreground">Examens d'Imagerie par Résonance Magnétique (IRM)</h3>
                        <p class="text-xs text-muted-foreground">Suivi longitudinal des lésions cérébrales et médullaires selon les critères de McDonald 2017</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-1.5"
                            :disabled="patient.mri_exams.length === 0"
                            @click="openMriViewer()"
                        >
                            <Columns2 class="size-3.5 text-primary" />
                            <span>Visionneuse & Comparateur</span>
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            class="gap-1.5"
                            @click="openNewMriDialog"
                        >
                            <Plus class="size-3.5" />
                            <span>Nouvel Examen IRM</span>
                        </Button>
                    </div>
                </div>

                <!-- Region Filter Pills -->
                <div v-if="patient.mri_exams.length > 0" class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        @click="mriRegionFilter = 'all'"
                        :class="[
                            'text-xs px-3 py-1 rounded-full border font-medium transition-colors',
                            mriRegionFilter === 'all'
                                ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                : 'bg-muted/40 hover:bg-muted text-muted-foreground border-border',
                        ]"
                    >
                        Toutes les IRM ({{ patient.mri_exams.length }})
                    </button>
                    <button
                        type="button"
                        @click="mriRegionFilter = 'cerebrale'"
                        :class="[
                            'text-xs px-3 py-1 rounded-full border font-medium transition-colors',
                            mriRegionFilter === 'cerebrale'
                                ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                : 'bg-muted/40 hover:bg-muted text-muted-foreground border-border',
                        ]"
                    >
                        Cérébrales ({{ patient.mri_exams.filter((m) => m.region === 'cerebrale' || m.region === 'les_deux').length }})
                    </button>
                    <button
                        type="button"
                        @click="mriRegionFilter = 'medullaire'"
                        :class="[
                            'text-xs px-3 py-1 rounded-full border font-medium transition-colors',
                            mriRegionFilter === 'medullaire'
                                ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                : 'bg-muted/40 hover:bg-muted text-muted-foreground border-border',
                        ]"
                    >
                        Médullaires ({{ patient.mri_exams.filter((m) => m.region === 'medullaire' || m.region === 'les_deux').length }})
                    </button>
                </div>

                <!-- MRI Cards Grid -->
                <div v-if="filteredMriExams.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Card v-for="mri in filteredMriExams" :key="mri.id" class="shadow-2xs overflow-hidden flex flex-col justify-between">
                        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <div class="p-1 rounded bg-primary/10 text-primary">
                                            <Crosshair class="size-3.5" />
                                        </div>
                                        <CardTitle class="text-sm font-semibold text-foreground">
                                            {{ getMriRegionLabel(mri.region) }}
                                        </CardTitle>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Calendar class="size-3 text-muted-foreground/70" />
                                        <span>Examen du {{ mri.exam_date || 'Date non précisée' }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full border', getMriTimingBadge(mri.timing).class]">
                                        {{ getMriTimingBadge(mri.timing).label }}
                                    </span>
                                    <span v-if="mri.sep_protocol_applied" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                                        <CheckCircle2 class="size-3" /> Protocole SEP
                                    </span>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-3 pb-3 text-xs space-y-3 flex-1">
                            <!-- Metrics summary -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-muted/30 p-2.5 rounded-lg border border-border/50 text-[11px]">
                                <div>
                                    <span class="text-muted-foreground block text-[10px]">Lésions T2 :</span>
                                    <span class="font-bold text-foreground font-mono">{{ mri.lesions_count_estimated || 'N/P' }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[10px]">Gadolinium :</span>
                                    <span :class="mri.contrast_enhancement === 'positive' ? 'font-semibold text-destructive' : 'font-medium text-foreground'">
                                        {{ mri.contrast_enhancement === 'positive' ? 'Rehaussée (Active)' : (mri.contrast_enhancement === 'negative' ? 'Non rehaussée' : 'Non injecté') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[10px]">Nouvelles lésions :</span>
                                    <span :class="(mri.new_lesions_count ?? 0) > 0 ? 'font-bold text-destructive font-mono' : 'font-medium text-muted-foreground font-mono'">
                                        {{ (mri.new_lesions_count ?? 0) > 0 ? `+${mri.new_lesions_count}` : '0' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Séquences disponibles -->
                            <div v-if="mri.sequences_available" class="space-y-1">
                                <span class="text-[11px] text-muted-foreground font-medium block">Séquences disponibles :</span>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="seq in mri.sequences_available.split(',').map((s) => s.trim()).filter(Boolean)"
                                        :key="seq"
                                        class="text-[10px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-mono border border-border/50"
                                    >
                                        {{ seq }}
                                    </span>
                                </div>
                            </div>

                            <!-- Topographie des lésions -->
                            <div v-if="mri.lesions_locations">
                                <span class="text-muted-foreground font-medium block text-[11px]">Topographie des lésions :</span>
                                <p class="text-foreground mt-0.5 leading-relaxed">{{ mri.lesions_locations }}</p>
                            </div>

                            <!-- Conclusion radiologique -->
                            <div v-if="mri.radiologist_report">
                                <span class="text-muted-foreground font-medium block text-[11px]">Conclusion radiologique :</span>
                                <p class="text-foreground mt-0.5 italic bg-muted/20 p-2 rounded border border-border/40 text-[11px] leading-relaxed">
                                    "{{ mri.radiologist_report }}"
                                </p>
                            </div>

                            <!-- Key Images Thumbnails if present -->
                            <div v-if="mri.key_images && mri.key_images.length > 0" class="space-y-1.5 pt-1">
                                <span class="text-[11px] text-muted-foreground font-medium block">Coupes clés & Fichiers joints ({{ mri.key_images.length }}) :</span>
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        v-for="(img, idx) in mri.key_images"
                                        :key="idx"
                                        :href="img.path"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-muted/50 hover:bg-muted text-[11px] font-medium text-primary border border-border/60 transition-colors"
                                    >
                                        <ImageIcon class="size-3 shrink-0" />
                                        <span class="truncate max-w-[150px]">{{ img.name }}</span>
                                    </a>
                                </div>
                            </div>
                        </CardContent>

                        <!-- Card Action Buttons -->
                        <div class="px-4 py-2.5 bg-muted/10 border-t border-border/50 flex items-center justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-7 text-xs gap-1.5"
                                @click="openMriViewer(mri.id)"
                            >
                                <Eye class="size-3 text-primary" />
                                <span>Visualiser</span>
                            </Button>

                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 text-muted-foreground hover:text-foreground"
                                    title="Modifier l'examen"
                                    @click="openEditMriDialog(mri)"
                                >
                                    <Pencil class="size-3.5" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 text-muted-foreground hover:text-destructive"
                                    title="Supprimer l'examen"
                                    @click="deleteMri(mri)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-xl border border-dashed border-border bg-card/50 p-12 text-center space-y-3">
                    <Crosshair class="size-10 mx-auto text-muted-foreground/40" />
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">Aucun examen IRM enregistré</p>
                        <p class="text-xs text-muted-foreground max-w-md mx-auto">
                            Enregistrez les IRM cérébrales et médullaires (Baseline initiale, Rebaseline à 6 mois, Contrôles annuels) pour documenter la charge lésionnelle et visualiser les coupes.
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5"
                        @click="openNewMriDialog"
                    >
                        <Plus class="size-3.5" />
                        <span>Enregistrer le premier examen IRM</span>
                    </Button>
                </div>
            </TabsContent>

            <!-- TAB 6: TRAITEMENTS -->
            <TabsContent value="treatments" class="space-y-6 pt-2">
                <!-- Header with Action Button -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-foreground">Stratégie Thérapeutique & Traitements de Fond (DMT)</h3>
                        <p class="text-xs text-muted-foreground">Immunomodulateurs, immunosuppresseurs, tolérance, lignes thérapeutiques et traitements symptomatiques</p>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5 shrink-0"
                        @click="openNewTreatmentDialog"
                    >
                        <Plus class="size-3.5" />
                        <span>Nouveau Traitement</span>
                    </Button>
                </div>

                <!-- Treatment KPI Overview Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Current Active DMT -->
                    <div class="p-4 rounded-xl border border-border bg-card space-y-1.5">
                        <span class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <Pill class="size-3.5 text-primary" />
                            <span>Traitement Actuel</span>
                        </span>
                        <div v-if="activeDiseaseModifyingTreatment">
                            <div class="text-sm font-bold text-foreground truncate">
                                {{ activeDiseaseModifyingTreatment.molecule_name }}
                            </div>
                            <p class="text-[11px] text-muted-foreground mt-0.5 truncate">
                                {{ activeDiseaseModifyingTreatment.therapeutic_class || 'Traitement de fond actif' }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <Badge class="text-[10px] bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/30">
                                    Depuis le {{ activeDiseaseModifyingTreatment.start_date }}
                                </Badge>
                            </div>
                        </div>
                        <div v-else class="text-xs text-muted-foreground italic pt-1">
                            Aucun traitement de fond actif ce jour
                        </div>
                    </div>

                    <!-- Total Lines / History -->
                    <div class="p-4 rounded-xl border border-border bg-card space-y-1.5">
                        <span class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <Layers class="size-3.5 text-primary" />
                            <span>Lignes Thérapeutiques</span>
                        </span>
                        <div class="text-2xl font-bold font-mono text-foreground">
                            {{ patient.treatments.filter((t) => t.category === 'fond').length }}
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            Ligne(s) de fond prescrite(s) au total
                        </p>
                    </div>

                    <!-- Symptomatic Treatments -->
                    <div class="p-4 rounded-xl border border-border bg-card space-y-2">
                        <span class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <Activity class="size-3.5 text-primary" />
                            <span>Traitements Symptomatiques</span>
                        </span>
                        <div class="space-y-1 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground text-[11px]">Vitamine D :</span>
                                <Badge
                                    :variant="patient.treatments.some((t) => t.vitamin_d_supplementation) ? 'default' : 'outline'"
                                    class="text-[10px] h-5"
                                >
                                    {{ patient.treatments.some((t) => t.vitamin_d_supplementation) ? 'Supplémenté' : 'Non renseigné' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground text-[11px]">Antidépresseurs :</span>
                                <Badge
                                    :variant="patient.treatments.some((t) => t.antidepressants) ? 'outline' : 'outline'"
                                    class="text-[10px] h-5"
                                >
                                    {{ patient.treatments.some((t) => t.antidepressants) ? 'Prescrit' : 'Non' }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Treatment Sequential Timeline (When >= 1 treatment) -->
                <div v-if="chronologicalTreatments.length > 0" class="p-4 rounded-xl border border-border bg-card space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                            <History class="size-3.5 text-primary" />
                            <span>Parcours Thérapeutique Chronologique</span>
                        </span>
                        <span class="text-[11px] text-muted-foreground">
                            {{ chronologicalTreatments.length }} étape(s) enregistrée(s)
                        </span>
                    </div>

                    <!-- Timeline track -->
                    <div class="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-border">
                        <div
                            v-for="(t, idx) in chronologicalTreatments"
                            :key="t.id"
                            class="relative text-xs space-y-1"
                        >
                            <!-- Dot -->
                            <div
                                :class="[
                                    'absolute -left-6 top-1 size-3 rounded-full border-2 bg-background',
                                    t.status === 'en_cours' ? 'border-emerald-500 bg-emerald-500' : 'border-muted-foreground/60',
                                ]"
                            />

                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-foreground text-sm">{{ t.molecule_name }}</span>
                                <span :class="['text-[10px] px-2 py-0.5 rounded-full border font-medium', getTreatmentStatusBadge(t.status).class]">
                                    {{ getTreatmentStatusBadge(t.status).label }}
                                </span>
                                <Badge variant="outline" class="text-[10px]">
                                    {{ getTreatmentCategoryLabel(t.category) }}
                                </Badge>
                                <span class="font-mono text-[11px] text-muted-foreground">
                                    {{ t.start_date || '—' }} <span v-if="t.end_date">→ {{ t.end_date }}</span><span v-else>→ Présent</span>
                                </span>
                            </div>

                            <p v-if="t.therapeutic_class" class="text-muted-foreground text-[11px]">
                                {{ t.therapeutic_class }} • <span v-if="t.dosage_regimen">{{ t.dosage_regimen }}</span>
                                <span v-if="t.administration_route">({{ t.administration_route }})</span>
                            </p>

                            <!-- Stop reason if switched or stopped -->
                            <div v-if="t.stop_reason" class="p-2 rounded bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-900 dark:text-amber-300">
                                <strong>Motif d'arrêt / switch :</strong> {{ t.stop_reason }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Treatments Table -->
                <div v-if="patient.treatments.length > 0" class="rounded-xl border border-border bg-card overflow-hidden shadow-2xs">
                    <div class="p-3 bg-muted/30 border-b border-border/60 flex items-center justify-between">
                        <span class="text-xs font-semibold text-foreground">
                            Historique Détaillé des Prescriptions
                        </span>
                    </div>

                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="font-semibold text-xs">Molécule & Spécialité</TableHead>
                                <TableHead class="font-semibold text-xs">Catégorie</TableHead>
                                <TableHead class="font-semibold text-xs">Période</TableHead>
                                <TableHead class="font-semibold text-xs">Statut</TableHead>
                                <TableHead class="font-semibold text-xs">Posologie & Voie</TableHead>
                                <TableHead class="font-semibold text-xs">Tolérance / Effets</TableHead>
                                <TableHead class="text-right font-semibold text-xs">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="treatment in patient.treatments" :key="treatment.id">
                                <TableCell>
                                    <div class="font-semibold text-sm text-foreground">
                                        {{ treatment.molecule_name }}
                                    </div>
                                    <div v-if="treatment.therapeutic_class" class="text-[11px] text-muted-foreground">
                                        {{ treatment.therapeutic_class }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="text-[11px] capitalize">
                                        {{ getTreatmentCategoryLabel(treatment.category) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-mono text-xs">
                                    <div>{{ treatment.start_date || '—' }}</div>
                                    <div class="text-[11px] text-muted-foreground">
                                        {{ treatment.end_date ? `au ${treatment.end_date}` : 'en cours' }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span :class="['text-[11px] px-2 py-0.5 rounded-full border inline-block font-medium', getTreatmentStatusBadge(treatment.status).class]">
                                        {{ getTreatmentStatusBadge(treatment.status).label }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-xs text-foreground">
                                    <div>{{ treatment.dosage_regimen || '—' }}</div>
                                    <div v-if="treatment.administration_route" class="text-[11px] text-muted-foreground">
                                        {{ treatment.administration_route }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground max-w-[200px]">
                                    <div v-if="treatment.adverse_effects" class="truncate" :title="treatment.adverse_effects">
                                        {{ treatment.adverse_effects }}
                                    </div>
                                    <span v-else class="text-muted-foreground/60 italic text-[11px]">Bonne tolérance</span>
                                    <div v-if="treatment.stop_reason" class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5 truncate" :title="treatment.stop_reason">
                                        Arrêt : {{ treatment.stop_reason }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-foreground"
                                            title="Modifier le traitement"
                                            @click="openEditTreatmentDialog(treatment)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive"
                                            title="Supprimer le traitement"
                                            @click="deleteTreatment(treatment)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Empty State -->
                <div v-else class="rounded-xl border border-dashed border-border bg-card/50 p-12 text-center space-y-3">
                    <Pill class="size-10 mx-auto text-muted-foreground/40" />
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">Aucun traitement enregistré</p>
                        <p class="text-xs text-muted-foreground max-w-md mx-auto">
                            Enregistrez les traitements de fond (Ligne 1 / Ligne 2), les traitements de poussées et les thérapeutiques symptomatiques pour ce patient.
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5"
                        @click="openNewTreatmentDialog"
                    >
                        <Plus class="size-3.5" />
                        <span>Enregistrer le premier traitement</span>
                    </Button>
                </div>
            </TabsContent>

            <!-- TAB 7: SUIVI LONGITUDINAL & EXAMENS COMPLÉMENTAIRES -->
            <TabsContent value="consultations" class="space-y-6 pt-2">
                <!-- Action Header Bar -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 rounded-xl border border-border bg-card shadow-2xs">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 font-semibold text-sm text-foreground">
                            <Stethoscope class="size-4 text-primary" />
                            <span>Suivi Longitudinal & Examens Complémentaires</span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Historique des consultations régulières, tolérance des traitements, analyse du LCR et marqueurs biologiques
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            class="gap-1.5"
                            @click="openNewConsultationDialog"
                        >
                            <Plus class="size-3.5" />
                            <span>Nouvelle Consultation</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-1.5"
                            @click="openNewComplementaryExamDialog"
                        >
                            <FlaskConical class="size-3.5 text-primary" />
                            <span>Nouvel Examen PL / Biologie</span>
                        </Button>
                    </div>
                </div>

                <!-- KPI Mini Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Consultations totales -->
                    <Card class="p-4 space-y-1.5">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Visites Enregistrées</span>
                            <Stethoscope class="size-4 text-primary" />
                        </div>
                        <div class="text-2xl font-bold font-mono text-foreground">
                            {{ patient.consultations.length }}
                        </div>
                        <p class="text-[11px] text-muted-foreground truncate">
                            Dernière : <strong class="text-foreground">{{ latestConsultation?.consultation_date || 'Aucune' }}</strong>
                        </p>
                    </Card>

                    <!-- Prochain Rendez-vous -->
                    <Card class="p-4 space-y-1.5">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Prochain RDV</span>
                            <Calendar class="size-4 text-primary" />
                        </div>
                        <div class="text-base font-bold font-mono text-foreground truncate">
                            {{ nextScheduledAppointment ? nextScheduledAppointment.split('-').reverse().join('/') : 'Non planifié' }}
                        </div>
                        <p class="text-[11px] text-muted-foreground truncate">
                            Contrôle semestriel ou annuel
                        </p>
                    </Card>

                    <!-- Statut Ponction Lombaire (LCR) -->
                    <Card class="p-4 space-y-1.5">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Statut LCR / BOC</span>
                            <TestTube class="size-4 text-primary" />
                        </div>
                        <div class="text-sm font-bold text-foreground truncate">
                            {{ latestLcrExam?.pl_oligoclonal_bands === 'positives' ? 'BOC Positives' : (latestLcrExam?.pl_oligoclonal_bands ? latestLcrExam.pl_oligoclonal_bands : 'Non documenté') }}
                        </div>
                        <p class="text-[11px] text-muted-foreground truncate">
                            <span v-if="latestLcrExam?.pl_proteinorachie">Prot. : {{ latestLcrExam.pl_proteinorachie }} g/L</span>
                            <span v-else>Synthèse intrathécale</span>
                        </p>
                    </Card>

                    <!-- Diagnostic Différentiel NMO / MOG -->
                    <Card class="p-4 space-y-1.5">
                        <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                            <span>Anti-NMO / MOG</span>
                            <Dna class="size-4 text-primary" />
                        </div>
                        <div class="text-sm font-bold text-foreground truncate">
                            <span v-if="latestBiologyExam">
                                NMO: {{ latestBiologyExam.biology_anti_nmo === 'negative' ? 'Nég' : (latestBiologyExam.biology_anti_nmo || '—') }} /
                                MOG: {{ latestBiologyExam.biology_anti_mog === 'negative' ? 'Nég' : (latestBiologyExam.biology_anti_mog || '—') }}
                            </span>
                            <span v-else>Non documenté</span>
                        </div>
                        <p class="text-[11px] text-muted-foreground truncate">
                            Exclusion NMOSD / MOGAD
                        </p>
                    </Card>
                </div>

                <!-- SECTION 1 : CONSULTATIONS DE SUIVI -->
                <Card>
                    <CardHeader class="pb-3 border-b border-border/50">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <Calendar class="size-4 text-primary" />
                                <span>Consultations de Suivi Longitudinal ({{ patient.consultations.length }})</span>
                            </CardTitle>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="gap-1 text-xs h-7 px-2"
                                @click="openNewConsultationDialog"
                            >
                                <Plus class="size-3" />
                                <span>Ajouter</span>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <div v-if="patient.consultations.length > 0" class="space-y-4">
                            <div
                                v-for="c in patient.consultations"
                                :key="c.id"
                                class="p-4 rounded-xl border border-border bg-card/60 hover:bg-card/90 transition-colors space-y-3 text-xs"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-border/40">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-sm text-foreground">
                                            Consultation du {{ c.consultation_date || '—' }}
                                        </span>
                                        <Badge
                                            :class="c.consultation_type === 'suivi_poussee' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' : 'bg-primary/10 text-primary border-primary/20'"
                                            class="text-[10px]"
                                        >
                                            {{ c.consultation_type === 'suivi_poussee' ? 'Suivi en cas de poussée' : 'Suivi programmé' }}
                                        </Badge>
                                        <Badge v-if="c.edss_score !== null" variant="secondary" class="font-mono text-[11px]">
                                            EDSS : {{ c.edss_score }}
                                        </Badge>
                                        <Badge
                                            v-if="c.relapses_since_last_visit && c.relapses_since_last_visit > 0"
                                            variant="destructive"
                                            class="text-[10px]"
                                        >
                                            {{ c.relapses_since_last_visit }} poussée(s) intercurrente(s)
                                        </Badge>
                                        <Badge v-else variant="outline" class="text-[10px] text-emerald-600 dark:text-emerald-400 border-emerald-500/30">
                                            0 poussée
                                        </Badge>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span v-if="c.doctor_name" class="text-muted-foreground text-[11px]">
                                            Médecin : <strong class="text-foreground">{{ c.doctor_name }}</strong>
                                        </span>
                                        <div class="flex items-center gap-1 ml-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-7"
                                                title="Modifier la consultation"
                                                @click="openEditConsultationDialog(c)"
                                            >
                                                <Pencil class="size-3.5 text-muted-foreground" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-7 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                title="Supprimer la consultation"
                                                @click="deleteConsultation(c)"
                                            >
                                                <Trash2 class="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Décision thérapeutique -->
                                <div v-if="c.therapeutic_decision" class="p-3 rounded-lg bg-primary/5 border border-primary/20 space-y-1">
                                    <div class="text-[11px] font-semibold text-primary flex items-center gap-1.5 uppercase tracking-wide">
                                        <Sparkles class="size-3" />
                                        <span>Décision Thérapeutique</span>
                                    </div>
                                    <p class="text-foreground font-medium text-xs">
                                        {{ c.therapeutic_decision }}
                                    </p>
                                </div>

                                <!-- Détails cliniques & tolérance -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-muted-foreground">
                                    <div v-if="c.current_treatment_evaluation">
                                        <strong class="text-foreground">Évaluation traitement :</strong> {{ c.current_treatment_evaluation }}
                                    </div>
                                    <div v-if="c.tolerance_and_adherence">
                                        <strong class="text-foreground">Tolérance & Observance :</strong> {{ c.tolerance_and_adherence }}
                                    </div>
                                    <div v-if="c.intercurrent_events">
                                        <strong class="text-foreground">Événements intercurrents :</strong> {{ c.intercurrent_events }}
                                    </div>
                                    <div v-if="c.recent_exam_results">
                                        <strong class="text-foreground">Examens récents :</strong> {{ c.recent_exam_results }}
                                    </div>
                                </div>

                                <!-- Prochain Rendez-vous & Notes -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-border/30 text-[11px]">
                                    <div v-if="c.next_appointment_date" class="text-muted-foreground flex items-center gap-1.5">
                                        <Clock class="size-3.5 text-primary" />
                                        <span>Prochain rendez-vous prévu le : <strong class="text-foreground font-mono">{{ c.next_appointment_date }}</strong></span>
                                    </div>
                                    <div v-else class="text-muted-foreground">
                                        Prochain rendez-vous : Non renseigné
                                    </div>
                                    <div v-if="c.notes" class="text-muted-foreground italic truncate max-w-md">
                                        « {{ c.notes }} »
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State Consultations -->
                        <div v-else class="text-center py-10 text-muted-foreground text-xs space-y-3">
                            <Stethoscope class="size-10 mx-auto text-muted-foreground/40" />
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-foreground">Aucune consultation enregistrée</p>
                                <p class="max-w-md mx-auto text-muted-foreground">
                                    Documentez la visite périodique du patient, l'évaluation du score EDSS, l'observance et les décisions thérapeutiques.
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openNewConsultationDialog"
                            >
                                <Plus class="size-3.5" />
                                <span>Enregistrer la première consultation</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- SECTION 2 : EXAMENS COMPLÉMENTAIRES (PL & BIOLOGIE) -->
                <Card>
                    <CardHeader class="pb-3 border-b border-border/50">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-semibold flex items-center gap-2">
                                <FlaskConical class="size-4 text-primary" />
                                <span>Ponction Lombaire & Marqueurs Biologiques Spécifiques ({{ patient.complementary_exams.length }})</span>
                            </CardTitle>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="gap-1 text-xs h-7 px-2"
                                @click="openNewComplementaryExamDialog"
                            >
                                <Plus class="size-3" />
                                <span>Ajouter</span>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <div v-if="patient.complementary_exams.length > 0" class="space-y-4">
                            <div
                                v-for="exam in patient.complementary_exams"
                                :key="exam.id"
                                class="p-4 rounded-xl border border-border bg-card/60 hover:bg-card/90 transition-colors space-y-3 text-xs"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-border/40">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-sm text-foreground">
                                            {{ exam.exam_type }} du {{ exam.exam_date || '—' }}
                                        </span>
                                        <Badge
                                            v-if="exam.pl_result"
                                            :variant="exam.pl_result === 'pathologique' ? 'destructive' : 'outline'"
                                            class="text-[10px] uppercase font-mono"
                                        >
                                            PL : {{ exam.pl_result }}
                                        </Badge>
                                        <Badge
                                            v-if="exam.pl_oligoclonal_bands"
                                            :class="exam.pl_oligoclonal_bands === 'positives' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' : 'bg-muted text-muted-foreground'"
                                            class="text-[10px]"
                                        >
                                            BOC : {{ exam.pl_oligoclonal_bands }}
                                        </Badge>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7"
                                            title="Modifier l'examen"
                                            @click="openEditComplementaryExamDialog(exam)"
                                        >
                                            <Pencil class="size-3.5 text-muted-foreground" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-destructive hover:text-destructive hover:bg-destructive/10"
                                            title="Supprimer l'examen"
                                            @click="deleteComplementaryExam(exam)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Grille des résultats LCR et Biologie -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <!-- Colonne LCR -->
                                    <div class="p-3 rounded-lg bg-muted/20 border border-border/40 space-y-1.5">
                                        <div class="font-semibold text-foreground flex items-center gap-1.5 text-[11px]">
                                            <TestTube class="size-3.5 text-primary" />
                                            <span>Analyse du LCR</span>
                                        </div>
                                        <div class="space-y-1 text-muted-foreground">
                                            <div>Cytorachie : <span class="font-mono text-foreground font-medium">{{ exam.pl_cytorachie ?? '—' }} élém/mm³</span></div>
                                            <div>Protéinorachie : <span class="font-mono text-foreground font-medium">{{ exam.pl_proteinorachie ?? '—' }} g/L</span></div>
                                            <div>Bandes oligoclonales : <strong class="text-foreground">{{ exam.pl_oligoclonal_bands || '—' }}</strong></div>
                                        </div>
                                    </div>

                                    <!-- Colonne Marqueurs Biologiques -->
                                    <div class="p-3 rounded-lg bg-muted/20 border border-border/40 space-y-1.5">
                                        <div class="font-semibold text-foreground flex items-center gap-1.5 text-[11px]">
                                            <Dna class="size-3.5 text-primary" />
                                            <span>Diagnostic Différentiel</span>
                                        </div>
                                        <div class="space-y-1 text-muted-foreground">
                                            <div>Anti-NMO (AQP4) : <strong :class="exam.biology_anti_nmo === 'positive' ? 'text-destructive font-bold' : 'text-foreground'">{{ exam.biology_anti_nmo || '—' }}</strong></div>
                                            <div>Anti-MOG : <strong :class="exam.biology_anti_mog === 'positive' ? 'text-destructive font-bold' : 'text-foreground'">{{ exam.biology_anti_mog || '—' }}</strong></div>
                                            <div v-if="exam.biology_anti_antinucleaire">AAN : <span class="text-foreground">{{ exam.biology_anti_antinucleaire }}</span></div>
                                            <div v-if="exam.biology_serology_hbv">VHB : <span class="text-foreground">{{ exam.biology_serology_hbv }}</span></div>
                                        </div>
                                    </div>

                                    <!-- Colonne IEPP & Autres -->
                                    <div class="p-3 rounded-lg bg-muted/20 border border-border/40 space-y-1.5">
                                        <div class="font-semibold text-foreground flex items-center gap-1.5 text-[11px]">
                                            <Activity class="size-3.5 text-primary" />
                                            <span>IEPP & Bilan Complémentaire</span>
                                        </div>
                                        <div class="space-y-1 text-muted-foreground">
                                            <div v-if="exam.iepp_sample_type">Prélèvement : <span class="text-foreground capitalize">{{ exam.iepp_sample_type.replace('_', ' ') }}</span></div>
                                            <div v-if="exam.iepp_kappa_index !== null && exam.iepp_kappa_index !== undefined">
                                                Index Kappa : <span class="font-mono font-bold text-foreground">{{ exam.iepp_kappa_index }}</span>
                                                <Badge
                                                    v-if="Number(exam.iepp_kappa_index) >= 5.8"
                                                    variant="destructive"
                                                    class="text-[9px] px-1 py-0 ml-1.5"
                                                >
                                                    Positif (≥ 5.8)
                                                </Badge>
                                            </div>
                                            <div v-if="exam.iepp_result" class="text-[11px] text-foreground font-medium">
                                                {{ exam.iepp_result }}
                                            </div>
                                            <div v-if="exam.biology_other" class="text-[11px]">
                                                Autres : {{ exam.biology_other }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="exam.notes" class="text-muted-foreground italic pt-1 border-t border-border/30 text-[11px]">
                                    Note : {{ exam.notes }}
                                </div>
                            </div>
                        </div>

                        <!-- Empty State Examens -->
                        <div v-else class="text-center py-10 text-muted-foreground text-xs space-y-3">
                            <FlaskConical class="size-10 mx-auto text-muted-foreground/40" />
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-foreground">Aucun examen biologique ou LCR enregistré</p>
                                <p class="max-w-md mx-auto text-muted-foreground">
                                    Enregistrez les résultats de ponction lombaire (cytorachie, protéinorachie, BOC) et la biologie spécifique (Anti-NMO, Anti-MOG).
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openNewComplementaryExamDialog"
                            >
                                <Plus class="size-3.5" />
                                <span>Enregistrer le premier examen</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- TAB: CHRONOLOGIE (MODULE 5) -->
            <TabsContent value="timeline" class="space-y-6 pt-2">
                <PatientDiseaseTimeline
                    :relapses="patient.relapses"
                    :treatments="patient.treatments"
                    :clinical-exams="patient.clinical_exams"
                    :mri-exams="patient.mri_exams"
                    :diagnosis-date="patient.disease_profile?.diagnosis_date"
                    :first-symptom-date="patient.disease_profile?.first_symptom_date"
                />
            </TabsContent>

            <!-- TAB: FICHE PRONOSTIQUE (MODULE 8) -->
            <TabsContent value="prognostic" class="space-y-6 pt-2">
                <PrognosticScoreCard
                    :patient-data="prognosticPatientData"
                />
            </TabsContent>
        </Tabs>

        <!-- Relapse Dialog Modal -->
        <RelapseDialog
            v-model:open="isRelapseDialogOpen"
            :patient-id="patient.id"
            :relapse="selectedRelapseToEdit"
            :diagnosis-date="patient.disease_profile?.diagnosis_date"
        />

        <!-- Clinical Exam Dialog Modal -->
        <ClinicalExamDialog
            v-model:open="isClinicalExamDialogOpen"
            :patient-id="patient.id"
            :exam="selectedExamToEdit"
        />

        <!-- MRI Exam Dialog Modal -->
        <MriExamDialog
            v-model:open="isMriDialogOpen"
            :patient-id="patient.id"
            :exam="selectedMriToEdit"
        />

        <!-- MRI Medical Viewer & Comparison Modal -->
        <MriViewerDialog
            v-model:open="isMriViewerOpen"
            :patient-id="patient.id"
            :patient-name="patient.full_name"
            :dossier-number="patient.dossier_number"
            :exams="patient.mri_exams"
            :initial-exam-id="mriViewerInitialExamId"
        />

        <!-- Treatment Dialog Modal -->
        <TreatmentDialog
            v-model:open="isTreatmentDialogOpen"
            :patient-id="patient.id"
            :patient-birth-date="patient.birth_date"
            :diagnosis-date="patient.disease_profile?.diagnosis_date"
            :first-symptom-date="patient.disease_profile?.first_symptom_date"
            :treatment="selectedTreatmentToEdit"
        />

        <!-- Consultation Dialog Modal -->
        <ConsultationDialog
            v-model:open="isConsultationDialogOpen"
            :patient-id="patient.id"
            :consultation="selectedConsultationToEdit"
            :current-treatment-name="activeDiseaseModifyingTreatment?.molecule_name"
            :latest-edss="summary.latest_edss"
        />

        <!-- Complementary Exam Dialog Modal -->
        <ComplementaryExamDialog
            v-model:open="isComplementaryExamDialogOpen"
            :patient-id="patient.id"
            :exam="selectedComplementaryExamToEdit"
        />
    </div>
</template>
