<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    Eye,
    FileText,
    HeartPulse,
    Loader2,
    Save,
    Sparkles,
    Stethoscope,
    User,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { store as examStore, update as examUpdate } from '@/routes/patients/clinical-exams';

export type FunctionalSystemsScores = {
    pyramidal?: number | null;
    cerebelleux?: number | null;
    tronc?: number | null;
    sensitif?: number | null;
    sphincterien?: number | null;
    visuel?: number | null;
    fonctions_cerebrales?: number | null;
};

export type ClinicalExamFormData = {
    id?: number;
    exam_date?: string | null;
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
    functional_systems_scores?: FunctionalSystemsScores | null;
    evaluator_name: string | null;
    clinical_comment: string | null;
};

type Props = {
    patientId: number;
    exam?: ClinicalExamFormData | null;
};

const props = defineProps<Props>();
const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);

const isEditing = computed(() => Boolean(props.exam && props.exam.id));
const showFunctionalSystems = ref(false);

const form = useForm({
    exam_date: new Date().toISOString().split('T')[0],
    edss_score: '2.0',
    t25fw: '' as number | string,
    nine_hpt: '' as number | string,
    syndrome_pyramidal: 'absent',
    syndrome_vestibulaire: 'absent',
    syndrome_ataxo_spasmodique: 'absent',
    syndrome_medullaire: 'absent',
    norb: 'absent',
    cranial_nerves_impairment: 'absent',
    cranial_nerves_details: '',
    other_signs: '',
    functional_systems_scores: {
        pyramidal: null,
        cerebelleux: null,
        tronc: null,
        sensitif: null,
        sphincterien: null,
        visuel: null,
        fonctions_cerebrales: null,
    } as FunctionalSystemsScores,
    evaluator_name: currentUser.value?.name || '',
    clinical_comment: '',
});

// Sync form values on dialog open or exam change
watch(
    () => [open.value, props.exam],
    ([isOpen]) => {
        if (isOpen) {
            if (props.exam) {
                form.exam_date = props.exam.exam_date_raw || '';
                form.edss_score = String(props.exam.edss_score ?? '2.0');
                form.t25fw = props.exam.t25fw !== null && props.exam.t25fw !== undefined ? String(props.exam.t25fw) : '';
                form.nine_hpt = props.exam.nine_hpt !== null && props.exam.nine_hpt !== undefined ? String(props.exam.nine_hpt) : '';
                form.syndrome_pyramidal = props.exam.syndrome_pyramidal || 'absent';
                form.syndrome_vestibulaire = props.exam.syndrome_vestibulaire || 'absent';
                form.syndrome_ataxo_spasmodique = props.exam.syndrome_ataxo_spasmodique || 'absent';
                form.syndrome_medullaire = props.exam.syndrome_medullaire || 'absent';
                form.norb = props.exam.norb || 'absent';
                form.cranial_nerves_impairment = props.exam.cranial_nerves_impairment || 'absent';
                form.cranial_nerves_details = props.exam.cranial_nerves_details || '';
                form.other_signs = props.exam.other_signs || '';
                form.functional_systems_scores = props.exam.functional_systems_scores || {
                    pyramidal: null,
                    cerebelleux: null,
                    tronc: null,
                    sensitif: null,
                    sphincterien: null,
                    visuel: null,
                    fonctions_cerebrales: null,
                };
                form.evaluator_name = props.exam.evaluator_name || currentUser.value?.name || '';
                form.clinical_comment = props.exam.clinical_comment || '';
                showFunctionalSystems.value = Boolean(
                    props.exam.functional_systems_scores &&
                    Object.values(props.exam.functional_systems_scores).some((v) => v !== null && v !== undefined)
                );
            } else {
                form.reset();
                form.exam_date = new Date().toISOString().split('T')[0];
                form.edss_score = '2.0';
                form.t25fw = '';
                form.nine_hpt = '';
                form.syndrome_pyramidal = 'absent';
                form.syndrome_vestibulaire = 'absent';
                form.syndrome_ataxo_spasmodique = 'absent';
                form.syndrome_medullaire = 'absent';
                form.norb = 'absent';
                form.cranial_nerves_impairment = 'absent';
                form.cranial_nerves_details = '';
                form.other_signs = '';
                form.functional_systems_scores = {
                    pyramidal: null,
                    cerebelleux: null,
                    tronc: null,
                    sensitif: null,
                    sphincterien: null,
                    visuel: null,
                    fonctions_cerebrales: null,
                };
                form.evaluator_name = currentUser.value?.name || '';
                form.clinical_comment = '';
                showFunctionalSystems.value = false;
            }
            form.clearErrors();
        }
    },
    { immediate: true }
);

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

