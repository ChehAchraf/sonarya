<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    Baby,
    BookOpen,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    DollarSign,
    FileText,
    HeartPulse,
    Info,
    Pill,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Syringe,
    User,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    THERAPEUTIC_CATALOG,
    VACCINATION_LIST,
    type MoleculeMonograph,
} from '@/data/therapeuticCatalog';

export type TreatmentFormData = {
    id?: number;
    category: 'fond' | 'poussee' | 'symptomatique' | string;
    therapeutic_line?: 'plateforme' | 'the' | 'autre' | string | null;
    molecule_name: string;
    therapeutic_class: string | null;
    start_date: string;
    start_date_raw?: string | null;
    end_date: string | null;
    end_date_raw?: string | null;
    status: 'en_cours' | 'arrete' | 'remplace' | string;
    has_treatment_changed?: boolean;
    switch_to_molecule?: string | null;
    care_access_interruption?: boolean;
    post_stop_reactivation?: boolean;
    anti_spastic?: boolean;
    other_symptomatic?: string | null;
    vaccinations?: string[] | null;
    pre_therapeutic_checklist?: string[] | null;
    pregnancy_contraception_notes?: string | null;
    estimated_price_mad?: string | null;
    dosage_regimen: string | null;
    administration_route: string | null;
    prescription_reason: string | null;
    stop_reason: string | null;
    adverse_effects: string | null;
    relapses_after_two_years: number | null;
    vitamin_d_supplementation: boolean;
    antidepressants: boolean;
    notes: string | null;
};

type Props = {
    open: boolean;
    patientId: number;
    patientBirthDate?: string | null;
    diagnosisDate?: string | null;
    firstSymptomDate?: string | null;
    treatment?: TreatmentFormData | null;
};

const props = withDefaults(defineProps<Props>(), {
    treatment: null,
    patientBirthDate: null,
    diagnosisDate: null,
    firstSymptomDate: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    category: 'fond',
    therapeutic_line: 'plateforme',
    molecule_name: '',
    therapeutic_class: '',
    start_date: '',
    end_date: '',
    status: 'en_cours',
    has_treatment_changed: false,
    switch_to_molecule: '',
    care_access_interruption: false,
    post_stop_reactivation: false,
    anti_spastic: false,
    other_symptomatic: '',
    vaccinations: [] as string[],
    pre_therapeutic_checklist: [] as string[],
    pregnancy_contraception_notes: '',
    estimated_price_mad: '',
    dosage_regimen: '',
    administration_route: 'Orale (PO)',
    prescription_reason: '',
    stop_reason: '',
    adverse_effects: '',
    relapses_after_two_years: '' as string | number,
    vitamin_d_supplementation: false,
    antidepressants: false,
    notes: '',
});

const isEditing = computed(() => !!props.treatment?.id);

// Catalog Filter & Selection
const catalogFilter = ref<'all' | 'plateforme' | 'the' | 'symptomatic'>('all');
const selectedMonograph = ref<MoleculeMonograph | null>(null);
const isMonographDialogOpen = ref(false);

// Tysabri LEMP Stratification variables
const jcvIndexLevel = ref<'inf_09' | 'mid_09_15' | 'sup_15'>('inf_09');
const exposureOver24Months = ref(false);
const priorImmunosuppression = ref(false);

// Filtered catalog list
const filteredCatalog = computed(() => {
    if (catalogFilter.value === 'all') return THERAPEUTIC_CATALOG;
    if (catalogFilter.value === 'plateforme') return THERAPEUTIC_CATALOG.filter((m) => m.line === 'plateforme');
    if (catalogFilter.value === 'the') return THERAPEUTIC_CATALOG.filter((m) => m.line === 'the');
    return [];
});

// Calculate LEMP Risk Level for Tysabri
const lempRiskEvaluation = computed(() => {
    if (jcvIndexLevel.value === 'inf_09') {
        return {
            level: 'Faible',
            badgeClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
            description: 'Risque estimé < 1 / 1 000 patients. Surveillance sérologique JCV semestrielle.',
        };
    }
    if (jcvIndexLevel.value === 'mid_09_15') {
        const desc = exposureOver24Months.value || priorImmunosuppression.value
            ? 'Risque intermédiaire à élevé (~ 3 à 5 / 1 000). IRM semestrielle recommandée.'
            : 'Risque intermédiaire (~ 1 à 2 / 1 000). Poursuite sous surveillance clinique et IRM.';
        return {
            level: 'Intermédiaire',
            badgeClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
            description: desc,
        };
    }
    // > 1.5
    if (exposureOver24Months.value || priorImmunosuppression.value) {
        return {
            level: 'Très Élevé',
            badgeClass: 'bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-500/40 font-bold',
            description: 'Risque élevé (> 1 / 100 à 1 / 70). Discussion collégiale pour switch thérapeutique ou schéma étendu (toutes les 6 semaines) avec IRM tous les 3-6 mois.',
        };
    }
    return {
        level: 'Modéré / Élevé',
        badgeClass: 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/30',
        description: 'Risque significatif (~ 3 à 5 / 1 000 avant 24 mois). Surveillance IRM rapprochée.',
    };
});

const isTysabri = computed(() => {
    return form.molecule_name.toLowerCase().includes('natalizumab') || form.molecule_name.toLowerCase().includes('tysabri');
});

const isHighEfficacy = computed(() => {
    return form.therapeutic_line === 'the' || (selectedMonograph.value && selectedMonograph.value.line === 'the');
});

