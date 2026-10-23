<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Brain,
    Calendar,
    Check,
    CheckCircle2,
    Crosshair,
    FileCheck,
    FileText,
    Image as ImageIcon,
    Layers,
    Plus,
    RefreshCw,
    Trash2,
    UploadCloud,
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

export type MriExamFormData = {
    id?: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    timing: 'baseline' | 'rebaseline_6m' | 'control' | string | null;
    region: 'cerebrale' | 'medullaire' | 'les_deux' | string | null;
    sep_protocol_applied: boolean;
    sequences_available: string | null;
    lesions_count_estimated: string | null;
    lesions_locations: string | null;
    contrast_enhancement: string | null;
    new_lesions_count: number | null;
    radiologist_report: string | null;
    dicom_study_uid?: string | null;
    notes: string | null;
    key_images?: Array<{ name: string; path: string; size?: number; mime?: string }> | null;
};

type Props = {
    open: boolean;
    patientId: number;
    exam?: MriExamFormData | null;
};

const props = withDefaults(defineProps<Props>(), {
    exam: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const replaceFileInputRef = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);
const existingImages = ref<Array<{ name: string; path: string; size?: number; mime?: string }>>([]);
const replaceTargetIndex = ref<number | null>(null);

const form = useForm({
    exam_date: '',
    timing: 'baseline',
    region: 'cerebrale',
    sep_protocol_applied: true,
    sequences_available: 'T1 3D, T2 FLAIR 3D, T2 TSE, Diffusion, T1 3D + Gado',
    lesions_count_estimated: '1-3',
    lesions_locations: '',
    contrast_enhancement: 'negative',
    new_lesions_count: 0,
    radiologist_report: '',
    dicom_study_uid: '',
    notes: '',
    images: [] as File[],
});

const isEditing = computed(() => !!props.exam?.id);

// Séquences IRM SEP courantes
const standardSequences = [
    'T1 3D',
    'T2 FLAIR 3D',
    'T2 TSE',
    'Diffusion B1000',
    'T1 3D + Gado',
    'DIR 3D',
    'T2 T2* / SWI',
];

// Presets de nombre de lésions
const lesionCountPresets = ['0', '1-3', '4-9', '≥ 10', 'Confluentes'];

// Topographies cérébrales et médullaires
const cerebralLocations = [
    'Périventriculaires',
    'Juxtacorticales / Corticales',
    'Sous-tentorielles (Tronc / Cervelet)',
    'Corps calleux',
    'Centres semi-ovales',
];

const medullaryLocations = [
    'Médullaire cervicale',
    'Médullaire thoracique / dorsale',
    'Cône médullaire',
    'Lésion courte périphérique (< 2 segments)',
];

const toggleSequence = (seq: string) => {
    const current = form.sequences_available ? form.sequences_available.split(',').map((s) => s.trim()).filter(Boolean) : [];
    if (current.includes(seq)) {
        form.sequences_available = current.filter((s) => s !== seq).join(', ');
    } else {
        current.push(seq);
        form.sequences_available = current.join(', ');
    }
};

const isSequenceActive = (seq: string) => {
    if (!form.sequences_available) return false;
    return form.sequences_available.split(',').map((s) => s.trim()).includes(seq);
};

const addLocationChip = (loc: string) => {
    if (!form.lesions_locations) {
        form.lesions_locations = loc;
    } else if (!form.lesions_locations.includes(loc)) {
        form.lesions_locations += `, ${loc}`;
    }
};

const handleFilesSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const newFiles = Array.from(target.files);
        selectedFiles.value = [...selectedFiles.value, ...newFiles];
        form.images = selectedFiles.value;
    }
};

const removeSelectedFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
    form.images = selectedFiles.value;
};

const triggerReplaceExistingImage = (idx: number) => {
    replaceTargetIndex.value = idx;
    if (replaceFileInputRef.value) {
        replaceFileInputRef.value.value = '';
        replaceFileInputRef.value.click();
    }
};

const handleReplaceFileSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0 && replaceTargetIndex.value !== null) {
        const file = target.files[0];
        // Retirer l'image existante à remplacer
        existingImages.value.splice(replaceTargetIndex.value, 1);
        // Ajouter le nouveau fichier de remplacement
        selectedFiles.value.push(file);
        form.images = selectedFiles.value;
        replaceTargetIndex.value = null;
    }
};