// Quick options for syndromes
const pyramidalOptions = [
    { value: 'absent', label: 'Absent (Normal)' },
    { value: 'déficit moteur', label: 'Déficit moteur' },
    { value: 'hyperréflexie / babinski', label: 'Hyperréflexie / Babinski' },
    { value: 'spasticité', label: 'Spasticité prédominante' },
    { value: 'paraparésie', label: 'Paraparésie spasmodique' },
];

const vestibulaireOptions = [
    { value: 'absent', label: 'Absent' },
    { value: 'ataxie statique / romberg', label: 'Ataxie statique / Romberg +' },
    { value: 'ataxie cinétique', label: 'Ataxie cinétique' },
    { value: 'vertiges / nystagmus', label: 'Vertiges / Nystagmus' },
];

const ataxoSpasmodiqueOptions = [
    { value: 'absent', label: 'Absent' },
    { value: 'léger', label: 'Léger' },
    { value: 'modéré', label: 'Modéré' },
    { value: 'sévère', label: 'Sévère' },
];

const medullaireOptions = [
    { value: 'absent', label: 'Absent' },
    { value: 'niveau sensitif', label: 'Niveau sensitif net' },
    { value: 'troubles sphinctériens', label: 'Troubles sphinctériens' },
    { value: 'signe de lhermitte', label: 'Signe de Lhermitte' },
];

const norbOptions = [
    { value: 'absent', label: 'Absent' },
    { value: 'séquelle unilatérale', label: 'Séquelle unilatérale' },
    { value: 'séquelle bilatérale', label: 'Séquelle bilatérale' },
    { value: 'poussée en cours', label: 'Épisode aigu en cours' },
];

const cranialNervesOptions = [
    { value: 'absent', label: 'Absente' },
    { value: 'oculomotrice (III/VI)', label: 'Atteinte oculomotrice (III / VI)' },
    { value: 'paralysie faciale (VII)', label: 'Paralysie faciale périphérique (VII)' },
    { value: 'névralgie trigéminale (V)', label: 'Névralgie trigéminale (V)' },
    { value: 'autre', label: 'Autre paire crânienne' },
];

// Functional systems definitions
const functionalSystemsConfig = [
    { key: 'pyramidal', label: 'Pyramidal (Force musculaire)', max: 5 },
    { key: 'cerebelleux', label: 'Cérébelleux (Coordination & Équilibre)', max: 5 },
    { key: 'tronc', label: 'Tronc cérébral (Parole, Déglutition, Paires)', max: 5 },
    { key: 'sensitif', label: 'Sensitif (Sensibilité tactile, proprioceptive)', max: 5 },
    { key: 'sphincterien', label: 'Sphinctérien (Vessie & Intestin)', max: 5 },
    { key: 'visuel', label: 'Visuel (Acuité visuelle & Champ)', max: 5 },
    { key: 'fonctions_cerebrales', label: 'Cérébral (Cognition & Humeur)', max: 5 },
] as const;

