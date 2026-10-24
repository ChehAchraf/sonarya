<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Activity,
    ArrowLeftRight,
    Brain,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Columns2,
    Crosshair,
    Eye,
    FileText,
    Image as ImageIcon,
    Layers,
    Move,
    RotateCcw,
    Sliders,
    Sun,
    Trash2,
    X,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { MriExamFormData } from './MriExamDialog.vue';

type Props = {
    open: boolean;
    patientId?: number;
    patientName: string;
    dossierNumber: string;
    exams: MriExamFormData[];
    initialExamId?: number | null;
};

const props = withDefaults(defineProps<Props>(), {
    patientId: undefined,
    initialExamId: null,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

// Viewer Mode: 'single' | 'compare'
const viewMode = ref<'single' | 'compare'>('single');

// Selected Exams
const selectedExamLeftId = ref<number | null>(null);
const selectedExamRightId = ref<number | null>(null);

// Selected Image / Slice index for uploaded files
const selectedImageIndexLeft = ref(0);
const selectedImageIndexRight = ref(0);

// Synchronized scrolling
const isSyncSlices = ref(true);

// Slices index for simulated series
const sliceIndexLeft = ref(12);
const sliceIndexRight = ref(12);
const totalSimulatedSlices = 24;

// Image display adjustments
const zoomLeft = ref(1.0);
const zoomRight = ref(1.0);
const brightnessLeft = ref(100);
const brightnessRight = ref(100);
const contrastLeft = ref(100);
const contrastRight = ref(100);
const isInvertedLeft = ref(false);
const isInvertedRight = ref(false);

// Panning states (x, y offsets) for navigation when zoomed in
const panLeft = ref({ x: 0, y: 0 });
const panRight = ref({ x: 0, y: 0 });
const isPanningLeft = ref(false);
const isPanningRight = ref(false);
const panStartLeft = ref({ x: 0, y: 0 });
const panStartRight = ref({ x: 0, y: 0 });
const isDeletingSlice = ref(false);

// Active sequence tab
const activeSequenceLeft = ref('FLAIR');
const activeSequenceRight = ref('FLAIR');
const availableSequences = ['FLAIR', 'T1 + Gado', 'T2 TSE', 'DIR', 'Diffusion'];

// Initialize selections when modal opens
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        selectedImageIndexLeft.value = 0;
        selectedImageIndexRight.value = 0;
        panLeft.value = { x: 0, y: 0 };
        panRight.value = { x: 0, y: 0 };

        if (props.exams.length > 0) {
            const sorted = [...props.exams].sort((a, b) => {
                const da = a.exam_date_raw ? new Date(a.exam_date_raw).getTime() : 0;
                const db = b.exam_date_raw ? new Date(b.exam_date_raw).getTime() : 0;
                return da - db;
            });

            if (props.initialExamId) {
                selectedExamLeftId.value = props.initialExamId;
                const other = sorted.find((e) => e.id !== props.initialExamId);
                selectedExamRightId.value = other ? other.id! : sorted[0].id!;
            } else {
                selectedExamLeftId.value = sorted[0].id!;
                selectedExamRightId.value = sorted.length > 1 ? sorted[sorted.length - 1].id! : sorted[0].id!;
            }
        }
    },
    { immediate: true },
);

// Computed selected exams
const examLeft = computed(() => {
    return props.exams.find((e) => e.id === selectedExamLeftId.value) || props.exams[0] || null;
});

const examRight = computed(() => {
    return props.exams.find((e) => e.id === selectedExamRightId.value) || props.exams[props.exams.length - 1] || null;
});

// Reset image index & pan when switching exams
watch(selectedExamLeftId, () => {
    selectedImageIndexLeft.value = 0;
    panLeft.value = { x: 0, y: 0 };
});
watch(selectedExamRightId, () => {
    selectedImageIndexRight.value = 0;
    panRight.value = { x: 0, y: 0 };
});

// Watch slice / image changes if synchronized
watch(selectedImageIndexLeft, (newVal) => {
    panLeft.value = { x: 0, y: 0 };
    if (isSyncSlices.value && viewMode.value === 'compare' && examRight.value?.key_images) {
        if (newVal < examRight.value.key_images.length) {
            selectedImageIndexRight.value = newVal;
        }
    }
});

watch(selectedImageIndexRight, () => {
    panRight.value = { x: 0, y: 0 };
});

watch(sliceIndexLeft, (newVal) => {
    if (isSyncSlices.value && viewMode.value === 'compare') {
        sliceIndexRight.value = newVal;
    }
});

const prevImageLeft = () => {
    if (examLeft.value?.key_images && selectedImageIndexLeft.value > 0) {
        selectedImageIndexLeft.value--;
    }
};

const nextImageLeft = () => {
    if (examLeft.value?.key_images && selectedImageIndexLeft.value < examLeft.value.key_images.length - 1) {
        selectedImageIndexLeft.value++;
    }
};

const prevImageRight = () => {
    if (examRight.value?.key_images && selectedImageIndexRight.value > 0) {
        selectedImageIndexRight.value--;
    }
};

const nextImageRight = () => {
    if (examRight.value?.key_images && selectedImageIndexRight.value < examRight.value.key_images.length - 1) {
        selectedImageIndexRight.value++;
    }
};

// Pan & Zoom Handlers - Left Viewport
const handleMouseDownLeft = (e: MouseEvent) => {
    if (e.button !== 0) return;
    isPanningLeft.value = true;
    panStartLeft.value = {
        x: e.clientX - panLeft.value.x,
        y: e.clientY - panLeft.value.y,
    };
};

const handleMouseMoveLeft = (e: MouseEvent) => {
    if (!isPanningLeft.value) return;
    panLeft.value = {
        x: e.clientX - panStartLeft.value.x,
        y: e.clientY - panStartLeft.value.y,
    };
};

const handleMouseUpLeft = () => {
    isPanningLeft.value = false;
};

const handleWheelLeft = (e: WheelEvent) => {
    const delta = e.deltaY < 0 ? 0.15 : -0.15;
    const newZoom = Math.min(3.5, Math.max(0.6, Math.round((zoomLeft.value + delta) * 100) / 100));
    zoomLeft.value = newZoom;
    if (newZoom <= 1.0) {
        panLeft.value = { x: 0, y: 0 };
    }
};