const removeExistingImage = (idx: number) => {
    existingImages.value.splice(idx, 1);
};

// Reset or sync when modal opens
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        selectedFiles.value = [];
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
        if (replaceFileInputRef.value) {
            replaceFileInputRef.value.value = '';
        }
        replaceTargetIndex.value = null;

        if (props.exam) {
            existingImages.value = props.exam.key_images ? JSON.parse(JSON.stringify(props.exam.key_images)) : [];
            form.exam_date = props.exam.exam_date_raw || props.exam.exam_date || '';
            form.timing = props.exam.timing || 'baseline';
            form.region = props.exam.region || 'cerebrale';
            form.sep_protocol_applied = props.exam.sep_protocol_applied ?? true;
            form.sequences_available = props.exam.sequences_available || '';
            form.lesions_count_estimated = props.exam.lesions_count_estimated || '1-3';
            form.lesions_locations = props.exam.lesions_locations || '';
            form.contrast_enhancement = props.exam.contrast_enhancement || 'negative';
            form.new_lesions_count = props.exam.new_lesions_count ?? 0;
            form.radiologist_report = props.exam.radiologist_report || '';
            form.dicom_study_uid = props.exam.dicom_study_uid || '';
            form.notes = props.exam.notes || '';
            form.images = [];
        } else {
            existingImages.value = [];
            form.reset();
            form.exam_date = new Date().toISOString().split('T')[0];
            form.timing = 'baseline';
            form.region = 'cerebrale';
            form.sep_protocol_applied = true;
            form.sequences_available = 'T1 3D, T2 FLAIR 3D, T2 TSE, Diffusion, T1 3D + Gado';
            form.lesions_count_estimated = '1-3';
            form.lesions_locations = '';
            form.contrast_enhancement = 'negative';
            form.new_lesions_count = 0;
            form.radiologist_report = '';
            form.dicom_study_uid = '';
            form.notes = '';
            form.images = [];
        }
        form.clearErrors();
    },
    { immediate: true },
);

