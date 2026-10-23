<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    BarChart3,
    Check,
    ChevronDown,
    ChevronUp,
    Dna,
    Download,
    Eye,
    FileCheck,
    FileSpreadsheet,
    FileText,
    Filter,
    FlaskConical,
    Layers,
    Pill,
    RefreshCw,
    Search,
    Shield,
    ShieldCheck,
    Sparkles,
    Stethoscope,
    TestTube,
    UploadCloud,
    Users,
    X,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import PatientImportDialog from '@/pages/Patients/Partials/PatientImportDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';

type PatientRow = {
    id: number;
    dossier_number: string;
    pseudonym: string;
    full_name: string;
    first_name: string;
    last_name: string;
    gender: 'male' | 'female';
    gender_label: string;
    age: number;
    city: string | null;
    clinical_form_label: string;
    clinical_form_code: string | null;
    latest_edss: string | number;
    relapses_count: number;
    active_treatment: string;
    treatment_status: string | null;
    boc_status: string | null;
    mri_contrast: string | null;
};

type PaginationLinks = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedPatients = {
    data: PatientRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLinks[];
};

type Statistics = {
    total_patients: number;
    female_count: number;
    male_count: number;
    female_ratio: string;
    average_age: number;
    clinical_forms: {
        remittente: number;
        secondaire: number;
        primaire: number;
    };
    boc_positive_rate: number;
    top_treatment: string;
    mri_total_count: number;
    mri_sep_protocol_count: number;
    mri_sep_protocol_rate: number;
};

type FilterState = {
    search: string;
    gender: string;
    age_min: string;
    age_max: string;
    city: string;
    clinical_form: string;
    age_onset_min: string;
    age_onset_max: string;
    age_diagnosis_min: string;
    age_diagnosis_max: string;
    edss_min: string;
    edss_max: string;
    relapses_min: string;
    relapses_max: string;
    mri_region: string;
    mri_contrast: string;
    mri_sep_protocol: string;
    boc: string;
    anti_nmo: string;
    anti_mog: string;
    treatment_molecule: string;
    treatment_status: string;
    vitamin_d: string;
    antidepressants: string;
    pseudonymize: boolean;
};

type Props = {
    patients: PaginatedPatients;
    statistics: Statistics;
    filters: FilterState;
    availableMolecules: string[];
    availableCities: string[];
};

const props = defineProps<Props>();

const isImportDialogOpen = ref(false);

const formFilters = reactive<FilterState>({
    search: props.filters.search || '',
    gender: props.filters.gender || 'all',
    age_min: props.filters.age_min || '',
    age_max: props.filters.age_max || '',
    city: props.filters.city || 'all',
    clinical_form: props.filters.clinical_form || 'all',
    age_onset_min: props.filters.age_onset_min || '',
    age_onset_max: props.filters.age_onset_max || '',
    age_diagnosis_min: props.filters.age_diagnosis_min || '',
    age_diagnosis_max: props.filters.age_diagnosis_max || '',
    edss_min: props.filters.edss_min || '',
    edss_max: props.filters.edss_max || '',
    relapses_min: props.filters.relapses_min || '',
    relapses_max: props.filters.relapses_max || '',
    mri_region: props.filters.mri_region || 'all',
    mri_contrast: props.filters.mri_contrast || 'all',
    mri_sep_protocol: props.filters.mri_sep_protocol || 'all',
    boc: props.filters.boc || 'all',
    anti_nmo: props.filters.anti_nmo || 'all',
    anti_mog: props.filters.anti_mog || 'all',
    treatment_molecule: props.filters.treatment_molecule || 'all',
    treatment_status: props.filters.treatment_status || 'all',
    vitamin_d: props.filters.vitamin_d || 'all',
    antidepressants: props.filters.antidepressants || 'all',
    pseudonymize: props.filters.pseudonymize ?? true,
});

const isFilterExpanded = ref(true);

