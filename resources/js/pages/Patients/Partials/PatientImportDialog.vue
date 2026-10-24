<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    FileText,
    HelpCircle,
    Loader2,
    UploadCloud,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'success'): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFileName = ref<string | null>(null);
const selectedFileSize = ref<string | null>(null);
const isDragging = ref(false);

const form = useForm({
    file: null as File | null,
});

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        setFile(target.files[0]);
    }
};

const handleDrop = (event: DragEvent) => {
    isDragging.value = false;
    if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
        setFile(event.dataTransfer.files[0]);
    }
};

const setFile = (file: File) => {
    form.file = file;
    selectedFileName.value = file.name;
    const sizeKb = (file.size / 1024).toFixed(1);
    selectedFileSize.value = `${sizeKb} Ko`;
};

const removeFile = () => {
    form.file = null;
    selectedFileName.value = null;
    selectedFileSize.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const downloadTemplate = () => {
    window.location.href = '/patients/import/template';
};

const submitImport = () => {
    if (!form.file) return;

    form.post('/patients/import', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
            removeFile();
        },
    });
};

const close = () => {
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-[calc(100vw-1.5rem)] sm:max-w-xl p-4 sm:p-6">
            <DialogHeader>
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                        <FileSpreadsheet class="size-5" />
                    </div>
                    <div>
                        <DialogTitle class="text-base font-semibold">
                            Importation de Données Patients (Excel / SPSS)
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Importez un lot de dossiers patients à partir d'un fichier tabulaire CSV ou Excel
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <!-- Template Download Card -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3.5 rounded-lg border border-border/80 bg-muted/30 gap-2.5">
                    <div class="space-y-0.5">
                        <span class="text-xs font-semibold text-foreground flex items-center gap-1.5">
                            <Download class="size-3.5 text-primary" />
                            <span>Modèle de fichier préformaté</span>
                        </span>
                        <p class="text-[11px] text-muted-foreground">
                            Téléchargez notre fichier modèle Excel/CSV avec les colonnes et exemples préremplis.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="downloadTemplate"
                        class="gap-1.5 w-full sm:w-auto shrink-0 text-xs justify-center"
                    >
                        <FileSpreadsheet class="size-3.5" />
                        <span>Télécharger Modèle</span>
                    </Button>
                </div>

                <!-- Drag & Drop File Upload Area -->
                <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    :class="[
                        'relative flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-xl transition-all cursor-pointer text-center',
                        isDragging ? 'border-primary bg-primary/5' : 'border-border/80 hover:border-primary/50 bg-card',
                        form.file ? 'border-emerald-500/50 bg-emerald-500/5' : '',
                    ]"
                    @click="fileInputRef?.click()"
                >
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".csv,.txt,.tsv"
                        class="hidden"
                        @change="handleFileSelect"
                    />

                    <div v-if="!form.file" class="space-y-2">
                        <div class="mx-auto size-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <UploadCloud class="size-5" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-foreground">
                                Cliquez pour sélectionner un fichier ou glissez-le ici
                            </p>
                            <p class="text-[11px] text-muted-foreground mt-0.5">
                                Fichiers CSV, TSV ou TXT compatibles Excel et SPSS (Max 10 Mo)
                            </p>
                        </div>
                    </div>

                    <!-- Selected File State -->
                    <div v-else class="flex items-center justify-between w-full p-2.5 rounded-lg bg-background border border-border">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <FileText class="size-4" />
                            </div>
                            <div class="text-left">
                                <span class="text-xs font-medium text-foreground block truncate max-w-[280px]">
                                    {{ selectedFileName }}
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    {{ selectedFileSize }}
                                </span>
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-7 text-muted-foreground hover:text-foreground"
                            @click.stop="removeFile"
                        >
                            <X class="size-3.5" />
                        </Button>
                    </div>
                </div>

                <!-- Error Notice -->
                <div v-if="form.errors.file" class="flex items-center gap-2 p-2.5 rounded-lg border border-destructive/30 bg-destructive/10 text-destructive text-xs">
                    <AlertCircle class="size-4 shrink-0" />
                    <span>{{ form.errors.file }}</span>
                </div>

                <!-- Instructions & Features -->
                <div class="p-3 rounded-lg border border-border/60 bg-muted/20 space-y-1.5 text-[11px] text-muted-foreground">
                    <div class="font-semibold text-foreground flex items-center gap-1.5 mb-1">
                        <HelpCircle class="size-3.5 text-primary" />
                        <span>Recommandations d'importation :</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 pl-1">
                        <li>Les séparateurs virgule <code class="font-mono text-[10px]">,</code>, point-virgule <code class="font-mono text-[10px]">;</code> ou tabulation sont détectés automatiquement.</li>
                        <li>Les dates peuvent être saisies aux formats <code class="font-mono text-[10px]">JJ/MM/AAAA</code> ou <code class="font-mono text-[10px]">AAAA-MM-JJ</code>.</li>
                        <li>Possibilité de renseigner l'âge d'apparition ou de diagnostic directement sans date exacte.</li>
                        <li>Conforme aux exigences de traçabilité CNDP (Loi 09-08) avec journal d'audit automatique.</li>
                    </ul>
                </div>
            </div>

            <DialogFooter class="flex items-center justify-between sm:justify-end gap-2 pt-2 border-t border-border/50">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="close"
                    :disabled="form.processing"
                >
                    Annuler
                </Button>

                <Button
                    type="button"
                    size="sm"
                    class="gap-1.5"
                    :disabled="!form.file || form.processing"
                    @click="submitImport"
                >
                    <Loader2 v-if="form.processing" class="size-3.5 animate-spin" />
                    <UploadCloud v-else class="size-3.5" />
                    <span>{{ form.processing ? 'Importation en cours...' : 'Lancer l\'Importation' }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