const submit = () => {
    if (isEditing.value && props.exam?.id) {
        form.transform((data) => ({
            ...data,
            retained_images: JSON.stringify(existingImages.value),
            _method: 'put',
        })).post(`/patients/${props.patientId}/mri-exams/${props.exam.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    } else {
        form.post(`/patients/${props.patientId}/mri-exams`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                emit('update:open', false);
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(val: boolean) => emit('update:open', val)">
        <DialogContent class="sm:max-w-3xl max-w-3xl max-h-[90vh] overflow-y-auto p-6 sm:rounded-2xl">
            <DialogHeader class="pb-3 border-b border-border">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                        <Crosshair class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold text-foreground">
                            {{ isEditing ? "Modifier l'Examen IRM" : "Nouvel Examen IRM" }}
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                            Enregistrement de l'imagerie cérébrale ou médullaire selon le protocole SEP McDonald
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-6 pt-3">
                <!-- SECTION 1 : DATE, TIMING & RÉGION -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <Calendar class="size-3.5 text-primary" />
                        <span>1. Date & Paramètres de l'Examen</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Date de l'examen -->
                        <div class="space-y-1.5">
                            <Label for="exam_date" class="text-xs font-medium">
                                Date de l'IRM <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="exam_date"
                                type="date"
                                v-model="form.exam_date"
                                :max="new Date().toISOString().split('T')[0]"
                                class="h-9 text-xs"
                                required
                            />
                            <p v-if="form.errors.exam_date" class="text-[11px] text-destructive">
                                {{ form.errors.exam_date }}
                            </p>
                        </div>

                        <!-- Timing de l'IRM -->
                        <div class="space-y-1.5">
                            <Label for="timing" class="text-xs font-medium">
                                Timing <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.timing">
                                <SelectTrigger id="timing" class="h-9 text-xs">
                                    <SelectValue placeholder="Sélectionner le timing" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="baseline">
                                        IRM Baseline (Initiale)
                                    </SelectItem>
                                    <SelectItem value="rebaseline_6m">
                                        IRM Rebaseline (6 mois de traitement)
                                    </SelectItem>
                                    <SelectItem value="control">
                                        IRM de Contrôle / Suivi
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.timing" class="text-[11px] text-destructive">
                                {{ form.errors.timing }}
                            </p>
                        </div>

                        <!-- Région explorée -->
                        <div class="space-y-1.5">
                            <Label for="region" class="text-xs font-medium">
                                Région Explorée <span class="text-destructive">*</span>
                            </Label>
                            <Select v-model="form.region">
                                <SelectTrigger id="region" class="h-9 text-xs">
                                    <SelectValue placeholder="Région explorée" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cerebrale">
                                        IRM Cérébrale
                                    </SelectItem>
                                    <SelectItem value="medullaire">
                                        IRM Médullaire
                                    </SelectItem>
                                    <SelectItem value="les_deux">
                                        Cérébro-Médullaire (Les deux)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.region" class="text-[11px] text-destructive">
                                {{ form.errors.region }}
                            </p>
                        </div>
                    </div>

                    <!-- Protocole IRM SEP Appliqué -->
                    <div class="p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/5 flex items-start gap-3">
                        <Checkbox
                            id="sep_protocol_applied"
                            :checked="form.sep_protocol_applied"
                            @update:checked="(val: boolean) => (form.sep_protocol_applied = val)"
                            class="mt-0.5"
                        />
                        <div class="space-y-0.5 cursor-pointer" @click="form.sep_protocol_applied = !form.sep_protocol_applied">
                            <Label for="sep_protocol_applied" class="text-xs font-semibold text-foreground cursor-pointer flex items-center gap-1.5">
                                <CheckCircle2 class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                                <span>Protocole IRM SEP appliqué</span>
                            </Label>
                            <p class="text-[11px] text-muted-foreground leading-relaxed">
                                Recommandations OFSEP/MAGNIMS : acquisition volumique 3D T1/FLAIR, coupes fines sagittales médullaires, et rehaussement gadolinium selon les indications cliniques.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 : SÉQUENCES DISPONIBLES & LÉSIONS T2 -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <Layers class="size-3.5 text-primary" />
                        <span>2. Séquences & Charge Lésionnelle</span>
                    </div>

                    <!-- Séquences disponibles -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="sequences_available" class="text-xs font-medium">
                                Séquences Disponibles
                            </Label>
                            <span class="text-[11px] text-muted-foreground">Cliquez pour ajouter/retirer</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="seq in standardSequences"
                                :key="seq"
                                type="button"
                                @click="toggleSequence(seq)"
                                :class="[
                                    'text-[11px] px-2.5 py-1 rounded-md border font-medium transition-colors',
                                    isSequenceActive(seq)
                                        ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                        : 'bg-muted/40 hover:bg-muted text-muted-foreground border-border',
                                ]"
                            >
                                <span v-if="isSequenceActive(seq)" class="mr-1 inline-block">✓</span>
                                {{ seq }}
                            </button>
                        </div>
                        <Input
                            id="sequences_available"
                            v-model="form.sequences_available"
                            placeholder="Ex: T1 3D, T2 FLAIR 3D, T2 TSE, Diffusion B1000..."
                            class="h-9 text-xs font-mono"
                        />
                    </div>

                    <!-- Charge lésionnelle T2 & Prise de contraste -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Nombre estimé de lésions -->
                        <div class="space-y-1.5">
                            <Label class="text-xs font-medium">
                                Nombre de lésions T2 estimé
                            </Label>
                            <div class="grid grid-cols-5 gap-1">
                                <button
                                    v-for="preset in lesionCountPresets"
                                    :key="preset"
                                    type="button"
                                    @click="form.lesions_count_estimated = preset"
                                    :class="[
                                        'h-8 text-[11px] font-semibold rounded-md border transition-colors',
                                        form.lesions_count_estimated === preset
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'bg-muted/40 hover:bg-muted text-muted-foreground border-border',
                                    ]"
                                >
                                    {{ preset }}
                                </button>
                            </div>
                        </div>

                        <!-- Prise de contraste au Gadolinium -->
                        <div class="space-y-1.5">
                            <Label for="contrast_enhancement" class="text-xs font-medium">
                                Prise de contraste (Gadolinium)
                            </Label>
                            <Select v-model="form.contrast_enhancement">
                                <SelectTrigger id="contrast_enhancement" class="h-9 text-xs">
                                    <SelectValue placeholder="Prise de contraste" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="negative">
                                        Absente (Aucune lésion active)
                                    </SelectItem>
                                    <SelectItem value="positive">
                                        Présente (Lésions rehaussées Gado+)
                                    </SelectItem>
                                    <SelectItem value="non_evaluee">
                                        Non évaluée / Non injecté
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Nouvelles lésions T2 / Actives -->
                        <div class="space-y-1.5">
                            <Label for="new_lesions_count" class="text-xs font-medium">
                                Nouvelles lésions (vs baseline)
                            </Label>
                            <Input
                                id="new_lesions_count"
                                type="number"
                                min="0"
                                max="100"
                                v-model.number="form.new_lesions_count"
                                class="h-9 text-xs font-mono"
                                placeholder="0"
                            />
                            <p class="text-[10px] text-muted-foreground">
                                Critère de dissémination dans le temps (DIT)
                            </p>
                        </div>
                    </div>

                    <!-- Topographie / Localisation des lésions -->
                    <div class="space-y-2">
                        <Label for="lesions_locations" class="text-xs font-medium">
                            Topographie & Localisation des Lésions
                        </Label>

                        <!-- Puces de raccourci adaptées à la région -->
                        <div class="flex flex-wrap gap-1.5">
                            <template v-if="form.region === 'cerebrale' || form.region === 'les_deux'">
                                <button
                                    v-for="loc in cerebralLocations"
                                    :key="loc"
                                    type="button"
                                    @click="addLocationChip(loc)"
                                    class="text-[10px] px-2 py-0.5 rounded border border-border bg-muted/40 hover:bg-muted text-muted-foreground transition-colors"
                                >
                                    + {{ loc }}
                                </button>
                            </template>
                            <template v-if="form.region === 'medullaire' || form.region === 'les_deux'">
                                <button
                                    v-for="loc in medullaryLocations"
                                    :key="loc"
                                    type="button"
                                    @click="addLocationChip(loc)"
                                    class="text-[10px] px-2 py-0.5 rounded border border-border bg-muted/40 hover:bg-muted text-muted-foreground transition-colors"
                                >
                                    + {{ loc }}
                                </button>
                            </template>
                        </div>

                        <Textarea
                            id="lesions_locations"
                            v-model="form.lesions_locations"
                            rows="2"
                            placeholder="Préciser les territoires atteints (ex: multiples hypersignaux périventriculaires orientés perpendiculairement aux ventricules, atteinte du corps calleux, hypersignal centromédullaire C3-C4)..."
                            class="text-xs"
                        />
                    </div>
                </div>

                <!-- SECTION 3 : COMPTE RENDU RADIOLOGIQUE & FICHIERS -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-1 border-b border-border/50 text-xs font-semibold text-foreground uppercase tracking-wider">
                        <FileText class="size-3.5 text-primary" />
                        <span>3. Compte Rendu & Fichiers d'Imagerie</span>
                    </div>

                    <!-- Compte rendu radiologique -->
                    <div class="space-y-1.5">
                        <Label for="radiologist_report" class="text-xs font-medium">
                            Compte Rendu / Conclusion Radiologique
                        </Label>
                        <Textarea
                            id="radiologist_report"
                            v-model="form.radiologist_report"
                            rows="3"
                            placeholder="Conclusion du radiologue (ex: Aspect d'encéphalomyélite démyélinisante répondant aux critères de dissémination spatiale et temporelle de McDonald 2017 sans activité gadolinophile décelable ce jour)..."
                            class="text-xs"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Identifiant PACS / DICOM Study UID -->
                        <div class="space-y-1.5">
                            <Label for="dicom_study_uid" class="text-xs font-medium">
                                Réf. PACS / DICOM Study UID (Optionnel)
                            </Label>
                            <Input
                                id="dicom_study_uid"
                                v-model="form.dicom_study_uid"
                                placeholder="1.2.840.113619.2.xxx ou N° d'accession PACS"
                                class="h-9 text-xs font-mono"
                            />
                        </div>

                        <!-- Notes complémentaires -->
                        <div class="space-y-1.5">
                            <Label for="notes" class="text-xs font-medium">
                                Notes complémentaires
                            </Label>
                            <Input
                                id="notes"
                                v-model="form.notes"
                                placeholder="Qualité de l'examen, artefacts de mouvement, etc."
                                class="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <!-- Téléversement de coupes clés ou compte rendu PDF -->
                    <div class="space-y-2">
                        <Label class="text-xs font-medium">
                            Captures d'IRM / Coupes Clés / Compte Rendu (DICOM, PNG, JPG, PDF)
                        </Label>

                        <div
                            @click="fileInputRef?.click()"
                            class="border-2 border-dashed border-border/80 hover:border-primary/60 rounded-xl p-4 text-center cursor-pointer transition-colors bg-muted/10 hover:bg-muted/20"
                        >
                            <input
                                ref="fileInputRef"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp,application/pdf,.dcm,.zip"
                                class="hidden"
                                @change="handleFilesSelected"
                            />
                            <UploadCloud class="size-6 mx-auto text-muted-foreground/60 mb-1" />
                            <p class="text-xs font-medium text-foreground">
                                Cliquez pour joindre des captures d'IRM ou un compte rendu
                            </p>
                            <p class="text-[11px] text-muted-foreground mt-0.5">
                                Fichiers acceptés : PNG, JPG, WEBP, PDF, DICOM (.dcm), archive ZIP (max 20 Mo)
                            </p>
                        </div>

                        <!-- Fichiers sélectionnés -->
                        <div v-if="selectedFiles.length > 0" class="space-y-1.5 pt-1">
                            <span class="text-[11px] font-semibold text-muted-foreground block">
                                Fichiers prêts à être enregistrés ({{ selectedFiles.length }}) :
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div
                                    v-for="(f, idx) in selectedFiles"
                                    :key="idx"
                                    class="flex items-center justify-between p-2 rounded-lg bg-muted/40 border border-border text-xs"
                                >
                                    <div class="flex items-center gap-2 truncate pr-2">
                                        <ImageIcon class="size-4 text-primary shrink-0" />
                                        <span class="truncate font-medium text-foreground">{{ f.name }}</span>
                                        <span class="text-[10px] text-muted-foreground font-mono shrink-0">
                                            ({{ (f.size / 1024).toFixed(0) }} Ko)
                                        </span>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="size-6 text-muted-foreground hover:text-destructive shrink-0"
                                        @click="removeSelectedFile(idx)"
                                    >
                                        <X class="size-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Fichiers déjà existants dans cet examen (avec options de remplacement et suppression) -->
                        <div v-if="isEditing && existingImages.length > 0" class="space-y-2 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                                    <ImageIcon class="size-3.5 text-primary" />
                                    <span>Fichiers actuellement associés ({{ existingImages.length }}) :</span>
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    Remplacer ou supprimer n'importe quelle coupe
                                </span>
                            </div>

                            <!-- Champ caché pour remplacer une coupe précise -->
                            <input
                                ref="replaceFileInputRef"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,application/pdf,.dcm,.zip"
                                class="hidden"
                                @change="handleReplaceFileSelected"
                            />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div
                                    v-for="(img, idx) in existingImages"
                                    :key="idx"
                                    class="flex items-center justify-between p-2 rounded-xl bg-muted/30 border border-border/80 hover:border-border transition-colors text-xs gap-2"
                                >
                                    <div class="flex items-center gap-2 truncate min-w-0">
                                        <!-- Miniature ou icône de fichier -->
                                        <div class="size-9 rounded-md bg-zinc-900 border border-border overflow-hidden shrink-0 flex items-center justify-center">
                                            <img
                                                v-if="img.mime?.startsWith('image/') || img.path?.match(/\.(jpe?g|png|webp)$/i)"
                                                :src="img.path"
                                                :alt="img.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <FileText v-else class="size-4 text-muted-foreground" />
                                        </div>
                                        <div class="truncate">
                                            <a
                                                :href="img.path"
                                                target="_blank"
                                                class="truncate font-medium text-foreground hover:text-primary transition-colors block text-xs"
                                                :title="img.name"
                                            >
                                                {{ img.name }}
                                            </a>
                                            <span v-if="img.size" class="text-[10px] text-muted-foreground font-mono">
                                                {{ (img.size / 1024).toFixed(0) }} Ko
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            class="h-7 px-2 text-[11px] gap-1 text-muted-foreground hover:text-foreground"
                                            title="Remplacer cette image"
                                            @click="triggerReplaceExistingImage(idx)"
                                        >
                                            <RefreshCw class="size-3" />
                                            <span class="hidden sm:inline">Remplacer</span>
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                            title="Supprimer cette coupe"
                                            @click="removeExistingImage(idx)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        <span>{{ isEditing ? 'Mettre à jour l\'examen' : 'Enregistrer l\'examen IRM' }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
