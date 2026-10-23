<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowLeft,
    Brain,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    FileText,
    HeartPulse,
    History,
    Loader2,
    Save,
    Sparkles,
    Stethoscope,
    User,
    Users,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import {
    index as patientsIndex,
    show as patientsShow,
} from '@/routes/patients';

type ClinicalFormOption = {
    value: string;
    label: string;
    full_label: string;
};

type PatientInfo = {
    id: number;
    dossier_number: string;
    full_name: string;
    first_name: string;
    last_name: string;
    gender: string;
    birth_date: string;
    birth_date_formatted: string;
    age: number | null;
    status_label: string;
    status_color: string;
};

type DiseaseProfileData = {
    first_symptom_date: string;
    first_symptom_age_estimated: number | null;
    diagnosis_date: string;
    diagnosis_age_estimated: number | null;
    prediagnostic_duration_months: number | null;
    initial_clinical_form: string;
    current_clinical_form: string;
    first_symptoms_description: string;
    diagnostic_criteria: string;
    initial_edss: string;
    notes: string;
};

type MedicalHistoryData = {
    medical_history: string;
    surgical_history: string;
    smoking: string;
    alcohol: string;
    toxic_other: string;
    pregnancies_count: number;
    deliveries_count: number;
    children_count: number;
    gyneco_notes: string;
    family_history_neurological: boolean;
    family_history_autoimmune: boolean;
    family_history_details: string;
};

type Props = {
    patient: PatientInfo;
    disease_profile: DiseaseProfileData;
    medical_history: MedicalHistoryData;
    clinical_forms: ClinicalFormOption[];
};

const props = defineProps<Props>();

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
                title: 'Profil SEP & Antécédents',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    // Disease Profile
    first_symptom_date: props.disease_profile.first_symptom_date || '',
    first_symptom_age_estimated: props.disease_profile.first_symptom_age_estimated,
    diagnosis_date: props.disease_profile.diagnosis_date || '',
    diagnosis_age_estimated: props.disease_profile.diagnosis_age_estimated,
    prediagnostic_duration_months: props.disease_profile.prediagnostic_duration_months,
    initial_clinical_form: props.disease_profile.initial_clinical_form || 'RR',
    current_clinical_form: props.disease_profile.current_clinical_form || 'RR',
    first_symptoms_description: props.disease_profile.first_symptoms_description || '',
    diagnostic_criteria: props.disease_profile.diagnostic_criteria || 'Critères de McDonald 2017',
    initial_edss: props.disease_profile.initial_edss || '',
    notes: props.disease_profile.notes || '',

    // Medical History
    medical_history: props.medical_history.medical_history || '',
    surgical_history: props.medical_history.surgical_history || '',
    smoking: props.medical_history.smoking || 'Non',
    alcohol: props.medical_history.alcohol || 'Non',
    toxic_other: props.medical_history.toxic_other || 'Non',
    pregnancies_count: props.medical_history.pregnancies_count || 0,
    deliveries_count: props.medical_history.deliveries_count || 0,
    children_count: props.medical_history.children_count || 0,
    gyneco_notes: props.medical_history.gyneco_notes || '',
    family_history_neurological: props.medical_history.family_history_neurological || false,
    family_history_autoimmune: props.medical_history.family_history_autoimmune || false,
    family_history_details: props.medical_history.family_history_details || '',
});

// Auto-calculation of ages and prediagnostic delay
const calculateAgeAtDate = (dateStr: string): number | null => {
    if (!dateStr || !props.patient.birth_date) return null;
    const birth = new Date(props.patient.birth_date);
    const target = new Date(dateStr);
    if (isNaN(birth.getTime()) || isNaN(target.getTime())) return null;

    let age = target.getFullYear() - birth.getFullYear();
    const monthDiff = target.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && target.getDate() < birth.getDate())) {
        age--;
    }
    return age >= 0 ? age : null;
};

// Watch first symptom date to auto-populate estimated age
watch(() => form.first_symptom_date, (val) => {
    if (val) {
        const calculated = calculateAgeAtDate(val);
        if (calculated !== null) {
            form.first_symptom_age_estimated = calculated;
        }
    }
    calculatePrediagnosticDelay();
});

