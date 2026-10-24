<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Activity as ActivityIcon,
    Calendar,
    CheckCircle2,
    Database,
    Eye,
    FileSpreadsheet,
    Filter,
    RotateCcw,
    Search,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Trash2,
    UserCheck,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

interface AuditItem {
    id: number;
    log_name: string;
    subject_label: string;
    subject_id: number | null;
    description: string;
    event: string;
    event_label: string;
    causer: {
        id: number | null;
        name: string;
        email: string;
        role: string | null;
    };
    properties: Record<string, any>;
    created_at: string;
    created_at_relative: string;
}

interface UserOption {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    activities: {
        data: AuditItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    metrics: {
        total_activities: number;
        exports_count: number;
        updates_count: number;
        active_users_count: number;
    };
    users: UserOption[];
    filters: {
        search: string;
        event: string;
        log_name: string;
        user_id: string;
        date_from: string;
        date_to: string;
    };
}>();

const search = ref(props.filters.search);
const event = ref(props.filters.event);
const logName = ref(props.filters.log_name);
const userId = ref(props.filters.user_id);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const selectedActivity = ref<AuditItem | null>(null);
const isDetailDialogOpen = ref(false);

const applyFilters = () => {
    router.get(
        '/audit-trail',
        {
            search: search.value || undefined,
            event: event.value !== 'all' ? event.value : undefined,
            log_name: logName.value !== 'all' ? logName.value : undefined,
            user_id: userId.value !== 'all' ? userId.value : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    event.value = 'all';
    logName.value = 'all';
    userId.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const openDetails = (activity: AuditItem) => {
    selectedActivity.value = activity;
    isDetailDialogOpen.value = true;
};

const getEventBadgeClass = (ev: string) => {
    switch (ev) {
        case 'created':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-300';
        case 'updated':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-300';
        case 'deleted':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-300';
        case 'export':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-300';
        case 'consultation_dossier':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-300';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200';
    }
};

const getRoleBadgeClass = (role: string | null) => {
    switch (role) {
        case 'admin':
            return 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border-red-200';
        case 'medecin':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border-blue-200';
        case 'operateur_saisie':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const formatRoleLabel = (role: string | null) => {
    switch (role) {
        case 'admin':
            return 'Administrateur';
        case 'medecin':
            return 'Médecin Neurologue';
        case 'operateur_saisie':
            return 'Opérateur de Saisie';
        default:
            return role || 'Utilisateur';
    }
};
</script>

<template>
    <Head title="Journal d'Audit & Traçabilité CNDP" />

    <div class="space-y-6 p-3 sm:p-6 lg:p-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b pb-5">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2 flex-wrap">
                                <span>Journal d'Audit & Traçabilité</span>
                                <Badge variant="outline" class="text-xs bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300">
                                    Conforme Loi 09-08 / CNDP
                                </Badge>
                            </h1>
                            <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                                Traçabilité inaltérable des accès, créations, modifications, suppressions et exports de données cliniques
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Button variant="outline" size="sm" @click="resetFilters" class="gap-1.5 text-xs w-full sm:w-auto justify-center">
                        <RotateCcw class="h-3.5 w-3.5" />
                        Actualiser
                    </Button>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Total Événements</span>
                        <div class="rounded-lg bg-primary/10 p-2 text-primary">
                            <ActivityIcon class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight">{{ metrics.total_activities }}</span>
                        <span class="text-xs text-muted-foreground">enregistrements</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Exports CNDP</span>
                        <div class="rounded-lg bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400">
                            <FileSpreadsheet class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                            {{ metrics.exports_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">extractions tracées</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Mises à Jour Médicales</span>
                        <div class="rounded-lg bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400">
                            <Database class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                            {{ metrics.updates_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">modifications</span>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">Praticiens Actifs</span>
                        <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-400">
                            <UserCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                            {{ metrics.active_users_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">utilisateurs</span>
                    </div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="rounded-xl border bg-card p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                        <Filter class="h-3.5 w-3.5" />
                        Filtrer le Journal de Traçabilité
                    </span>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="text-xs text-primary hover:underline"
                    >
                        Réinitialiser tous les filtres
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">Recherche textuelle</label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Description, médecin, module..."
                                class="pl-8 text-xs h-9"
                            />
                        </div>
                    </div>

                    <!-- Event Type -->
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">Type d'opération</label>
                        <select
                            v-model="event"
                            @change="applyFilters"
                            class="w-full h-9 rounded-md border border-input bg-background px-2.5 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="all">Toutes les opérations</option>
                            <option value="created">Création (created)</option>
                            <option value="updated">Modification (updated)</option>
                            <option value="deleted">Suppression (deleted)</option>
                            <option value="export">Export CNDP (export)</option>
                            <option value="consultation_dossier">Consultation Dossier</option>
                        </select>
                    </div>

                    <!-- Module / Log Name -->
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">Module / Donnée</label>
                        <select
                            v-model="logName"
                            @change="applyFilters"
                            class="w-full h-9 rounded-md border border-input bg-background px-2.5 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="all">Tous les modules</option>
                            <option value="patient">Dossier Patient</option>
                            <option value="treatment">Traitements DMT</option>
                            <option value="mri_exam">Imagerie & IRM</option>
                            <option value="clinical_exam">Examen Clinique / EDSS</option>
                            <option value="consultation">Consultation de Suivi</option>
                            <option value="complementary_exam">Biologie & PL</option>
                            <option value="relapse">Poussées</option>
                            <option value="export">Exports Cohorte</option>
                            <option value="user">Utilisateurs & Accès</option>
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">Praticien / Auteur</label>
                        <select
                            v-model="userId"
                            @change="applyFilters"
                            class="w-full h-9 rounded-md border border-input bg-background px-2.5 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="all">Tous les praticiens</option>
                            <option v-for="u in users" :key="u.id" :value="String(u.id)">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Action Button -->
                    <div class="flex items-end">
                        <Button @click="applyFilters" size="sm" class="w-full h-9 text-xs gap-1.5">
                            <Search class="h-3.5 w-3.5" />
                            Filtrer
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Audit Log Table -->
            <div class="rounded-xl border bg-card shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b bg-muted/50 font-semibold text-muted-foreground">
                            <tr>
                                <th class="p-3 w-40">Date & Heure</th>
                                <th class="p-3 w-48">Praticien / Auteur</th>
                                <th class="p-3 w-36">Opération</th>
                                <th class="p-3 w-44">Module & Cible</th>
                                <th class="p-3">Description de l'Événement</th>
                                <th class="p-3 text-right w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="act in activities.data"
                                :key="act.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <!-- Timestamp -->
                                <td class="p-3 whitespace-nowrap">
                                    <div class="font-medium text-foreground">{{ act.created_at }}</div>
                                    <div class="text-[11px] text-muted-foreground">{{ act.created_at_relative }}</div>
                                </td>

                                <!-- Causer / Doctor -->
                                <td class="p-3">
                                    <div class="font-medium text-foreground">{{ act.causer.name }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <Badge
                                            variant="outline"
                                            :class="['text-[10px] px-1.5 py-0', getRoleBadgeClass(act.causer.role)]"
                                        >
                                            {{ formatRoleLabel(act.causer.role) }}
                                        </Badge>
                                    </div>
                                </td>

                                <!-- Event Badge -->
                                <td class="p-3">
                                    <Badge
                                        variant="outline"
                                        :class="['text-xs font-semibold px-2 py-0.5 border', getEventBadgeClass(act.event)]"
                                    >
                                        {{ act.event_label }}
                                    </Badge>
                                </td>

                                <!-- Subject Module -->
                                <td class="p-3">
                                    <div class="font-medium text-foreground">{{ act.subject_label }}</div>
                                    <div v-if="act.subject_id" class="text-[11px] text-muted-foreground">
                                        ID: #{{ act.subject_id }}
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="p-3">
                                    <div class="text-foreground line-clamp-2">{{ act.description }}</div>
                                    <div
                                        v-if="act.properties && (act.properties.ip || act.properties.mode)"
                                        class="mt-1 flex items-center gap-2 text-[10px] text-muted-foreground"
                                    >
                                        <span v-if="act.properties.mode" class="font-mono bg-muted px-1 rounded">
                                            mode: {{ act.properties.mode }}
                                        </span>
                                        <span v-if="act.properties.ip" class="font-mono bg-muted px-1 rounded">
                                            IP: {{ act.properties.ip }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Action Inspect -->
                                <td class="p-3 text-right">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 px-2 text-xs gap-1"
                                        @click="openDetails(act)"
                                    >
                                        <Eye class="h-3.5 w-3.5 text-muted-foreground" />
                                        Détails
                                    </Button>
                                </td>
                            </tr>

                            <tr v-if="activities.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-muted-foreground">
                                    <Shield class="h-8 w-8 mx-auto mb-2 opacity-40" />
                                    Aucun enregistrement d'audit ne correspond à vos critères.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="activities.last_page > 1"
                    class="flex items-center justify-between border-t px-4 py-3 bg-card"
                >
                    <div class="text-xs text-muted-foreground">
                        Affichage de
                        <span class="font-medium">{{ (activities.current_page - 1) * activities.per_page + 1 }}</span>
                        à
                        <span class="font-medium">{{ Math.min(activities.current_page * activities.per_page, activities.total) }}</span>
                        sur
                        <span class="font-medium">{{ activities.total }}</span> événements
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            v-for="(link, i) in activities.links"
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 min-w-[32px] px-2 text-xs"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Dialog Modal -->
        <Dialog v-model:open="isDetailDialogOpen">
            <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg">
                        <ShieldCheck class="h-5 w-5 text-primary" />
                        Détail de l'Enregistrement d'Audit #{{ selectedActivity?.id }}
                    </DialogTitle>
                    <DialogDescription>
                        Fiche d'audit inaltérable certifiant l'action clinique effectuée
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedActivity" class="space-y-4 py-2 text-xs">
                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-lg border bg-muted/40">
                        <div>
                            <span class="text-muted-foreground">Date et Heure :</span>
                            <div class="font-semibold text-foreground mt-0.5">
                                {{ selectedActivity.created_at }} ({{ selectedActivity.created_at_relative }})
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Praticien / Opérateur :</span>
                            <div class="font-semibold text-foreground mt-0.5">
                                {{ selectedActivity.causer.name }} ({{ selectedActivity.causer.email }})
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Type d'opération :</span>
                            <div class="mt-0.5">
                                <Badge
                                    variant="outline"
                                    :class="['text-xs', getEventBadgeClass(selectedActivity.event)]"
                                >
                                    {{ selectedActivity.event_label }}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Module / Cible :</span>
                            <div class="font-semibold text-foreground mt-0.5">
                                {{ selectedActivity.subject_label }}
                                <span v-if="selectedActivity.subject_id" class="text-muted-foreground">
                                    (#{{ selectedActivity.subject_id }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="p-3 rounded-lg border">
                        <span class="text-muted-foreground font-medium">Description certifiée :</span>
                        <p class="text-foreground mt-1 text-sm font-medium">
                            {{ selectedActivity.description }}
                        </p>
                    </div>

                    <!-- Properties / Changes Diff -->
                    <div v-if="selectedActivity.properties" class="space-y-3">
                        <!-- Old vs Attributes Diff for Updates -->
                        <div
                            v-if="selectedActivity.properties.old || selectedActivity.properties.attributes"
                            class="grid grid-cols-1 md:grid-cols-2 gap-3"
                        >
                            <!-- Previous values -->
                            <div v-if="selectedActivity.properties.old" class="border rounded-lg p-3 bg-rose-50/30 dark:bg-rose-950/20 border-rose-200">
                                <div class="font-semibold text-rose-800 dark:text-rose-300 mb-2 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                    Valeurs Antérieures (Old)
                                </div>
                                <pre class="font-mono text-[11px] whitespace-pre-wrap overflow-x-auto max-h-60 p-2 bg-background rounded border text-foreground">{{ JSON.stringify(selectedActivity.properties.old, null, 2) }}</pre>
                            </div>

                            <!-- New values -->
                            <div v-if="selectedActivity.properties.attributes" class="border rounded-lg p-3 bg-emerald-50/30 dark:bg-emerald-950/20 border-emerald-200">
                                <div class="font-semibold text-emerald-800 dark:text-emerald-300 mb-2 flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Nouvelles Valeurs (Attributes)
                                </div>
                                <pre class="font-mono text-[11px] whitespace-pre-wrap overflow-x-auto max-h-60 p-2 bg-background rounded border text-foreground">{{ JSON.stringify(selectedActivity.properties.attributes, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Generic Properties (for Exports, Views, etc.) -->
                        <div
                            v-else-if="Object.keys(selectedActivity.properties).length > 0"
                            class="border rounded-lg p-3 bg-muted/20"
                        >
                            <div class="font-semibold text-foreground mb-2">Paramètres de l'action :</div>
                            <pre class="font-mono text-[11px] whitespace-pre-wrap overflow-x-auto max-h-60 p-2 bg-background rounded border text-foreground">{{ JSON.stringify(selectedActivity.properties, null, 2) }}</pre>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" size="sm">Fermer</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
</template>