const handleTouchStartLeft = (e: TouchEvent) => {
    if (e.touches.length === 1) {
        isPanningLeft.value = true;
        panStartLeft.value = {
            x: e.touches[0].clientX - panLeft.value.x,
            y: e.touches[0].clientY - panLeft.value.y,
        };
    }
};

const handleTouchMoveLeft = (e: TouchEvent) => {
    if (!isPanningLeft.value || e.touches.length !== 1) return;
    panLeft.value = {
        x: e.touches[0].clientX - panStartLeft.value.x,
        y: e.touches[0].clientY - panStartLeft.value.y,
    };
};

const resetPanZoomLeft = () => {
    zoomLeft.value = 1.0;
    panLeft.value = { x: 0, y: 0 };
};

// Pan & Zoom Handlers - Right Viewport
const handleMouseDownRight = (e: MouseEvent) => {
    if (e.button !== 0) return;
    isPanningRight.value = true;
    panStartRight.value = {
        x: e.clientX - panRight.value.x,
        y: e.clientY - panRight.value.y,
    };
};

const handleMouseMoveRight = (e: MouseEvent) => {
    if (!isPanningRight.value) return;
    panRight.value = {
        x: e.clientX - panStartRight.value.x,
        y: e.clientY - panStartRight.value.y,
    };
};

const handleMouseUpRight = () => {
    isPanningRight.value = false;
};

const handleWheelRight = (e: WheelEvent) => {
    const delta = e.deltaY < 0 ? 0.15 : -0.15;
    const newZoom = Math.min(3.5, Math.max(0.6, Math.round((zoomRight.value + delta) * 100) / 100));
    zoomRight.value = newZoom;
    if (newZoom <= 1.0) {
        panRight.value = { x: 0, y: 0 };
    }
};

const handleTouchStartRight = (e: TouchEvent) => {
    if (e.touches.length === 1) {
        isPanningRight.value = true;
        panStartRight.value = {
            x: e.touches[0].clientX - panRight.value.x,
            y: e.touches[0].clientY - panRight.value.y,
        };
    }
};

const handleTouchMoveRight = (e: TouchEvent) => {
    if (!isPanningRight.value || e.touches.length !== 1) return;
    panRight.value = {
        x: e.touches[0].clientX - panStartRight.value.x,
        y: e.touches[0].clientY - panStartRight.value.y,
    };
};

const resetPanZoomRight = () => {
    zoomRight.value = 1.0;
    panRight.value = { x: 0, y: 0 };
};

// Direct Slice Deletion from viewer
const deleteCurrentSliceLeft = () => {
    if (!props.patientId || !examLeft.value?.id || !examLeft.value.key_images) return;
    const currentImg = examLeft.value.key_images[selectedImageIndexLeft.value];
    if (!currentImg) return;

    if (!confirm(`Supprimer définitivement la coupe "${currentImg.name}" de cet examen IRM ?`)) {
        return;
    }

    isDeletingSlice.value = true;
    router.delete(`/patients/${props.patientId}/mri-exams/${examLeft.value.id}/images/${selectedImageIndexLeft.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeletingSlice.value = false;
            if (selectedImageIndexLeft.value > 0) {
                selectedImageIndexLeft.value--;
            }
        },
        onError: () => {
            isDeletingSlice.value = false;
        },
    });
};

const deleteCurrentSliceRight = () => {
    if (!props.patientId || !examRight.value?.id || !examRight.value.key_images) return;
    const currentImg = examRight.value.key_images[selectedImageIndexRight.value];
    if (!currentImg) return;

    if (!confirm(`Supprimer définitivement la coupe "${currentImg.name}" de cet examen IRM ?`)) {
        return;
    }

    isDeletingSlice.value = true;
    router.delete(`/patients/${props.patientId}/mri-exams/${examRight.value.id}/images/${selectedImageIndexRight.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeletingSlice.value = false;
            if (selectedImageIndexRight.value > 0) {
                selectedImageIndexRight.value--;
            }
        },
        onError: () => {
            isDeletingSlice.value = false;
        },
    });
};

const resetViewer = (side: 'left' | 'right' | 'both') => {
    if (side === 'left' || side === 'both') {
        zoomLeft.value = 1.0;
        panLeft.value = { x: 0, y: 0 };
        brightnessLeft.value = 100;
        contrastLeft.value = 100;
        isInvertedLeft.value = false;
        sliceIndexLeft.value = 12;
        selectedImageIndexLeft.value = 0;
    }
    if (side === 'right' || side === 'both') {
        zoomRight.value = 1.0;
        panRight.value = { x: 0, y: 0 };
        brightnessRight.value = 100;
        contrastRight.value = 100;
        isInvertedRight.value = false;
        sliceIndexRight.value = 12;
        selectedImageIndexRight.value = 0;
    }
};