// Watch diagnosis date to auto-populate estimated age
watch(() => form.diagnosis_date, (val) => {
    if (val) {
        const calculated = calculateAgeAtDate(val);
        if (calculated !== null) {
            form.diagnosis_age_estimated = calculated;
        }
    }
    calculatePrediagnosticDelay();
});

const calculatePrediagnosticDelay = () => {
    if (form.first_symptom_date && form.diagnosis_date) {
        const start = new Date(form.first_symptom_date);
        const end = new Date(form.diagnosis_date);
        if (!isNaN(start.getTime()) && !isNaN(end.getTime()) && end >= start) {
            const months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
            form.prediagnostic_duration_months = Math.max(0, months);
            return;
        }
    }

    // Fallback calculation using estimated ages when exact dates are not both provided
    if (
        form.first_symptom_age_estimated !== null &&
        form.first_symptom_age_estimated !== undefined &&
        form.diagnosis_age_estimated !== null &&
        form.diagnosis_age_estimated !== undefined
    ) {
        const diffYears = Number(form.diagnosis_age_estimated) - Number(form.first_symptom_age_estimated);
        if (diffYears >= 0) {
            form.prediagnostic_duration_months = diffYears * 12;
            return;
        }
    }

    form.prediagnostic_duration_months = null;
};

// Also trigger delay calculation if ages change directly
watch([() => form.first_symptom_age_estimated, () => form.diagnosis_age_estimated], () => {
    if (!form.first_symptom_date || !form.diagnosis_date) {
        calculatePrediagnosticDelay();
    }
});

// Copy initial form to current form helper
const copyInitialToCurrent = () => {
    form.current_clinical_form = form.initial_clinical_form;
};

// Quick Symptom Chips
const commonSymptomChips = [
    'NORB (Névrite optique rétrobulbaire)',
    'Paresthésies et engourdissements',
    'Signe de Lhermitte',
    'Déficit moteur / Paraparésie',
    'Troubles de la marche et de l\'équilibre',
    'Syndrome vestibulaire / Vertiges',
    'Diplopie / Troubles oculomoteurs',
    'Troubles sphinctériens urinaires',
    'Fatigue pathologique extrême',
];

const appendSymptom = (chip: string) => {
    if (!form.first_symptoms_description) {
        form.first_symptoms_description = chip;
    } else if (!form.first_symptoms_description.includes(chip)) {
        form.first_symptoms_description += ` • ${chip}`;
    }
};

// Quick Diagnostic Criteria Chips
const commonCriteria = [
    'Critères de McDonald 2017',
    'Critères de McDonald 2010',
    'BOC positives + Dissémination spatiale (DIS)',
    'Dissémination spatiale (DIS) et temporelle (DIT)',
];

const setCriteria = (criteria: string) => {
    form.diagnostic_criteria = criteria;
};

// EDSS Score options from 0.0 to 10.0
const edssOptions = [
    '0.0', '1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0', '5.5', '6.0', '6.5', '7.0', '7.5', '8.0', '8.5', '9.0', '9.5', '10.0',
];

const getEdssInterpretation = (scoreVal: string | number | null | undefined): string => {
    if (scoreVal === '' || scoreVal === null || scoreVal === undefined) return '';
    const score = Number(scoreVal);
    if (isNaN(score)) return '';
    if (score === 0) return 'Examen neurologique normal (aucun handicap)';
    if (score <= 1.5) return 'Signes minimes, aucune incapacité fonctionnelle';
    if (score <= 2.5) return 'Handicap minime dans 1 ou 2 systèmes fonctionnels';
    if (score <= 3.5) return 'Handicap modéré, marche autonome sans aide';
    if (score === 4.0) return 'Handicap relativement sévère, périmètre autonome ≥ 500 m';
    if (score === 4.5) return 'Autonome, périmètre de marche sans aide ≥ 300 m';
    if (score === 5.0) return 'Périmètre de marche sans aide ni repos d\'environ 200 m';
    if (score === 5.5) return 'Périmètre de marche sans aide d\'environ 100 m';
    if (score === 6.0) return 'Aide unilatérale permanente requise pour marcher 100 m (canne / béquille)';
    if (score === 6.5) return 'Aide bilatérale permanente requise pour marcher 20 m (deux cannes / déambulateur)';
    if (score === 7.0) return 'Incapable de marcher plus de 5 m même avec aide, confiné au fauteuil roulant';
    if (score <= 8.5) return 'Fauteuil roulant motorisé ou alité la majeure partie de la journée';
    if (score <= 9.5) return 'Patient totalement dépendant et grabataire';
    if (score === 10.0) return 'Décès lié à la SEP';
    return '';
};

