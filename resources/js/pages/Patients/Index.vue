<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    FileText,
    Plus,
    RotateCcw,
    Search,
    UploadCloud,
    UserCheck,
    Users,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PatientImportDialog from '@/pages/Patients/Partials/PatientImportDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
            {
                title: 'Dossiers Patients',
                href: patientsIndex(),
            },
        ],
    },
});

type PatientListItem = {
    id: number;
    dossier_number: string;
    full_name: string;
    first_name: string;
    last_name: string;
    cin: string | null;
    gender: string;
    gender_label: string;
    birth_date: string;
    age: number | null;
    phone: string | null;
    city: string | null;
    status: string;
    status_label: string;
    status_color: string;
    clinical_form: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedPatients = {
    data: PatientListItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

type SelectOption = {
    value: string;
    label: string;
};

type Props = {
    patients: PaginatedPatients;
    filters: {
        search: string;
        status: string;
        gender: string;
    };
    statuses: SelectOption[];
    genders: SelectOption[];
};

const props = defineProps<Props>();
const page = usePage();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const gender = ref(props.filters.gender || 'all');
const isImportDialogOpen = ref(false);

const hasActiveFilters = computed(() => {
    return (
        search.value.trim() !== '' ||
        (status.value !== '' && status.value !== 'all') ||
        (gender.value !== '' && gender.value !== 'all')
    );
});

const applyFilters = () => {
    router.get(
        patientsIndex.url(),
        {
            search: search.value.trim() || undefined,
            status: status.value !== 'all' ? status.value : undefined,
            gender: gender.value !== 'all' ? gender.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

const handleStatusChange = (val: unknown) => {
    status.value = String(val ?? 'all');
    applyFilters();
};

const handleGenderChange = (val: unknown) => {
    gender.value = String(val ?? 'all');
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    gender.value = 'all';
    applyFilters();
};
</script>

<template>
    <Head title="Dossiers Patients" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground">
                        Dossiers Patients
                    </h1>
                    <span
                        class="rounded-md bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground"
                    >
                        {{ patients.total }} enregistrés
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                    Registre clinique et suivi longitudinal des patients atteints de sclérose en plaques
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <Button
                    type="button"
                    variant="outline"
                    class="gap-1.5 flex-1 sm:flex-initial text-xs sm:text-sm"
                    size="default"
                    @click="isImportDialogOpen = true"
                >
                    <UploadCloud class="size-4" />
                    <span>Importer (Excel / SPSS)</span>
                </Button>

                <Button as-child class="gap-1.5 flex-1 sm:flex-initial text-xs sm:text-sm" size="default">
                    <Link :href="patientsCreate.url()">
                        <Plus class="size-4" />
                        <span>Nouveau Dossier</span>
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Success Flash Alert -->
        <div
            v-if="page.props.flash?.success"
            class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 shadow-2xs dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-200"
        >
            <CheckCircle2 class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
            <p class="font-medium">{{ page.props.flash.success }}</p>
        </div>

        <!-- Filter & Search Bar -->
        <div class="rounded-xl border border-border bg-card p-3 sm:p-4 shadow-2xs">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Rechercher par N° dossier, nom, prénom, CIN, ville..."
                        class="pl-9 h-9 sm:h-10 text-xs sm:text-sm"
                    />
                </div>

                <div class="grid grid-cols-2 md:flex md:items-center gap-2 md:gap-3 w-full md:w-auto">
                    <!-- Status Filter -->
                    <div class="w-full md:w-52">
                        <Select :model-value="status" @update:model-value="handleStatusChange">
                            <SelectTrigger class="w-full h-9 sm:h-10 text-xs sm:text-sm">
                                <SelectValue placeholder="Tous les statuts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les statuts</SelectItem>
                                <SelectItem
                                    v-for="item in statuses"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Gender Filter -->
                    <div class="w-full md:w-40">
                        <Select :model-value="gender" @update:model-value="handleGenderChange">
                            <SelectTrigger class="w-full h-9 sm:h-10 text-xs sm:text-sm">
                                <SelectValue placeholder="Sexe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les sexes</SelectItem>
                                <SelectItem
                                    v-for="item in genders"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Reset Filters Button -->
                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    @click="resetFilters"
                    class="shrink-0 text-muted-foreground hover:text-foreground gap-1.5 text-xs w-full md:w-auto justify-center"
                    title="Réinitialiser les filtres"
                >
                    <RotateCcw class="size-3.5" />
                    <span>Réinitialiser les filtres</span>
                </Button>
            </div>
        </div>

        <!-- Patients Data Table -->
        <div class="rounded-xl border border-border bg-card shadow-2xs overflow-hidden">
            <Table>
                <TableHeader class="bg-muted/50">
                    <TableRow>
                        <TableHead class="w-[140px] font-semibold">N° Dossier</TableHead>
                        <TableHead class="font-semibold">Patient</TableHead>
                        <TableHead class="font-semibold">CIN</TableHead>
                        <TableHead class="font-semibold">Sexe / Âge</TableHead>
                        <TableHead class="font-semibold">Ville</TableHead>
                        <TableHead class="font-semibold">Téléphone</TableHead>
                        <TableHead class="font-semibold">Forme SEP</TableHead>
                        <TableHead class="font-semibold">Statut</TableHead>
                        <TableHead class="text-right font-semibold">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="patient in patients.data"
                        :key="patient.id"
                        class="hover:bg-muted/40 transition-colors"
                    >
                        <!-- N° Dossier -->
                        <TableCell class="font-mono font-medium text-xs text-foreground">
                            {{ patient.dossier_number }}
                        </TableCell>

                        <!-- Nom & Prénom -->
                        <TableCell>
                            <div class="font-medium text-foreground">
                                {{ patient.full_name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Créé le {{ patient.created_at || '—' }}
                            </div>
                        </TableCell>

                        <!-- CIN -->
                        <TableCell class="text-sm text-muted-foreground font-mono">
                            {{ patient.cin || '—' }}
                        </TableCell>

                        <!-- Sexe & Âge -->
                        <TableCell class="text-sm">
                            <span class="font-medium text-foreground">
                                {{ patient.gender === 'female' ? 'Femme' : 'Homme' }}
                            </span>
                            <span v-if="patient.age" class="text-muted-foreground text-xs ml-1">
                                ({{ patient.age }} ans)
                            </span>
                        </TableCell>

                        <!-- Ville -->
                        <TableCell class="text-sm text-foreground">
                            {{ patient.city || '—' }}
                        </TableCell>

                        <!-- Téléphone -->
                        <TableCell class="text-sm text-muted-foreground font-mono text-xs">
                            {{ patient.phone || '—' }}
                        </TableCell>

                        <!-- Forme SEP -->
                        <TableCell>
                            <Badge
                                v-if="patient.clinical_form"
                                variant="outline"
                                class="font-mono text-[11px]"
                            >
                                {{ patient.clinical_form }}
                            </Badge>
                            <span v-else class="text-xs text-muted-foreground">—</span>
                        </TableCell>

                        <!-- Statut -->
                        <TableCell>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium',
                                    patient.status_color,
                                ]"
                            >
                                {{ patient.status_label }}
                            </span>
                        </TableCell>

                        <!-- Actions -->
                        <TableCell class="text-right">
                            <Button
                                as-child
                                variant="outline"
                                size="sm"
                                class="gap-1 text-xs hover:bg-muted"
                            >
                                <Link :href="patientsShow.url({ patient: patient.id })">
                                    <FileText class="size-3.5" />
                                    <span>Consulter</span>
                                </Link>
                            </Button>
                        </TableCell>
                    </TableRow>

                    <!-- Empty State -->
                    <TableRow v-if="patients.data.length === 0">
                        <TableCell colspan="9" class="h-44 text-center">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <Users class="size-8 text-muted-foreground/60" />
                                <p class="text-sm font-medium text-foreground">
                                    Aucun dossier patient trouvé
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Essayez de modifier vos critères de recherche ou réinitialisez les filtres.
                                </p>
                                <Button
                                    v-if="hasActiveFilters"
                                    variant="outline"
                                    size="sm"
                                    class="mt-2"
                                    @click="resetFilters"
                                >
                                    Réinitialiser les filtres
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Table Footer Pagination -->
            <div
                v-if="patients.total > 0"
                class="flex flex-col gap-3 border-t border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between text-xs text-muted-foreground"
            >
                <div class="text-center sm:text-left">
                    Affichage de
                    <span class="font-medium text-foreground">{{ patients.from || 0 }}</span>
                    à
                    <span class="font-medium text-foreground">{{ patients.to || 0 }}</span>
                    sur
                    <span class="font-medium text-foreground">{{ patients.total }}</span>
                    patients
                </div>

                <div class="flex items-center justify-center sm:justify-end gap-1 flex-wrap">
                    <template v-for="(link, index) in patients.links" :key="index">
                        <!-- Previous button -->
                        <Link
                            v-if="index === 0"
                            :href="link.url || '#'"
                            :class="[
                                'inline-flex h-8 items-center justify-center rounded-md border border-border px-2.5 font-medium transition',
                                link.url
                                    ? 'hover:bg-muted text-foreground'
                                    : 'pointer-events-none opacity-40 text-muted-foreground',
                            ]"
                        >
                            <ChevronLeft class="size-3.5 mr-1" />
                            <span>Précédent</span>
                        </Link>

                        <!-- Next button -->
                        <Link
                            v-else-if="index === patients.links.length - 1"
                            :href="link.url || '#'"
                            :class="[
                                'inline-flex h-8 items-center justify-center rounded-md border border-border px-2.5 font-medium transition',
                                link.url
                                    ? 'hover:bg-muted text-foreground'
                                    : 'pointer-events-none opacity-40 text-muted-foreground',
                            ]"
                        >
                            <span>Suivant</span>
                            <ChevronRight class="size-3.5 ml-1" />
                        </Link>

                        <!-- Page number buttons -->
                        <Link
                            v-else
                            :href="link.url || '#'"
                            :class="[
                                'inline-flex size-8 items-center justify-center rounded-md text-xs font-medium transition',
                                link.active
                                    ? 'bg-primary text-primary-foreground font-semibold'
                                    : link.url
                                      ? 'hover:bg-muted text-foreground'
                                      : 'pointer-events-none opacity-40 text-muted-foreground',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <PatientImportDialog v-model:open="isImportDialogOpen" />
    </div>
</template>