const submit = () => {
    form.transform((data) => ({
        ...data,
        t25fw: data.t25fw === '' || data.t25fw === null ? null : Number(data.t25fw),
        nine_hpt: data.nine_hpt === '' || data.nine_hpt === null ? null : Number(data.nine_hpt),
    }));

    if (isEditing.value && props.exam?.id) {
        form.put(examUpdate.url({ patient: props.patientId, clinicalExam: props.exam.id }), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        });
    } else {
        form.post(examStore.url(props.patientId), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        });
    }
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-[calc(100vw-1.5rem)] sm:max-w-3xl max-h-[90vh] overflow-y-auto p-4 sm:p-6">
            <DialogHeader class="pb-3 border-b border-border/60">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Stethoscope class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold">
                            {{ isEditing ? 'Modifier l\'Évaluation Clinique' : 'Enregistrer une Évaluation Clinique' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Examen neurologique objectif, calcul de l'EDSS et cotation des systèmes fonctionnels de Kurtzke.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 pt-3">
                <!-- Section 1: Date & Score EDSS & Examinateur -->
                <div class="space-y-4 p-4 rounded-xl border border-border/70 bg-card">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <Activity class="size-3.5 text-primary" />
                            <span>Score Global d'Invalidité EDSS</span>
                        </h4>
                        <span class="text-xs text-muted-foreground font-mono">0.0 (Normal) à 10.0 (Décès)</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Date de l'examen -->
                        <div class="space-y-1.5">
                            <Label for="exam_date" class="text-sm font-medium">
                                Date de l'Évaluation <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="exam_date"
                                v-model="form.exam_date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.exam_date" />
                        </div>

                        <!-- Examinateur -->
                        <div class="space-y-1.5">
                            <Label for="evaluator_name" class="text-sm font-medium">
                                Médecin Examinateur
                            </Label>
                            <Input
                                id="evaluator_name"
                                v-model="form.evaluator_name"
                                placeholder="ex: Dr. Neurologue"
                            />
                            <InputError :message="form.errors.evaluator_name" />
                        </div>
                    </div>

                    <!-- EDSS Dual Input / Select Combo -->
                    <div class="space-y-2 pt-1">
                        <div class="flex items-center justify-between">
                            <Label for="clinical_edss_score" class="text-sm font-medium">
                                Score EDSS Objectivé <span class="text-destructive">*</span>
                            </Label>
                            <button
                                v-if="form.edss_score"
                                type="button"
                                @click="form.edss_score = ''"
                                class="text-[11px] text-muted-foreground hover:text-destructive underline decoration-dotted transition-colors"
                            >
                                Effacer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div class="relative">
                                <Input
                                    id="clinical_edss_score"
                                    v-model="form.edss_score"
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    max="10"
                                    placeholder="Saisie libre (ex: 2.5)"
                                    class="font-mono text-base font-bold text-primary"
                                    required
                                />
                                <span class="absolute right-2.5 top-2.5 text-xs text-muted-foreground pointer-events-none font-mono">
                                    / 10
                                </span>
                            </div>

                            <Select v-model="form.edss_score">
                                <SelectTrigger class="w-full font-mono text-sm">
                                    <SelectValue placeholder="Choisir dans la liste..." />
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

                        <!-- Quick Benchmark Pills -->
                        <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                            <span class="text-[11px] font-medium text-muted-foreground mr-1">Repères rapides :</span>
                            <button
                                v-for="preset in ['0.0', '1.5', '2.0', '3.0', '4.0', '6.0', '6.5', '7.0']"
                                :key="preset"
                                type="button"
                                @click="form.edss_score = preset"
                                :class="[
                                    'px-2 py-0.5 rounded text-xs font-mono font-medium border transition-colors',
                                    form.edss_score === preset || Number(form.edss_score) === Number(preset)
                                        ? 'bg-primary text-primary-foreground border-primary font-semibold'
                                        : 'bg-muted/50 hover:bg-muted text-foreground border-border/70'
                                ]"
                            >
                                {{ preset }}
                            </button>
                        </div>

                        <!-- Live Kurtzke Interpretation Banner -->
                        <div
                            v-if="getEdssInterpretation(form.edss_score)"
                            class="p-2.5 rounded-lg bg-primary/5 border border-primary/20 text-xs flex items-center gap-2 text-foreground"
                        >
                            <Activity class="size-3.5 text-primary shrink-0" />
                            <div>
                                <span class="font-bold font-mono text-primary mr-1.5">EDSS {{ form.edss_score }} :</span>
                                <span>{{ getEdssInterpretation(form.edss_score) }}</span>
                            </div>
                        </div>

                        <InputError :message="form.errors.edss_score" />
                    </div>

                    <!-- Tests Fonctionnels Objectifs (T25FW & 9HPT - Module 7) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border/60">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="t25fw" class="text-xs font-medium">
                                    Test de Marche T25FW (secondes)
                                </Label>
                                <span class="text-[10px] text-muted-foreground font-mono">Timed 25-Foot Walk</span>
                            </div>
                            <Input
                                id="t25fw"
                                v-model="form.t25fw"
                                type="number"
                                step="0.1"
                                min="0"
                                max="500"
                                placeholder="ex: 5.2"
                                class="h-9 text-xs font-mono"
                            />
                            <p class="text-[10px] text-muted-foreground">Temps pour parcourir 7,62 mètres</p>
                            <InputError :message="form.errors.t25fw" />
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="nine_hpt" class="text-xs font-medium">
                                    Test de Dextérité 9HPT (secondes)
                                </Label>
                                <span class="text-[10px] text-muted-foreground font-mono">9-Hole Peg Test</span>
                            </div>
                            <Input
                                id="nine_hpt"
                                v-model="form.nine_hpt"
                                type="number"
                                step="0.1"
                                min="0"
                                max="500"
                                placeholder="ex: 19.8"
                                class="h-9 text-xs font-mono"
                            />
                            <p class="text-[10px] text-muted-foreground">Temps du test des 9 chevilles</p>
                            <InputError :message="form.errors.nine_hpt" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Syndromes Neurologiques Objectivés -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <Stethoscope class="size-3.5 text-primary" />
                            <span>Syndromes Neurologiques Objectivés</span>
                        </h4>
                        <span class="text-[11px] text-muted-foreground">Examen physique ciblé</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Syndrome Pyramidal -->
                        <div class="space-y-1.5">
                            <Label for="syndrome_pyramidal" class="text-xs font-medium">
                                Syndrome Pyramidal
                            </Label>
                            <Select v-model="form.syndrome_pyramidal">
                                <SelectTrigger id="syndrome_pyramidal" class="text-xs">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in pyramidalOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.syndrome_pyramidal" />
                        </div>

                        <!-- Syndrome Vestibulaire / Cérébelleux -->
                        <div class="space-y-1.5">
                            <Label for="syndrome_vestibulaire" class="text-xs font-medium">
                                Syndrome Cérébello-Vestibulaire
                            </Label>
                            <Select v-model="form.syndrome_vestibulaire">
                                <SelectTrigger id="syndrome_vestibulaire" class="text-xs">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in vestibulaireOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.syndrome_vestibulaire" />
                        </div>

                        <!-- Syndrome Ataxo-spasmodique -->
                        <div class="space-y-1.5">
                            <Label for="syndrome_ataxo_spasmodique" class="text-xs font-medium">
                                Syndrome Ataxo-spasmodique (Marche)
                            </Label>
                            <Select v-model="form.syndrome_ataxo_spasmodique">
                                <SelectTrigger id="syndrome_ataxo_spasmodique" class="text-xs">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in ataxoSpasmodiqueOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.syndrome_ataxo_spasmodique" />
                        </div>

                        <!-- Syndrome Médullaire -->
                        <div class="space-y-1.5">
                            <Label for="syndrome_medullaire" class="text-xs font-medium">
                                Syndrome Médullaire
                            </Label>
                            <Select v-model="form.syndrome_medullaire">
                                <SelectTrigger id="syndrome_medullaire" class="text-xs">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in medullaireOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.syndrome_medullaire" />
                        </div>

                        <!-- Névrite optique (NORB) -->
                        <div class="space-y-1.5">
                            <Label for="norb" class="text-xs font-medium">
                                Atteinte Visuelle / NORB
                            </Label>
                            <Select v-model="form.norb">
                                <SelectTrigger id="norb" class="text-xs">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in norbOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.norb" />
                        </div>

                        <!-- Paires Crâniennes -->
                        <div class="space-y-1.5">
                            <Label for="cranial_nerves_impairment" class="text-xs font-medium">
                                Atteinte des Paires Crâniennes
                            </Label>
                            <Select v-model="form.cranial_nerves_impairment">
                                <SelectTrigger id="cranial_nerves_impairment" class="text-xs">
                                    <SelectValue placeholder="Sélectionner..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in cranialNervesOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.cranial_nerves_impairment" />
                        </div>
                    </div>

                    <!-- Détails Paires Crâniennes si atteinte présente -->
                    <div
                        v-if="form.cranial_nerves_impairment !== 'absent'"
                        class="space-y-1.5 p-3 rounded-lg bg-muted/30 border border-border/70"
                    >
                        <Label for="cranial_nerves_details" class="text-xs font-medium">
                            Précisions sur l'atteinte des paires crâniennes
                        </Label>
                        <Input
                            id="cranial_nerves_details"
                            v-model="form.cranial_nerves_details"
                            placeholder="ex: Diplopie dans le regard à droite, atteinte du VI droit..."
                            class="text-xs"
                        />
                        <InputError :message="form.errors.cranial_nerves_details" />
                    </div>
                </div>

                <Separator />

                <!-- Section 3: Cotation Détaillée des 7 Systèmes Fonctionnels (Optionnel) -->
                <div class="space-y-3">
                    <button
                        type="button"
                        @click="showFunctionalSystems = !showFunctionalSystems"
                        class="w-full flex items-center justify-between p-3 rounded-lg border border-border/70 hover:bg-muted/30 transition-colors text-left"
                    >
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-4 text-primary" />
                            <div>
                                <span class="text-xs font-semibold text-foreground block">
                                    Cotation des 7 Systèmes Fonctionnels de Kurtzke (FS)
                                </span>
                                <span class="text-[11px] text-muted-foreground">
                                    Sous-scores facultatifs (0 à 5) pour un dossier de recherche clinique exhaustif
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <span>{{ showFunctionalSystems ? 'Masquer' : 'Afficher' }}</span>
                            <ChevronUp v-if="showFunctionalSystems" class="size-4" />
                            <ChevronDown v-else class="size-4" />
                        </div>
                    </button>

                    <div v-if="showFunctionalSystems" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl border border-border/60 bg-muted/10">
                        <div
                            v-for="fs in functionalSystemsConfig"
                            :key="fs.key"
                            class="space-y-1 p-2.5 rounded-lg border border-border/40 bg-card"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <Label :for="`fs_${fs.key}`" class="font-medium text-foreground text-xs">
                                    {{ fs.label }}
                                </Label>
                                <span class="font-mono text-primary font-bold">
                                    {{ form.functional_systems_scores[fs.key] !== null ? `Grade ${form.functional_systems_scores[fs.key]}` : '—' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 pt-1">
                                <button
                                    v-for="grade in [0, 1, 2, 3, 4, 5]"
                                    :key="grade"
                                    type="button"
                                    @click="form.functional_systems_scores[fs.key] = form.functional_systems_scores[fs.key] === grade ? null : grade"
                                    :class="[
                                        'size-7 rounded font-mono text-xs font-semibold transition-colors flex items-center justify-center border',
                                        form.functional_systems_scores[fs.key] === grade
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'bg-muted/40 hover:bg-muted text-muted-foreground border-border/60'
                                    ]"
                                >
                                    {{ grade }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Section 4: Autres Signes & Commentaires Cliniques -->
                <div class="space-y-3">
                    <div class="space-y-1.5">
                        <Label for="other_signs" class="text-xs font-medium">
                            Autres Signes Cliniques (Fatigue, Troubles cognitifs, Douleurs neuropathiques...)
                        </Label>
                        <Textarea
                            id="other_signs"
                            v-model="form.other_signs"
                            rows="2"
                            placeholder="ex: Fatigue chronique avec score EMIF-SEP élevé, troubles attentionnels signalés par l'entourage..."
                            class="text-xs"
                        />
                        <InputError :message="form.errors.other_signs" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="clinical_comment" class="text-xs font-medium">
                            Synthèse & Décision Thérapeutique de la Consultation
                        </Label>
                        <Textarea
                            id="clinical_comment"
                            v-model="form.clinical_comment"
                            rows="3"
                            placeholder="ex: Stabilité clinique et de l'EDSS sous Ocrélizumab. Tolérance excellente. Poursuite du protocole actuel sans modification..."
                            class="text-xs"
                        />
                        <InputError :message="form.errors.clinical_comment" />
                    </div>
                </div>

                <DialogFooter class="pt-3 border-t border-border/60 flex items-center justify-between gap-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button type="button" variant="outline" size="sm" :disabled="form.processing">
                            Annuler
                        </Button>
                    </DialogClose>
                    <Button type="submit" size="sm" :disabled="form.processing" class="gap-1.5">
                        <Loader2 v-if="form.processing" class="size-3.5 animate-spin" />
                        <Save v-else class="size-3.5" />
                        <span>{{ isEditing ? 'Mettre à jour l\'évaluation' : 'Enregistrer l\'évaluation' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
