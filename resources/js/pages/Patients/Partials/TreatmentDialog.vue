<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    FileText,
    Pill,
    ShieldAlert,
    Sparkles,
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

export type TreatmentFormData = {
    id?: number;
    category: 'fond' | 'poussee' | 'symptomatique' | string;
    molecule_name: string;
    therapeutic_class: string | null;
    start_date: string;
    start_date_raw?: string | null;
    end_date: string | null;
    end_date_raw?: string | null;
    status: 'en_cours' | 'arrete' | 'remplace' | string;
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
    molecule_name: '',
    therapeutic_class: '',
    start_date: '',
    end_date: '',
    status: 'en_cours',
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

// Pre-defined SEP molecules library with therapeutic specs
const sepMoleculesPresets = [
    // Ligne 1 (Première intention / Modérée)
    {
        name: 'Diméthylfumarate (Tecfidera)',
        category: 'fond',
        class: 'Immunomodulateur oral (Active la voie Nrf2)',
        route: 'Orale (PO)',
        dosage: '240 mg 2 fois par jour (après 120 mg x 2 pendant 7j)',
        reason: 'Forme récurrente-rémittente active en première intention',
    },
    {
        name: 'Tériflunomide (Aubagio)',
        category: 'fond',
        class: 'Inhibiteur réversible de la DHODH',
        route: 'Orale (PO)',
        dosage: '14 mg 1 fois par jour',
        reason: 'Forme récurrente-rémittente active en première intention',
    },
    {
        name: 'Interféron bêta-1a (Avonex)',
        category: 'fond',
        class: 'Interféron bêta-1a',
        route: 'Intramusculaire (IM)',
        dosage: '30 µg (6 MUI) 1 fois par semaine',
        reason: 'Traitement de fond de première intention',
    },
    {
        name: 'Interféron bêta-1a (Rebif)',
        category: 'fond',
        class: 'Interféron bêta-1a',
        route: 'Sous-cutanée (SC)',
        dosage: '44 µg 3 fois par semaine',
        reason: 'Traitement de fond de première intention',
    },
    {
        name: 'Interféron bêta-1b (Betaferon / Extavia)',
        category: 'fond',
        class: 'Interféron bêta-1b',
        route: 'Sous-cutanée (SC)',
        dosage: '250 µg un jour sur deux',
        reason: 'Traitement de fond de première intention',
    },
    {
        name: 'Acétate de glatiramère (Copaxone)',
        category: 'fond',
        class: 'Polymère synthétique d\'acides aminés',
        route: 'Sous-cutanée (SC)',
        dosage: '40 mg 3 fois par semaine ou 20 mg/jour',
        reason: 'Traitement de fond de première ligne (sécurité grossesse)',
    },
    {
        name: 'Peginterféron bêta-1a (Plegridy)',
        category: 'fond',
        class: 'Peginterféron bêta retard',
        route: 'Sous-cutanée (SC)',
        dosage: '125 µg toutes les 2 semaines',
        reason: 'Traitement de fond de première intention',
    },
    // Ligne 2 (Haute efficacité / Seconde intention)
    {
        name: 'Ocrélizumab (Ocrevus)',
        category: 'fond',
        class: 'Anticorps monoclonal humanisé anti-CD20',
        route: 'Intraveineuse (IV)',
        dosage: '600 mg tous les 6 mois en perfusion (J1 et J15 de 300 mg à l\'initiation)',
        reason: 'SEP récurrente très active ou forme primaire progressive (PPMS)',
    },
    {
        name: 'Ofatumumab (Kesimpta)',
        category: 'fond',
        class: 'Anticorps monoclonal humain anti-CD20',
        route: 'Sous-cutanée (SC)',
        dosage: '20 mg par mois en auto-injection (induction S0, S1, S2)',
        reason: 'SEP récurrente active avec critères de sévérité',
    },
    {
        name: 'Natalizumab (Tysabri)',
        category: 'fond',
        class: 'Anticorps monoclonal anti-intégrine VLA-4',
        route: 'Intraveineuse (IV)',
        dosage: '300 mg en perfusion toutes les 4 à 6 semaines',
        reason: 'SEP récurrente-rémittente très active (surveillance index anti-JCV)',
    },
    {
        name: 'Fingolimod (Gilenya)',
        category: 'fond',
        class: 'Modulateur des récepteurs de la sphingosine-1-phosphate (S1P)',
        route: 'Orale (PO)',
        dosage: '0.5 mg 1 fois par jour (surveillance cardiaque 6h J1)',
        reason: 'Escalade thérapeutique après échec de ligne 1',
    },
    {
        name: 'Siponimod (Mayzent)',
        category: 'fond',
        class: 'Modulateur sélectif S1P1 et S1P5',
        route: 'Orale (PO)',
        dosage: '2 mg 1 fois par jour après titration',
        reason: 'SEP secondairement progressive (SPMS) active',
    },
    {
        name: 'Cladribine (Mavenclad)',
        category: 'fond',
        class: 'Analogue de la désoxyadénosine (Immunoreconstitution)',
        route: 'Orale (PO)',
        dosage: '3.5 mg/kg sur 2 ans (2 semaines de traitement par an)',
        reason: 'SEP récurrente très active en échec d\'autres thérapies',
    },
    // Traitement de poussée
    {
        name: 'Méthylprednisolone (Solumédrol)',
        category: 'poussee',
        class: 'Glucocorticoïde puissant',
        route: 'Intraveineuse (IV)',
        dosage: '1 g/jour en perfusion dans 250ml G5% x 3 à 5 jours',
        reason: 'Traitement d\'attaque d\'une poussée aiguë avec retentissement fonctionnel',
    },
    // Symptomatiques
    {
        name: 'Baclofène (Liorésal)',
        category: 'symptomatique',
        class: 'Agoniste GABA-B antispastique',
        route: 'Orale (PO)',
        dosage: '15 à 60 mg/jour en 3 prises progressives',
        reason: 'Spasticité des membres inférieurs gênant la déambulation',
    },
    {
        name: 'Fampridine (Fampyra)',
        category: 'symptomatique',
        class: 'Bloqueur des canaux potassiques voltage-dépendants',
        route: 'Orale (PO)',
        dosage: '10 mg 2 fois par jour à 12h d\'intervalle',
        reason: 'Amélioration de la vitesse et du périmètre de marche',
    },
    {
        name: 'Prégabaline (Lyrica)',
        category: 'symptomatique',
        class: 'Analogue GABA antalgique neuropathique',
        route: 'Orale (PO)',
        dosage: '75 à 300 mg/jour en 2 prises',
        reason: 'Douleurs neuropathiques paroxystiques ou continues',
    },
];

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
    'Décision ou refus du patient',
    'Rupture d\'approvisionnement ou contrainte médico-légale',
    'Fin programmée de protocole (ex: Cladribine après 2 ans)',
];