const administrationRoutes = [
    'Orale (PO)',
    'Sous-cutanée (SC)',
    'Intraveineuse (IV)',
    'Intramusculaire (IM)',
    'Autre',
];

const stopReasonsPresets = [
    'Échappement thérapeutique (Reprise d\'activité clinique / Poussée)',
    'Échappement thérapeutique (Activité IRM : nouvelles lésions T2 ou Gado+)',
    'Effets indésirables / Mauvaise tolérance',
    'Positivité ou élévation de l\'index anticorps anti-JCV (risque LEMP)',
    'Projet de grossesse / Maternité',
    'Anomalie biologique (Lymphopénie, cytolyse hépatique)',
    'Interruption d\'accès aux soins / Rupture de stock / Refus AMO',
    'Décision ou refus du patient',
    'Fin programmée de protocole (ex: Cladribine après 2 ans)',
];

// Presets pour poussée & symptomatiques
const symptomaticPresets = [
    {
        name: 'Méthylprednisolone (Solumédrol)',
        category: 'poussee',
        class: 'Glucocorticoïde puissant',
        route: 'Intraveineuse (IV)',
        dosage: '1 g/jour en perfusion dans 250ml G5% x 3 à 5 jours',
        reason: 'Traitement d\'attaque d\'une poussée aiguë avec retentissement fonctionnel',
        price: 'Disponible à l\'hôpital',
    },
    {
        name: 'Baclofène (Liorésal 10mg)',
        category: 'symptomatique',
        class: 'Agoniste GABA-B antispastique',
        route: 'Orale (PO)',
        dosage: '15 à 60 mg/jour en 3 prises progressives',
        reason: 'Spasticité gênante des membres inférieurs',
        price: '~55 DH',
    },
    {
        name: 'Fampridine (Fampyra 10mg)',
        category: 'symptomatique',
        class: 'Bloqueur des canaux potassiques',
        route: 'Orale (PO)',
        dosage: '10 mg 2 fois par jour à 12h d\'intervalle',
        reason: 'Amélioration du périmètre et de la vitesse de marche',
        price: '~650 DH',
    },
    {
        name: 'Prégabaline (Lyrica 75mg / 150mg)',
        category: 'symptomatique',
        class: 'Analogue GABA antalgique neuropathique',
        route: 'Orale (PO)',
        dosage: '75 à 300 mg/jour en 2 prises',
        reason: 'Douleurs neuropathiques paroxystiques ou permanentes',
        price: '~180 DH',
    },
];

const applyCatalogMolecule = (mono: MoleculeMonograph) => {
    selectedMonograph.value = mono;
    form.molecule_name = mono.moleculeName.includes('(')
        ? mono.moleculeName
        : `${mono.moleculeName} (${mono.commercialNames})`;
    form.therapeutic_line = mono.line;
    form.category = 'fond';
    form.therapeutic_class = mono.therapeuticClass;
    form.administration_route = mono.administrationRoute;
    form.dosage_regimen = mono.posology;
    form.prescription_reason = mono.prescriptionReason;
    form.estimated_price_mad = mono.estimatedPriceMAD;
    form.pregnancy_contraception_notes = mono.pregnancyContraceptionNotes;
    form.pre_therapeutic_checklist = [...mono.preTherapeuticChecklist];
};

const applySymptomaticPreset = (preset: typeof symptomaticPresets[0]) => {
    selectedMonograph.value = null;
    form.molecule_name = preset.name;
    form.category = preset.category;
    form.therapeutic_line = 'autre';
    form.therapeutic_class = preset.class;
    form.administration_route = preset.route;
    form.dosage_regimen = preset.dosage;
    form.prescription_reason = preset.reason;
    form.estimated_price_mad = preset.price;
    form.pre_therapeutic_checklist = [];
    form.pregnancy_contraception_notes = '';
};

const toggleChecklistItem = (item: string) => {
    const idx = form.pre_therapeutic_checklist.indexOf(item);
    if (idx === -1) {
        form.pre_therapeutic_checklist.push(item);
    } else {
        form.pre_therapeutic_checklist.splice(idx, 1);
    }
};

const toggleVaccineItem = (vaxId: string) => {
    const idx = form.vaccinations.indexOf(vaxId);
    if (idx === -1) {
        form.vaccinations.push(vaxId);
    } else {
        form.vaccinations.splice(idx, 1);
    }
};

// Calculate patient age at treatment start
const ageAtTreatmentStart = computed(() => {
    if (!form.start_date || !props.patientBirthDate) return null;
    const birth = new Date(props.patientBirthDate);
    const start = new Date(form.start_date);
    if (isNaN(birth.getTime()) || isNaN(start.getTime())) return null;
    let age = start.getFullYear() - birth.getFullYear();
    const m = start.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && start.getDate() < birth.getDate())) {
        age--;
    }
    return age > 0 ? age : null;
});

// Calculate disease duration before treatment (months)
const diseaseDurationBeforeTreatment = computed(() => {
    const refDateStr = props.diagnosisDate || props.firstSymptomDate;
    if (!form.start_date || !refDateStr) return null;
    const refDate = new Date(refDateStr);
    const start = new Date(form.start_date);
    if (isNaN(refDate.getTime()) || isNaN(start.getTime())) return null;
    const diffMonths = Math.round((start.getTime() - refDate.getTime()) / (1000 * 60 * 60 * 24 * 30.4375));
    return diffMonths >= 0 ? diffMonths : 0;
});

