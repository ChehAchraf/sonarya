<script setup lang="ts">
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    ArrowRight,
    Calendar,
    CheckCircle2,
    ChevronRight,
    Clock,
    Crosshair,
    Filter,
    HeartPulse,
    Info,
    Pill,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
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
    vitamin_d_supplementation: boolean;
    antidepressants: boolean;
    notes: string | null;
};

type ClinicalExam = {
    id: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    edss_score: string | number;
    t25fw?: number | string | null;
    nine_hpt?: number | string | null;
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
    lesions_count_estimated: string | null;
    contrast_enhancement: string | null;
    new_lesions_count: number | null;
};

type Props = {
    relapses: Relapse[];
    treatments: Treatment[];
    clinicalExams: ClinicalExam[];
    mriExams: MriExam[];
    diagnosisDate?: string | null;
    firstSymptomDate?: string | null;
};

const props = defineProps<Props>();

// Timeline filters
const filterShowRelapses = ref(true);
const filterShowTreatments = ref(true);
const filterShowClinical = ref(true);
const filterShowMri = ref(true);
const selectedEvent = ref<any | null>(null);

// Chronological sorting of all events
type UnifiedEvent = {
    id: string;
    type: 'symptom_inaugural' | 'diagnosis' | 'relapse' | 'treatment' | 'clinical' | 'mri';
    dateRaw: string;
    dateFormatted: string;
    title: string;
    subtitle?: string;
    statusBadge?: { label: string; class: string };
    details: Record<string, any>;
    rawItem: any;
};

