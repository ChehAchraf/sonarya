<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    HeartPulse,
    Loader2,
    Pill,
    Save,
    Stethoscope,
    TrendingUp,
    X,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { store as relapseStore, update as relapseUpdate } from '@/routes/patients/relapses';

export type RelapseFormData = {
    id?: number;
    start_date?: string | null;
    start_date_raw?: string | null;
    end_date?: string | null;
    end_date_raw?: string | null;
    duration_days?: number | null;
    symptoms: string;
    severity?: 'legere' | 'moderee' | 'severe' | string | null;
    is_before_diagnosis: boolean;
    treatment_administered: string | null;
    administration_route: string | null;
    clinical_recovery: string | null;
    edss_before_relapse?: number | string | null;
    edss_after_relapse?: number | string | null;
    edss_evaluation_date?: string | null;
    edss_evaluation_date_raw?: string | null;
    sequelae: string | null;
    hospitalization_required: boolean;
    notes: string | null;
};

type Props = {
    patientId: number;
    diagnosisDate?: string | null;
    relapse?: RelapseFormData | null;
};

const props = defineProps<Props>();
const open = defineModel<boolean>('open', { default: false });

const isEditing = computed(() => Boolean(props.relapse && props.relapse.id));

const form = useForm({
    start_date: '',
    end_date: '',
    symptoms: '',
    severity: 'moderee' as 'legere' | 'moderee' | 'severe' | string,
    is_before_diagnosis: false,
    treatment_administered: 'Bolus Solumédrol 1g/j IV x 3 jours',
    administration_route: 'Intraveineuse (IV)',
    clinical_recovery: 'Complète',
    edss_before_relapse: '' as number | string,
    edss_after_relapse: '' as number | string,
    edss_evaluation_date: '',
    sequelae: '',
    hospitalization_required: false,
    notes: '',
});

// Sync form values when the dialog opens or when relapse prop changes
watch(
    () => [open.value, props.relapse],
    ([isOpen]) => {
        if (isOpen) {
            if (props.relapse) {
                form.start_date = props.relapse.start_date_raw || '';
                form.end_date = props.relapse.end_date_raw || '';
                form.symptoms = props.relapse.symptoms || '';
                form.severity = props.relapse.severity || 'moderee';
                form.is_before_diagnosis = Boolean(props.relapse.is_before_diagnosis);
                form.treatment_administered = props.relapse.treatment_administered || '';
                form.administration_route = props.relapse.administration_route || 'Intraveineuse (IV)';
                form.clinical_recovery = props.relapse.clinical_recovery || 'Complète';
                form.edss_before_relapse = props.relapse.edss_before_relapse !== null && props.relapse.edss_before_relapse !== undefined ? props.relapse.edss_before_relapse : '';
                form.edss_after_relapse = props.relapse.edss_after_relapse !== null && props.relapse.edss_after_relapse !== undefined ? props.relapse.edss_after_relapse : '';
                form.edss_evaluation_date = props.relapse.edss_evaluation_date_raw || '';
                form.sequelae = props.relapse.sequelae || '';
                form.hospitalization_required = Boolean(props.relapse.hospitalization_required);
                form.notes = props.relapse.notes || '';
            } else {
                form.reset();
                form.start_date = '';
                form.end_date = '';
                form.symptoms = '';
                form.severity = 'moderee';
                form.is_before_diagnosis = false;
                form.treatment_administered = 'Bolus Solumédrol 1g/j IV x 3 jours';
                form.administration_route = 'Intraveineuse (IV)';
                form.clinical_recovery = 'Complète';
                form.edss_before_relapse = '';
                form.edss_after_relapse = '';
                form.edss_evaluation_date = '';
                form.sequelae = '';
                form.hospitalization_required = false;
                form.notes = '';
            }
            form.clearErrors();
        }
    },
    { immediate: true }
);

