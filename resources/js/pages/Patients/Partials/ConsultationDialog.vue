<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    Check,
    Clock,
    FileText,
    HeartPulse,
    Pill,
    Sparkles,
    Stethoscope,
    User,
    X,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type ConsultationFormData = {
    id?: number;
    consultation_date: string;
    consultation_date_raw?: string | null;
    consultation_type?: string | null;
    doctor_name: string | null;
    intercurrent_events: string | null;
    relapses_since_last_visit: number;
    edss_score: string | number | null;
    current_treatment_evaluation: string | null;
    tolerance_and_adherence: string | null;
    recent_exam_results: string | null;
    therapeutic_decision: string | null;
    next_appointment_date: string | null;
    next_appointment_date_raw?: string | null;
    notes: string | null;
};

type Props = {
    open: boolean;
    patientId: number;
    consultation?: ConsultationFormData | null;
    currentTreatmentName?: string | null;
    latestEdss?: string | number | null;
};

const props = withDefaults(defineProps<Props>(), {
    consultation: null,
    currentTreatmentName: null,
    latestEdss: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const page = usePage();
const currentUser = computed(() => (page.props as any).auth?.user);

const form = useForm({
    consultation_date: new Date().toISOString().split('T')[0],
    consultation_type: 'suivi_rdv',
    doctor_name: currentUser.value?.name ? `Dr. ${currentUser.value.name}` : '',
    intercurrent_events: '',
    relapses_since_last_visit: 0,
    edss_score: '' as string | number,
    current_treatment_evaluation: '',
    tolerance_and_adherence: '',
    recent_exam_results: '',
    therapeutic_decision: '',
    next_appointment_date: '',
    notes: '',
});

const isEditing = computed(() => !!props.consultation?.id);

// Quick presets for therapeutic decision
const therapeuticDecisionPresets = [
    'Maintien du traitement de fond actuel',
    'Escalade thérapeutique vers une ligne supérieure (SEP active)',
    'Switch thérapeutique pour intolérance clinique/biologique',
    'Optimisation des thérapeutiques symptomatiques et rééducation',
    'Programmation d\'une IRM de contrôle avant décision définitive',
];

// Quick presets for tolerance and adherence
const tolerancePresets = [
    'Bonne tolérance clinique et biologique, excellente observance',
    'Effets indésirables légers et transitoires, prise régulière',
    'Intolérance signalée avec sauts de prises, surveillance requise',
];

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        if (props.consultation) {
            form.consultation_date = props.consultation.consultation_date_raw || props.consultation.consultation_date || '';
            form.consultation_type = props.consultation.consultation_type || 'suivi_rdv';
            form.doctor_name = props.consultation.doctor_name || '';
            form.intercurrent_events = props.consultation.intercurrent_events || '';
            form.relapses_since_last_visit = props.consultation.relapses_since_last_visit ?? 0;
            form.edss_score = props.consultation.edss_score !== null && props.consultation.edss_score !== undefined
                ? String(props.consultation.edss_score)
                : '';
            form.current_treatment_evaluation = props.consultation.current_treatment_evaluation || '';
            form.tolerance_and_adherence = props.consultation.tolerance_and_adherence || '';
            form.recent_exam_results = props.consultation.recent_exam_results || '';
            form.therapeutic_decision = props.consultation.therapeutic_decision || '';
            form.next_appointment_date = props.consultation.next_appointment_date_raw || props.consultation.next_appointment_date || '';
            form.notes = props.consultation.notes || '';
        } else {
            form.reset();
            form.consultation_date = new Date().toISOString().split('T')[0];
            form.consultation_type = 'suivi_rdv';
            form.doctor_name = currentUser.value?.name ? `Dr. ${currentUser.value.name}` : '';
            form.intercurrent_events = '';
            form.relapses_since_last_visit = 0;
            form.edss_score = props.latestEdss ? String(props.latestEdss) : '';
            form.current_treatment_evaluation = props.currentTreatmentName
                ? `Traitement sous ${props.currentTreatmentName}`
                : '';
            form.tolerance_and_adherence = 'Bonne tolérance clinique et biologique, excellente observance';
            form.recent_exam_results = '';
            form.therapeutic_decision = 'Maintien du traitement de fond actuel';
            form.next_appointment_date = '';
            form.notes = '';
        }
        form.clearErrors();
    },
    { immediate: true },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        edss_score: data.edss_score === '' || data.edss_score === null ? null : Number(data.edss_score),
        next_appointment_date: data.next_appointment_date || null,
        relapses_since_last_visit: Number(data.relapses_since_last_visit || 0),
    }));

    if (isEditing.value && props.consultation?.id) {
        form.put(`/patients/${props.patientId}/consultations/${props.consultation.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    } else {
        form.post(`/patients/${props.patientId}/consultations`, {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(val: boolean) => emit('update:open', val)">
        <DialogContent class="sm:max-w-2xl max-w-2xl max-h-[92vh] overflow-y-auto p-6 sm:rounded-2xl">
            <DialogHeader class="pb-3 border-b border-border">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                        <Stethoscope class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold text-foreground">
                            {{ isEditing ? 'Modifier la Consultation de Suivi' : 'Nouvelle Consultation de Suivi' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Évaluation clinique longitudinale, tolérance du traitement et décision thérapeutique (Module 9)
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 pt-3">
                <!-- SECTION 1 : DATE & MÉDECIN -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Calendar class="size-3.5 text-primary" />
                            <span>1. Date & Médecin Examinateur</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="consultation_date" class="text-xs font-medium">
                                Date de la Consultation <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="consultation_date"
                                type="date"
                                v-model="form.consultation_date"
                                :max="new Date().toISOString().split('T')[0]"
                                required
                                class="h-9 text-xs"
                            />
                            <p v-if="form.errors.consultation_date" class="text-[11px] text-destructive">
                                {{ form.errors.consultation_date }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="doctor_name" class="text-xs font-medium">
                                Médecin Responsable / Examinateur
                            </Label>
                            <Input
                                id="doctor_name"
                                type="text"
                                v-model="form.doctor_name"
                                placeholder="Dr. Nom Prénom"
                                class="h-9 text-xs"
                            />
                            <p v-if="form.errors.doctor_name" class="text-[11px] text-destructive">
                                {{ form.errors.doctor_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Type de Consultation (Module 9) -->
                    <div class="space-y-1.5 pt-1">
                        <Label class="text-xs font-medium">Type de Consultation (Module 9)</Label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button
                                type="button"
                                :class="[
                                    'px-3 py-2 rounded-lg border text-xs font-medium transition-all text-left flex items-center justify-between',
                                    form.consultation_type === 'suivi_rdv'
                                        ? 'border-primary bg-primary/10 text-primary font-semibold ring-1 ring-primary'
                                        : 'border-border hover:bg-muted/40 text-muted-foreground'
                                ]"
                                @click="form.consultation_type = 'suivi_rdv'"
                            >
                                <div class="flex items-center gap-2">
                                    <Clock class="size-3.5 text-primary" />
                                    <span>Suivi par RDV programmé</span>
                                </div>
                                <Check v-if="form.consultation_type === 'suivi_rdv'" class="size-3.5" />
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'px-3 py-2 rounded-lg border text-xs font-medium transition-all text-left flex items-center justify-between',
                                    form.consultation_type === 'suivi_poussee'
                                        ? 'border-amber-600 bg-amber-500/10 text-amber-700 dark:text-amber-400 font-semibold ring-1 ring-amber-500'
                                        : 'border-border hover:bg-muted/40 text-muted-foreground'
                                ]"
                                @click="form.consultation_type = 'suivi_poussee'"
                            >
                                <div class="flex items-center gap-2">
                                    <AlertCircle class="size-3.5 text-amber-600 dark:text-amber-400" />
                                    <span>Suivi en cas de poussée</span>
                                </div>
                                <Check v-if="form.consultation_type === 'suivi_poussee'" class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 : ÉVÉNEMENTS INTERCURRENTS, POUSSÉES & SCORE EDSS -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Activity class="size-3.5 text-primary" />
                            <span>2. Évolution Clinique & Handicap</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="relapses_since_last_visit" class="text-xs font-medium">
                                Poussées depuis la dernière visite
                            </Label>
                            <Input
                                id="relapses_since_last_visit"
                                type="number"
                                min="0"
                                max="20"
                                v-model.number="form.relapses_since_last_visit"
                                class="h-9 text-xs font-mono"
                            />
                            <p class="text-[10px] text-muted-foreground">
                                Nombre d'épisodes de poussée survenus dans l'intervalle
                            </p>
                            <p v-if="form.errors.relapses_since_last_visit" class="text-[11px] text-destructive">
                                {{ form.errors.relapses_since_last_visit }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edss_score" class="text-xs font-medium">
                                Score EDSS au cours de cette visite
                            </Label>
                            <Input
                                id="edss_score"
                                type="number"
                                step="0.5"
                                min="0"
                                max="10"
                                v-model="form.edss_score"
                                placeholder="ex: 2.5"
                                class="h-9 text-xs font-mono font-medium"
                            />
                            <p class="text-[10px] text-muted-foreground">
                                Évaluation actuelle de l'Expanded Disability Status Scale (0.0 à 10.0)
                            </p>
                            <p v-if="form.errors.edss_score" class="text-[11px] text-destructive">
                                {{ form.errors.edss_score }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="intercurrent_events" class="text-xs font-medium">
                            Symptômes & Événements Intercurrents
                        </Label>
                        <Textarea
                            id="intercurrent_events"
                            v-model="form.intercurrent_events"
                            rows="2"
                            placeholder="Épisodes infectieux, fatigue inhabituelle, troubles de la marche, vertiges..."
                            class="text-xs resize-none"
                        />
                        <p v-if="form.errors.intercurrent_events" class="text-[11px] text-destructive">
                            {{ form.errors.intercurrent_events }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 3 : ÉVALUATION DU TRAITEMENT & OBSERVANCE -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Pill class="size-3.5 text-primary" />
                            <span>3. Évaluation du Traitement & Tolérance</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="current_treatment_evaluation" class="text-xs font-medium">
                            Traitement actuel & Évaluation
                        </Label>
                        <Input
                            id="current_treatment_evaluation"
                            type="text"
                            v-model="form.current_treatment_evaluation"
                            placeholder="ex: Tecfidera 240mg x2/j — efficacité clinique satisfaisante"
                            class="h-9 text-xs"
                        />
                        <p v-if="form.errors.current_treatment_evaluation" class="text-[11px] text-destructive">
                            {{ form.errors.current_treatment_evaluation }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="tolerance_and_adherence" class="text-xs font-medium">
                            Tolérance & Observance Thérapeutique
                        </Label>
                        <!-- Quick presets -->
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="preset in tolerancePresets"
                                :key="preset"
                                type="button"
                                @click="form.tolerance_and_adherence = preset"
                                :class="[
                                    'text-[11px] px-2.5 py-1 rounded-md border font-normal transition-colors text-left',
                                    form.tolerance_and_adherence === preset
                                        ? 'bg-primary/10 text-primary border-primary/40 font-semibold'
                                        : 'bg-muted/30 hover:bg-muted text-muted-foreground border-border/60',
                                ]"
                            >
                                {{ preset }}
                            </button>
                        </div>
                        <Textarea
                            id="tolerance_and_adherence"
                            v-model="form.tolerance_and_adherence"
                            rows="2"
                            placeholder="Tolérance digestive, hématologique, hépatique, régularité des prises..."
                            class="text-xs resize-none mt-1"
                        />
                        <p v-if="form.errors.tolerance_and_adherence" class="text-[11px] text-destructive">
                            {{ form.errors.tolerance_and_adherence }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="recent_exam_results" class="text-xs font-medium">
                            Résultats des Examens Récents (IRM / Biologie)
                        </Label>
                        <Input
                            id="recent_exam_results"
                            type="text"
                            v-model="form.recent_exam_results"
                            placeholder="ex: IRM cérébrale du 10/05/2025 stable, NFS et bilan hépatique normaux"
                            class="h-9 text-xs"
                        />
                        <p v-if="form.errors.recent_exam_results" class="text-[11px] text-destructive">
                            {{ form.errors.recent_exam_results }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 4 : DÉCISION THÉRAPEUTIQUE & PROCHAIN RENDEZ-VOUS -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-3.5 text-primary" />
                            <span>4. Décision Thérapeutique & Prochain RDV</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="therapeutic_decision" class="text-xs font-medium">
                            Décision Thérapeutique Retenue
                        </Label>
                        <!-- Decision presets -->
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="decision in therapeuticDecisionPresets"
                                :key="decision"
                                type="button"
                                @click="form.therapeutic_decision = decision"
                                :class="[
                                    'text-[11px] px-2.5 py-1 rounded-md border font-normal transition-colors text-left',
                                    form.therapeutic_decision === decision
                                        ? 'bg-primary text-primary-foreground border-primary font-semibold'
                                        : 'bg-muted/30 hover:bg-muted text-foreground border-border/60',
                                ]"
                            >
                                {{ decision }}
                            </button>
                        </div>
                        <Input
                            id="therapeutic_decision"
                            type="text"
                            v-model="form.therapeutic_decision"
                            placeholder="Préciser la décision ou sélectionner un modèle ci-dessus"
                            class="h-9 text-xs mt-1"
                        />
                        <p v-if="form.errors.therapeutic_decision" class="text-[11px] text-destructive">
                            {{ form.errors.therapeutic_decision }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="next_appointment_date" class="text-xs font-medium">
                                Prochaine Consultation Prévue
                            </Label>
                            <Input
                                id="next_appointment_date"
                                type="date"
                                v-model="form.next_appointment_date"
                                :min="form.consultation_date"
                                class="h-9 text-xs"
                            />
                            <p class="text-[10px] text-muted-foreground">
                                Date prévisionnelle de la prochaine visite de contrôle
                            </p>
                            <p v-if="form.errors.next_appointment_date" class="text-[11px] text-destructive">
                                {{ form.errors.next_appointment_date }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="notes" class="text-xs font-medium">
                                Commentaires Libres
                            </Label>
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                placeholder="Observations diverses, consignes remises au patient..."
                                class="text-xs resize-none"
                            />
                            <p v-if="form.errors.notes" class="text-[11px] text-destructive">
                                {{ form.errors.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DIALOG FOOTER -->
                <DialogFooter class="pt-4 border-t border-border flex items-center justify-between sm:justify-between">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="emit('update:open', false)"
                        :disabled="form.processing"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing"
                        class="gap-1.5"
                    >
                        <Check class="size-4" />
                        <span>{{ isEditing ? 'Mettre à jour' : 'Enregistrer la consultation' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