const unifiedTimelineEvents = computed<UnifiedEvent[]>(() => {
    const list: UnifiedEvent[] = [];

    // 1. First symptom / onset
    if (props.firstSymptomDate) {
        list.push({
            id: 'event-first-symptom',
            type: 'symptom_inaugural',
            dateRaw: props.firstSymptomDate,
            dateFormatted: props.firstSymptomDate,
            title: 'Première Manifestation Clinique',
            subtitle: 'Début des premiers symptômes évocateurs de SEP',
            statusBadge: { label: 'Inaugural', class: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border-indigo-500/30' },
            details: { date: props.firstSymptomDate },
            rawItem: null,
        });
    }

    // 2. Diagnosis confirmation
    if (props.diagnosisDate) {
        list.push({
            id: 'event-diagnosis',
            type: 'diagnosis',
            dateRaw: props.diagnosisDate,
            dateFormatted: props.diagnosisDate,
            title: 'Diagnostic Confirmé de SEP',
            subtitle: 'Validation formelle selon les critères de McDonald',
            statusBadge: { label: 'Diagnostic', class: 'bg-primary/10 text-primary border-primary/30 font-semibold' },
            details: { date: props.diagnosisDate },
            rawItem: null,
        });
    }

    // 3. Relapses
    if (filterShowRelapses.value) {
        props.relapses.forEach((r) => {
            if (!r.start_date_raw && !r.start_date) return;
            const dateStr = r.start_date_raw || r.start_date || '';
            const recoveryClass = r.clinical_recovery === 'complete'
                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                : (r.clinical_recovery === 'sequelles' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30' : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30');

            list.push({
                id: `relapse-${r.id}`,
                type: 'relapse',
                dateRaw: dateStr,
                dateFormatted: r.start_date || dateStr,
                title: 'Poussée Clinique Aiguë',
                subtitle: r.symptoms,
                statusBadge: {
                    label: r.clinical_recovery ? `Récupération: ${r.clinical_recovery}` : 'Poussée',
                    class: recoveryClass,
                },
                details: {
                    symptoms: r.symptoms,
                    treatment: r.treatment_administered || 'Aucun traitement aigu consigné',
                    recovery: r.clinical_recovery || 'Non précisée',
                    sequelae: r.sequelae || 'Aucune',
                    hospitalized: r.hospitalization_required ? 'Oui' : 'Non',
                    duration: r.duration_days ? `${r.duration_days} jours` : 'Non précisée',
                },
                rawItem: r,
            });
        });
    }

    // 4. Treatments
    if (filterShowTreatments.value) {
        props.treatments.forEach((t) => {
            if (!t.start_date_raw && !t.start_date) return;
            const dateStr = t.start_date_raw || t.start_date || '';
            const isOngoing = t.status === 'en_cours';

            list.push({
                id: `treatment-${t.id}`,
                type: 'treatment',
                dateRaw: dateStr,
                dateFormatted: t.start_date || dateStr,
                title: `Traitement : ${t.molecule_name}`,
                subtitle: `${t.category === 'fond' ? 'Traitement de Fond (DMT)' : (t.category || 'Traitement')} • ${t.dosage_regimen || 'Posologie standard'}`,
                statusBadge: {
                    label: isOngoing ? 'En cours' : (t.status === 'remplace' ? 'Switch / Remplacé' : 'Arrêté'),
                    class: isOngoing
                        ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30 font-semibold'
                        : (t.status === 'remplace' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' : 'bg-muted text-muted-foreground border-border'),
                },
                details: {
                    molecule: t.molecule_name,
                    category: t.category,
                    therapeuticClass: t.therapeutic_class || 'Non précisée',
                    start: t.start_date,
                    end: t.end_date || (isOngoing ? 'Toujours en cours' : '—'),
                    stopReason: t.stop_reason || 'Néant',
                    adverseEffects: t.adverse_effects || 'Aucun effet indésirable consigné',
                },
                rawItem: t,
            });
        });
    }

    // 5. Clinical Exams (EDSS)
    if (filterShowClinical.value) {
        props.clinicalExams.forEach((e) => {
            if (!e.exam_date_raw && !e.exam_date) return;
            const dateStr = e.exam_date_raw || e.exam_date || '';
            const edssNum = Number(e.edss_score);
            const edssClass = edssNum <= 2.5
                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                : (edssNum <= 5.5 ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30' : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30 font-bold');

            list.push({
                id: `clinical-${e.id}`,
                type: 'clinical',
                dateRaw: dateStr,
                dateFormatted: e.exam_date || dateStr,
                title: `Évaluation EDSS : ${edssNum.toFixed(1)}`,
                subtitle: e.evaluator_name ? `Examinateur : ${e.evaluator_name}` : 'Consultation neurologique',
                statusBadge: {
                    label: `EDSS ${edssNum.toFixed(1)}`,
                    class: edssClass,
                },
                details: {
                    edss: edssNum.toFixed(1),
                    t25fw: e.t25fw ? `${e.t25fw} secondes` : 'Non réalisé',
                    nine_hpt: e.nine_hpt ? `${e.nine_hpt} secondes` : 'Non réalisé',
                    evaluator: e.evaluator_name || 'Non précisé',
                    comment: e.clinical_comment || 'Aucun commentaire',
                },
                rawItem: e,
            });
        });
    }

    // 6. MRI Exams
    if (filterShowMri.value) {
        props.mriExams.forEach((m) => {
            if (!m.exam_date_raw && !m.exam_date) return;
            const dateStr = m.exam_date_raw || m.exam_date || '';

            list.push({
                id: `mri-${m.id}`,
                type: 'mri',
                dateRaw: dateStr,
                dateFormatted: m.exam_date || dateStr,
                title: `IRM ${m.region || 'Cérébrale'} (${m.timing || 'Suivi'})`,
                subtitle: `Protocole SEP : ${m.sep_protocol_applied ? 'Respecté' : 'Non conforme'} • ${m.lesions_count_estimated || 'Lésions non quantifiées'}`,
                statusBadge: {
                    label: m.sep_protocol_applied ? 'Protocole SEP Respecté' : 'Hors Protocole',
                    class: m.sep_protocol_applied
                        ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                        : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30',
                },
                details: {
                    timing: m.timing || 'Suivi',
                    region: m.region || 'Cérébrale',
                    sepProtocol: m.sep_protocol_applied ? 'Oui (Protocole SEP conforme)' : 'Non respecté',
                    lesionsCount: m.lesions_count_estimated || 'Non chiffré',
                    contrast: m.contrast_enhancement || 'Aucun rehaussement',
                    newLesions: m.new_lesions_count !== null ? `${m.new_lesions_count} nouvelle(s)` : 'Non quantifiées',
                },
                rawItem: m,
            });
        });
    }

    // Sort chronologically ascending
    return list.sort((a, b) => {
        const da = new Date(a.dateRaw).getTime();
        const db = new Date(b.dateRaw).getTime();
        return da - db;
    });
});

// Treatment Gantt Bars
const dmtLines = computed(() => {
    return props.treatments
        .filter((t) => t.category === 'fond' || t.status === 'en_cours')
        .sort((a, b) => {
            const da = a.start_date_raw ? new Date(a.start_date_raw).getTime() : 0;
            const db = b.start_date_raw ? new Date(b.start_date_raw).getTime() : 0;
            return da - db;
        });
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header & Interactive Filter Bar -->
        <Card class="border-border">
            <CardHeader class="pb-3">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Activity class="size-4" />
                            </div>
                            <CardTitle class="text-base font-bold text-foreground">
                                Module 5 : Diagramme Chronologique de l'Évolution de la Maladie
                            </CardTitle>
                        </div>
                        <CardDescription class="text-xs mt-1">
                            Visualisation interactive unifiée : Poussées aiguës, Solumédrol, Lignes de traitements de fond (DMT), Évolution EDSS et Imageries IRM.
                        </CardDescription>
                    </div>

                    <!-- Filter Checkboxes / Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :class="['h-7 text-xs px-2.5 gap-1.5', filterShowRelapses ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30 font-semibold' : 'opacity-60']"
                            @click="filterShowRelapses = !filterShowRelapses"
                        >
                            <HeartPulse class="size-3 text-rose-500" />
                            <span>Poussées ({{ relapses.length }})</span>
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :class="['h-7 text-xs px-2.5 gap-1.5', filterShowTreatments ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30 font-semibold' : 'opacity-60']"
                            @click="filterShowTreatments = !filterShowTreatments"
                        >
                            <Pill class="size-3 text-emerald-500" />
                            <span>DMT & Fond ({{ treatments.length }})</span>
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :class="['h-7 text-xs px-2.5 gap-1.5', filterShowClinical ? 'bg-primary/10 text-primary border-primary/30 font-semibold' : 'opacity-60']"
                            @click="filterShowClinical = !filterShowClinical"
                        >
                            <Activity class="size-3 text-primary" />
                            <span>EDSS ({{ clinicalExams.length }})</span>
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :class="['h-7 text-xs px-2.5 gap-1.5', filterShowMri ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30 font-semibold' : 'opacity-60']"
                            @click="filterShowMri = !filterShowMri"
                        >
                            <Crosshair class="size-3 text-amber-500" />
                            <span>IRM ({{ mriExams.length }})</span>
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <!-- DMT Sequences / Gantt Bar (Module 5 Specification) -->
            <CardContent v-if="dmtLines.length > 0" class="pt-0 pb-4">
                <div class="p-3 rounded-xl bg-muted/20 border border-border/60 space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold text-foreground">
                        <span class="flex items-center gap-1.5">
                            <Pill class="size-3.5 text-primary" />
                            <span>Lignes Thérapeutiques de Fond (Historique des Switchs)</span>
                        </span>
                        <span class="text-[11px] text-muted-foreground font-mono">
                            {{ dmtLines.length }} séquence(s) thérapeutique(s)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5 pt-1">
                        <div
                            v-for="(dmt, idx) in dmtLines"
                            :key="dmt.id"
                            :class="[
                                'p-2.5 rounded-lg border text-xs space-y-1 transition-all',
                                dmt.status === 'en_cours'
                                    ? 'bg-emerald-500/5 border-emerald-500/30 shadow-xs'
                                    : 'bg-card border-border/70'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-foreground flex items-center gap-1.5">
                                    <span class="size-5 rounded-full bg-primary/10 text-primary text-[10px] flex items-center justify-center font-bold font-mono">
                                        {{ idx + 1 }}
                                    </span>
                                    <span>{{ dmt.molecule_name }}</span>
                                </span>
                                <Badge
                                    v-if="dmt.status === 'en_cours'"
                                    class="text-[9px] bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30"
                                >
                                    En cours
                                </Badge>
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="text-[9px]"
                                >
                                    {{ dmt.status === 'remplace' ? 'Remplacé' : 'Arrêté' }}
                                </Badge>
                            </div>

                            <div class="text-[11px] text-muted-foreground space-y-0.5 pt-0.5">
                                <div class="font-mono">
                                    {{ dmt.start_date || '—' }} <ArrowRight class="inline size-3 mx-0.5 text-muted-foreground" /> {{ dmt.end_date || 'Présent' }}
                                </div>
                                <div v-if="dmt.stop_reason" class="text-[10px] text-amber-700 dark:text-amber-400 font-medium truncate" :title="dmt.stop_reason">
                                    Motif arrêt : {{ dmt.stop_reason }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Chronological Interactive Timeline Stream -->
        <div v-if="unifiedTimelineEvents.length > 0" class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-border/80">
            <div
                v-for="evt in unifiedTimelineEvents"
                :key="evt.id"
                class="relative group"
            >
                <!-- Marker Dot -->
                <div
                    :class="[
                        'absolute -left-6 sm:-left-8 top-1.5 size-6 sm:size-7 rounded-full flex items-center justify-center border shadow-xs transition-transform group-hover:scale-110',
                        evt.type === 'relapse' ? 'bg-rose-500 text-white border-rose-600' :
                        evt.type === 'treatment' ? 'bg-emerald-500 text-white border-emerald-600' :
                        evt.type === 'clinical' ? 'bg-primary text-primary-foreground border-primary' :
                        evt.type === 'mri' ? 'bg-amber-500 text-white border-amber-600' :
                        'bg-indigo-500 text-white border-indigo-600'
                    ]"
                >
                    <HeartPulse v-if="evt.type === 'relapse'" class="size-3.5" />
                    <Pill v-else-if="evt.type === 'treatment'" class="size-3.5" />
                    <Activity v-else-if="evt.type === 'clinical'" class="size-3.5" />
                    <Crosshair v-else-if="evt.type === 'mri'" class="size-3.5" />
                    <Sparkles v-else class="size-3.5" />
                </div>

                <!-- Event Content Card -->
                <div class="rounded-xl border border-border bg-card p-4 hover:border-primary/40 hover:shadow-xs transition-all space-y-2.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-1.5 border-b border-border/40">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-mono font-bold text-muted-foreground">
                                    {{ evt.dateFormatted }}
                                </span>
                                <span class="font-bold text-sm text-foreground">
                                    {{ evt.title }}
                                </span>
                                <Badge v-if="evt.statusBadge" :class="['text-[10px] px-2 py-0 border', evt.statusBadge.class]">
                                    {{ evt.statusBadge.label }}
                                </Badge>
                            </div>
                            <p v-if="evt.subtitle" class="text-xs text-muted-foreground mt-0.5">
                                {{ evt.subtitle }}
                            </p>
                        </div>
                    </div>

                    <!-- Contextual Event Specifics -->
                    <!-- Relapse details -->
                    <div v-if="evt.type === 'relapse'" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs bg-muted/20 p-2.5 rounded-lg border border-border/50">
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Traitement de Poussée :</span>
                            <strong class="text-foreground">{{ evt.details.treatment }}</strong>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Récupération :</span>
                            <span class="capitalize font-medium text-foreground">{{ evt.details.recovery }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Séquelles :</span>
                            <span class="text-foreground">{{ evt.details.sequelae }}</span>
                        </div>
                    </div>

                    <!-- Treatment details -->
                    <div v-else-if="evt.type === 'treatment'" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs bg-muted/20 p-2.5 rounded-lg border border-border/50">
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Classe Thérapeutique :</span>
                            <strong class="text-foreground">{{ evt.details.therapeuticClass }}</strong>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Période d'Administration :</span>
                            <span class="font-mono text-foreground">{{ evt.details.start }} → {{ evt.details.end }}</span>
                        </div>
                        <div v-if="evt.details.stopReason && evt.details.stopReason !== 'Néant'">
                            <span class="text-muted-foreground block text-[11px]">Motif d'Interruption :</span>
                            <span class="text-amber-700 dark:text-amber-400 font-medium">{{ evt.details.stopReason }}</span>
                        </div>
                    </div>

                    <!-- Clinical Exam details -->
                    <div v-else-if="evt.type === 'clinical'" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs bg-muted/20 p-2.5 rounded-lg border border-border/50">
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Score EDSS :</span>
                            <strong class="text-primary font-mono text-sm">EDSS {{ evt.details.edss }}</strong>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Marche T25FW :</span>
                            <span class="font-mono text-foreground">{{ evt.details.t25fw }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Dextérité 9HPT :</span>
                            <span class="font-mono text-foreground">{{ evt.details.nine_hpt }}</span>
                        </div>
                    </div>

                    <!-- MRI details -->
                    <div v-else-if="evt.type === 'mri'" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs bg-muted/20 p-2.5 rounded-lg border border-border/50">
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Protocole SEP :</span>
                            <strong :class="evt.rawItem?.sep_protocol_applied ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                                {{ evt.details.sepProtocol }}
                            </strong>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Activité Récente :</span>
                            <span class="text-foreground font-medium">{{ evt.details.newLesions }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[11px]">Rehaussement Gadolinium :</span>
                            <span class="text-foreground">{{ evt.details.contrast }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-12 rounded-xl border border-dashed border-border bg-card p-6 space-y-3">
            <Activity class="size-10 mx-auto text-muted-foreground/40" />
            <div class="space-y-1">
                <p class="text-sm font-semibold text-foreground">Aucun événement chronologique disponible</p>
                <p class="text-xs text-muted-foreground max-w-md mx-auto">
                    Activez les filtres ci-dessus ou ajoutez des poussées, des traitements ou des examens cliniques pour afficher la chronologie de la maladie.
                </p>
            </div>
        </div>
    </div>
</template>
