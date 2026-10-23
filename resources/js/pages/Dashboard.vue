<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Brain,
    Calendar,
    ChevronRight,
    Crosshair,
    FileText,
    HeartPulse,
    Info,
    Layers,
    Pill,
    Plus,
    Stethoscope,
    TrendingUp,
    UserCheck,
    UserPlus,
    Users,
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
import { dashboard } from '@/routes';
import {
    create as patientsCreate,
    index as patientsIndex,
    show as patientsShow,
} from '@/routes/patients';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: dashboard(),
            },
        ],
    },
});

type Kpis = {
    total_patients: number;
    active_patients: number;
    new_this_year: number;
    average_age: number;
    total_relapses: number;
    total_mri_exams: number;
    sex_ratio: {
        female_count: number;
        female_percentage: number;
        male_count: number;
        male_percentage: number;
        ratio: number;
    };
};

type ClinicalFormItem = {
    form: string;
    label: string;
    full_label: string;
    count: number;
    percentage: number;
};

type TreatmentItem = {
    molecule: string;
    count: number;
    percentage: number;
};

type EdssItem = {
    label: string;
    description: string;
    count: number;
};

type RecentPatient = {
    id: number;
    dossier_number: string;
    full_name: string;
    gender: string;
    age: number | null;
    city: string | null;
    status: string;
    status_label: string;
    status_color: string;
    clinical_form: string | null;
    created_at: string | null;
};

type Props = {
    kpis: Kpis;
    clinical_forms: ClinicalFormItem[];
    top_treatments: TreatmentItem[];
    edss_distribution: EdssItem[];
    recent_patients: RecentPatient[];
};

const props = defineProps<Props>();

// Palette for clinical forms (subtle, accessible, medical standard)
const formColors: Record<string, { stroke: string; bg: string; text: string }> = {
    RR: {
        stroke: '#2563eb', // blue-600
        bg: 'bg-blue-600',
        text: 'text-blue-600 dark:text-blue-400',
    },
    SP: {
        stroke: '#d97706', // amber-600
        bg: 'bg-amber-600',
        text: 'text-amber-600 dark:text-amber-400',
    },
    PP: {
        stroke: '#dc2626', // red-600
        bg: 'bg-red-600',
        text: 'text-red-600 dark:text-red-400',
    },
    CIS: {
        stroke: '#059669', // emerald-600
        bg: 'bg-emerald-600',
        text: 'text-emerald-600 dark:text-emerald-400',
    },
    RIS: {
        stroke: '#7c3aed', // violet-600
        bg: 'bg-violet-600',
        text: 'text-violet-600 dark:text-violet-400',
    },
};

const defaultColor = {
    stroke: '#64748b',
    bg: 'bg-slate-500',
    text: 'text-slate-600 dark:text-slate-400',
};

// SVG Donut calculation
const donutRadius = 60;
const donutCircumference = 2 * Math.PI * donutRadius; // ~376.99

const activeSegment = ref<ClinicalFormItem | null>(null);

const donutSegments = computed(() => {
    const total = props.clinical_forms.reduce((sum, item) => sum + item.count, 0);
    if (total === 0) {
        return [];
    }

    let accumulatedAngle = 0;
    return props.clinical_forms.map((item) => {
        const ratio = item.count / total;
        const length = ratio * donutCircumference;
        const offset = -accumulatedAngle;
        accumulatedAngle += length;

        const color = formColors[item.form] || defaultColor;

        return {
            ...item,
            strokeDasharray: `${length} ${donutCircumference}`,
            strokeDashoffset: offset,
            color,
        };
    });
});

const totalEdssRecorded = computed(() => {
    return props.edss_distribution.reduce((acc, curr) => acc + curr.count, 0);
});

const maxTreatmentCount = computed(() => {
    if (props.top_treatments.length === 0) return 1;
    return Math.max(...props.top_treatments.map((t) => t.count), 1);
});
</script>