// Watch when dialog opens
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        if (props.treatment) {
            form.category = props.treatment.category || 'fond';
            form.therapeutic_line = props.treatment.therapeutic_line || 'plateforme';
            form.molecule_name = props.treatment.molecule_name || '';
            form.therapeutic_class = props.treatment.therapeutic_class || '';
            form.start_date = props.treatment.start_date_raw || props.treatment.start_date || '';
            form.end_date = props.treatment.end_date_raw || props.treatment.end_date || '';
            form.status = props.treatment.status || 'en_cours';
            form.has_treatment_changed = !!props.treatment.has_treatment_changed;
            form.switch_to_molecule = props.treatment.switch_to_molecule || '';
            form.care_access_interruption = !!props.treatment.care_access_interruption;
            form.post_stop_reactivation = !!props.treatment.post_stop_reactivation;
            form.anti_spastic = !!props.treatment.anti_spastic;
            form.other_symptomatic = props.treatment.other_symptomatic || '';
            form.vaccinations = Array.isArray(props.treatment.vaccinations) ? [...props.treatment.vaccinations] : [];
            form.pre_therapeutic_checklist = Array.isArray(props.treatment.pre_therapeutic_checklist) ? [...props.treatment.pre_therapeutic_checklist] : [];
            form.pregnancy_contraception_notes = props.treatment.pregnancy_contraception_notes || '';
            form.estimated_price_mad = props.treatment.estimated_price_mad || '';
            form.dosage_regimen = props.treatment.dosage_regimen || '';
            form.administration_route = props.treatment.administration_route || 'Orale (PO)';
            form.prescription_reason = props.treatment.prescription_reason || '';
            form.stop_reason = props.treatment.stop_reason || '';
            form.adverse_effects = props.treatment.adverse_effects || '';
            form.relapses_after_two_years = props.treatment.relapses_after_two_years !== null && props.treatment.relapses_after_two_years !== undefined
                ? props.treatment.relapses_after_two_years
                : '';
            form.vitamin_d_supplementation = props.treatment.vitamin_d_supplementation ?? false;
            form.antidepressants = props.treatment.antidepressants ?? false;
            form.notes = props.treatment.notes || '';

            // Try to match existing molecule in catalog
            const matched = THERAPEUTIC_CATALOG.find((m) =>
                form.molecule_name.toLowerCase().includes(m.moleculeName.toLowerCase()) ||
                (m.commercialNames && form.molecule_name.toLowerCase().includes(m.commercialNames.split(' ')[0].toLowerCase()))
            );
            selectedMonograph.value = matched || null;
        } else {
            form.reset();
            form.category = 'fond';
            form.therapeutic_line = 'plateforme';
            form.molecule_name = '';
            form.therapeutic_class = '';
            form.start_date = new Date().toISOString().split('T')[0];
            form.end_date = '';
            form.status = 'en_cours';
            form.has_treatment_changed = false;
            form.switch_to_molecule = '';
            form.care_access_interruption = false;
            form.post_stop_reactivation = false;
            form.anti_spastic = false;
            form.other_symptomatic = '';
            form.vaccinations = [];
            form.pre_therapeutic_checklist = [];
            form.pregnancy_contraception_notes = '';
            form.estimated_price_mad = '';
            form.dosage_regimen = '';
            form.administration_route = 'Orale (PO)';
            form.prescription_reason = '';
            form.stop_reason = '';
            form.adverse_effects = '';
            form.relapses_after_two_years = '';
            form.vitamin_d_supplementation = false;
            form.antidepressants = false;
            form.notes = '';
            selectedMonograph.value = null;
        }
        form.clearErrors();
    },
    { immediate: true },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        end_date: data.end_date || null,
        relapses_after_two_years: data.relapses_after_two_years === '' || data.relapses_after_two_years === null
            ? null
            : Number(data.relapses_after_two_years),
    }));

    if (isEditing.value && props.treatment?.id) {
        form.put(`/patients/${props.patientId}/treatments/${props.treatment.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    } else {
        form.post(`/patients/${props.patientId}/treatments`, {
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
        <DialogContent class="max-w-[calc(100vw-1.5rem)] sm:max-w-4xl max-h-[92vh] overflow-y-auto p-4 sm:p-6 sm:rounded-2xl">
            <DialogHeader class="pb-3 border-b border-border">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                            <Pill class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-lg font-bold text-foreground">
                                {{ isEditing ? 'Modifier le Traitement' : 'Nouveau Traitement SEP' }}
                            </DialogTitle>
                            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                                Traitements de fond (Plateforme / THE), protocole marocain & suivi thérapeutique
                            </DialogDescription>
                        </div>
                    </div>

                    <Button
                        v-if="selectedMonograph"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="isMonographDialogOpen = true"
                        class="gap-1.5 text-xs border-primary/40 text-primary hover:bg-primary/10 hidden sm:flex"
                    >
                        <BookOpen class="size-3.5" />
                        <span>Fiche Thérapeutique Maroc</span>
                    </Button>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 pt-3">
                <!-- SECTION 1 : SÉLECTION DE LA MOLÉCULE (CATALOGUE MAROCAIN) -->
                <div class="space-y-3.5 p-4 rounded-xl border border-border/70 bg-card/60">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-border/50">
                        <div class="flex items-center gap-2 text-xs font-semibold text-foreground uppercase tracking-wider">
                            <Sparkles class="size-3.5 text-primary" />
                            <span>1. Molécule & Monographie Marocaine</span>
                        </div>

                        <!-- Filtres du catalogue -->
                        <div class="flex flex-wrap items-center gap-1">
                            <button
                                type="button"
                                @click="catalogFilter = 'all'"
                                :class="[
                                    'text-[10.5px] px-2 py-0.5 rounded-md font-medium transition-colors',
                                    catalogFilter === 'all'
                                        ? 'bg-primary text-primary-foreground font-semibold'
                                        : 'bg-muted/40 hover:bg-muted text-muted-foreground',
                                ]"
                            >
                                Tous
                            </button>
                            <button
                                type="button"
                                @click="catalogFilter = 'plateforme'"
                                :class="[
                                    'text-[10.5px] px-2 py-0.5 rounded-md font-medium transition-colors',
                                    catalogFilter === 'plateforme'
                                        ? 'bg-blue-600 text-white font-semibold'
                                        : 'bg-muted/40 hover:bg-muted text-muted-foreground',
                                ]"
                            >
                                Plateforme (Ligne 1)
                            </button>
                            <button
                                type="button"
                                @click="catalogFilter = 'the'"
                                :class="[
                                    'text-[10.5px] px-2 py-0.5 rounded-md font-medium transition-colors',
                                    catalogFilter === 'the'
                                        ? 'bg-purple-600 text-white font-semibold'
                                        : 'bg-muted/40 hover:bg-muted text-muted-foreground',
                                ]"
                            >
                                Haute Efficacité (THE)
                            </button>
                            <button
                                type="button"
                                @click="catalogFilter = 'symptomatic'"
                                :class="[
                                    'text-[10.5px] px-2 py-0.5 rounded-md font-medium transition-colors',
                                    catalogFilter === 'symptomatic'
                                        ? 'bg-emerald-600 text-white font-semibold'
                                        : 'bg-muted/40 hover:bg-muted text-muted-foreground',
                                ]"
                            >
                                Poussée & Symptomatique
                            </button>
                        </div>
                    </div>

                    <!-- Grille des molécules marocaines -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-medium text-muted-foreground block">
                            Sélection rapide depuis la monographie marocaine (affiche des prix en DH & checklists) :
                        </Label>

                        <div v-if="catalogFilter !== 'symptomatic'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1.5 rounded-lg bg-muted/20 border border-border/50">
                            <div
                                v-for="mono in filteredCatalog"
                                :key="mono.id"
                                @click="applyCatalogMolecule(mono)"
                                :class="[
                                    'p-2 rounded-lg border text-left cursor-pointer transition-all flex flex-col justify-between gap-1',
                                    selectedMonograph?.id === mono.id
                                        ? 'border-primary bg-primary/10 shadow-xs'
                                        : 'border-border/70 bg-background/80 hover:bg-muted/40',
                                ]"
                            >
                                <div class="flex items-start justify-between gap-1">
                                    <span class="text-xs font-bold text-foreground leading-tight">{{ mono.moleculeName }}</span>
                                    <Badge
                                        :variant="mono.line === 'the' ? 'default' : 'secondary'"
                                        class="text-[9px] px-1 py-0 uppercase shrink-0"
                                    >
                                        {{ mono.line === 'the' ? 'THE' : 'Plateforme' }}
                                    </Badge>
                                </div>
                                <div class="text-[10.5px] text-muted-foreground truncate">
                                    {{ mono.commercialNames }}
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-foreground font-mono font-medium pt-1 border-t border-border/40">
                                    <span class="text-primary">{{ mono.estimatedPriceMAD }}</span>
                                    <span class="text-[9.5px] text-muted-foreground capitalize">{{ mono.administrationRoute.split(' ')[0] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Symptomatiques / Poussée -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="sym in symptomaticPresets"
                                :key="sym.name"
                                @click="applySymptomaticPreset(sym)"
                                class="p-2.5 rounded-lg border border-border/70 bg-background hover:bg-muted/40 cursor-pointer transition-colors space-y-1"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-foreground">{{ sym.name }}</span>
                                    <Badge variant="outline" class="text-[9.5px]">{{ sym.category }}</Badge>
                                </div>
                                <p class="text-[11px] text-muted-foreground">{{ sym.dosage }}</p>
                                <span class="text-[10px] text-primary font-mono block">{{ sym.price }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bannière d'informations de la molécule sélectionnée -->
                    <div v-if="selectedMonograph" class="p-3 rounded-lg border border-primary/30 bg-primary/5 space-y-2 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-primary">{{ selectedMonograph.moleculeName }}</span>
                                <Badge variant="outline" class="text-[10px] font-mono">{{ selectedMonograph.commercialNames }}</Badge>
                                <Badge :variant="selectedMonograph.line === 'the' ? 'default' : 'secondary'" class="text-[10px]">
                                    {{ selectedMonograph.lineLabel }}
                                </Badge>
                            </div>
                            <span class="text-xs font-mono font-bold text-foreground">Prix : {{ selectedMonograph.estimatedPriceMAD }}</span>
                        </div>

                        <!-- Info Grossesse & Allaitement -->
                        <div v-if="selectedMonograph.pregnancyContraceptionNotes" class="flex items-start gap-1.5 text-[11px] p-2 rounded bg-background/80 border border-border/50">
                            <Baby class="size-3.5 text-rose-500 shrink-0 mt-0.5" />
                            <div>
                                <strong class="text-foreground">Grossesse & Contraception :</strong>
                                <span class="text-muted-foreground ml-1">{{ selectedMonograph.pregnancyContraceptionNotes }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de saisie détaillée -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <!-- Catégorie -->
                        <div class="space-y-1">
                            <Label for="category" class="text-xs font-medium">Catégorie <span class="text-destructive">*</span></Label>
                            <Select v-model="form.category">
                                <SelectTrigger id="category" class="h-9 text-xs">
                                    <SelectValue placeholder="Catégorie" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="fond">Traitement de Fond (DMT)</SelectItem>
                                    <SelectItem value="poussee">Traitement de Poussée</SelectItem>
                                    <SelectItem value="symptomatique">Traitement Symptomatique</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.category" class="text-[11px] text-destructive">{{ form.errors.category }}</p>
                        </div>

                        <!-- Ligne thérapeutique -->
                        <div class="space-y-1">
                            <Label for="therapeutic_line" class="text-xs font-medium">Ligne Thérapeutique</Label>
                            <Select v-model="form.therapeutic_line">
                                <SelectTrigger id="therapeutic_line" class="h-9 text-xs">
                                    <SelectValue placeholder="Ligne" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="plateforme">Traitements plateforme (Ligne 1)</SelectItem>
                                    <SelectItem value="the">Haute efficacité (THE)</SelectItem>
                                    <SelectItem value="autre">Autre / Non classé</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Prix estimé MAD -->
                        <div class="space-y-1">
                            <Label for="estimated_price_mad" class="text-xs font-medium flex items-center gap-1">
                                <DollarSign class="size-3 text-emerald-600" />
                                <span>Tarif / Prix au Maroc (DH)</span>
                            </Label>
                            <Input
                                id="estimated_price_mad"
                                v-model="form.estimated_price_mad"
                                placeholder="ex: 14 234 DH"
                                class="h-9 text-xs font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Nom de la Molécule -->
                        <div class="space-y-1">
                            <Label for="molecule_name" class="text-xs font-medium">
                                Molécule & Spécialité <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="molecule_name"
                                v-model="form.molecule_name"
                                placeholder="ex: Ocrélizumab (Ocrevus)"
                                class="h-9 text-xs font-medium"
                                required
                            />
                            <p v-if="form.errors.molecule_name" class="text-[11px] text-destructive">{{ form.errors.molecule_name }}</p>
                        </div>

                        <!-- Voie d'administration -->
                        <div class="space-y-1">
                            <Label for="administration_route" class="text-xs font-medium">Voie d'administration</Label>
                            <Select v-model="form.administration_route">
                                <SelectTrigger id="administration_route" class="h-9 text-xs">
                                    <SelectValue placeholder="Voie d'administration" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="route in administrationRoutes" :key="route" :value="route">
                                        {{ route }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Posologie & Schéma posologique -->
                    <div class="space-y-1">
                        <Label for="dosage_regimen" class="text-xs font-medium">Posologie & Schéma Posologique</Label>
                        <Input
                            id="dosage_regimen"
                            v-model="form.dosage_regimen"
                            placeholder="ex: 600 mg tous les 6 mois (300 mg à J1 et J15)"
                            class="h-9 text-xs font-mono"
                        />
                    </div>
                </div>

                <!-- SECTION 2 : STRATIFICATION LEMP & VIRUS JC (SPÉCIFIQUE TYSABRI) -->
                <div v-if="isTysabri" class="p-4 rounded-xl border border-amber-500/40 bg-amber-500/5 space-y-3">
                    <div class="flex items-center justify-between pb-1.5 border-b border-amber-500/30">
                        <div class="flex items-center gap-2 text-xs font-bold text-amber-900 dark:text-amber-300">
                            <ShieldAlert class="size-4 text-amber-600" />
                            <span>Stratification du Risque de LEMP (Protocole Natalizumab / Tysabri)</span>
                        </div>
                        <Badge :class="lempRiskEvaluation.badgeClass">
                            Risque LEMP : {{ lempRiskEvaluation.level }}
                        </Badge>
                    </div>

                    <p class="text-[11px] text-muted-foreground leading-relaxed">
                        Selon le consensus international et la monographie marocaine, le risque de Leucoencéphalopathie Multifocale Progressive (LEMP) dépend de 3 facteurs :
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <!-- Facteur 1 : Index JC -->
                        <div class="space-y-1">
                            <Label class="text-xs font-medium">1. Index Anticorps Virus JC</Label>
                            <Select v-model="jcvIndexLevel">
                                <SelectTrigger class="h-8 text-xs bg-background">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="inf_09">Index ≤ 0.9 (Faible)</SelectItem>
                                    <SelectItem value="mid_09_15">Index 0.9 à 1.5 (Intermédiaire)</SelectItem>
                                    <SelectItem value="sup_15">Index > 1.5 (Élevé)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Facteur 2 : Durée de traitement -->
                        <div class="flex items-center gap-2 p-2 rounded-lg border border-border/70 bg-background cursor-pointer mt-5 sm:mt-0" @click="exposureOver24Months = !exposureOver24Months">
                            <Checkbox id="exposure_24" :checked="exposureOver24Months" />
                            <Label for="exposure_24" class="text-xs cursor-pointer">
                                2. Durée de perfusion ≥ 24 mois
                            </Label>
                        </div>

                        <!-- Facteur 3 : Immunosuppresseurs antérieurs -->
                        <div class="flex items-center gap-2 p-2 rounded-lg border border-border/70 bg-background cursor-pointer mt-5 sm:mt-0" @click="priorImmunosuppression = !priorImmunosuppression">
                            <Checkbox id="prior_is" :checked="priorImmunosuppression" />
                            <Label for="prior_is" class="text-xs cursor-pointer">
                                3. Antécédent d'immunosuppresseur
                            </Label>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-background border border-amber-500/20 text-[11px] text-amber-900 dark:text-amber-200">
                        <strong>Recommandation :</strong> {{ lempRiskEvaluation.description }}
                    </div>
                </div>

                <!-- ALERTE VACCINS VIVANTS POUR THE -->
                <div v-if="isHighEfficacy" class="p-3 rounded-lg border border-rose-500/30 bg-rose-500/10 flex items-start gap-2.5 text-xs">
                    <AlertTriangle class="size-4 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                    <div class="space-y-0.5">
                        <strong class="text-rose-700 dark:text-rose-300 font-semibold">
                            Recommandation Vaccinale Stricte (Cahier des charges p.13) :
                        </strong>
                        <p class="text-muted-foreground text-[11px] leading-relaxed">
                            Les <strong>vaccins vivants atténués (VZV Varicelle, ROR)</strong> sont <u>strictement contre-indiqués</u> sous traitement de haute efficacité / immunosuppresseurs.
                            Si la sérologie VZV est négative, la vaccination doit être réalisée <strong>au moins 4 à 6 semaines avant</strong> l'initiation de la molécule !
                        </p>
                    </div>
                </div>

                <!-- CHECKLIST PRÉ-THÉRAPEUTIQUE DYNAMIQUE -->
                <div v-if="selectedMonograph && selectedMonograph.preTherapeuticChecklist.length > 0" class="space-y-2 p-3.5 rounded-xl border border-border/70 bg-card/60">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50">
                        <span class="text-xs font-semibold text-foreground flex items-center gap-1.5 uppercase tracking-wide">
                            <CheckCircle2 class="size-3.5 text-emerald-600" />
                            <span>Bilan Pré-thérapeutique Requis (Checklist Officielle)</span>
                        </span>
                        <span class="text-[10px] text-muted-foreground">Cochez les bilans validés</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div
                            v-for="item in selectedMonograph.preTherapeuticChecklist"
                            :key="item"
                            @click="toggleChecklistItem(item)"
                            class="flex items-center gap-2 p-2 rounded-lg border border-border/60 bg-background hover:bg-muted/30 cursor-pointer transition-colors"
                        >
                            <Checkbox :checked="form.pre_therapeutic_checklist.includes(item)" />
                            <span class="text-[11px] text-foreground">{{ item }}</span>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3 : CALENDRIER, STATUT & CHANGEMENT / SWITCH -->
                <div class="space-y-4 p-4 rounded-xl border border-border/70 bg-card/60">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <Calendar class="size-3.5 text-primary" />
                        <span>2. Période, Statut & Changement Thérapeutique</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Date de début -->
                        <div class="space-y-1">
                            <Label for="start_date" class="text-xs font-medium">Date de Début <span class="text-destructive">*</span></Label>
                            <Input
                                id="start_date"
                                type="date"
                                v-model="form.start_date"
                                :max="new Date().toISOString().split('T')[0]"
                                class="h-9 text-xs"
                                required
                            />
                            <p v-if="form.errors.start_date" class="text-[11px] text-destructive">{{ form.errors.start_date }}</p>
                        </div>

                        <!-- Statut -->
                        <div class="space-y-1">
                            <Label for="status" class="text-xs font-medium">Statut du Traitement <span class="text-destructive">*</span></Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status" class="h-9 text-xs">
                                    <SelectValue placeholder="Statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="en_cours">En cours (Actif)</SelectItem>
                                    <SelectItem value="arrete">Arrêté</SelectItem>
                                    <SelectItem value="remplace">Remplacé (Switch)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Date de fin -->
                        <div class="space-y-1">
                            <Label for="end_date" class="text-xs font-medium">Date de Fin</Label>
                            <Input
                                id="end_date"
                                type="date"
                                v-model="form.end_date"
                                :min="form.start_date"
                                :disabled="form.status === 'en_cours'"
                                class="h-9 text-xs disabled:opacity-40"
                            />
                        </div>
                    </div>

                    <!-- Métriques d'âge et durée -->
                    <div v-if="ageAtTreatmentStart !== null || diseaseDurationBeforeTreatment !== null" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-2.5 rounded-lg bg-muted/30 border border-border/60 text-xs">
                        <div v-if="ageAtTreatmentStart !== null" class="flex items-center gap-2">
                            <User class="size-4 text-primary shrink-0" />
                            <div>
                                <span class="text-muted-foreground block text-[10.5px]">Âge à l'initiation du traitement :</span>
                                <span class="font-bold text-foreground font-mono">{{ ageAtTreatmentStart }} ans</span>
                            </div>
                        </div>
                        <div v-if="diseaseDurationBeforeTreatment !== null" class="flex items-center gap-2">
                            <Clock class="size-4 text-primary shrink-0" />
                            <div>
                                <span class="text-muted-foreground block text-[10.5px]">Délai diagnostic / début traitement :</span>
                                <span class="font-bold text-foreground font-mono">{{ diseaseDurationBeforeTreatment }} mois</span>
                            </div>
                        </div>
                    </div>

                    <!-- CHANGEMENT DE TRAITEMENT / SWITCH -->
                    <div class="space-y-3 p-3 rounded-lg border border-border/80 bg-muted/20">
                        <div class="flex items-center gap-2.5">
                            <Checkbox
                                id="has_treatment_changed"
                                :checked="form.has_treatment_changed || form.status !== 'en_cours'"
                                @update:checked="(val: boolean) => (form.has_treatment_changed = val)"
                            />
                            <Label for="has_treatment_changed" class="text-xs font-semibold text-foreground cursor-pointer">
                                Ce traitement a fait l'objet d'un changement ou arrêt (Switch / Arrêt)
                            </Label>
                        </div>

                        <div v-if="form.has_treatment_changed || form.status !== 'en_cours'" class="space-y-3 pt-2 border-t border-border/50">
                            <!-- Molécule de relais -->
                            <div class="space-y-1">
                                <Label for="switch_to_molecule" class="text-xs font-medium">
                                    Molécule de Relais / Nouveau Traitement Introduit
                                </Label>
                                <Input
                                    id="switch_to_molecule"
                                    v-model="form.switch_to_molecule"
                                    placeholder="ex: Relais par Ocrélizumab (Ocrevus 600mg)"
                                    class="h-9 text-xs"
                                />
                            </div>

                            <!-- Motifs d'arrêt prédéfinis -->
                            <div class="space-y-1.5">
                                <Label for="stop_reason" class="text-xs font-medium flex items-center justify-between">
                                    <span>Motif d'arrêt ou de changement :</span>
                                    <span class="text-[10px] text-muted-foreground">Cliquez sur un motif pour l'appliquer</span>
                                </Label>
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="reason in stopReasonsPresets"
                                        :key="reason"
                                        type="button"
                                        @click="form.stop_reason = reason"
                                        class="text-[10px] px-2 py-0.5 rounded border border-border bg-background hover:bg-muted text-foreground transition-colors text-left"
                                    >
                                        {{ reason }}
                                    </button>
                                </div>
                                <Textarea
                                    id="stop_reason"
                                    v-model="form.stop_reason"
                                    rows="2"
                                    placeholder="Circonstances précises du changement ou de l'arrêt..."
                                    class="text-xs mt-1"
                                />
                            </div>

                            <!-- Cases à cocher spécifiques du Cahier des charges -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <div class="flex items-start gap-2 p-2 rounded border border-border/70 bg-background cursor-pointer" @click="form.care_access_interruption = !form.care_access_interruption">
                                    <Checkbox id="care_access" :checked="form.care_access_interruption" class="mt-0.5" />
                                    <div>
                                        <Label for="care_access" class="text-xs font-medium cursor-pointer">
                                            Interruption liée à l'accès aux soins
                                        </Label>
                                        <p class="text-[10px] text-muted-foreground">
                                            Rupture de stock, refus AMO/assurance, motif financier
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2 p-2 rounded border border-border/70 bg-background cursor-pointer" @click="form.post_stop_reactivation = !form.post_stop_reactivation">
                                    <Checkbox id="post_stop" :checked="form.post_stop_reactivation" class="mt-0.5" />
                                    <div>
                                        <Label for="post_stop" class="text-xs font-medium cursor-pointer">
                                            Réactivation après arrêt du traitement
                                        </Label>
                                        <p class="text-[10px] text-muted-foreground">
                                            Rebond de l'activité clinique ou IRM après interruption
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4 : VACCINATIONS & TRAITEMENTS SYMPTOMATIQUES ASSOCIÉS -->
                <div class="space-y-4 p-4 rounded-xl border border-border/70 bg-card/60">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <Activity class="size-3.5 text-primary" />
                        <span>3. Vaccinations & Traitements Symptomatiques Associés</span>
                    </div>

                    <!-- Statut vaccinal (Cahier des charges p.13) -->
                    <div class="space-y-2 p-3 rounded-lg border border-border/70 bg-muted/20">
                        <div class="flex items-center justify-between">
                            <Label class="text-xs font-semibold flex items-center gap-1.5 text-foreground">
                                <Syringe class="size-3.5 text-primary" />
                                <span>Statut Vaccinal du Patient (Recommandations SEP Maroc) :</span>
                            </Label>
                            <span class="text-[10.5px] text-muted-foreground">Cochez les vaccins à jour</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                v-for="vax in VACCINATION_LIST"
                                :key="vax.id"
                                @click="toggleVaccineItem(vax.id)"
                                :class="[
                                    'p-2 rounded border text-left cursor-pointer transition-colors flex items-start gap-1.5',
                                    vax.type === 'vivant_attenue'
                                        ? 'border-amber-500/40 bg-amber-500/5'
                                        : 'border-border/70 bg-background',
                                    form.vaccinations.includes(vax.id) ? 'ring-1 ring-primary' : '',
                                ]"
                            >
                                <Checkbox :checked="form.vaccinations.includes(vax.id)" class="mt-0.5" />
                                <div class="space-y-0.5">
                                    <span class="text-[11px] font-medium text-foreground block leading-tight">{{ vax.label }}</span>
                                    <span v-if="vax.type === 'vivant_attenue'" class="text-[9px] text-amber-600 dark:text-amber-400 font-bold block">
                                        Vivant atténué
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Traitements Symptomatiques Associés (Cahier des charges 12.3) -->
                    <div class="space-y-3 pt-2">
                        <span class="text-xs font-semibold text-foreground block">
                            Traitements Symptomatiques Concomitants :
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Vitamine D -->
                            <div class="p-2.5 rounded-lg border border-border/70 bg-background flex items-start gap-2 cursor-pointer" @click="form.vitamin_d_supplementation = !form.vitamin_d_supplementation">
                                <Checkbox :checked="form.vitamin_d_supplementation" class="mt-0.5" />
                                <div class="space-y-0.5">
                                    <Label class="text-xs font-medium cursor-pointer">Vitamine D3</Label>
                                    <p class="text-[10px] text-muted-foreground">Cholécalciférol régulier</p>
                                </div>
                            </div>

                            <!-- Antidépresseurs -->
                            <div class="p-2.5 rounded-lg border border-border/70 bg-background flex items-start gap-2 cursor-pointer" @click="form.antidepressants = !form.antidepressants">
                                <Checkbox :checked="form.antidepressants" class="mt-0.5" />
                                <div class="space-y-0.5">
                                    <Label class="text-xs font-medium cursor-pointer">Antidépresseurs</Label>
                                    <p class="text-[10px] text-muted-foreground">Syndrome dépressif / anxiété</p>
                                </div>
                            </div>

                            <!-- Anti-spastiques -->
                            <div class="p-2.5 rounded-lg border border-border/70 bg-background flex items-start gap-2 cursor-pointer" @click="form.anti_spastic = !form.anti_spastic">
                                <Checkbox :checked="form.anti_spastic" class="mt-0.5" />
                                <div class="space-y-0.5">
                                    <Label class="text-xs font-medium cursor-pointer">Anti-spastique</Label>
                                    <p class="text-[10px] text-muted-foreground">Baclofène (Liorésal) etc.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Autre symptomatique -->
                        <div class="space-y-1">
                            <Label for="other_symptomatic" class="text-xs font-medium">Autres Traitements Symptomatiques</Label>
                            <Input
                                id="other_symptomatic"
                                v-model="form.other_symptomatic"
                                placeholder="ex: Fampridine (Fampyra), Prégabaline, Rééducation motrice..."
                                class="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <!-- Tolérance et effets indésirables -->
                    <div class="space-y-1 pt-2">
                        <Label for="adverse_effects" class="text-xs font-medium">Tolérance & Effets Indésirables</Label>
                        <Textarea
                            id="adverse_effects"
                            v-model="form.adverse_effects"
                            rows="2"
                            placeholder="Tolérance clinique et biologique (flush, troubles digestifs, lymphopénie...)"
                            class="text-xs"
                        />
                    </div>

                    <!-- Notes complémentaires -->
                    <div class="space-y-1">
                        <Label for="notes" class="text-xs font-medium">Notes & Synthèse du Médecin</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            placeholder="Observations du neurologue concernant l'observance ou le suivi..."
                            class="text-xs"
                        />
                    </div>
                </div>

                <DialogFooter class="pt-4 border-t border-border gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                        :disabled="form.processing"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        class="gap-1.5"
                        :disabled="form.processing"
                    >
                        <Check class="size-4" />
                        <span>{{ isEditing ? 'Mettre à jour le traitement' : 'Enregistrer le traitement' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- SOUS-DIALOGUE : MONOGRAPHIE DÉTAILLÉE DE LA MOLÉCULE (AFFICHE DE TRAITEMENT) -->
    <Dialog :open="isMonographDialogOpen" @update:open="(val: boolean) => (isMonographDialogOpen = val)">
        <DialogContent v-if="selectedMonograph" class="max-w-[calc(100vw-1.5rem)] sm:max-w-2xl max-h-[85vh] overflow-y-auto p-4 sm:p-6 sm:rounded-2xl">
            <DialogHeader class="pb-3 border-b border-border">
                <div class="flex items-center gap-2">
                    <BookOpen class="size-5 text-primary" />
                    <div>
                        <DialogTitle class="text-base font-bold text-foreground">
                            {{ selectedMonograph.moleculeName.includes('(') ? selectedMonograph.moleculeName : `${selectedMonograph.moleculeName} (${selectedMonograph.commercialNames})` }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Monographie clinique officielle (Maroc) & données de pharmacovigilance
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-4 pt-3 text-xs">
                <!-- Identification & Prix -->
                <div class="p-3 rounded-lg bg-muted/30 border border-border/60 grid grid-cols-2 gap-2 font-mono">
                    <div>
                        <span class="text-muted-foreground text-[11px] block">Classe :</span>
                        <span class="font-bold text-foreground">{{ selectedMonograph.therapeuticClass }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground text-[11px] block">Prix public au Maroc :</span>
                        <span class="font-bold text-primary">{{ selectedMonograph.estimatedPriceMAD }}</span>
                    </div>
                </div>

                <!-- Posologie -->
                <div class="space-y-1">
                    <span class="font-semibold text-foreground block">Posologie & Administration :</span>
                    <p class="text-muted-foreground leading-relaxed">{{ selectedMonograph.posology }}</p>
                </div>

                <!-- Bilan pré-thérapeutique -->
                <div class="space-y-1.5">
                    <span class="font-semibold text-foreground block">Bilan Pré-thérapeutique Recommandé :</span>
                    <ul class="list-disc pl-4 space-y-1 text-muted-foreground">
                        <li v-for="item in selectedMonograph.preTherapeuticChecklist" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </div>

                <!-- Surveillance -->
                <div class="space-y-1.5">
                    <span class="font-semibold text-foreground block">Surveillance & Tolérance :</span>
                    <ul class="list-disc pl-4 space-y-1 text-muted-foreground">
                        <li v-for="item in selectedMonograph.surveillanceChecklist" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </div>

                <!-- Grossesse & Allaitement -->
                <div class="p-3 rounded-lg bg-primary/5 border border-primary/20 space-y-1">
                    <span class="font-semibold text-primary block flex items-center gap-1.5">
                        <Baby class="size-4" />
                        <span>Grossesse, Contraception & Allaitement :</span>
                    </span>
                    <p class="text-foreground leading-relaxed">{{ selectedMonograph.pregnancyContraceptionNotes }}</p>
                </div>
            </div>

            <DialogFooter class="pt-3 border-t border-border">
                <Button type="button" variant="outline" size="sm" @click="isMonographDialogOpen = false">
                    Fermer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