const smokingOptions = ['Non', 'Fumeur actif', 'Sevré', 'Occasionnel'];
const alcoholOptions = ['Non', 'Occasionnel', 'Régulier', 'Sevré'];

const submit = () => {
    form.put(`/patients/${props.patient.id}/disease-profile`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Histoire & Antécédents — ${patient.full_name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto w-full">
        <!-- Top Navigation Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <Button
                    as-child
                    variant="ghost"
                    size="icon"
                    class="size-8 text-muted-foreground hover:text-foreground shrink-0"
                >
                    <Link :href="patientsShow.url({ patient: patient.id })" title="Retour au dossier patient">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            Histoire de la Maladie & Antécédents
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
                        Dossier de <strong class="text-foreground">{{ patient.full_name }}</strong> • {{ patient.gender === 'female' ? 'Femme' : 'Homme' }}
                        <span v-if="patient.age"> • {{ patient.age }} ans (Né(e) le {{ patient.birth_date_formatted }})</span>
                    </p>
                </div>
            </div>

            <!-- Top Actions -->
            <div class="flex items-center gap-2.5 self-start sm:self-auto">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link :href="patientsShow.url({ patient: patient.id })">
                        Annuler
                    </Link>
                </Button>
                <Button
                    type="button"
                    size="sm"
                    class="gap-1.5"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                    <Save v-else class="size-4" />
                    <span>Enregistrer</span>
                </Button>
            </div>
        </div>

        <!-- Global Error Alert -->
        <div
            v-if="form.hasErrors"
            class="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive"
        >
            <AlertCircle class="size-5 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold">Des erreurs ont été détectées dans le formulaire :</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-xs">
                    <li v-for="(error, key) in form.errors" :key="key">
                        {{ error }}
                    </li>
                </ul>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- SECTION 1: PROFIL CLINIQUE DE LA SEP -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Brain class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Profil Clinique & Diagnostic de la SEP
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Chronologie d'apparition, phénotype clinique et critères diagnostiques
                                </CardDescription>
                            </div>
                        </div>
                        <Badge variant="outline" class="font-mono text-xs">
                            Module 2
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <!-- Row 1: Début des symptômes & Diagnostic (Date OU Âge au choix) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                        <!-- 1er symptôme (Date et/ou Âge) -->
                        <div class="lg:col-span-5 p-3.5 rounded-lg border border-border/80 bg-muted/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <Clock class="size-3.5 text-primary" />
                                    <span>1. Début des premiers symptômes</span>
                                </Label>
                                <span v-if="form.first_symptom_age_estimated !== null && form.first_symptom_age_estimated !== undefined" class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded">
                                    {{ form.first_symptom_age_estimated }} ans
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1.5">
                                    <Label for="first_symptom_date" class="text-xs text-foreground font-medium">
                                        Date exacte (si connue)
                                    </Label>
                                    <Input
                                        id="first_symptom_date"
                                        v-model="form.first_symptom_date"
                                        type="date"
                                        class="text-xs"
                                    />
                                    <InputError :message="form.errors.first_symptom_date" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="first_symptom_age_estimated" class="text-xs text-foreground font-medium">
                                        Âge au début (ans)
                                    </Label>
                                    <Input
                                        id="first_symptom_age_estimated"
                                        :model-value="form.first_symptom_age_estimated ?? undefined"
                                        @update:model-value="(val: string | number) => (form.first_symptom_age_estimated = val === '' ? null : Number(val))"
                                        type="number"
                                        min="0"
                                        max="120"
                                        placeholder="ex: 28"
                                        class="font-mono text-xs"
                                    />
                                    <InputError :message="form.errors.first_symptom_age_estimated" />
                                </div>
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Vous pouvez saisir soit la date exacte, soit directement l'âge au début de la maladie si la date précise n'est pas connue.
                            </p>
                        </div>

                        <!-- Confirmation Diagnostique (Date et/ou Âge) -->
                        <div class="lg:col-span-5 p-3.5 rounded-lg border border-border/80 bg-muted/20 space-y-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <CheckCircle2 class="size-3.5 text-primary" />
                                    <span>2. Confirmation du diagnostic</span>
                                </Label>
                                <span v-if="form.diagnosis_age_estimated !== null && form.diagnosis_age_estimated !== undefined" class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded">
                                    {{ form.diagnosis_age_estimated }} ans
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1.5">
                                    <Label for="diagnosis_date" class="text-xs text-foreground font-medium">
                                        Date exacte (si connue)
                                    </Label>
                                    <Input
                                        id="diagnosis_date"
                                        v-model="form.diagnosis_date"
                                        type="date"
                                        class="text-xs"
                                    />
                                    <InputError :message="form.errors.diagnosis_date" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="diagnosis_age_estimated" class="text-xs text-foreground font-medium">
                                        Âge au diagnostic (ans)
                                    </Label>
                                    <Input
                                        id="diagnosis_age_estimated"
                                        :model-value="form.diagnosis_age_estimated ?? undefined"
                                        @update:model-value="(val: string | number) => (form.diagnosis_age_estimated = val === '' ? null : Number(val))"
                                        type="number"
                                        min="0"
                                        max="120"
                                        placeholder="ex: 30"
                                        class="font-mono text-xs"
                                    />
                                    <InputError :message="form.errors.diagnosis_age_estimated" />
                                </div>
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Confirmation formelle de la SEP. L'âge est calculé depuis la date, ou saisissable directement.
                            </p>
                        </div>

                        <!-- Délai diagnostique calculé -->
                        <div class="lg:col-span-2 p-3.5 rounded-lg border border-border/80 bg-muted/20 space-y-3 flex flex-col justify-between">
                            <div>
                                <Label for="prediagnostic_duration_months" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <Activity class="size-3.5 text-primary" />
                                    <span>Délai (Mois)</span>
                                </Label>
                                <div class="mt-2.5">
                                    <Input
                                        id="prediagnostic_duration_months"
                                        :model-value="form.prediagnostic_duration_months ?? undefined"
                                        @update:model-value="(val: string | number) => (form.prediagnostic_duration_months = val === '' ? null : Number(val))"
                                        type="number"
                                        min="0"
                                        placeholder="Auto..."
                                        class="font-mono text-sm font-semibold"
                                    />
                                    <InputError :message="form.errors.prediagnostic_duration_months" />
                                </div>
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Calculé automatiquement ou renseigné manuellement.
                            </p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Row 2: Formes Cliniques (Initiale & Actuelle) -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Forme initiale -->
                        <div class="space-y-2">
                            <Label for="initial_clinical_form" class="text-sm font-medium">
                                Forme Clinique Initiale <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.initial_clinical_form">
                                <SelectTrigger id="initial_clinical_form" class="w-full">
                                    <SelectValue placeholder="Sélectionner la forme initiale" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="item in clinical_forms"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }} — {{ item.full_label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.initial_clinical_form" />
                        </div>

                        <!-- Forme actuelle -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="current_clinical_form" class="text-sm font-medium">
                                    Forme Clinique Actuelle <span class="text-destructive">*</span>
                                </Label>
                                <button
                                    type="button"
                                    class="text-[11px] font-medium text-primary hover:underline cursor-pointer"
                                    @click="copyInitialToCurrent"
                                >
                                    Identique à l'initiale
                                </button>
                            </div>
                            <Select v-model="form.current_clinical_form">
                                <SelectTrigger id="current_clinical_form" class="w-full">
                                    <SelectValue placeholder="Sélectionner la forme actuelle" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="item in clinical_forms"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }} — {{ item.full_label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.current_clinical_form" />
                        </div>
                    </div>

                    <!-- Row 3: Symptomatologie inaugurale avec puces rapides -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="first_symptoms_description" class="text-sm font-medium">
                                Symptômes Inauguraux & Mode d'Installation
                            </Label>
                            <span class="text-[11px] text-muted-foreground">Cliquez sur un symptôme pour l'ajouter :</span>
                        </div>
                        <!-- Symptom Chips for quick entry -->
                        <div class="flex flex-wrap gap-1.5 py-1">
                            <button
                                v-for="chip in commonSymptomChips"
                                :key="chip"
                                type="button"
                                class="inline-flex items-center rounded-md border border-border bg-muted/40 px-2 py-0.5 text-xs text-muted-foreground hover:bg-primary/10 hover:text-primary hover:border-primary/40 transition-colors cursor-pointer"
                                @click="appendSymptom(chip)"
                            >
                                + {{ chip }}
                            </button>
                        </div>
                        <Textarea
                            id="first_symptoms_description"
                            v-model="form.first_symptoms_description"
                            placeholder="ex: NORB droite typique avec baisse d'acuité visuelle et douleurs aux mouvements oculaires..."
                            class="min-h-20"
                        />
                        <InputError :message="form.errors.first_symptoms_description" />
                    </div>

                    <!-- Row 4: Critères Diagnostiques & EDSS Initial -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="diagnostic_criteria" class="text-sm font-medium">
                                    Critères Diagnostiques Retenus
                                </Label>
                            </div>
                            <div class="flex flex-wrap gap-1.5 py-1">
                                <button
                                    v-for="c in commonCriteria"
                                    :key="c"
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-border bg-muted/40 px-2 py-0.5 text-[11px] text-muted-foreground hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer"
                                    @click="setCriteria(c)"
                                >
                                    {{ c }}
                                </button>
                            </div>
                            <Input
                                id="diagnostic_criteria"
                                v-model="form.diagnostic_criteria"
                                placeholder="ex: Critères de McDonald 2017"
                            />
                            <InputError :message="form.errors.diagnostic_criteria" />
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <Label for="initial_edss" class="text-sm font-medium">
                                    Score EDSS Initial au Diagnostic
                                </Label>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] text-muted-foreground font-mono">0.0 à 10.0 (par pas de 0.5)</span>
                                    <button
                                        v-if="form.initial_edss !== '' && form.initial_edss !== null"
                                        type="button"
                                        @click="form.initial_edss = ''"
                                        class="text-[11px] text-muted-foreground hover:text-destructive underline decoration-dotted transition-colors"
                                    >
                                        Effacer
                                    </button>
                                </div>
                            </div>

                            <!-- Input + Select Combo: doctor can type freely OR select from dropdown -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="relative">
                                    <Input
                                        id="initial_edss"
                                        v-model="form.initial_edss"
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="10"
                                        placeholder="Saisie libre (ex: 2.0)"
                                        class="font-mono text-sm font-semibold"
                                    />
                                    <span class="absolute right-2.5 top-2 text-xs text-muted-foreground pointer-events-none font-mono">
                                        / 10
                                    </span>
                                </div>

                                <Select v-model="form.initial_edss">
                                    <SelectTrigger class="w-full font-mono text-sm">
                                        <SelectValue placeholder="Ou choisir dans la liste..." />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-60">
                                        <SelectItem
                                            v-for="score in edssOptions"
                                            :key="score"
                                            :value="score"
                                        >
                                            EDSS {{ score }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Quick Preset Benchmark Pills -->
                            <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                <span class="text-[11px] font-medium text-muted-foreground mr-1">Repères fréquents :</span>
                                <button
                                    v-for="preset in ['0.0', '1.5', '2.0', '3.0', '4.0', '6.0', '6.5', '7.0']"
                                    :key="preset"
                                    type="button"
                                    @click="form.initial_edss = preset"
                                    :class="[
                                        'px-2 py-0.5 rounded text-xs font-mono font-medium border transition-colors',
                                        form.initial_edss === preset || Number(form.initial_edss) === Number(preset)
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'bg-muted/50 hover:bg-muted text-foreground border-border/70'
                                    ]"
                                >
                                    {{ preset }}
                                </button>
                            </div>

                            <!-- Real-time Clinical Interpretation Banner -->
                            <div
                                v-if="getEdssInterpretation(form.initial_edss)"
                                class="p-2.5 rounded-lg bg-primary/5 border border-primary/20 text-xs flex items-center gap-2 text-foreground"
                            >
                                <Activity class="size-3.5 text-primary shrink-0" />
                                <div>
                                    <span class="font-bold font-mono text-primary mr-1.5">EDSS {{ form.initial_edss }} :</span>
                                    <span>{{ getEdssInterpretation(form.initial_edss) }}</span>
                                </div>
                            </div>

                            <InputError :message="form.errors.initial_edss" />
                            <p class="text-[11px] text-muted-foreground">
                                Échelle d'état d'invalidité élargie de Kurtzke au moment du diagnostic.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- SECTION 2: ANTÉCÉDENTS PERSONNELS & HABITUDES -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <History class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Antécédents Personnels & Profil Toxique
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Terrain médical personnel, antécédents chirurgicaux et habitudes de vie
                                </CardDescription>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="medical_history" class="text-sm font-medium">
                                Antécédents Médicaux Personnels
                            </Label>
                            <Textarea
                                id="medical_history"
                                v-model="form.medical_history"
                                placeholder="HTA, Diabète, Dysthyroïdie, Psoriasis, Allergies..."
                                class="min-h-20"
                            />
                            <InputError :message="form.errors.medical_history" />
                        </div>

                        <div class="space-y-2">
                            <Label for="surgical_history" class="text-sm font-medium">
                                Antécédents Chirurgicaux
                            </Label>
                            <Textarea
                                id="surgical_history"
                                v-model="form.surgical_history"
                                placeholder="Interventions passées, appendicectomie, césarienne..."
                                class="min-h-20"
                            />
                            <InputError :message="form.errors.surgical_history" />
                        </div>
                    </div>

                    <!-- Toxiques (Tabac, Alcool, Autres) -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 pt-1">
                        <div class="space-y-2">
                            <Label for="smoking" class="text-sm font-medium">
                                Tabagisme
                            </Label>
                            <Select v-model="form.smoking">
                                <SelectTrigger id="smoking" class="w-full">
                                    <SelectValue placeholder="Statut tabagique" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opt in smokingOptions" :key="opt" :value="opt">
                                        {{ opt }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.smoking" />
                        </div>

                        <div class="space-y-2">
                            <Label for="alcohol" class="text-sm font-medium">
                                Consommation d'Alcool
                            </Label>
                            <Select v-model="form.alcohol">
                                <SelectTrigger id="alcohol" class="w-full">
                                    <SelectValue placeholder="Alcool" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="opt in alcoholOptions" :key="opt" :value="opt">
                                        {{ opt }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.alcohol" />
                        </div>

                        <div class="space-y-2">
                            <Label for="toxic_other" class="text-sm font-medium">
                                Autres Toxiques / Expositions
                            </Label>
                            <Input
                                id="toxic_other"
                                v-model="form.toxic_other"
                                placeholder="Cannabis, Solvants professionnels..."
                            />
                            <InputError :message="form.errors.toxic_other" />
                        </div>
                    </div>

                    <!-- Profil Gynéco-Obstétrique si Femme -->
                    <div v-if="patient.gender === 'female'" class="rounded-xl border border-border/70 bg-muted/20 p-4 space-y-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-foreground">
                            <HeartPulse class="size-4 text-primary" />
                            <span>Profil Gynéco-Obstétrique (Spécifique Femme)</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <Label for="pregnancies_count" class="text-xs font-medium">
                                    Grossesses (Gravida)
                                </Label>
                                <Input
                                    id="pregnancies_count"
                                    v-model="form.pregnancies_count"
                                    type="number"
                                    min="0"
                                    max="20"
                                />
                                <InputError :message="form.errors.pregnancies_count" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="deliveries_count" class="text-xs font-medium">
                                    Accouchements (Para)
                                </Label>
                                <Input
                                    id="deliveries_count"
                                    v-model="form.deliveries_count"
                                    type="number"
                                    min="0"
                                    max="20"
                                />
                                <InputError :message="form.errors.deliveries_count" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="children_count" class="text-xs font-medium">
                                    Enfants vivants
                                </Label>
                                <Input
                                    id="children_count"
                                    v-model="form.children_count"
                                    type="number"
                                    min="0"
                                    max="20"
                                />
                                <InputError :message="form.errors.children_count" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="gyneco_notes" class="text-xs font-medium">
                                Notes Gynéco / Contraception / Allaitement
                            </Label>
                            <Input
                                id="gyneco_notes"
                                v-model="form.gyneco_notes"
                                placeholder="Mode de contraception, fausses couches spontanées, projet de grossesse..."
                            />
                            <InputError :message="form.errors.gyneco_notes" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- SECTION 3: ANTÉCÉDENTS FAMILIAUX & GÉNÉTIQUES -->
            <Card>
                <CardHeader class="pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Users class="size-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-semibold">
                                    Antécédents Familiaux & Auto-Immunité
                                </CardTitle>
                                <CardDescription class="text-xs">
                                    Cas familiaux de SEP au premier degré et maladies auto-immunes
                                </CardDescription>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- SEP 1er degré -->
                        <div class="flex items-start gap-3 p-3.5 rounded-xl border border-border bg-card">
                            <Checkbox
                                id="family_history_neurological"
                                :checked="form.family_history_neurological"
                                @update:checked="(val: boolean | 'indeterminate') => (form.family_history_neurological = Boolean(val))"
                                class="mt-0.5"
                            />
                            <div class="space-y-1 leading-none">
                                <Label
                                    for="family_history_neurological"
                                    class="text-sm font-medium cursor-pointer"
                                >
                                    SEP au 1er degré
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Père, mère, frère, sœur ou enfant atteint de SEP.
                                </p>
                            </div>
                        </div>

                        <!-- Maladies auto-immunes familiales -->
                        <div class="flex items-start gap-3 p-3.5 rounded-xl border border-border bg-card">
                            <Checkbox
                                id="family_history_autoimmune"
                                :checked="form.family_history_autoimmune"
                                @update:checked="(val: boolean | 'indeterminate') => (form.family_history_autoimmune = Boolean(val))"
                                class="mt-0.5"
                            />
                            <div class="space-y-1 leading-none">
                                <Label
                                    for="family_history_autoimmune"
                                    class="text-sm font-medium cursor-pointer"
                                >
                                    Maladies Auto-immunes Familiales
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Lupus, Gougerot, Thyroïdite, Diabète T1, Crohn...
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="family_history_details" class="text-sm font-medium">
                            Détails & Degré de Parenté
                        </Label>
                        <Textarea
                            id="family_history_details"
                            v-model="form.family_history_details"
                            placeholder="Préciser le lien de parenté (ex: sœur aînée avec SEP rémittente, tante maternelle avec Basedow)..."
                            class="min-h-16"
                        />
                        <InputError :message="form.errors.family_history_details" />
                    </div>

                    <div class="space-y-2">
                        <Label for="notes" class="text-sm font-medium">
                            Remarques & Commentaires Particuliers
                        </Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Toute observation médicale ou génétique utile pour le suivi..."
                            class="min-h-20"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </CardContent>
            </Card>

            <!-- Bottom Sticky Action Bar -->
            <div class="sticky bottom-4 z-10 flex items-center justify-between rounded-xl border border-border bg-card/95 p-4 shadow-lg backdrop-blur-sm">
                <div class="text-xs text-muted-foreground">
                    Modifications apportées au dossier : <span class="font-mono font-medium text-foreground">{{ patient.dossier_number }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        as-child
                        variant="outline"
                        type="button"
                    >
                        <Link :href="patientsShow.url({ patient: patient.id })">
                            Annuler
                        </Link>
                    </Button>
                    <Button
                        type="submit"
                        class="gap-2 min-w-44"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <Save v-else class="size-4" />
                        <span>Enregistrer le Profil</span>
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