const hasActiveFilters = computed(() => {
    return !!(
        formFilters.search ||
        (formFilters.gender && formFilters.gender !== 'all') ||
        formFilters.age_min ||
        formFilters.age_max ||
        (formFilters.city && formFilters.city !== 'all') ||
        (formFilters.clinical_form && formFilters.clinical_form !== 'all') ||
        formFilters.age_onset_min ||
        formFilters.age_onset_max ||
        formFilters.age_diagnosis_min ||
        formFilters.age_diagnosis_max ||
        formFilters.edss_min ||
        formFilters.edss_max ||
        formFilters.relapses_min ||
        formFilters.relapses_max ||
        (formFilters.mri_region && formFilters.mri_region !== 'all') ||
        (formFilters.mri_contrast && formFilters.mri_contrast !== 'all') ||
        (formFilters.boc && formFilters.boc !== 'all') ||
        (formFilters.anti_nmo && formFilters.anti_nmo !== 'all') ||
        (formFilters.anti_mog && formFilters.anti_mog !== 'all') ||
        (formFilters.treatment_molecule && formFilters.treatment_molecule !== 'all') ||
        (formFilters.treatment_status && formFilters.treatment_status !== 'all') ||
        (formFilters.vitamin_d && formFilters.vitamin_d !== 'all') ||
        (formFilters.antidepressants && formFilters.antidepressants !== 'all')
    );
});