const applyMoleculePreset = (preset: typeof sepMoleculesPresets[0]) => {
    form.molecule_name = preset.name;
    form.category = preset.category;
    form.therapeutic_class = preset.class;
    form.administration_route = preset.route;
    form.dosage_regimen = preset.dosage;
    if (!form.prescription_reason) {
        form.prescription_reason = preset.reason;
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
            form.molecule_name = props.treatment.molecule_name || '';
            form.therapeutic_class = props.treatment.therapeutic_class || '';
            form.start_date = props.treatment.start_date_raw || props.treatment.start_date || '';
            form.end_date = props.treatment.end_date_raw || props.treatment.end_date || '';
            form.status = props.treatment.status || 'en_cours';
            form.dosage_regimen = props.treatment.dosage_regimen || '';
            form.administration_route = props.treatment.administration_route || 'Orale (PO)';
            form.prescription_reason = props.treatment.prescription_reason || '';
            form.stop_reason = props.treatment.stop_reason || '';
            form.relapses_after_two_years = props.treatment.relapses_after_two_years !== null && props.treatment.relapses_after_two_years !== undefined
                ? props.treatment.relapses_after_two_years
                : '';
            form.vitamin_d_supplementation = props.treatment.vitamin_d_supplementation ?? false;
            form.antidepressants = props.treatment.antidepressants ?? false;
            form.notes = props.treatment.notes || '';
        } else {
            form.reset();
            form.category = 'fond';
            form.molecule_name = '';
            form.therapeutic_class = '';
            form.start_date = new Date().toISOString().split('T')[0];
            form.end_date = '';
            form.status = 'en_cours';
            form.dosage_regimen = '';
            form.administration_route = 'Orale (PO)';
            form.prescription_reason = '';
            form.stop_reason = '';
            form.adverse_effects = '';
            form.relapses_after_two_years = '';
            form.vitamin_d_supplementation = false;
            form.antidepressants = false;
            form.notes = '';
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
        <DialogContent class="max-w-[calc(100vw-1.5rem)] sm:max-w-3xl max-h-[90vh] overflow-y-auto p-4 sm:p-6 sm:rounded-2xl">
            <DialogHeader class="pb-3 border-b border-border">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                        <Pill class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold text-foreground">
                            {{ isEditing ? 'Modifier le Traitement' : 'Nouveau Traitement' }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Enregistrement du traitement de fond (DMT), de poussée ou symptomatique selon le protocole thérapeutique
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 pt-3">
                <!-- SECTION 1 : MOLÉCULE & SÉLECTION RAPIDE -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-3.5 text-primary" />
                            <span>1. Molécule & Catégorie Thérapeutique</span>
                        </div>
                        <span class="text-[11px] text-muted-foreground font-normal">Recommandations SEP</span>
                    </div>

                    <!-- Presets rapides de molécules SEP -->
                    <div class="space-y-2">
                        <Label class="text-xs font-medium">Sélection Rapide des Molécules SEP Courantes</Label>
                        <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto p-1.5 rounded-lg bg-muted/20 border border-border/50">
                            <button
                                v-for="preset in sepMoleculesPresets"
                                :key="preset.name"
                                type="button"
                                @click="applyMoleculePreset(preset)"
                                :class="[
                                    'text-[11px] px-2.5 py-1 rounded-md border font-medium transition-colors text-left',
                                    form.molecule_name === preset.name
                                        ? 'bg-primary text-primary-foreground border-primary shadow-2xs font-semibold'
                                        : 'bg-muted/40 hover:bg-muted text-foreground border-border',
                                ]"
                            >
                                <span v-if="preset.category === 'fond'" class="inline-block size-1.5 rounded-full bg-blue-500 mr-1.5" />
                                <span v-else-if="preset.category === 'poussee'" class="inline-block size-1.5 rounded-full bg-rose-500 mr-1.5" />
                                <span v-else class="inline-block size-1.5 rounded-full bg-emerald-500 mr-1.5" />
                                {{ preset.name }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Catégorie -->
                        <div class="space-y-1.5">
                            <Label for="category" class="text-xs font-medium">
                                Catégorie <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.category">
                                <SelectTrigger id="category" class="h-9 text-xs">
                                    <SelectValue placeholder="Catégorie" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="fond">
                                        Traitement de Fond (DMT)
                                    </SelectItem>
                                    <SelectItem value="poussee">
                                        Traitement de Poussée
                                    </SelectItem>
                                    <SelectItem value="symptomatique">
                                        Traitement Symptomatique
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.category" class="text-[11px] text-destructive">
                                {{ form.errors.category }}
                            </p>
                        </div>

                        <!-- Nom de la Molécule -->
                        <div class="sm:col-span-2 space-y-1.5">
                            <Label for="molecule_name" class="text-xs font-medium">
                                Nom de la Molécule / Spécialité <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="molecule_name"
                                v-model="form.molecule_name"
                                placeholder="Ex: Diméthylfumarate (Tecfidera), Ocrélizumab (Ocrevus)..."
                                class="h-9 text-xs font-medium"
                                required
                            />
                            <p v-if="form.errors.molecule_name" class="text-[11px] text-destructive">
                                {{ form.errors.molecule_name }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Classe thérapeutique -->
                        <div class="space-y-1.5">
                            <Label for="therapeutic_class" class="text-xs font-medium">
                                Classe Thérapeutique
                            </Label>
                            <Input
                                id="therapeutic_class"
                                v-model="form.therapeutic_class"
                                placeholder="Ex: Anticorps monoclonal anti-CD20, Modulateur S1P..."
                                class="h-9 text-xs"
                            />
                        </div>

                        <!-- Voie d'administration -->
                        <div class="space-y-1.5">
                            <Label for="administration_route" class="text-xs font-medium">
                                Voie d'administration
                            </Label>
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

                    <!-- Posologie & Schéma d'administration -->
                    <div class="space-y-1.5">
                        <Label for="dosage_regimen" class="text-xs font-medium">
                            Posologie & Schéma d'Administration
                        </Label>
                        <Input
                            id="dosage_regimen"
                            v-model="form.dosage_regimen"
                            placeholder="Ex: 240 mg 2 fois par jour, 600 mg tous les 6 mois..."
                            class="h-9 text-xs font-mono"
                        />
                    </div>
                </div>

                <!-- SECTION 2 : CALENDRIER & STATUT DU TRAITEMENT -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <Calendar class="size-3.5 text-primary" />
                        <span>2. Période & Statut Thérapeutique</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Date de début -->
                        <div class="space-y-1.5">
                            <Label for="start_date" class="text-xs font-medium">
                                Date de Début <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="start_date"
                                type="date"
                                v-model="form.start_date"
                                :max="new Date().toISOString().split('T')[0]"
                                class="h-9 text-xs"
                                required
                            />
                            <p v-if="form.errors.start_date" class="text-[11px] text-destructive">
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <!-- Statut -->
                        <div class="space-y-1.5">
                            <Label for="status" class="text-xs font-medium">
                                Statut du Traitement <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status" class="h-9 text-xs">
                                    <SelectValue placeholder="Statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="en_cours">
                                        En cours (Actif)
                                    </SelectItem>
                                    <SelectItem value="arrete">
                                        Arrêté
                                    </SelectItem>
                                    <SelectItem value="remplace">
                                        Remplacé (Switch)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.status" class="text-[11px] text-destructive">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Date de fin (si arrêté ou remplacé) -->
                        <div class="space-y-1.5">
                            <Label for="end_date" class="text-xs font-medium">
                                Date de Fin
                                <span v-if="form.status !== 'en_cours'" class="text-muted-foreground text-[10px]"> (requis si arrêté)</span>
                            </Label>
                            <Input
                                id="end_date"
                                type="date"
                                v-model="form.end_date"
                                :min="form.start_date"
                                :disabled="form.status === 'en_cours'"
                                class="h-9 text-xs disabled:opacity-40"
                            />
                            <p v-if="form.errors.end_date" class="text-[11px] text-destructive">
                                {{ form.errors.end_date }}
                            </p>
                        </div>
                    </div>

                    <!-- Métriques automatiques : Âge au début et durée d'évolution -->
                    <div v-if="ageAtTreatmentStart !== null || diseaseDurationBeforeTreatment !== null" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-xl bg-muted/30 border border-border/60 text-xs">
                        <div v-if="ageAtTreatmentStart !== null" class="flex items-center gap-2">
                            <User class="size-4 text-primary shrink-0" />
                            <div>
                                <span class="text-muted-foreground block text-[11px]">Âge à l'initiation du traitement :</span>
                                <span class="font-bold text-foreground font-mono">{{ ageAtTreatmentStart }} ans</span>
                            </div>
                        </div>

                        <div v-if="diseaseDurationBeforeTreatment !== null" class="flex items-center gap-2">
                            <Clock class="size-4 text-primary shrink-0" />
                            <div>
                                <span class="text-muted-foreground block text-[11px]">Délai diagnostic / début de traitement :</span>
                                <span class="font-bold text-foreground font-mono">
                                    {{ diseaseDurationBeforeTreatment }} mois
                                    <span class="text-muted-foreground font-normal">({{ (diseaseDurationBeforeTreatment / 12).toFixed(1) }} ans)</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Motif d'arrêt ou de changement (si arrêté ou remplacé) -->
                    <div v-if="form.status !== 'en_cours'" class="space-y-2 p-3.5 rounded-xl border border-amber-500/30 bg-amber-500/5">
                        <div class="flex items-center justify-between">
                            <Label for="stop_reason" class="text-xs font-semibold text-amber-900 dark:text-amber-300 flex items-center gap-1.5">
                                <ShieldAlert class="size-3.5" />
                                <span>Motif d'Arrêt ou de Changement Thérapeutique (Switch)</span>
                            </Label>
                            <span class="text-[10px] text-amber-700 dark:text-amber-400">Cliquez pour insérer</span>
                        </div>

                        <div class="flex flex-wrap gap-1">
                            <button
                                v-for="reason in stopReasonsPresets"
                                :key="reason"
                                type="button"
                                @click="form.stop_reason = reason"
                                class="text-[10px] px-2 py-0.5 rounded border border-amber-500/30 bg-background/80 hover:bg-background text-foreground transition-colors"
                            >
                                {{ reason }}
                            </button>
                        </div>

                        <Textarea
                            id="stop_reason"
                            v-model="form.stop_reason"
                            rows="2"
                            placeholder="Préciser les circonstances de l'arrêt (ex: reprise d'activité radiologique à 12 mois avec 2 nouvelles lésions en T2)..."
                            class="text-xs"
                        />
                    </div>
                </div>

                <!-- SECTION 3 : TOLÉRANCE, EFFICACITÉ & TRAITEMENTS SYMPTOMATIQUES ASSOCIÉS -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <Activity class="size-3.5 text-primary" />
                        <span>3. Tolérance, Efficacité & Symptomatologie</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Motif de prescription -->
                        <div class="space-y-1.5">
                            <Label for="prescription_reason" class="text-xs font-medium">
                                Motif de Prescription Initial
                            </Label>
                            <Input
                                id="prescription_reason"
                                v-model="form.prescription_reason"
                                placeholder="Ex: Forme active d'emblée, échec de première ligne..."
                                class="h-9 text-xs"
                            />
                        </div>

                        <!-- Recul à 2 ans & Nombre de poussées après 2 ans -->
                        <div class="space-y-1.5">
                            <Label for="relapses_after_two_years" class="text-xs font-medium">
                                Poussées après 2 ans de traitement
                            </Label>
                            <Input
                                id="relapses_after_two_years"
                                type="number"
                                min="0"
                                max="50"
                                v-model.number="form.relapses_after_two_years"
                                placeholder="Laisser vide si recul < 2 ans"
                                class="h-9 text-xs font-mono"
                            />
                            <p class="text-[10px] text-muted-foreground">
                                Documenter si le patient dispose d'un recul complet de 24 mois
                            </p>
                        </div>
                    </div>

                    <!-- Tolérance et Effets Indésirables -->
                    <div class="space-y-1.5">
                        <Label for="adverse_effects" class="text-xs font-medium">
                            Tolérance & Effets Indésirables Observés
                        </Label>
                        <Textarea
                            id="adverse_effects"
                            v-model="form.adverse_effects"
                            rows="2"
                            placeholder="Ex: Flushs cutanés, troubles digestifs, lymphopénie modérée grade 1..."
                            class="text-xs"
                        />
                    </div>

                    <!-- Section Traitements Symptomatiques Associés (Cahier des charges 12.3) -->
                    <div class="p-3.5 rounded-xl border border-border bg-muted/20 space-y-3">
                        <span class="text-xs font-semibold text-foreground block">
                            Traitements Symptomatiques Associés (Cahier des Charges 12.3) :
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Supplémentation Vitamine D -->
                            <div class="p-2.5 rounded-lg border border-border/60 bg-card flex items-start gap-2.5">
                                <Checkbox
                                    id="vitamin_d_supplementation"
                                    :checked="form.vitamin_d_supplementation"
                                    @update:checked="(val: boolean) => (form.vitamin_d_supplementation = val)"
                                    class="mt-0.5"
                                />
                                <div class="cursor-pointer space-y-0.5" @click="form.vitamin_d_supplementation = !form.vitamin_d_supplementation">
                                    <Label for="vitamin_d_supplementation" class="text-xs font-medium text-foreground cursor-pointer">
                                        Supplémentation en Vitamine D
                                    </Label>
                                    <p class="text-[10px] text-muted-foreground">
                                        Ampoules ou gouttes de Cholécalciférol (D3)
                                    </p>
                                </div>
                            </div>

                            <!-- Antidépresseurs -->
                            <div class="p-2.5 rounded-lg border border-border/60 bg-card flex items-start gap-2.5">
                                <Checkbox
                                    id="antidepressants"
                                    :checked="form.antidepressants"
                                    @update:checked="(val: boolean) => (form.antidepressants = val)"
                                    class="mt-0.5"
                                />
                                <div class="cursor-pointer space-y-0.5" @click="form.antidepressants = !form.antidepressants">
                                    <Label for="antidepressants" class="text-xs font-medium text-foreground cursor-pointer">
                                        Traitement Antidépresseur Concomitant
                                    </Label>
                                    <p class="text-[10px] text-muted-foreground">
                                        ISRS, IRSNa pour syndrome dépressif / anxiété
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes complémentaires -->
                    <div class="space-y-1.5">
                        <Label for="notes" class="text-xs font-medium">Notes & Synthèse Thérapeutique</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            placeholder="Observations du neurologue concernant l'observance, le suivi sérologique ou les bilans paracliniques..."
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
</template>