// Auto-calculation of duration in days
const calculatedDurationDays = computed(() => {
    if (!form.start_date || !form.end_date) return null;
    const start = new Date(form.start_date);
    const end = new Date(form.end_date);
    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) return null;
    const diffTime = Math.abs(end.getTime() - start.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

// Auto-suggest pre-diagnosis status based on date
watch(
    () => form.start_date,
    (dateStr) => {
        if (!isEditing.value && dateStr && props.diagnosisDate) {
            const start = new Date(dateStr);
            const diag = new Date(props.diagnosisDate);
            if (!isNaN(start.getTime()) && !isNaN(diag.getTime())) {
                form.is_before_diagnosis = start < diag;
            }
        }
    }
);

// Quick symptom chips
const quickSymptoms = [
    'NORB (Névrite optique rétrobulbaire)',
    'Déficit moteur / Paraparésie',
    'Paresthésies et engourdissements',
    'Signe de Lhermitte',
    'Ataxie / Troubles de l\'équilibre',
    'Diplopie / Ophtalmoplégie internucléaire',
    'Troubles sphinctériens aigus',
    'Myélite aiguë transverse',
    'Syndrome vestibulaire / Vertiges',
];

const appendSymptom = (chip: string) => {
    if (!form.symptoms) {
        form.symptoms = chip;
    } else if (!form.symptoms.includes(chip)) {
        form.symptoms += ` • ${chip}`;
    }
};

// Protocol chips
const treatmentProtocols = [
    'Bolus Solumédrol 1g/j IV x 3 jours',
    'Bolus Solumédrol 1g/j IV x 5 jours',
    'Corticothérapie orale forte dose',
    'Échanges plasmatiques (Plasmaphérèse)',
    'Immunoglobulines IV (IgIV)',
    'Aucun traitement corticoïde',
];

const setTreatment = (protocol: string) => {
    form.treatment_administered = protocol;
    if (protocol.includes('IV')) {
        form.administration_route = 'Intraveineuse (IV)';
    } else if (protocol.includes('orale')) {
        form.administration_route = 'Orale (PO)';
    } else if (protocol.includes('Aucun')) {
        form.administration_route = 'Néant';
    }
};

const recoveryOptions = [
    { value: 'Complète', label: 'Récupération Complète (Ad integrum)' },
    { value: 'Incomplète', label: 'Récupération Incomplète (Séquelles persistantes)' },
    { value: 'Nulle', label: 'Récupération Nulle (Échec du traitement)' },
    { value: 'Non évaluée', label: 'Non évaluée / En cours d\'évolution' },
];

const administrationRoutes = [
    'Intraveineuse (IV)',
    'Orale (PO)',
    'Autre',
    'Néant',
];

const submit = () => {
    if (isEditing.value && props.relapse?.id) {
        form.put(relapseUpdate.url({ patient: props.patientId, relapse: props.relapse.id }), {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        });
    } else {
        form.post(relapseStore.url(props.patientId), {
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
        <DialogContent class="max-w-[calc(100vw-1.5rem)] sm:max-w-2xl max-h-[90vh] overflow-y-auto p-4 sm:p-6">
            <DialogHeader class="pb-3 border-b border-border/60">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <HeartPulse class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold">
                            {{ isEditing ? 'Modifier la Poussée' : 'Enregistrer une Nouvelle Poussée' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Registre clinique des poussées aiguës, traitements administrés et mode de récupération.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-5 pt-3">
                <!-- Section 1: Dates & Moment -->
                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Calendar class="size-3.5 text-primary" />
                        <span>Chronologie de l'Épisode Aigu</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Date Début -->
                        <div class="space-y-1.5">
                            <Label for="relapse_start_date" class="text-sm font-medium">
                                Date de Début <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="relapse_start_date"
                                v-model="form.start_date"
                                type="date"
                                required
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>

                        <!-- Date Fin -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="relapse_end_date" class="text-sm font-medium">
                                    Date de Fin
                                </Label>
                                <span v-if="calculatedDurationDays !== null" class="text-xs font-semibold text-primary font-mono">
                                    Durée : {{ calculatedDurationDays }} jours
                                </span>
                            </div>
                            <Input
                                id="relapse_end_date"
                                v-model="form.end_date"
                                type="date"
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>

                    <!-- Moment / Statut par rapport au diagnostic -->
                    <div class="flex items-center gap-2 p-3 rounded-lg border border-border/70 bg-muted/20">
                        <Checkbox
                            id="is_before_diagnosis"
                            :checked="form.is_before_diagnosis"
                            @update:checked="(val: boolean | 'indeterminate') => (form.is_before_diagnosis = Boolean(val))"
                        />
                        <Label
                            for="is_before_diagnosis"
                            class="text-xs font-medium cursor-pointer text-foreground"
                        >
                            Poussée inaugurale ou survenue <strong>avant la confirmation du diagnostic</strong> de SEP
                        </Label>
                    </div>
                </div>

                <Separator />

                <!-- Section 2: Symptômes Cliniques -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label for="relapse_symptoms" class="text-sm font-medium flex items-center gap-1.5">
                            <Stethoscope class="size-3.5 text-primary" />
                            <span>Symptômes & Atteintes Neurologiques</span>
                            <span class="text-destructive">*</span>
                        </Label>
                        <span class="text-[11px] text-muted-foreground">Cliquez pour insérer rapidement</span>
                    </div>

                    <!-- Quick Symptom Chips -->
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="chip in quickSymptoms"
                            :key="chip"
                            type="button"
                            @click="appendSymptom(chip)"
                            class="text-[11px] px-2.5 py-1 rounded-md bg-muted/70 hover:bg-muted text-foreground border border-border/60 transition-colors"
                        >
                            + {{ chip }}
                        </button>
                    </div>

                    <Textarea
                        id="relapse_symptoms"
                        v-model="form.symptoms"
                        rows="3"
                        placeholder="Description détaillée de l'épisode neurologique (ex: NORB gauche avec baisse de l'acuité visuelle et douleurs rétro-orbitaires)..."
                        class="text-sm"
                        required
                    />
                    <InputError :message="form.errors.symptoms" />

                    <!-- Sévérité Clinique de la Poussée -->
                    <div class="space-y-1.5 pt-2">
                        <Label class="text-xs font-semibold text-foreground flex items-center justify-between">
                            <span>Sévérité Clinique de la Poussée</span>
                            <span class="text-[11px] text-muted-foreground font-normal">Impact fonctionnel aigu</span>
                        </Label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="form.severity = 'legere'"
                                :class="[
                                    'px-2.5 py-2 rounded-lg border text-xs font-medium transition-all text-center flex flex-col items-center gap-1',
                                    form.severity === 'legere'
                                        ? 'bg-emerald-500/15 border-emerald-500 text-emerald-700 dark:text-emerald-300 font-semibold shadow-xs'
                                        : 'bg-muted/30 border-border/70 text-muted-foreground hover:bg-muted/60'
                                ]"
                            >
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                <span>Légère</span>
                                <span class="text-[9px] opacity-75">Sensitif / minime</span>
                            </button>
                            <button
                                type="button"
                                @click="form.severity = 'moderee'"
                                :class="[
                                    'px-2.5 py-2 rounded-lg border text-xs font-medium transition-all text-center flex flex-col items-center gap-1',
                                    form.severity === 'moderee'
                                        ? 'bg-amber-500/15 border-amber-500 text-amber-700 dark:text-amber-300 font-semibold shadow-xs'
                                        : 'bg-muted/30 border-border/70 text-muted-foreground hover:bg-muted/60'
                                ]"
                            >
                                <span class="size-2 rounded-full bg-amber-500"></span>
                                <span>Modérée</span>
                                <span class="text-[9px] opacity-75">Gêne motrice / visuelle</span>
                            </button>
                            <button
                                type="button"
                                @click="form.severity = 'severe'"
                                :class="[
                                    'px-2.5 py-2 rounded-lg border text-xs font-medium transition-all text-center flex flex-col items-center gap-1',
                                    form.severity === 'severe'
                                        ? 'bg-rose-500/15 border-rose-500 text-rose-700 dark:text-rose-300 font-semibold shadow-xs'
                                        : 'bg-muted/30 border-border/70 text-muted-foreground hover:bg-muted/60'
                                ]"
                            >
                                <span class="size-2 rounded-full bg-rose-500"></span>
                                <span>Sévère</span>
                                <span class="text-[9px] opacity-75">Déficit majeur / grabataire</span>
                            </button>
                        </div>
                        <InputError :message="form.errors.severity" />
                    </div>
                </div>

                <Separator />

                <!-- Section 3: Prise en Charge Thérapeutique -->
                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Pill class="size-3.5 text-primary" />
                        <span>Prise en Charge Thérapeutique</span>
                    </h4>

                    <!-- Treatment Protocols -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="treatment_administered" class="text-sm font-medium">
                                Traitement Administré
                            </Label>
                            <span class="text-[11px] text-muted-foreground">Protocoles fréquents</span>
                        </div>

                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <button
                                v-for="protocol in treatmentProtocols"
                                :key="protocol"
                                type="button"
                                @click="setTreatment(protocol)"
                                :class="[
                                    'text-[11px] px-2.5 py-0.5 rounded-md border font-mono transition-colors',
                                    form.treatment_administered === protocol
                                        ? 'bg-primary text-primary-foreground border-primary font-semibold'
                                        : 'bg-muted/40 hover:bg-muted text-foreground border-border/70'
                                ]"
                            >
                                {{ protocol }}
                            </button>
                        </div>

                        <Input
                            id="treatment_administered"
                            v-model="form.treatment_administered"
                            placeholder="ex: Bolus Solumédrol 1g/j IV x 3 jours"
                        />
                        <InputError :message="form.errors.treatment_administered" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Voie d'administration -->
                        <div class="space-y-1.5">
                            <Label for="administration_route" class="text-sm font-medium">
                                Voie d'Administration
                            </Label>
                            <Select v-model="form.administration_route">
                                <SelectTrigger id="administration_route">
                                    <SelectValue placeholder="Choisir la voie" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="route in administrationRoutes"
                                        :key="route"
                                        :value="route"
                                    >
                                        {{ route }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.administration_route" />
                        </div>

                        <!-- Hospitalisation -->
                        <div class="space-y-1.5 flex flex-col justify-end">
                            <div class="flex items-center gap-2 p-2.5 rounded-lg border border-border/70 bg-muted/20">
                                <Checkbox
                                    id="hospitalization_required"
                                    :checked="form.hospitalization_required"
                                    @update:checked="(val: boolean | 'indeterminate') => (form.hospitalization_required = Boolean(val))"
                                />
                                <Label
                                    for="hospitalization_required"
                                    class="text-xs font-medium cursor-pointer text-foreground"
                                >
                                    Hospitalisation conventionnelle requise
                                </Label>
                            </div>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Section 4: Récupération Clinique & Séquelles -->
                <div class="space-y-3">
                    <h4 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                        <Activity class="size-3.5 text-primary" />
                        <span>Évolution Clinique & Séquelles</span>
                    </h4>

                    <div class="space-y-1.5">
                        <Label for="clinical_recovery" class="text-sm font-medium">
                            Qualité de Récupération Clinique
                        </Label>
                        <Select v-model="form.clinical_recovery">
                            <SelectTrigger id="clinical_recovery">
                                <SelectValue placeholder="Sélectionner le mode de récupération" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in recoveryOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.clinical_recovery" />
                    </div>

                    <!-- Séquelles résiduelles si incomplète ou nulle -->
                    <div
                        v-if="form.clinical_recovery === 'Incomplète' || form.clinical_recovery === 'Nulle'"
                        class="space-y-1.5 p-3 rounded-lg bg-amber-500/10 border border-amber-500/20"
                    >
                        <Label for="relapse_sequelae" class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                            Séquelles Fonctionnelles Résiduelles
                        </Label>
                        <Input
                            id="relapse_sequelae"
                            v-model="form.sequelae"
                            placeholder="ex: Baisse définitive de l'acuité visuelle résiduelle à 6/10..."
                            class="bg-background"
                        />
                        <InputError :message="form.errors.sequelae" />
                    </div>

                    <!-- Observations / Notes -->
                    <div class="space-y-1.5">
                        <Label for="relapse_notes" class="text-sm font-medium">
                            Commentaires & Observations Complémentaires
                        </Label>
                        <Textarea
                            id="relapse_notes"
                            v-model="form.notes"
                            rows="2"
                            placeholder="Remarques particulières, tolérance aux corticoïdes, etc..."
                            class="text-xs"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>

                <Separator />

                <!-- Section 5: Évaluation EDSS (Avant & Après la Poussée) -->
                <div class="space-y-3 p-3.5 rounded-xl bg-muted/30 border border-border/70">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-semibold text-foreground uppercase tracking-wider flex items-center gap-1.5">
                            <TrendingUp class="size-3.5 text-primary" />
                            <span>Suivi du Handicap EDSS (Échelle 0 à 10)</span>
                        </h4>
                        <span class="text-[10px] font-mono text-muted-foreground">Recommandation clinique</span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Permet de distinguer l'aggravation transitoire pendant la poussée du handicap résiduel stabilisé après récupération.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <!-- EDSS Avant la poussée -->
                        <div class="space-y-1.5">
                            <Label for="edss_before_relapse" class="text-xs font-medium">
                                EDSS Avant Poussée <span class="text-muted-foreground text-[10px]">(si dispo)</span>
                            </Label>
                            <Input
                                id="edss_before_relapse"
                                v-model="form.edss_before_relapse"
                                type="number"
                                step="0.5"
                                min="0"
                                max="10"
                                placeholder="ex: 1.5"
                                class="font-mono bg-background"
                            />
                            <InputError :message="form.errors.edss_before_relapse" />
                        </div>

                        <!-- EDSS Après la poussée -->
                        <div class="space-y-1.5">
                            <Label for="edss_after_relapse" class="text-xs font-medium">
                                EDSS Après Poussée <span class="text-muted-foreground text-[10px]">(Post-récup.)</span>
                            </Label>
                            <Input
                                id="edss_after_relapse"
                                v-model="form.edss_after_relapse"
                                type="number"
                                step="0.5"
                                min="0"
                                max="10"
                                placeholder="ex: 2.0"
                                class="font-mono bg-background"
                            />
                            <InputError :message="form.errors.edss_after_relapse" />
                        </div>

                        <!-- Date d'évaluation EDSS -->
                        <div class="space-y-1.5">
                            <Label for="edss_evaluation_date" class="text-xs font-medium">
                                Date Réelle Évaluation EDSS
                            </Label>
                            <Input
                                id="edss_evaluation_date"
                                v-model="form.edss_evaluation_date"
                                type="date"
                                class="font-mono bg-background"
                            />
                            <InputError :message="form.errors.edss_evaluation_date" />
                        </div>
                    </div>

                    <!-- Delta EDSS calculé en direct si les deux sont remplis -->
                    <div
                        v-if="form.edss_before_relapse !== '' && form.edss_after_relapse !== ''"
                        class="text-xs px-2.5 py-1.5 rounded-lg border flex items-center justify-between"
                        :class="Number(form.edss_after_relapse) > Number(form.edss_before_relapse)
                            ? 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-400 font-medium'
                            : Number(form.edss_after_relapse) < Number(form.edss_before_relapse)
                            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-400 font-medium'
                            : 'bg-muted/50 border-border text-muted-foreground'"
                    >
                        <span>Variation nette du handicap :</span>
                        <span class="font-mono font-bold">
                            {{ Number(form.edss_after_relapse) - Number(form.edss_before_relapse) > 0 ? '+' : '' }}{{ (Number(form.edss_after_relapse) - Number(form.edss_before_relapse)).toFixed(1) }} points EDSS
                            <span v-if="Number(form.edss_after_relapse) > Number(form.edss_before_relapse)" class="ml-1 text-[10px] font-normal">
                                (Progression / Aggravation résiduelle)
                            </span>
                            <span v-else-if="Number(form.edss_after_relapse) === Number(form.edss_before_relapse)" class="ml-1 text-[10px] font-normal">
                                (Handicap stable après récupération)
                            </span>
                        </span>
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
                        <span>{{ isEditing ? 'Mettre à jour' : 'Enregistrer la poussée' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