const applyFilters = () => {
    const cleanParams: Record<string, any> = {};

    Object.entries(formFilters).forEach(([key, val]) => {
        if (val !== '' && val !== null && val !== undefined && val !== 'all') {
            cleanParams[key] = val;
        }
    });

    cleanParams.pseudonymize = formFilters.pseudonymize ? 1 : 0;

    router.get('/research', cleanParams, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    formFilters.search = '';
    formFilters.gender = 'all';
    formFilters.age_min = '';
    formFilters.age_max = '';
    formFilters.city = 'all';
    formFilters.clinical_form = 'all';
    formFilters.age_onset_min = '';
    formFilters.age_onset_max = '';
    formFilters.age_diagnosis_min = '';
    formFilters.age_diagnosis_max = '';
    formFilters.edss_min = '';
    formFilters.edss_max = '';
    formFilters.relapses_min = '';
    formFilters.relapses_max = '';
    formFilters.mri_region = 'all';
    formFilters.mri_contrast = 'all';
    formFilters.mri_sep_protocol = 'all';
    formFilters.boc = 'all';
    formFilters.anti_nmo = 'all';
    formFilters.anti_mog = 'all';
    formFilters.treatment_molecule = 'all';
    formFilters.treatment_status = 'all';
    formFilters.vitamin_d = 'all';
    formFilters.antidepressants = 'all';

    applyFilters();
};

const triggerExport = (pseudonymize: boolean) => {
    const params = new URLSearchParams();

    Object.entries(formFilters).forEach(([key, val]) => {
        if (val !== '' && val !== null && val !== undefined && val !== 'all' && key !== 'pseudonymize') {
            params.append(key, String(val));
        }
    });

    params.append('pseudonymize', pseudonymize ? '1' : '0');

    window.location.href = `/research/export?${params.toString()}`;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tableau de bord',
                href: dashboard(),
            },
            {
                title: 'Recherche & Cohortes',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <Head title="Recherche Multicritère & Statistiques SEP" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
        <!-- TOP HEADER & EXPORT ACTIONS -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-2 border-b border-border/60">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary border border-primary/20">
                        <FileSpreadsheet class="size-6" />
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <span>Recherche Multicritère & Statistiques SEP</span>
                            <Badge variant="outline" class="font-mono text-xs">Module 10</Badge>
                        </h1>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Exploration de cohorte, filtrage clinique croisé et export structuré pour SPSS, R et Excel (Loi 09-08 / CNDP)
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Toggle Pseudonymization mode -->
                <button
                    type="button"
                    @click="formFilters.pseudonymize = !formFilters.pseudonymize"
                    :class="[
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors',
                        formFilters.pseudonymize
                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                            : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30',
                    ]"
                    :title="formFilters.pseudonymize ? 'Identifiants pseudonymisés actifs (recommandé recherche)' : 'Mode nominatif actif'"
                >
                    <ShieldCheck v-if="formFilters.pseudonymize" class="size-4" />
                    <Shield v-else class="size-4" />
                    <span>{{ formFilters.pseudonymize ? 'Mode Pseudonymisé' : 'Mode Nominatif' }}</span>
                </button>

                <!-- Import Excel / SPSS -->
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                    @click="isImportDialogOpen = true"
                >
                    <UploadCloud class="size-3.5 text-muted-foreground" />
                    <span>Importer (Excel/SPSS)</span>
                </Button>

                <!-- Export CSV Pseudonymisé -->
                <Button
                    type="button"
                    size="sm"
                    class="gap-1.5 shadow-2xs font-medium"
                    @click="triggerExport(true)"
                >
                    <Download class="size-3.5" />
                    <span>Export Recherche (SPSS/R)</span>
                </Button>

                <!-- Export Nominatif -->
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                    @click="triggerExport(false)"
                >
                    <FileText class="size-3.5 text-muted-foreground" />
                    <span class="hidden sm:inline">Export Nominatif</span>
                </Button>
            </div>
        </div>

        <!-- COHORT KPI STATS BAR (7 METRICS) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <!-- 1. Total Patients -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>Effectif Cohorte</span>
                    <Users class="size-3.5 text-primary" />
                </div>
                <div class="text-2xl font-bold font-mono text-foreground">
                    {{ statistics.total_patients }}
                </div>
                <p class="text-[10px] text-muted-foreground truncate">
                    Patients filtrés
                </p>
            </Card>

            <!-- 2. Gender Ratio -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>Ratio Sexe (F:H)</span>
                    <Users class="size-3.5 text-pink-500" />
                </div>
                <div class="text-lg font-bold font-mono text-foreground truncate">
                    {{ statistics.female_ratio }}
                </div>
                <p class="text-[10px] text-muted-foreground truncate">
                    {{ statistics.female_count }} Femmes / {{ statistics.male_count }} Hommes
                </p>
            </Card>

            <!-- 3. Average Age -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>Âge Moyen</span>
                    <Activity class="size-3.5 text-primary" />
                </div>
                <div class="text-lg font-bold font-mono text-foreground">
                    {{ statistics.average_age }} <span class="text-xs font-normal text-muted-foreground">ans</span>
                </div>
                <p class="text-[10px] text-muted-foreground truncate">
                    Cohorte actuelle
                </p>
            </Card>

            <!-- 4. Clinical Forms Distribution -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>Formes Cliniques</span>
                    <BarChart3 class="size-3.5 text-primary" />
                </div>
                <div class="text-xs font-bold font-mono text-foreground flex items-center gap-1.5">
                    <span class="text-primary">{{ statistics.clinical_forms.remittente }} RR</span>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-amber-600 dark:text-amber-400">{{ statistics.clinical_forms.secondaire }} SP</span>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-purple-600 dark:text-purple-400">{{ statistics.clinical_forms.primaire }} PP</span>
                </div>
                <p class="text-[10px] text-muted-foreground truncate">
                    Rémittente / Second. / Prim.
                </p>
            </Card>

            <!-- 5. BOC Positive Rate -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>BOC Positives</span>
                    <TestTube class="size-3.5 text-amber-500" />
                </div>
                <div class="text-lg font-bold font-mono text-foreground">
                    {{ statistics.boc_positive_rate }} <span class="text-xs font-normal text-muted-foreground">%</span>
                </div>
                <p class="text-[10px] text-muted-foreground truncate">
                    Synthèse intrathécale
                </p>
            </Card>

            <!-- 6. Top Treatment -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>DMT Fréquent</span>
                    <Pill class="size-3.5 text-emerald-500" />
                </div>
                <div class="text-xs font-bold text-foreground truncate" :title="statistics.top_treatment">
                    {{ statistics.top_treatment }}
                </div>
                <p class="text-[10px] text-muted-foreground truncate">
                    Traitement de fond
                </p>
            </Card>

            <!-- 7. Protocole IRM SEP -->
            <Card class="p-3.5 space-y-1">
                <div class="flex items-center justify-between text-muted-foreground text-[11px] font-medium">
                    <span>Protocole IRM SEP</span>
                    <Layers class="size-3.5 text-cyan-500" />
                </div>
                <div class="text-lg font-bold font-mono text-foreground">
                    {{ statistics.mri_sep_protocol_rate }} <span class="text-xs font-normal text-muted-foreground">%</span>
                </div>
                <p class="text-[10px] text-muted-foreground truncate" :title="`${statistics.mri_sep_protocol_count} sur ${statistics.mri_total_count} examens conformes`">
                    {{ statistics.mri_sep_protocol_count }} / {{ statistics.mri_total_count }} conformes
                </p>
            </Card>
        </div>

        <!-- MULTICRITERIA FILTER PANEL -->
        <Card class="border-border shadow-2xs">
            <CardHeader class="pb-3 border-b border-border/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Filter class="size-4 text-primary" />
                        <CardTitle class="text-sm font-semibold">
                            Filtres de Recherche Multicritère
                        </CardTitle>
                        <Badge v-if="hasActiveFilters" variant="secondary" class="text-[10px] font-semibold">
                            Filtres actifs
                        </Badge>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="gap-1 text-xs h-7 text-muted-foreground hover:text-foreground"
                            @click="resetFilters"
                        >
                            <RefreshCw class="size-3" />
                            <span>Réinitialiser</span>
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="gap-1 text-xs h-7"
                            @click="isFilterExpanded = !isFilterExpanded"
                        >
                            <span>{{ isFilterExpanded ? 'Masquer' : 'Afficher les filtres' }}</span>
                            <ChevronUp v-if="isFilterExpanded" class="size-3.5" />
                            <ChevronDown v-else class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent v-show="isFilterExpanded" class="pt-4 space-y-5 text-xs">
                <!-- Row 1 : Démographie & Généralités -->
                <div class="space-y-2">
                    <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Users class="size-3 text-primary" />
                        <span>1. Données Sociodémographiques</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div class="space-y-1">
                            <Label for="f_search" class="text-[11px]">Recherche textuelle</Label>
                            <div class="relative">
                                <Search class="size-3.5 absolute left-2.5 top-2.5 text-muted-foreground" />
                                <Input
                                    id="f_search"
                                    type="text"
                                    v-model="formFilters.search"
                                    placeholder="Nom, CIN, Dossier..."
                                    class="h-8 pl-8 text-xs"
                                    @keydown.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_gender" class="text-[11px]">Sexe</Label>
                            <Select v-model="formFilters.gender">
                                <SelectTrigger id="f_gender" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous les sexes</SelectItem>
                                    <SelectItem value="female" class="text-xs">Féminin (F)</SelectItem>
                                    <SelectItem value="male" class="text-xs">Masculin (H)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-[11px]">Âge Actuel (Min - Max)</Label>
                            <div class="flex items-center gap-1.5">
                                <Input
                                    type="number"
                                    min="0"
                                    max="110"
                                    placeholder="Min"
                                    v-model="formFilters.age_min"
                                    class="h-8 text-xs font-mono"
                                />
                                <span class="text-muted-foreground">-</span>
                                <Input
                                    type="number"
                                    min="0"
                                    max="110"
                                    placeholder="Max"
                                    v-model="formFilters.age_max"
                                    class="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_city" class="text-[11px]">Ville / Résidence</Label>
                            <Select v-model="formFilters.city">
                                <SelectTrigger id="f_city" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Toutes les villes</SelectItem>
                                    <SelectItem
                                        v-for="c in availableCities"
                                        :key="c"
                                        :value="c"
                                        class="text-xs"
                                    >
                                        {{ c }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_clinical_form" class="text-[11px]">Forme Clinique</Label>
                            <Select v-model="formFilters.clinical_form">
                                <SelectTrigger id="f_clinical_form" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Toutes les formes</SelectItem>
                                    <SelectItem value="remittente_recurrente" class="text-xs">Rémittente-Récurrente (RR)</SelectItem>
                                    <SelectItem value="secondairement_progressive" class="text-xs">Secondairement Progressive (SP)</SelectItem>
                                    <SelectItem value="primaire_progressive" class="text-xs">Primaire Progressive (PP)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Row 2 : Histoire de la Maladie & Score EDSS -->
                <div class="space-y-2 pt-2 border-t border-border/40">
                    <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Activity class="size-3 text-primary" />
                        <span>2. Diagnostic, Handicap & Poussées</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="space-y-1">
                            <Label class="text-[11px]">Âge au Début (Symptômes)</Label>
                            <div class="flex items-center gap-1.5">
                                <Input
                                    type="number"
                                    min="0"
                                    max="90"
                                    placeholder="Min"
                                    v-model="formFilters.age_onset_min"
                                    class="h-8 text-xs font-mono"
                                />
                                <span class="text-muted-foreground">-</span>
                                <Input
                                    type="number"
                                    min="0"
                                    max="90"
                                    placeholder="Max"
                                    v-model="formFilters.age_onset_max"
                                    class="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-[11px]">Âge au Diagnostic</Label>
                            <div class="flex items-center gap-1.5">
                                <Input
                                    type="number"
                                    min="0"
                                    max="90"
                                    placeholder="Min"
                                    v-model="formFilters.age_diagnosis_min"
                                    class="h-8 text-xs font-mono"
                                />
                                <span class="text-muted-foreground">-</span>
                                <Input
                                    type="number"
                                    min="0"
                                    max="90"
                                    placeholder="Max"
                                    v-model="formFilters.age_diagnosis_max"
                                    class="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-[11px]">Score EDSS (0.0 - 10.0)</Label>
                            <div class="flex items-center gap-1.5">
                                <Input
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    max="10"
                                    placeholder="Min"
                                    v-model="formFilters.edss_min"
                                    class="h-8 text-xs font-mono"
                                />
                                <span class="text-muted-foreground">-</span>
                                <Input
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    max="10"
                                    placeholder="Max"
                                    v-model="formFilters.edss_max"
                                    class="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label class="text-[11px]">Nombre Total de Poussées</Label>
                            <div class="flex items-center gap-1.5">
                                <Input
                                    type="number"
                                    min="0"
                                    max="50"
                                    placeholder="Min"
                                    v-model="formFilters.relapses_min"
                                    class="h-8 text-xs font-mono"
                                />
                                <span class="text-muted-foreground">-</span>
                                <Input
                                    type="number"
                                    min="0"
                                    max="50"
                                    placeholder="Max"
                                    v-model="formFilters.relapses_max"
                                    class="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 3 : Imagerie IRM & Biologie LCR -->
                <div class="space-y-2 pt-2 border-t border-border/40">
                    <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <FlaskConical class="size-3 text-primary" />
                        <span>3. IRM & Marqueurs Biologiques</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                        <div class="space-y-1">
                            <Label for="f_mri_region" class="text-[11px]">Région IRM</Label>
                            <Select v-model="formFilters.mri_region">
                                <SelectTrigger id="f_mri_region" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Toutes les régions</SelectItem>
                                    <SelectItem value="cerebrale" class="text-xs">Cérébrale</SelectItem>
                                    <SelectItem value="medullaire" class="text-xs">Médullaire</SelectItem>
                                    <SelectItem value="les_deux" class="text-xs">Cérébro-Médullaire</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_mri_contrast" class="text-[11px]">Prise de Contraste (Gadolinium)</Label>
                            <Select v-model="formFilters.mri_contrast">
                                <SelectTrigger id="f_mri_contrast" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous</SelectItem>
                                    <SelectItem value="oui" class="text-xs">Présente (Inflammation active)</SelectItem>
                                    <SelectItem value="non" class="text-xs">Absente</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_mri_sep_protocol" class="text-[11px]">Protocole IRM SEP</Label>
                            <Select v-model="formFilters.mri_sep_protocol">
                                <SelectTrigger id="f_mri_sep_protocol" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous</SelectItem>
                                    <SelectItem value="oui" class="text-xs">Protocole SEP respecté</SelectItem>
                                    <SelectItem value="non" class="text-xs">Protocole non conforme</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_boc" class="text-[11px]">Bandes Oligoclonales (LCR)</Label>
                            <Select v-model="formFilters.boc">
                                <SelectTrigger id="f_boc" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous</SelectItem>
                                    <SelectItem value="positives" class="text-xs">Positives (Synthèse intrathécale)</SelectItem>
                                    <SelectItem value="negatives" class="text-xs">Négatives</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_anti_nmo" class="text-[11px]">Anti-NMO (Aquaporine-4)</Label>
                            <Select v-model="formFilters.anti_nmo">
                                <SelectTrigger id="f_anti_nmo" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous</SelectItem>
                                    <SelectItem value="negative" class="text-xs">Négatif (SEP typique)</SelectItem>
                                    <SelectItem value="positive" class="text-xs">Positif (NMOSD)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_anti_mog" class="text-[11px]">Anti-MOG (MOGAD)</Label>
                            <Select v-model="formFilters.anti_mog">
                                <SelectTrigger id="f_anti_mog" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous</SelectItem>
                                    <SelectItem value="negative" class="text-xs">Négatif</SelectItem>
                                    <SelectItem value="positive" class="text-xs">Positif (MOGAD)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Row 4 : Traitements Thérapeutiques -->
                <div class="space-y-2 pt-2 border-t border-border/40">
                    <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Pill class="size-3 text-primary" />
                        <span>4. Traitements Thérapeutiques</span>
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="space-y-1">
                            <Label for="f_molecule" class="text-[11px]">Molécule de Fond (DMT)</Label>
                            <Select v-model="formFilters.treatment_molecule">
                                <SelectTrigger id="f_molecule" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Toutes les molécules</SelectItem>
                                    <SelectItem
                                        v-for="m in availableMolecules"
                                        :key="m"
                                        :value="m"
                                        class="text-xs"
                                    >
                                        {{ m }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_status" class="text-[11px]">Statut du Traitement</Label>
                            <Select v-model="formFilters.treatment_status">
                                <SelectTrigger id="f_status" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Tous les statuts</SelectItem>
                                    <SelectItem value="en_cours" class="text-xs">En cours</SelectItem>
                                    <SelectItem value="remplace" class="text-xs">Remplacé (Switch)</SelectItem>
                                    <SelectItem value="arrete" class="text-xs">Arrêté</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_vit_d" class="text-[11px]">Supplémentation Vitamine D</Label>
                            <Select v-model="formFilters.vitamin_d">
                                <SelectTrigger id="f_vit_d" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Indifférent</SelectItem>
                                    <SelectItem value="oui" class="text-xs">Oui (Supplémenté)</SelectItem>
                                    <SelectItem value="non" class="text-xs">Non</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label for="f_antidep" class="text-[11px]">Anti-dépresseurs</Label>
                            <Select v-model="formFilters.antidepressants">
                                <SelectTrigger id="f_antidep" class="h-8 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all" class="text-xs">Indifférent</SelectItem>
                                    <SelectItem value="oui" class="text-xs">Oui</SelectItem>
                                    <SelectItem value="non" class="text-xs">Non</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Action Button Row -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="resetFilters"
                        :disabled="!hasActiveFilters"
                    >
                        Réinitialiser
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="gap-1.5"
                        @click="applyFilters"
                    >
                        <Search class="size-3.5" />
                        <span>Appliquer les filtres</span>
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- RESULTS DATA TABLE -->
        <Card class="border-border shadow-2xs">
            <CardHeader class="pb-3 border-b border-border/50">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <CardTitle class="text-sm font-semibold flex items-center gap-2">
                        <Users class="size-4 text-primary" />
                        <span>Patients de la Cohorte Filtrée ({{ patients.total }})</span>
                    </CardTitle>
                    <div class="text-xs text-muted-foreground">
                        Affichage {{ patients.from || 0 }} à {{ patients.to || 0 }} sur {{ patients.total }} patients
                    </div>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted/30">
                            <TableRow>
                                <TableHead class="text-xs font-semibold">Identifiant</TableHead>
                                <TableHead class="text-xs font-semibold">
                                    {{ formFilters.pseudonymize ? 'Code Pseudonyme' : 'Patient' }}
                                </TableHead>
                                <TableHead class="text-xs font-semibold">Sexe / Âge</TableHead>
                                <TableHead class="text-xs font-semibold">Ville</TableHead>
                                <TableHead class="text-xs font-semibold">Forme Clinique</TableHead>
                                <TableHead class="text-xs font-semibold">EDSS</TableHead>
                                <TableHead class="text-xs font-semibold">Poussées</TableHead>
                                <TableHead class="text-xs font-semibold">Traitement de Fond</TableHead>
                                <TableHead class="text-xs font-semibold">BOC (LCR)</TableHead>
                                <TableHead class="text-xs font-semibold">IRM Gado</TableHead>
                                <TableHead class="text-xs font-semibold text-right">Dossier</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="p in patients.data"
                                :key="p.id"
                                class="hover:bg-muted/40 transition-colors text-xs"
                            >
                                <TableCell class="font-mono font-semibold text-foreground">
                                    {{ p.dossier_number }}
                                </TableCell>
                                <TableCell>
                                    <div v-if="formFilters.pseudonymize" class="font-mono text-primary font-medium">
                                        {{ p.pseudonym }}
                                    </div>
                                    <div v-else class="font-medium text-foreground">
                                        {{ p.full_name }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span :class="p.gender === 'female' ? 'text-pink-600 dark:text-pink-400' : 'text-blue-600 dark:text-blue-400'" class="font-medium">
                                        {{ p.gender === 'female' ? 'F' : 'H' }}
                                    </span>
                                    <span class="text-muted-foreground">, {{ p.age }} ans</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ p.city || '—' }}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="text-[10px]">
                                        {{ p.clinical_form_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-mono font-bold text-foreground">
                                    {{ p.latest_edss }}
                                </TableCell>
                                <TableCell class="font-mono">
                                    {{ p.relapses_count }}
                                </TableCell>
                                <TableCell>
                                    <span v-if="p.active_treatment !== 'Aucun'" class="font-medium text-foreground">
                                        {{ p.active_treatment }}
                                    </span>
                                    <span v-else class="text-muted-foreground italic">Aucun</span>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        v-if="p.boc_status"
                                        :class="p.boc_status === 'positives' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' : 'bg-muted text-muted-foreground'"
                                        class="text-[10px]"
                                    >
                                        {{ p.boc_status }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell>
                                    <span v-if="p.mri_contrast === 'present'" class="text-destructive font-semibold">
                                        Prise +
                                    </span>
                                    <span v-else-if="p.mri_contrast === 'absent'" class="text-emerald-600 dark:text-emerald-400">
                                        Sans prise
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="sm"
                                        class="size-8 p-0"
                                        title="Ouvrir le dossier patient"
                                    >
                                        <Link :href="`/patients/${p.id}`">
                                            <Eye class="size-4 text-primary" />
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="patients.data.length === 0">
                                <TableCell colspan="11" class="text-center py-12 text-muted-foreground text-xs space-y-2">
                                    <FileSpreadsheet class="size-8 mx-auto text-muted-foreground/40" />
                                    <p class="font-semibold text-foreground text-sm">Aucun patient ne correspond aux critères sélectionnés</p>
                                    <p class="max-w-md mx-auto text-muted-foreground">
                                        Modifiez ou réinitialisez vos filtres pour élargir la cohorte de recherche.
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="mt-2"
                                        @click="resetFilters"
                                    >
                                        Réinitialiser les filtres
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- PAGINATION -->
                <div v-if="patients.last_page > 1" class="flex items-center justify-between p-4 border-t border-border">
                    <div class="text-xs text-muted-foreground">
                        Page {{ patients.current_page }} sur {{ patients.last_page }}
                    </div>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in patients.links" :key="i">
                            <Button
                                v-if="link.url"
                                as-child
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                class="h-8 min-w-8 px-2 text-xs"
                            >
                                <Link :href="link.url" preserve-scroll preserve-state>
                                    <span v-html="link.label" />
                                </Link>
                            </Button>
                            <span
                                v-else
                                class="px-2 py-1 text-xs text-muted-foreground opacity-50"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>

        <PatientImportDialog v-model:open="isImportDialogOpen" />
    </div>
</template>