<template>
    <Head title="Tableau de Bord — Registre SEP" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- Top Hero Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <Brain class="size-4" />
                    </span>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        Service de Neurologie
                    </h1>
                    <span class="rounded-md border border-border bg-muted/60 px-2 py-0.5 font-mono text-xs font-medium text-muted-foreground">
                        Registre SEP
                    </span>
                </div>
                <p class="text-sm text-muted-foreground mt-1">
                    Plateforme de suivi longitudinal et de gestion clinique des patients atteints de Sclérose En Plaques (GST TTA).
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex items-center gap-2.5">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                >
                    <Link :href="patientsIndex.url()">
                        <Users class="size-3.5" />
                        <span>Tous les Dossiers</span>
                    </Link>
                </Button>
                <Button
                    as-child
                    size="sm"
                    class="gap-1.5"
                >
                    <Link :href="patientsCreate.url()">
                        <UserPlus class="size-3.5" />
                        <span>Nouveau Dossier</span>
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Metric KPI Cards Row -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Total Patients -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-semibold uppercase tracking-wider">File Active Totale</span>
                    <Users class="size-4 text-primary" />
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-foreground font-mono">
                        {{ kpis.total_patients }}
                    </span>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        {{ kpis.active_patients }} actifs
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    +{{ kpis.new_this_year }} nouveaux cas enregistrés cette année
                </p>
            </div>

            <!-- 2. Sex-Ratio F/H -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-semibold uppercase tracking-wider">Sex-Ratio (F/H)</span>
                    <Users class="size-4 text-primary" />
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-foreground font-mono">
                        {{ kpis.sex_ratio.ratio }} : 1
                    </span>
                    <span class="text-xs text-muted-foreground">
                        Femmes / Hommes
                    </span>
                </div>
                <!-- Dual-colored progress bar -->
                <div class="mt-2 flex h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        class="bg-rose-500 transition-all duration-300"
                        :style="{ width: `${kpis.sex_ratio.female_percentage}%` }"
                        :title="`Femmes : ${kpis.sex_ratio.female_count} (${kpis.sex_ratio.female_percentage}%)`"
                    />
                    <div
                        class="bg-sky-500 transition-all duration-300"
                        :style="{ width: `${kpis.sex_ratio.male_percentage}%` }"
                        :title="`Hommes : ${kpis.sex_ratio.male_count} (${kpis.sex_ratio.male_percentage}%)`"
                    />
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-muted-foreground">
                    <span class="flex items-center gap-1">
                        <span class="inline-block size-1.5 rounded-full bg-rose-500" />
                        {{ kpis.sex_ratio.female_count }} Femmes ({{ kpis.sex_ratio.female_percentage }}%)
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="inline-block size-1.5 rounded-full bg-sky-500" />
                        {{ kpis.sex_ratio.male_count }} Hommes ({{ kpis.sex_ratio.male_percentage }}%)
                    </span>
                </div>
            </div>

            <!-- 3. Âge Moyen de la Cohorte -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-semibold uppercase tracking-wider">Âge Moyen</span>
                    <Calendar class="size-4 text-primary" />
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tracking-tight text-foreground font-mono">
                        {{ kpis.average_age }}
                    </span>
                    <span class="text-sm font-medium text-foreground">ans</span>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Âge moyen des patients dans la cohorte active
                </p>
            </div>

            <!-- 4. Suivi des Événements & IRM -->
            <div class="rounded-xl border border-border bg-card p-5 shadow-2xs">
                <div class="flex items-center justify-between text-muted-foreground">
                    <span class="text-xs font-semibold uppercase tracking-wider">Événements & IRM</span>
                    <Crosshair class="size-4 text-primary" />
                </div>
                <div class="mt-3 flex items-baseline gap-3">
                    <div>
                        <span class="text-2xl font-bold font-mono text-foreground">{{ kpis.total_relapses }}</span>
                        <span class="text-xs text-muted-foreground block -mt-1">Poussées</span>
                    </div>
                    <span class="text-border text-2xl font-light">|</span>
                    <div>
                        <span class="text-2xl font-bold font-mono text-foreground">{{ kpis.total_mri_exams }}</span>
                        <span class="text-xs text-muted-foreground block -mt-1">Examens IRM</span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Totalité des examens archivés dans la base
                </p>
            </div>
        </div>

        <!-- Main Charts Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left Chart (7 cols): Répartition des Formes Cliniques SEP -->
            <Card class="lg:col-span-7">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <Brain class="size-4 text-primary" />
                                <span>Répartition des Formes Cliniques</span>
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Typologie phénotypique de la cohorte selon la classification SEP
                            </CardDescription>
                        </div>
                        <Badge variant="outline" class="font-mono text-[11px]">
                            {{ kpis.total_patients }} patients
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                        <!-- Interactive SVG Donut Chart -->
                        <div class="sm:col-span-6 flex flex-col items-center justify-center relative py-2">
                            <svg
                                class="size-48 -rotate-90 transform"
                                viewBox="0 0 160 160"
                            >
                                <!-- Background empty ring -->
                                <circle
                                    cx="80"
                                    cy="80"
                                    :r="donutRadius"
                                    fill="transparent"
                                    stroke="currentColor"
                                    stroke-width="18"
                                    class="text-muted/40"
                                />

                                <!-- Segments -->
                                <circle
                                    v-for="segment in donutSegments"
                                    :key="segment.form"
                                    cx="80"
                                    cy="80"
                                    :r="donutRadius"
                                    fill="transparent"
                                    :stroke="segment.color.stroke"
                                    stroke-width="18"
                                    :stroke-dasharray="segment.strokeDasharray"
                                    :stroke-dashoffset="segment.strokeDashoffset"
                                    stroke-linecap="butt"
                                    class="transition-all duration-300 cursor-pointer hover:opacity-85"
                                    @mouseenter="activeSegment = segment"
                                    @mouseleave="activeSegment = null"
                                />
                            </svg>

                            <!-- Donut Center Label -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                    {{ activeSegment ? activeSegment.label : 'Total' }}
                                </span>
                                <span class="text-2xl font-extrabold font-mono text-foreground">
                                    {{ activeSegment ? activeSegment.count : kpis.total_patients }}
                                </span>
                                <span class="text-[11px] font-medium text-primary">
                                    {{ activeSegment ? `${activeSegment.percentage}%` : 'Cohorte SEP' }}
                                </span>
                            </div>
                        </div>

                        <!-- Legend and breakdown details -->
                        <div class="sm:col-span-6 space-y-2.5">
                            <div
                                v-for="item in clinical_forms"
                                :key="item.form"
                                class="flex items-center justify-between p-2 rounded-lg border border-border/50 bg-muted/20 hover:bg-muted/40 transition-colors cursor-pointer text-xs"
                                @mouseenter="activeSegment = item"
                                @mouseleave="activeSegment = null"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span
                                        class="size-2.5 rounded-full shrink-0"
                                        :class="formColors[item.form]?.bg || 'bg-slate-500'"
                                    />
                                    <div class="truncate">
                                        <span class="font-semibold text-foreground mr-1.5">{{ item.label }}</span>
                                        <span class="text-muted-foreground text-[11px] hidden sm:inline truncate">{{ item.full_label }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 font-mono">
                                    <span class="font-semibold text-foreground">{{ item.count }}</span>
                                    <span class="text-muted-foreground text-[11px] w-10 text-right">
                                        {{ item.percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Right Chart (5 cols): Degrés d'Incapacité EDSS (Kurtzke) -->
            <Card class="lg:col-span-5">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <Activity class="size-4 text-primary" />
                                <span>Échelle d'Handicap (EDSS)</span>
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Distribution des scores d'invalidité neurologique
                            </CardDescription>
                        </div>
                        <Badge variant="outline" class="font-mono text-[11px]">
                            Kurtzke
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-for="edss in edss_distribution"
                        :key="edss.label"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-foreground">{{ edss.label }}</span>
                            <span class="font-mono font-semibold text-foreground">
                                {{ edss.count }} patients
                            </span>
                        </div>
                        <!-- Progress Bar -->
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full bg-primary transition-all duration-300 rounded-full"
                                :style="{
                                    width: `${totalEdssRecorded > 0 ? (edss.count / totalEdssRecorded) * 100 : 0}%`,
                                }"
                            />
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            {{ edss.description }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Second Row: Top Treatments & Recent Patients -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Top Treatments DMT (5 cols) -->
            <Card class="lg:col-span-5">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <Pill class="size-4 text-primary" />
                                <span>Traitements de Fond en Cours</span>
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Molécules modificatrices de la maladie (DMT)
                            </CardDescription>
                        </div>
                        <Badge variant="outline" class="text-xs">
                            En cours
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3.5">
                    <div v-if="top_treatments.length > 0" class="space-y-3">
                        <div
                            v-for="treatment in top_treatments"
                            :key="treatment.molecule"
                            class="space-y-1.5"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-foreground">{{ treatment.molecule }}</span>
                                <div class="flex items-center gap-2 font-mono">
                                    <span class="font-semibold text-foreground">{{ treatment.count }}</span>
                                    <span class="text-muted-foreground text-[11px]">({{ treatment.percentage }}%)</span>
                                </div>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full bg-emerald-600 dark:bg-emerald-500 transition-all duration-300 rounded-full"
                                    :style="{
                                        width: `${(treatment.count / maxTreatmentCount) * 100}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground text-xs">
                        <Pill class="size-6 mx-auto mb-1 text-muted-foreground/50" />
                        <p>Aucun traitement de fond actif enregistré</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Patient Files (7 cols) -->
            <Card class="lg:col-span-7">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold flex items-center gap-2">
                                <FileText class="size-4 text-primary" />
                                <span>Derniers Dossiers Enregistrés</span>
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Accès rapide aux derniers patients admis dans le registre
                            </CardDescription>
                        </div>
                        <Button
                            as-child
                            variant="ghost"
                            size="sm"
                            class="gap-1 text-xs text-primary"
                        >
                            <Link :href="patientsIndex.url()">
                                <span>Voir tout</span>
                                <ArrowRight class="size-3.5" />
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-muted/30">
                            <TableRow>
                                <TableHead class="text-xs font-semibold">N° Dossier</TableHead>
                                <TableHead class="text-xs font-semibold">Patient</TableHead>
                                <TableHead class="text-xs font-semibold">Forme</TableHead>
                                <TableHead class="text-xs font-semibold">Statut</TableHead>
                                <TableHead class="text-right text-xs font-semibold">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="patient in recent_patients"
                                :key="patient.id"
                                class="hover:bg-muted/30 transition-colors"
                            >
                                <TableCell class="font-mono text-xs font-medium">
                                    {{ patient.dossier_number }}
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium text-xs text-foreground">
                                        {{ patient.full_name }}
                                    </div>
                                    <div class="text-[11px] text-muted-foreground">
                                        {{ patient.gender === 'female' ? 'Femme' : 'Homme' }}
                                        <span v-if="patient.age">({{ patient.age }} ans)</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        v-if="patient.clinical_form"
                                        variant="outline"
                                        class="font-mono text-[10px]"
                                    >
                                        {{ patient.clinical_form }}
                                    </Badge>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
                                            patient.status_color,
                                        ]"
                                    >
                                        {{ patient.status_label }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        class="size-7 p-0"
                                        title="Consulter le dossier"
                                    >
                                        <Link :href="patientsShow.url({ patient: patient.id })">
                                            <ChevronRight class="size-3.5" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="recent_patients.length === 0">
                                <TableCell colspan="5" class="h-24 text-center text-xs text-muted-foreground">
                                    Aucun patient enregistré pour le moment.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