const getTimingBadge = (timing: string | null | undefined) => {
    switch (timing) {
        case 'baseline':
            return { label: 'Baseline (Initiale)', class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/30' };
        case 'rebaseline_6m':
            return { label: 'Rebaseline (6 mois)', class: 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-500/30' };
        default:
            return { label: 'Contrôle / Suivi', class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30' };
    }
};

// Intervalle en mois entre examens
const intervalMonths = computed(() => {
    if (!examLeft.value?.exam_date_raw || !examRight.value?.exam_date_raw) return null;
    const d1 = new Date(examLeft.value.exam_date_raw);
    const d2 = new Date(examRight.value.exam_date_raw);
    const diffTime = Math.abs(d2.getTime() - d1.getTime());
    const diffMonths = Math.round(diffTime / (1000 * 60 * 60 * 24 * 30.4375));
    return diffMonths;
});
</script>

<template>
    <Dialog :open="open" @update:open="(val: boolean) => emit('update:open', val)">
        <DialogContent
            :show-close-button="false"
            class="sm:max-w-[96vw] sm:w-[96vw] max-w-[96vw] w-[96vw] h-[92vh] max-h-[94vh] p-0 flex flex-col bg-zinc-950 text-zinc-100 border-zinc-800 rounded-2xl overflow-hidden shadow-2xl"
        >
            <!-- ACCESSIBILITY HEADERS (Reka-UI Requirements) -->
            <DialogHeader class="sr-only">
                <DialogTitle>Visionneuse & Comparateur d'IRM SEP</DialogTitle>
                <DialogDescription>
                    Visualisation et comparaison des examens d'IRM cérébrale et médullaire du patient {{ patientName }}
                </DialogDescription>
            </DialogHeader>

            <!-- TOP MEDICAL HEADER TOOLBAR -->
            <div class="min-h-14 py-2 px-3 sm:px-4 bg-zinc-900 border-b border-zinc-800 flex flex-wrap items-center justify-between shrink-0 select-none gap-2">
                <!-- Patient & Module Info -->
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="p-1.5 rounded-lg bg-primary/20 text-primary border border-primary/30 shrink-0">
                        <Crosshair class="size-4" />
                    </div>
                    <div class="min-w-0 truncate">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-white tracking-wide truncate">
                                Visionneuse IRM
                            </span>
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400 border border-zinc-700 shrink-0 hidden sm:inline-block">
                                DICOM / Coupes 3D
                            </span>
                        </div>
                        <p class="text-[11px] text-zinc-400 truncate">
                            {{ patientName }} • <span class="font-mono text-zinc-300">{{ dossierNumber }}</span>
                        </p>
                    </div>
                </div>

                <!-- Mode & Control Actions -->
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 flex-wrap">
                    <!-- Mode Switch: Single vs Compare -->
                    <div class="flex items-center bg-zinc-800 p-0.5 rounded-lg border border-zinc-700">
                        <button
                            type="button"
                            @click="viewMode = 'single'"
                            :class="[
                                'px-2.5 sm:px-3 py-1 rounded text-xs font-medium transition-colors flex items-center gap-1',
                                viewMode === 'single' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-zinc-400 hover:text-zinc-200',
                            ]"
                        >
                            <Eye class="size-3.5" />
                            <span>Unique</span>
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'compare'"
                            :disabled="exams.length < 2"
                            :class="[
                                'px-2.5 sm:px-3 py-1 rounded text-xs font-medium transition-colors flex items-center gap-1',
                                viewMode === 'compare' ? 'bg-primary text-primary-foreground shadow-xs' : 'text-zinc-400 hover:text-zinc-200 disabled:opacity-40 disabled:cursor-not-allowed',
                            ]"
                            :title="exams.length < 2 ? 'Nécessite au moins 2 examens pour comparer' : 'Comparer deux IRM côte à côte'"
                        >
                            <Columns2 class="size-3.5" />
                            <span>Comparer</span>
                        </button>
                    </div>

                    <!-- Synchronize toggle in compare mode -->
                    <Button
                        v-if="viewMode === 'compare'"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="isSyncSlices = !isSyncSlices"
                        :class="[
                            'h-8 text-xs gap-1 border px-2 sm:px-2.5',
                            isSyncSlices ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40' : 'text-zinc-400 border-zinc-800',
                        ]"
                    >
                        <ArrowLeftRight class="size-3" />
                        <span class="hidden sm:inline">Synchroniser</span>
                    </Button>

                    <!-- Reset All -->
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 text-xs text-zinc-400 hover:text-white px-2 sm:px-2.5"
                        @click="resetViewer('both')"
                    >
                        <RotateCcw class="size-3 sm:mr-1" />
                        <span class="hidden sm:inline">Réinitialiser</span>
                    </Button>

                    <!-- Dedicated Close Button -->
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-8 text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg shrink-0"
                        title="Fermer la visionneuse"
                        @click="emit('update:open', false)"
                    >
                        <X class="size-4" />
                    </Button>
                </div>
            </div>

            <!-- COMPARISON DELTA HEADER (When compare mode active) -->
            <div
                v-if="viewMode === 'compare' && examLeft && examRight && examLeft.id !== examRight.id"
                class="px-4 py-2 bg-zinc-900/60 border-b border-zinc-800 text-xs flex items-center justify-between shrink-0"
            >
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="text-zinc-400 font-medium">Bilan Comparatif :</span>
                    <span class="text-zinc-300">
                        Intervalle : <strong class="text-white">{{ intervalMonths ?? '—' }} mois</strong>
                    </span>
                    <span class="text-zinc-300">
                        Activité Gadolinium :
                        <span :class="examRight.contrast_enhancement === 'positive' ? 'text-rose-400 font-semibold' : 'text-emerald-400 font-semibold'">
                            {{ examRight.contrast_enhancement === 'positive' ? 'Active (Gado+)' : 'Quiescente' }}
                        </span>
                    </span>
                    <span v-if="examRight.new_lesions_count !== null && examRight.new_lesions_count > 0" class="text-rose-400 font-bold bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/30">
                        +{{ examRight.new_lesions_count }} nouvelle(s) lésion(s) T2
                    </span>
                    <span v-else class="text-emerald-400 font-medium bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/30">
                        Aucune nouvelle lésion décelée
                    </span>
                </div>
            </div>

            <!-- MAIN VIEWPORT GRID -->
            <div class="flex-1 grid grid-cols-1 overflow-hidden" :class="viewMode === 'compare' ? 'md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-zinc-800' : ''">
                <!-- VIEWPORT LEFT (OR SINGLE VIEW) -->
                <div class="flex flex-col h-full overflow-hidden bg-black select-none">
                    <!-- Exam Selection & Metadata Bar -->
                    <div class="p-2.5 bg-zinc-900/80 border-b border-zinc-800 flex items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center gap-2 max-w-[65%]">
                            <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider shrink-0">
                                {{ viewMode === 'compare' ? 'Examen A :' : 'Examen :' }}
                            </span>
                            <Select
                                :model-value="String(selectedExamLeftId)"
                                @update:model-value="(val: any) => (selectedExamLeftId = Number(val))"
                            >
                                <SelectTrigger class="h-8 text-xs bg-zinc-800 border-zinc-700 text-zinc-200">
                                    <SelectValue placeholder="Sélectionner un examen" />
                                </SelectTrigger>
                                <SelectContent class="bg-zinc-900 border-zinc-800 text-zinc-200">
                                    <SelectItem v-for="e in exams" :key="e.id" :value="String(e.id)">
                                        IRM du {{ e.exam_date }} ({{ e.region }}) — {{ e.timing }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="examLeft" class="flex items-center gap-1.5 shrink-0">
                            <span :class="['text-[10px] font-medium px-2 py-0.5 rounded border', getTimingBadge(examLeft.timing).class]">
                                {{ getTimingBadge(examLeft.timing).label }}
                            </span>
                            <span v-if="examLeft.sep_protocol_applied" class="text-[10px] text-emerald-400 font-medium flex items-center gap-1">
                                <Check class="size-3" /> Protocole SEP
                            </span>
                        </div>
                    </div>

                    <!-- Sequence Selectors Bar -->
                    <div class="px-3 py-1.5 bg-zinc-950 border-b border-zinc-900 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1">
                            <button
                                v-for="seq in availableSequences"
                                :key="seq"
                                type="button"
                                @click="activeSequenceLeft = seq"
                                :class="[
                                    'px-2 py-0.5 rounded text-[11px] font-medium transition-colors',
                                    activeSequenceLeft === seq ? 'bg-zinc-700 text-white font-bold' : 'text-zinc-500 hover:text-zinc-300',
                                ]"
                            >
                                {{ seq }}
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Visual pan hint when zoomed -->
                            <span
                                v-if="zoomLeft > 1"
                                class="text-[10px] text-cyan-400 bg-cyan-950/60 border border-cyan-800/60 px-2 py-0.5 rounded flex items-center gap-1 font-medium select-none"
                            >
                                <Move class="size-2.5" />
                                <span>Glisser pour naviguer</span>
                            </span>

                            <!-- Reset Zoom/Pan button -->
                            <button
                                v-if="zoomLeft !== 1.0 || panLeft.x !== 0 || panLeft.y !== 0"
                                type="button"
                                @click="resetPanZoomLeft"
                                class="text-xs text-zinc-400 hover:text-white px-1.5 py-0.5 rounded border border-zinc-800 hover:border-zinc-700 text-[10px] flex items-center gap-1 transition-colors"
                                title="Réinitialiser zoom et centrage (ou double-clic sur l'image)"
                            >
                                <RotateCcw class="size-2.5" />
                                <span>100%</span>
                            </button>

                            <!-- Zoom in / out -->
                            <button
                                @click="zoomLeft = Math.max(0.6, Math.round((zoomLeft - 0.15) * 100) / 100); if (zoomLeft <= 1) panLeft = { x: 0, y: 0 };"
                                class="text-zinc-400 hover:text-white p-1"
                                title="Dézoomer"
                            >
                                <ZoomOut class="size-3.5" />
                            </button>
                            <span class="text-[10px] font-mono text-zinc-400">{{ Math.round(zoomLeft * 100) }}%</span>
                            <button
                                @click="zoomLeft = Math.min(3.5, Math.round((zoomLeft + 0.15) * 100) / 100)"
                                class="text-zinc-400 hover:text-white p-1"
                                title="Zoomer"
                            >
                                <ZoomIn class="size-3.5" />
                            </button>
                            <!-- Invert -->
                            <button
                                @click="isInvertedLeft = !isInvertedLeft"
                                :class="['text-xs px-1.5 py-0.5 rounded border text-[10px]', isInvertedLeft ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-400 border-zinc-800 hover:text-white']"
                                title="Inverser les niveaux de gris"
                            >
                                Inverser
                            </button>
                        </div>
                    </div>

                    <!-- Canvas Medical Viewer Body -->
                    <div class="flex-1 relative overflow-hidden flex flex-col items-center justify-center p-2">
                        <!-- If Exam has actual uploaded images -->
                        <div
                            v-if="examLeft?.key_images && examLeft.key_images.length > 0"
                            class="relative w-full h-full flex flex-col items-center justify-center overflow-hidden"
                        >
                            <!-- Main Displayed Image with Zoom & Invert Filters & Pan Dragging -->
                            <div
                                class="flex-1 w-full flex items-center justify-center p-2 overflow-hidden select-none"
                                :class="[
                                    zoomLeft > 1 ? (isPanningLeft ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default'
                                ]"
                                @mousedown="handleMouseDownLeft"
                                @mousemove="handleMouseMoveLeft"
                                @mouseup="handleMouseUpLeft"
                                @mouseleave="handleMouseUpLeft"
                                @wheel="handleWheelLeft"
                                @touchstart.passive="handleTouchStartLeft"
                                @touchmove.passive="handleTouchMoveLeft"
                                @touchend="handleMouseUpLeft"
                                @dblclick="resetPanZoomLeft"
                            >
                                <img
                                    :src="examLeft.key_images[selectedImageIndexLeft]?.path"
                                    :alt="examLeft.key_images[selectedImageIndexLeft]?.name || 'Coupe IRM'"
                                    class="max-h-[58vh] max-w-full object-contain rounded-lg border border-zinc-800 shadow-2xl transition-transform duration-75 select-none pointer-events-none"
                                    draggable="false"
                                    :style="{
                                        transform: `translate(${panLeft.x}px, ${panLeft.y}px) scale(${zoomLeft})`,
                                        filter: `brightness(${brightnessLeft}%) contrast(${contrastLeft}%) ${isInvertedLeft ? 'invert(1)' : ''}`,
                                    }"
                                />
                            </div>

                            <!-- Image Switching Controls & Filmstrip Gallery -->
                            <div class="w-full bg-zinc-900/90 backdrop-blur-sm border-t border-zinc-800 p-2 shrink-0 space-y-2">
                                <!-- Navigation Arrows & Slice Info & Delete button -->
                                <div class="flex items-center justify-between text-xs px-2 gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-6 text-zinc-300 hover:text-white hover:bg-zinc-800 disabled:opacity-30 shrink-0"
                                            :disabled="selectedImageIndexLeft === 0"
                                            @click="prevImageLeft"
                                        >
                                            <ChevronLeft class="size-4" />
                                        </Button>
                                        <span class="font-mono text-zinc-200 font-semibold text-[11px] shrink-0">
                                            Coupe {{ selectedImageIndexLeft + 1 }} / {{ examLeft.key_images.length }} :
                                        </span>
                                        <span class="text-zinc-400 text-[11px] truncate max-w-[160px] sm:max-w-[200px]" :title="examLeft.key_images[selectedImageIndexLeft]?.name">
                                            {{ examLeft.key_images[selectedImageIndexLeft]?.name }}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-6 text-zinc-300 hover:text-white hover:bg-zinc-800 disabled:opacity-30 shrink-0"
                                            :disabled="selectedImageIndexLeft === examLeft.key_images.length - 1"
                                            @click="nextImageLeft"
                                        >
                                            <ChevronRight class="size-4" />
                                        </Button>

                                        <!-- Supprimer cette coupe directement de la visionneuse -->
                                        <Button
                                            v-if="props.patientId"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 px-2 text-[10px] text-zinc-400 hover:text-rose-400 hover:bg-rose-500/10 gap-1 ml-2 transition-colors shrink-0"
                                            title="Supprimer définitivement cette coupe de l'examen"
                                            :disabled="isDeletingSlice"
                                            @click="deleteCurrentSliceLeft"
                                        >
                                            <Trash2 class="size-3 text-rose-400" />
                                            <span class="hidden sm:inline">Supprimer la coupe</span>
                                        </Button>
                                    </div>

                                    <!-- Range Slider to navigate through images -->
                                    <div class="flex items-center gap-2 w-36 sm:w-48 shrink-0">
                                        <input
                                            type="range"
                                            min="0"
                                            :max="examLeft.key_images.length - 1"
                                            v-model.number="selectedImageIndexLeft"
                                            class="w-full accent-primary h-1.5 bg-zinc-800 rounded-lg cursor-pointer"
                                        />
                                    </div>
                                </div>

                                <!-- Thumbnails Filmstrip (if multiple images) -->
                                <div v-if="examLeft.key_images.length > 1" class="flex items-center gap-2 overflow-x-auto py-1 px-1">
                                    <button
                                        v-for="(img, idx) in examLeft.key_images"
                                        :key="idx"
                                        type="button"
                                        @click="selectedImageIndexLeft = idx"
                                        :class="[
                                            'relative shrink-0 rounded-md overflow-hidden border-2 transition-all p-0.5 bg-zinc-950',
                                            selectedImageIndexLeft === idx ? 'border-primary ring-2 ring-primary/40' : 'border-zinc-800 opacity-60 hover:opacity-100',
                                        ]"
                                    >
                                        <img :src="img.path" :alt="img.name" class="w-14 h-14 object-cover rounded" />
                                        <span class="absolute bottom-0 right-0 bg-black/80 text-[9px] font-mono px-1 rounded-tl text-white">
                                            #{{ idx + 1 }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Else High Resolution Anatomical Medical MRI Simulation (When no uploaded images yet) -->
                        <div
                            v-else
                            class="relative w-full h-full flex flex-col items-center justify-center p-4 overflow-hidden select-none"
                            :class="[
                                zoomLeft > 1 ? (isPanningLeft ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default'
                            ]"
                            @mousedown="handleMouseDownLeft"
                            @mousemove="handleMouseMoveLeft"
                            @mouseup="handleMouseUpLeft"
                            @mouseleave="handleMouseUpLeft"
                            @wheel="handleWheelLeft"
                            @touchstart.passive="handleTouchStartLeft"
                            @touchmove.passive="handleTouchMoveLeft"
                            @touchend="handleMouseUpLeft"
                            @dblclick="resetPanZoomLeft"
                        >
                            <div
                                class="w-80 h-96 bg-zinc-950 border border-zinc-800 rounded-xl flex flex-col items-center justify-center p-4 relative shadow-2xl overflow-hidden transition-transform duration-75 pointer-events-none"
                                :style="{
                                    transform: `translate(${panLeft.x}px, ${panLeft.y}px) scale(${zoomLeft})`,
                                    filter: `brightness(${brightnessLeft}%) contrast(${contrastLeft}%) ${isInvertedLeft ? 'invert(1)' : ''}`,
                                }"
                            >
                                <svg viewBox="0 0 300 360" class="w-full h-full">
                                    <defs>
                                        <radialGradient id="brainGrad" cx="50%" cy="48%" r="45%">
                                            <stop offset="0%" stop-color="#27272a" />
                                            <stop offset="70%" stop-color="#18181b" />
                                            <stop offset="95%" stop-color="#09090b" />
                                            <stop offset="100%" stop-color="#000000" />
                                        </radialGradient>
                                        <filter id="glow">
                                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                                            <feMerge>
                                                <feMergeNode in="coloredBlur"/>
                                                <feMergeNode in="SourceGraphic"/>
                                            </feMerge>
                                        </filter>
                                    </defs>

                                    <!-- Skull & Brain Contour -->
                                    <ellipse cx="150" cy="175" rx="115" ry="140" fill="url(#brainGrad)" stroke="#3f3f46" stroke-width="3" />
                                    <ellipse cx="150" cy="175" rx="105" ry="130" fill="none" stroke="#27272a" stroke-width="1.5" />

                                    <!-- Brain Ventricles -->
                                    <path
                                        d="M 145 130 C 135 150, 130 170, 142 195 C 138 215, 132 230, 145 240 C 147 240, 148 200, 146 175 Z"
                                        fill="#09090b"
                                        stroke="#52525b"
                                        stroke-width="1"
                                    />
                                    <path
                                        d="M 155 130 C 165 150, 170 170, 158 195 C 162 215, 168 230, 155 240 C 153 240, 152 200, 154 175 Z"
                                        fill="#09090b"
                                        stroke="#52525b"
                                        stroke-width="1"
                                    />

                                    <!-- Brain Sulci & Gyri -->
                                    <path d="M 65 160 Q 100 165 130 160" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />
                                    <path d="M 170 160 Q 200 165 235 160" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />
                                    <path d="M 75 210 Q 110 215 135 210" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />
                                    <path d="M 165 210 Q 190 215 225 210" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />

                                    <!-- Multiple Sclerosis Demyelinating Lesions -->
                                    <ellipse cx="120" cy="165" rx="9" ry="5" fill="#f4f4f5" filter="url(#glow)" opacity="0.9" />
                                    <ellipse cx="178" cy="180" rx="8" ry="6" fill="#f4f4f5" filter="url(#glow)" opacity="0.85" />
                                    <circle cx="85" cy="140" r="5" fill="#e4e4e7" filter="url(#glow)" opacity="0.9" />
                                    <ellipse cx="150" cy="120" rx="7" ry="4" fill="#fafafa" filter="url(#glow)" opacity="0.95" />

                                    <!-- Rehaussement Gadolinium si actif -->
                                    <circle
                                        v-if="examLeft?.contrast_enhancement === 'positive'"
                                        cx="120"
                                        cy="165"
                                        r="3"
                                        fill="#f43f5e"
                                        filter="url(#glow)"
                                    />

                                    <!-- HUD -->
                                    <text x="25" y="40" class="fill-zinc-400 font-mono text-[10px] tracking-widest">
                                        {{ activeSequenceLeft }} • COUPE {{ sliceIndexLeft }}/{{ totalSimulatedSlices }}
                                    </text>
                                    <text x="25" y="55" class="fill-zinc-500 font-mono text-[9px]">
                                        THK 1.0mm • TE 90 • TR 8000
                                    </text>
                                    <text x="275" y="40" text-anchor="end" class="fill-zinc-400 font-mono text-[10px]">
                                        R: {{ examLeft?.region ?? 'Cérébrale' }}
                                    </text>
                                    <text x="150" y="340" text-anchor="middle" class="fill-zinc-500 font-mono text-[10px] uppercase">
                                        Service Neurologie — GST TTA
                                    </text>
                                </svg>
                            </div>

                            <!-- Simulated Slice Navigation Slider -->
                            <div class="absolute bottom-3 left-4 right-4 bg-zinc-900/90 backdrop-blur-sm px-4 py-2 rounded-xl border border-zinc-800 flex items-center gap-3">
                                <span class="text-[10px] font-mono text-zinc-400 shrink-0">Coupe {{ sliceIndexLeft }}/{{ totalSimulatedSlices }}</span>
                                <input
                                    type="range"
                                    min="1"
                                    :max="totalSimulatedSlices"
                                    v-model.number="sliceIndexLeft"
                                    class="w-full accent-primary h-1.5 bg-zinc-800 rounded-lg cursor-pointer"
                                />
                                <span class="text-[10px] font-mono text-zinc-500 shrink-0">{{ (sliceIndexLeft * 1.5).toFixed(1) }} mm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Radiologist Summary Box -->
                    <div class="p-3 bg-zinc-950 border-t border-zinc-900 text-xs shrink-0 space-y-1">
                        <div class="flex items-center justify-between text-zinc-400 text-[11px]">
                            <span>Charge lésionnelle : <strong class="text-white">{{ examLeft?.lesions_count_estimated || 'N/P' }}</strong></span>
                            <span>Prise de Gado : <strong :class="examLeft?.contrast_enhancement === 'positive' ? 'text-rose-400' : 'text-emerald-400'">{{ examLeft?.contrast_enhancement === 'positive' ? 'Positive (Active)' : (examLeft?.contrast_enhancement === 'negative' ? 'Absente' : 'Non injecté') }}</strong></span>
                        </div>
                        <p v-if="examLeft?.radiologist_report" class="text-zinc-300 text-[11px] italic truncate">
                            "{{ examLeft.radiologist_report }}"
                        </p>
                    </div>
                </div>

                <!-- VIEWPORT RIGHT (COMPARISON VIEW ONLY) -->
                <div v-if="viewMode === 'compare'" class="flex flex-col h-full overflow-hidden bg-black select-none">
                    <!-- Exam Selection & Metadata Bar -->
                    <div class="p-2.5 bg-zinc-900/80 border-b border-zinc-800 flex items-center justify-between gap-2 shrink-0">
                        <div class="flex items-center gap-2 max-w-[65%]">
                            <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider shrink-0">
                                Examen B :
                            </span>
                            <Select
                                :model-value="String(selectedExamRightId)"
                                @update:model-value="(val: any) => (selectedExamRightId = Number(val))"
                            >
                                <SelectTrigger class="h-8 text-xs bg-zinc-800 border-zinc-700 text-zinc-200">
                                    <SelectValue placeholder="Sélectionner l'examen de comparaison" />
                                </SelectTrigger>
                                <SelectContent class="bg-zinc-900 border-zinc-800 text-zinc-200">
                                    <SelectItem v-for="e in exams" :key="e.id" :value="String(e.id)">
                                        IRM du {{ e.exam_date }} ({{ e.region }}) — {{ e.timing }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="examRight" class="flex items-center gap-1.5 shrink-0">
                            <span :class="['text-[10px] font-medium px-2 py-0.5 rounded border', getTimingBadge(examRight.timing).class]">
                                {{ getTimingBadge(examRight.timing).label }}
                            </span>
                            <span v-if="examRight.sep_protocol_applied" class="text-[10px] text-emerald-400 font-medium flex items-center gap-1">
                                <Check class="size-3" /> Protocole SEP
                            </span>
                        </div>
                    </div>

                    <!-- Sequence Selectors Bar -->
                    <div class="px-3 py-1.5 bg-zinc-950 border-b border-zinc-900 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1">
                            <button
                                v-for="seq in availableSequences"
                                :key="seq"
                                type="button"
                                @click="activeSequenceRight = seq"
                                :class="[
                                    'px-2 py-0.5 rounded text-[11px] font-medium transition-colors',
                                    activeSequenceRight === seq ? 'bg-zinc-700 text-white font-bold' : 'text-zinc-500 hover:text-zinc-300',
                                ]"
                            >
                                {{ seq }}
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Visual pan hint when zoomed -->
                            <span
                                v-if="zoomRight > 1"
                                class="text-[10px] text-cyan-400 bg-cyan-950/60 border border-cyan-800/60 px-2 py-0.5 rounded flex items-center gap-1 font-medium select-none"
                            >
                                <Move class="size-2.5" />
                                <span>Glisser pour naviguer</span>
                            </span>

                            <!-- Reset Zoom/Pan button -->
                            <button
                                v-if="zoomRight !== 1.0 || panRight.x !== 0 || panRight.y !== 0"
                                type="button"
                                @click="resetPanZoomRight"
                                class="text-xs text-zinc-400 hover:text-white px-1.5 py-0.5 rounded border border-zinc-800 hover:border-zinc-700 text-[10px] flex items-center gap-1 transition-colors"
                                title="Réinitialiser zoom et centrage (ou double-clic sur l'image)"
                            >
                                <RotateCcw class="size-2.5" />
                                <span>100%</span>
                            </button>

                            <!-- Zoom in / out -->
                            <button
                                @click="zoomRight = Math.max(0.6, Math.round((zoomRight - 0.15) * 100) / 100); if (zoomRight <= 1) panRight = { x: 0, y: 0 };"
                                class="text-zinc-400 hover:text-white p-1"
                                title="Dézoomer"
                            >
                                <ZoomOut class="size-3.5" />
                            </button>
                            <span class="text-[10px] font-mono text-zinc-400">{{ Math.round(zoomRight * 100) }}%</span>
                            <button
                                @click="zoomRight = Math.min(3.5, Math.round((zoomRight + 0.15) * 100) / 100)"
                                class="text-zinc-400 hover:text-white p-1"
                                title="Zoomer"
                            >
                                <ZoomIn class="size-3.5" />
                            </button>
                            <!-- Invert -->
                            <button
                                @click="isInvertedRight = !isInvertedRight"
                                :class="['text-xs px-1.5 py-0.5 rounded border text-[10px]', isInvertedRight ? 'bg-zinc-200 text-zinc-900' : 'text-zinc-400 border-zinc-800 hover:text-white']"
                                title="Inverser les niveaux de gris"
                            >
                                Inverser
                            </button>
                        </div>
                    </div>

                    <!-- Canvas Medical Viewer Body -->
                    <div class="flex-1 relative overflow-hidden flex flex-col items-center justify-center p-2">
                        <!-- If Exam Right has actual uploaded images -->
                        <div
                            v-if="examRight?.key_images && examRight.key_images.length > 0"
                            class="relative w-full h-full flex flex-col items-center justify-center overflow-hidden"
                        >
                            <!-- Main Displayed Image with Zoom & Invert Filters & Pan Dragging -->
                            <div
                                class="flex-1 w-full flex items-center justify-center p-2 overflow-hidden select-none"
                                :class="[
                                    zoomRight > 1 ? (isPanningRight ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default'
                                ]"
                                @mousedown="handleMouseDownRight"
                                @mousemove="handleMouseMoveRight"
                                @mouseup="handleMouseUpRight"
                                @mouseleave="handleMouseUpRight"
                                @wheel="handleWheelRight"
                                @touchstart.passive="handleTouchStartRight"
                                @touchmove.passive="handleTouchMoveRight"
                                @touchend="handleMouseUpRight"
                                @dblclick="resetPanZoomRight"
                            >
                                <img
                                    :src="examRight.key_images[selectedImageIndexRight]?.path"
                                    :alt="examRight.key_images[selectedImageIndexRight]?.name || 'Coupe IRM'"
                                    class="max-h-[58vh] max-w-full object-contain rounded-lg border border-zinc-800 shadow-2xl transition-transform duration-75 select-none pointer-events-none"
                                    draggable="false"
                                    :style="{
                                        transform: `translate(${panRight.x}px, ${panRight.y}px) scale(${zoomRight})`,
                                        filter: `brightness(${brightnessRight}%) contrast(${contrastRight}%) ${isInvertedRight ? 'invert(1)' : ''}`,
                                    }"
                                />
                            </div>

                            <!-- Image Switching Controls & Filmstrip Gallery -->
                            <div class="w-full bg-zinc-900/90 backdrop-blur-sm border-t border-zinc-800 p-2 shrink-0 space-y-2">
                                <div class="flex items-center justify-between text-xs px-2 gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-6 text-zinc-300 hover:text-white hover:bg-zinc-800 disabled:opacity-30 shrink-0"
                                            :disabled="selectedImageIndexRight === 0"
                                            @click="prevImageRight"
                                        >
                                            <ChevronLeft class="size-4" />
                                        </Button>
                                        <span class="font-mono text-zinc-200 font-semibold text-[11px] shrink-0">
                                            Coupe {{ selectedImageIndexRight + 1 }} / {{ examRight.key_images.length }} :
                                        </span>
                                        <span class="text-zinc-400 text-[11px] truncate max-w-[160px] sm:max-w-[200px]" :title="examRight.key_images[selectedImageIndexRight]?.name">
                                            {{ examRight.key_images[selectedImageIndexRight]?.name }}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-6 text-zinc-300 hover:text-white hover:bg-zinc-800 disabled:opacity-30 shrink-0"
                                            :disabled="selectedImageIndexRight === examRight.key_images.length - 1"
                                            @click="nextImageRight"
                                        >
                                            <ChevronRight class="size-4" />
                                        </Button>

                                        <!-- Supprimer cette coupe directement de la visionneuse -->
                                        <Button
                                            v-if="props.patientId"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 px-2 text-[10px] text-zinc-400 hover:text-rose-400 hover:bg-rose-500/10 gap-1 ml-2 transition-colors shrink-0"
                                            title="Supprimer définitivement cette coupe de l'examen"
                                            :disabled="isDeletingSlice"
                                            @click="deleteCurrentSliceRight"
                                        >
                                            <Trash2 class="size-3 text-rose-400" />
                                            <span class="hidden sm:inline">Supprimer la coupe</span>
                                        </Button>
                                    </div>

                                    <div class="flex items-center gap-2 w-36 sm:w-48 shrink-0">
                                        <input
                                            type="range"
                                            min="0"
                                            :max="examRight.key_images.length - 1"
                                            v-model.number="selectedImageIndexRight"
                                            class="w-full accent-primary h-1.5 bg-zinc-800 rounded-lg cursor-pointer"
                                        />
                                    </div>
                                </div>

                                <div v-if="examRight.key_images.length > 1" class="flex items-center gap-2 overflow-x-auto py-1 px-1">
                                    <button
                                        v-for="(img, idx) in examRight.key_images"
                                        :key="idx"
                                        type="button"
                                        @click="selectedImageIndexRight = idx"
                                        :class="[
                                            'relative shrink-0 rounded-md overflow-hidden border-2 transition-all p-0.5 bg-zinc-950',
                                            selectedImageIndexRight === idx ? 'border-primary ring-2 ring-primary/40' : 'border-zinc-800 opacity-60 hover:opacity-100',
                                        ]"
                                    >
                                        <img :src="img.path" :alt="img.name" class="w-14 h-14 object-cover rounded" />
                                        <span class="absolute bottom-0 right-0 bg-black/80 text-[9px] font-mono px-1 rounded-tl text-white">
                                            #{{ idx + 1 }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Else Simulated anatomical slice for exam right -->
                        <div
                            v-else
                            class="relative w-full h-full flex flex-col items-center justify-center p-4 overflow-hidden select-none"
                            :class="[
                                zoomRight > 1 ? (isPanningRight ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default'
                            ]"
                            @mousedown="handleMouseDownRight"
                            @mousemove="handleMouseMoveRight"
                            @mouseup="handleMouseUpRight"
                            @mouseleave="handleMouseUpRight"
                            @wheel="handleWheelRight"
                            @touchstart.passive="handleTouchStartRight"
                            @touchmove.passive="handleTouchMoveRight"
                            @touchend="handleMouseUpRight"
                            @dblclick="resetPanZoomRight"
                        >
                            <div
                                class="w-80 h-96 bg-zinc-950 border border-zinc-800 rounded-xl flex flex-col items-center justify-center p-4 relative shadow-2xl overflow-hidden transition-transform duration-75 pointer-events-none"
                                :style="{
                                    transform: `translate(${panRight.x}px, ${panRight.y}px) scale(${zoomRight})`,
                                    filter: `brightness(${brightnessRight}%) contrast(${contrastRight}%) ${isInvertedRight ? 'invert(1)' : ''}`,
                                }"
                            >
                                <svg viewBox="0 0 300 360" class="w-full h-full">
                                    <defs>
                                        <radialGradient id="brainGrad2" cx="50%" cy="48%" r="45%">
                                            <stop offset="0%" stop-color="#27272a" />
                                            <stop offset="70%" stop-color="#18181b" />
                                            <stop offset="95%" stop-color="#09090b" />
                                            <stop offset="100%" stop-color="#000000" />
                                        </radialGradient>
                                        <filter id="glow2">
                                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                                            <feMerge>
                                                <feMergeNode in="coloredBlur"/>
                                                <feMergeNode in="SourceGraphic"/>
                                            </feMerge>
                                        </filter>
                                    </defs>

                                    <!-- Skull & Brain Contour -->
                                    <ellipse cx="150" cy="175" rx="115" ry="140" fill="url(#brainGrad2)" stroke="#3f3f46" stroke-width="3" />
                                    <ellipse cx="150" cy="175" rx="105" ry="130" fill="none" stroke="#27272a" stroke-width="1.5" />

                                    <!-- Brain Ventricles -->
                                    <path
                                        d="M 145 130 C 135 150, 130 170, 142 195 C 138 215, 132 230, 145 240 C 147 240, 148 200, 146 175 Z"
                                        fill="#09090b"
                                        stroke="#52525b"
                                        stroke-width="1"
                                    />
                                    <path
                                        d="M 155 130 C 165 150, 170 170, 158 195 C 162 215, 168 230, 155 240 C 153 240, 152 200, 154 175 Z"
                                        fill="#09090b"
                                        stroke="#52525b"
                                        stroke-width="1"
                                    />

                                    <!-- Sulci -->
                                    <path d="M 65 160 Q 100 165 130 160" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />
                                    <path d="M 170 160 Q 200 165 235 160" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />
                                    <path d="M 75 210 Q 110 215 135 210" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />
                                    <path d="M 165 210 Q 190 215 225 210" stroke="#3f3f46" stroke-width="1.5" fill="none" opacity="0.6" />

                                    <!-- Lesions -->
                                    <ellipse cx="120" cy="165" rx="9" ry="5" fill="#f4f4f5" filter="url(#glow2)" opacity="0.9" />
                                    <ellipse cx="178" cy="180" rx="8" ry="6" fill="#f4f4f5" filter="url(#glow2)" opacity="0.85" />
                                    <circle cx="85" cy="140" r="5" fill="#e4e4e7" filter="url(#glow2)" opacity="0.9" />
                                    <ellipse cx="150" cy="120" rx="7" ry="4" fill="#fafafa" filter="url(#glow2)" opacity="0.95" />

                                    <!-- New Lesion if present in exam Right -->
                                    <circle
                                        v-if="examRight?.new_lesions_count && examRight.new_lesions_count > 0"
                                        cx="210"
                                        cy="145"
                                        r="6"
                                        fill="#38bdf8"
                                        filter="url(#glow2)"
                                    />
                                    <circle
                                        v-if="examRight?.contrast_enhancement === 'positive'"
                                        cx="210"
                                        cy="145"
                                        r="3.5"
                                        fill="#f43f5e"
                                        filter="url(#glow2)"
                                    />

                                    <!-- HUD -->
                                    <text x="25" y="40" class="fill-zinc-400 font-mono text-[10px] tracking-widest">
                                        {{ activeSequenceRight }} • COUPE {{ sliceIndexRight }}/{{ totalSimulatedSlices }}
                                    </text>
                                    <text x="25" y="55" class="fill-zinc-500 font-mono text-[9px]">
                                        THK 1.0mm • TE 90 • TR 8000
                                    </text>
                                    <text x="275" y="40" text-anchor="end" class="fill-zinc-400 font-mono text-[10px]">
                                        R: {{ examRight?.region ?? 'Cérébrale' }}
                                    </text>
                                    <text x="150" y="340" text-anchor="middle" class="fill-zinc-500 font-mono text-[10px] uppercase">
                                        Service Neurologie — GST TTA
                                    </text>
                                </svg>
                            </div>

                            <!-- Slice Navigation Slider -->
                            <div class="absolute bottom-3 left-4 right-4 bg-zinc-900/90 backdrop-blur-sm px-4 py-2 rounded-xl border border-zinc-800 flex items-center gap-3">
                                <span class="text-[10px] font-mono text-zinc-400 shrink-0">Coupe {{ sliceIndexRight }}/{{ totalSimulatedSlices }}</span>
                                <input
                                    type="range"
                                    min="1"
                                    :max="totalSimulatedSlices"
                                    v-model.number="sliceIndexRight"
                                    class="w-full accent-primary h-1.5 bg-zinc-800 rounded-lg cursor-pointer"
                                />
                                <span class="text-[10px] font-mono text-zinc-500 shrink-0">{{ (sliceIndexRight * 1.5).toFixed(1) }} mm</span>
                            </div>
                        </div>
                    </div>

                    <!-- Radiologist Summary Box -->
                    <div class="p-3 bg-zinc-950 border-t border-zinc-900 text-xs shrink-0 space-y-1">
                        <div class="flex items-center justify-between text-zinc-400 text-[11px]">
                            <span>Charge lésionnelle : <strong class="text-white">{{ examRight?.lesions_count_estimated || 'N/P' }}</strong></span>
                            <span>Prise de Gado : <strong :class="examRight?.contrast_enhancement === 'positive' ? 'text-rose-400' : 'text-emerald-400'">{{ examRight?.contrast_enhancement === 'positive' ? 'Positive (Active)' : (examRight?.contrast_enhancement === 'negative' ? 'Absente' : 'Non injecté') }}</strong></span>
                        </div>
                        <p v-if="examRight?.radiologist_report" class="text-zinc-300 text-[11px] italic truncate">
                            "{{ examRight.radiologist_report }}"
                        </p>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
