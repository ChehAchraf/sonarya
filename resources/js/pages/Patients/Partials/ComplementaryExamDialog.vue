<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    Check,
    Dna,
    FileCheck,
    FileText,
    FlaskConical,
    ShieldAlert,
    Sparkles,
    TestTube,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export type ComplementaryExamFormData = {
    id?: number;
    exam_date: string;
    exam_date_raw?: string | null;
    exam_type: string;
    pl_result: string | null;
    pl_cytorachie: number | string | null;
    pl_proteinorachie: number | string | null;
    pl_oligoclonal_bands: string | null;
    biology_anti_nmo: string | null;
    biology_anti_mog: string | null;
    biology_anti_antinucleaire: string | null;
    biology_anti_bgsa: string | null;
    biology_serology_hbv: string | null;
    biology_other: string | null;
    iepp_sample_type: string | null;
    iepp_result: string | null;
    iepp_kappa_index?: number | string | null;
    report_file_path?: string | null;
    notes: string | null;
};

type Props = {
    open: boolean;
    patientId: number;
    exam?: ComplementaryExamFormData | null;
};

const props = withDefaults(defineProps<Props>(), {
    exam: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const form = useForm({
    exam_date: new Date().toISOString().split('T')[0],
    exam_type: 'Ponction Lombaire & Biologie',
    pl_result: 'pathologique',
    pl_cytorachie: '' as number | string,
    pl_proteinorachie: '' as number | string,
    pl_oligoclonal_bands: 'positives',
    biology_anti_nmo: 'negative',
    biology_anti_mog: 'negative',
    biology_anti_antinucleaire: 'negative',
    biology_anti_bgsa: 'negative',
    biology_serology_hbv: 'negative',
    biology_other: '',
    iepp_sample_type: 'sang_et_lcr',
    iepp_result: 'Profil de type 2 : Synthèse intrathécale exclusive d\'IgG (BOC présentes dans le LCR, absentes dans le sérum)',
    iepp_kappa_index: '' as number | string,
    report_file_path: '',
    notes: '',
});

const isEditing = computed(() => !!props.exam?.id);

const examTypeOptions = [
    { value: 'Ponction Lombaire & Biologie', label: 'Bilan Complet : Ponction Lombaire + Biologie' },
    { value: 'Ponction Lombaire (LCR)', label: 'Ponction Lombaire isolée (LCR)' },
    { value: 'Biologie Spécifique', label: 'Biologie Sérique Spécifique (NMO, MOG, AAN...)' },
    { value: 'IEPP Sang/LCR', label: 'Immuno-électrophorèse des Protéines (IEPP)' },
];

const plResultOptions = [
    { value: 'normal', label: 'Normal (Non inflammatoire)' },
    { value: 'pathologique', label: 'Pathologique (Inflammatoire)' },
    { value: 'non_realise', label: 'Non réalisé' },
    { value: 'indisponible', label: 'Résultat indisponible' },
];

const bocOptions = [
    { value: 'positives', label: 'BOC Positives (≥ 2 bandes LCR distinctes)' },
    { value: 'negatives', label: 'BOC Négatives' },
    { value: 'non_recherchees', label: 'Non recherchées' },
];

const biologicalStatusOptions = [
    { value: 'negative', label: 'Négatif' },
    { value: 'positive', label: 'Positif' },
    { value: 'non_disponible', label: 'Non réalisé / Indisponible' },
];

const ieppSampleOptions = [
    { value: 'sang_et_lcr', label: 'Sang et LCR (Comparatif recommandé)' },
    { value: 'lcr', label: 'LCR uniquement' },
    { value: 'sang', label: 'Sérum sanguin uniquement' },
];

const applyTypicalSepPreset = () => {
    form.exam_type = 'Ponction Lombaire & Biologie';
    form.pl_result = 'pathologique';
    form.pl_cytorachie = '3.0';
    form.pl_proteinorachie = '0.42';
    form.pl_oligoclonal_bands = 'positives';
    form.biology_anti_nmo = 'negative';
    form.biology_anti_mog = 'negative';
    form.biology_anti_antinucleaire = 'negative';
    form.biology_anti_bgsa = 'negative';
    form.biology_serology_hbv = 'negative';
    form.iepp_sample_type = 'sang_et_lcr';
    form.iepp_result = 'Profil de type 2 : Synthèse intrathécale exclusive d\'IgG (critère McDonald 2017 validé)';
    form.iepp_kappa_index = '8.5';
    form.notes = 'Bilan conforme aux critères diagnostiques d\'une SEP récurrente.';
};

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        if (props.exam) {
            form.exam_date = props.exam.exam_date_raw || props.exam.exam_date || '';
            form.exam_type = props.exam.exam_type || 'Ponction Lombaire & Biologie';
            form.pl_result = props.exam.pl_result || 'pathologique';
            form.pl_cytorachie = props.exam.pl_cytorachie !== null && props.exam.pl_cytorachie !== undefined
                ? String(props.exam.pl_cytorachie)
                : '';
            form.pl_proteinorachie = props.exam.pl_proteinorachie !== null && props.exam.pl_proteinorachie !== undefined
                ? String(props.exam.pl_proteinorachie)
                : '';
            form.pl_oligoclonal_bands = props.exam.pl_oligoclonal_bands || 'positives';
            form.biology_anti_nmo = props.exam.biology_anti_nmo || 'negative';
            form.biology_anti_mog = props.exam.biology_anti_mog || 'negative';
            form.biology_anti_antinucleaire = props.exam.biology_anti_antinucleaire || 'negative';
            form.biology_anti_bgsa = props.exam.biology_anti_bgsa || 'negative';
            form.biology_serology_hbv = props.exam.biology_serology_hbv || 'negative';
            form.biology_other = props.exam.biology_other || '';
            form.iepp_sample_type = props.exam.iepp_sample_type || 'sang_et_lcr';
            form.iepp_result = props.exam.iepp_result || '';
            form.iepp_kappa_index = props.exam.iepp_kappa_index !== null && props.exam.iepp_kappa_index !== undefined
                ? String(props.exam.iepp_kappa_index)
                : '';
            form.report_file_path = props.exam.report_file_path || '';
            form.notes = props.exam.notes || '';
        } else {
            form.reset();
            form.exam_date = new Date().toISOString().split('T')[0];
            form.exam_type = 'Ponction Lombaire & Biologie';
            form.pl_result = 'pathologique';
            form.pl_cytorachie = '';
            form.pl_proteinorachie = '';
            form.pl_oligoclonal_bands = 'positives';
            form.biology_anti_nmo = 'negative';
            form.biology_anti_mog = 'negative';
            form.biology_anti_antinucleaire = 'negative';
            form.biology_anti_bgsa = 'negative';
            form.biology_serology_hbv = 'negative';
            form.biology_other = '';
            form.iepp_sample_type = 'sang_et_lcr';
            form.iepp_result = 'Profil de type 2 : Synthèse intrathécale exclusive d\'IgG';
            form.iepp_kappa_index = '';
            form.report_file_path = '';
            form.notes = '';
        }
        form.clearErrors();
    },
    { immediate: true },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        pl_cytorachie: data.pl_cytorachie === '' || data.pl_cytorachie === null ? null : Number(data.pl_cytorachie),
        pl_proteinorachie: data.pl_proteinorachie === '' || data.pl_proteinorachie === null ? null : Number(data.pl_proteinorachie),
        iepp_kappa_index: data.iepp_kappa_index === '' || data.iepp_kappa_index === null ? null : Number(data.iepp_kappa_index),
        report_file_path: data.report_file_path || null,
    }));

    if (isEditing.value && props.exam?.id) {
        form.put(`/patients/${props.patientId}/complementary-exams/${props.exam.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    } else {
        form.post(`/patients/${props.patientId}/complementary-exams`, {
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
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                            <FlaskConical class="size-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-lg font-bold text-foreground">
                                {{ isEditing ? 'Modifier l\'Examen Complémentaire' : 'Nouvel Examen Complémentaire (PL & Biologie)' }}
                            </DialogTitle>
                            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                                Analyse du LCR, Bandes Oligoclonales, Sérologies et Diagnostic Différentiel (Module 4)
                            </DialogDescription>
                        </div>
                    </div>

                    <!-- Preset Button -->
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="applyTypicalSepPreset"
                        class="gap-1.5 text-xs h-8 hidden sm:flex"
                    >
                        <Sparkles class="size-3.5 text-amber-500" />
                        <span>Profil SEP Typique</span>
                    </Button>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 pt-3">
                <!-- SECTION 1 : DATE & TYPE D'EXAMEN -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Calendar class="size-3.5 text-primary" />
                            <span>1. Date & Nature du Bilan</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="exam_date" class="text-xs font-medium">
                                Date de Réalisation <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="exam_date"
                                type="date"
                                v-model="form.exam_date"
                                :max="new Date().toISOString().split('T')[0]"
                                required
                                class="h-9 text-xs"
                            />
                            <p v-if="form.errors.exam_date" class="text-[11px] text-destructive">
                                {{ form.errors.exam_date }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="exam_type" class="text-xs font-medium">
                                Type d'Examen <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.exam_type">
                                <SelectTrigger id="exam_type" class="h-9 text-xs">
                                    <SelectValue placeholder="Sélectionner le type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in examTypeOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.exam_type" class="text-[11px] text-destructive">
                                {{ form.errors.exam_type }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 : PONCTION LOMBAIRE (LCR) -->
                <div class="space-y-4 p-4 rounded-xl border border-border/70 bg-card/60">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2 text-primary">
                            <TestTube class="size-4" />
                            <span>2. Ponction Lombaire & Analyse du LCR</span>
                        </div>
                        <Badge variant="outline" class="text-[10px] uppercase font-mono">LCR</Badge>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="pl_result" class="text-xs font-medium">
                                Résultat Global de la PL
                            </Label>
                            <Select v-model="form.pl_result">
                                <SelectTrigger id="pl_result" class="h-9 text-xs">
                                    <SelectValue placeholder="Résultat global" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in plResultOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.pl_result" class="text-[11px] text-destructive">
                                {{ form.errors.pl_result }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="pl_oligoclonal_bands" class="text-xs font-medium">
                                Bandes Oligoclonales (BOC)
                            </Label>
                            <Select v-model="form.pl_oligoclonal_bands">
                                <SelectTrigger id="pl_oligoclonal_bands" class="h-9 text-xs">
                                    <SelectValue placeholder="BOC" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in bocOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[10px] text-muted-foreground">
                                Critère de dissémination temporelle (McDonald 2017)
                            </p>
                            <p v-if="form.errors.pl_oligoclonal_bands" class="text-[11px] text-destructive">
                                {{ form.errors.pl_oligoclonal_bands }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="pl_cytorachie" class="text-xs font-medium">
                                Cytorachie (éléments / mm³)
                            </Label>
                            <Input
                                id="pl_cytorachie"
                                type="number"
                                step="0.1"
                                min="0"
                                max="1000"
                                v-model="form.pl_cytorachie"
                                placeholder="Norme < 5 éléments/mm³ (ex: 3.5)"
                                class="h-9 text-xs font-mono"
                            />
                            <p v-if="form.errors.pl_cytorachie" class="text-[11px] text-destructive">
                                {{ form.errors.pl_cytorachie }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="pl_proteinorachie" class="text-xs font-medium">
                                Protéinorachie (g / L)
                            </Label>
                            <Input
                                id="pl_proteinorachie"
                                type="number"
                                step="0.01"
                                min="0"
                                max="10"
                                v-model="form.pl_proteinorachie"
                                placeholder="Norme 0.15 - 0.45 g/L (ex: 0.42)"
                                class="h-9 text-xs font-mono"
                            />
                            <p v-if="form.errors.pl_proteinorachie" class="text-[11px] text-destructive">
                                {{ form.errors.pl_proteinorachie }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3 : BIOLOGIE SPÉCIFIQUE (DIAGNOSTIC DIFFÉRENTIEL) -->
                <div class="space-y-4 p-4 rounded-xl border border-border/70 bg-card/60">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2 text-primary">
                            <Dna class="size-4" />
                            <span>3. Marqueurs Biologiques & Diagnostic Différentiel</span>
                        </div>
                        <Badge variant="outline" class="text-[10px] uppercase font-mono">Sérum</Badge>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Anti-NMO -->
                        <div class="space-y-1.5">
                            <Label for="biology_anti_nmo" class="text-xs font-medium">
                                Anticorps Anti-NMO (Aquaporine-4 / AQP4)
                            </Label>
                            <Select v-model="form.biology_anti_nmo">
                                <SelectTrigger id="biology_anti_nmo" class="h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in biologicalStatusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[10px] text-muted-foreground">Exclusion NMOSD / Devic</p>
                        </div>

                        <!-- Anti-MOG -->
                        <div class="space-y-1.5">
                            <Label for="biology_anti_mog" class="text-xs font-medium">
                                Anticorps Anti-MOG (MOGAD)
                            </Label>
                            <Select v-model="form.biology_anti_mog">
                                <SelectTrigger id="biology_anti_mog" class="h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in biologicalStatusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[10px] text-muted-foreground">Exclusion pathologie MOGAD</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Anti-nucléaires -->
                        <div class="space-y-1.5">
                            <Label for="biology_anti_antinucleaire" class="text-xs font-medium">
                                Anti-Nucléaires (AAN)
                            </Label>
                            <Select v-model="form.biology_anti_antinucleaire">
                                <SelectTrigger id="biology_anti_antinucleaire" class="h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in biologicalStatusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Anti-BGSA -->
                        <div class="space-y-1.5">
                            <Label for="biology_anti_bgsa" class="text-xs font-medium">
                                Anti-BGSA / Auto-immuns
                            </Label>
                            <Select v-model="form.biology_anti_bgsa">
                                <SelectTrigger id="biology_anti_bgsa" class="h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in biologicalStatusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sérologie VHB -->
                        <div class="space-y-1.5">
                            <Label for="biology_serology_hbv" class="text-xs font-medium">
                                Sérologie VHB (Hépatite B)
                            </Label>
                            <Select v-model="form.biology_serology_hbv">
                                <SelectTrigger id="biology_serology_hbv" class="h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in biologicalStatusOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="biology_other" class="text-xs font-medium">
                            Autres Sérologies & Marqueurs Biologiques
                        </Label>
                        <Input
                            id="biology_other"
                            type="text"
                            v-model="form.biology_other"
                            placeholder="ex: VIH négatif, TPHA-VDRL négatif, Sérologie Lyme négative, Vitamine B12 normale"
                            class="h-9 text-xs"
                        />
                        <p v-if="form.errors.biology_other" class="text-[11px] text-destructive">
                            {{ form.errors.biology_other }}
                        </p>
                    </div>
                </div>

                <!-- SECTION 4 : IEPP (IMMUNO-ÉLECTROPHORÈSE) -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <div class="flex items-center gap-2">
                            <Activity class="size-3.5 text-primary" />
                            <span>4. IEPP & Profil Comparatif Sang / LCR</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label for="iepp_sample_type" class="text-xs font-medium">
                                Type de Prélèvement
                            </Label>
                            <Select v-model="form.iepp_sample_type">
                                <SelectTrigger id="iepp_sample_type" class="h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in ieppSampleOptions"
                                        :key="opt.value"
                                        :value="opt.value"
                                        class="text-xs"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="iepp_kappa_index" class="text-xs font-medium">
                                    Index Kappa (KFLC LCR / Sérum)
                                </Label>
                                <span class="text-[10px] text-muted-foreground font-mono">Norme < 5.8</span>
                            </div>
                            <Input
                                id="iepp_kappa_index"
                                type="number"
                                step="0.1"
                                min="0"
                                max="1000"
                                v-model="form.iepp_kappa_index"
                                placeholder="ex: 8.5"
                                class="h-9 text-xs font-mono"
                            />
                            <p v-if="form.errors.iepp_kappa_index" class="text-[11px] text-destructive">
                                {{ form.errors.iepp_kappa_index }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="notes" class="text-xs font-medium">
                                Notes Cliniques
                            </Label>
                            <Input
                                id="notes"
                                type="text"
                                v-model="form.notes"
                                placeholder="Commentaires ou observations particulières..."
                                class="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="iepp_result" class="text-xs font-medium">
                            Résultat de l'Immuno-électrophorèse & Profil Isociblage
                        </Label>
                        <Textarea
                            id="iepp_result"
                            v-model="form.iepp_result"
                            rows="2"
                            placeholder="Description du profil IEPP (Profil de type 2: synthèse intrathécale, Profil de type 4: profil miroir...)"
                            class="text-xs resize-none"
                        />
                        <p v-if="form.errors.iepp_result" class="text-[11px] text-destructive">
                            {{ form.errors.iepp_result }}
                        </p>
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
                        <span>{{ isEditing ? 'Mettre à jour' : 'Enregistrer l\'examen' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
