<script setup lang="ts">
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    Brain,
    Check,
    CheckCircle2,
    Crosshair,
    Dna,
    Eye,
    FileText,
    FlaskConical,
    HeartPulse,
    Info,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    User,
    X,
} from '@lucide/vue';
import { computed, reactive } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type PatientProps = {
    age: number | null;
    gender: string;
    first_symptom_age?: number | null;
    initial_edss?: number | string | null;
    boc_positive?: boolean;
    kappa_high?: boolean;
    relapses_count: number;
    first_relapse_sequelae?: boolean;
    short_relapse_interval?: boolean;
    mri_high_lesion_load?: boolean;
    mri_infratentorial?: boolean;
    mri_spinal?: boolean;
    mri_gadolinium?: boolean;
};

const props = defineProps<{
    patientData: PatientProps;
}>();

// 5-Axis interactive assessment factors with auto-initialization
const factors = reactive({
    // Axe 1: Démographique
    age_over_40: (props.patientData.first_symptom_age ?? props.patientData.age ?? 0) >= 40,
    male_gender: props.patientData.gender === 'male',

    // Axe 2: Clinique
    polysymptomatic_onset: false,
    motor_involvement: false,
    cerebellar_involvement: false,
    sphincteric_involvement: false,
    high_initial_edss: Number(props.patientData.initial_edss || 0) >= 3.0,
    incomplete_first_recovery: !!props.patientData.first_relapse_sequelae,
    short_inter_relapse_interval: !!props.patientData.short_relapse_interval,

    // Axe 3: IRM Initiale
    high_t2_lesions: !!props.patientData.mri_high_lesion_load,
    infratentorial_lesions: !!props.patientData.mri_infratentorial,
    spinal_cord_lesions: !!props.patientData.mri_spinal,
    gadolinium_enhancement: !!props.patientData.mri_gadolinium,

    // Axe 4: Biomarqueurs
    boc_positive: !!props.patientData.boc_positive,
    kappa_index_high: !!props.patientData.kappa_high,
    nfl_elevated: false,

    // Axe 5: Paraclinique
    pev_abnormal: false,
    oct_rnfl_thinning: false,
});

// Scoring logic
const axisScores = computed(() => {
    let axis1 = 0;
    if (factors.age_over_40) axis1++;
    if (factors.male_gender) axis1++;

    let axis2 = 0;
    if (factors.polysymptomatic_onset) axis2++;
    if (factors.motor_involvement) axis2++;
    if (factors.cerebellar_involvement) axis2++;
    if (factors.sphincteric_involvement) axis2++;
    if (factors.high_initial_edss) axis2++;
    if (factors.incomplete_first_recovery) axis2++;
    if (factors.short_inter_relapse_interval) axis2++;

    let axis3 = 0;
    if (factors.high_t2_lesions) axis3++;
    if (factors.infratentorial_lesions) axis3++;
    if (factors.spinal_cord_lesions) axis3++;
    if (factors.gadolinium_enhancement) axis3++;

    let axis4 = 0;
    if (factors.boc_positive) axis4++;
    if (factors.kappa_index_high) axis4++;
    if (factors.nfl_elevated) axis4++;

    let axis5 = 0;
    if (factors.pev_abnormal) axis5++;
    if (factors.oct_rnfl_thinning) axis5++;

    const total = axis1 + axis2 + axis3 + axis4 + axis5;

    let riskLevel = 'Faible';
    let riskBadgeClass = 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30';
    let recommendation = 'Évolution standard généralement favorable. Traitement de fond de première ligne ou surveillance adaptée.';

    if (total >= 6 || (axis3 >= 3 && axis2 >= 2)) {
        riskLevel = 'Élevé (Haut Risque Évolutif)';
        riskBadgeClass = 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30 font-bold';
        recommendation = 'Présence de multiples facteurs péjoratifs. Indication précoce d\'un traitement de haute efficacité d\'emblée (Hit Hard and Early).';
    } else if (total >= 3) {
        riskLevel = 'Modéré (Vigilance Thérapeutique)';
        riskBadgeClass = 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30 font-semibold';
        recommendation = 'Surveillance clinique et IRM semestrielle stricte. Escalade rapide en cas de reprise d\'activité clinique ou radiologique.';
    }

    return {
        axis1,
        axis2,
        axis3,
        axis4,
        axis5,
        total,
        riskLevel,
        riskBadgeClass,
        recommendation,
    };
});
</script>

<template>
    <div class="space-y-6">
        <!-- Top Assessment Summary Card -->
        <Card class="border-border">
            <CardHeader class="pb-3">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <ShieldAlert class="size-4" />
                            </div>
                            <CardTitle class="text-base font-bold text-foreground">
                                Module 8 : Fiche d'Évaluation des Facteurs de Mauvais Pronostic (5 Axes)
                            </CardTitle>
                        </div>
                        <CardDescription class="text-xs mt-1">
                            Analyse pronostique multidimensionnelle basée sur le consensus international pour guider le choix thérapeutique précoce.
                        </CardDescription>
                    </div>

                    <!-- Risk Level Badge -->
                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <Badge :class="['text-xs font-semibold px-3 py-1 border', axisScores.riskBadgeClass]">
                            Risque Pronostique : {{ axisScores.riskLevel }} ({{ axisScores.total }} critère{{ axisScores.total > 1 ? 's' : '' }})
                        </Badge>
                    </div>
                </div>
            </CardHeader>

            <CardContent class="pt-0">
                <div class="p-3.5 rounded-xl bg-primary/5 border border-primary/20 flex items-start gap-3">
                    <Sparkles class="size-4 text-primary shrink-0 mt-0.5" />
                    <div class="space-y-0.5 text-xs">
                        <span class="font-bold text-foreground">Orientation Thérapeutique Recommandée :</span>
                        <p class="text-muted-foreground">{{ axisScores.recommendation }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- 5 Axes Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- AXE 1: Profil Démographique -->
            <Card class="border-border">
                <CardHeader class="pb-2.5">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xs font-semibold flex items-center gap-1.5 uppercase tracking-wider text-foreground">
                            <User class="size-3.5 text-primary" />
                            <span>1. Profil Démographique</span>
                        </CardTitle>
                        <Badge variant="outline" class="font-mono text-[10px]">{{ axisScores.axis1 }} / 2</Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2.5 pt-0 text-xs">
                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.age_over_40 = !factors.age_over_40"
                    >
                        <Checkbox id="f_age" :checked="factors.age_over_40" @update:checked="(val: boolean) => (factors.age_over_40 = val)" />
                        <Label for="f_age" class="cursor-pointer font-medium flex-1">
                            Âge au début ≥ 40 ans (Début tardif)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.male_gender = !factors.male_gender"
                    >
                        <Checkbox id="f_gender" :checked="factors.male_gender" @update:checked="(val: boolean) => (factors.male_gender = val)" />
                        <Label for="f_gender" class="cursor-pointer font-medium flex-1">
                            Sexe masculin (Progression plus rapide)
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <!-- AXE 2: Présentation Clinique Inaugurale -->
            <Card class="border-border md:col-span-2 lg:col-span-2">
                <CardHeader class="pb-2.5">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xs font-semibold flex items-center gap-1.5 uppercase tracking-wider text-foreground">
                            <HeartPulse class="size-3.5 text-primary" />
                            <span>2. Présentation Clinique Inaugurale & Évolutive</span>
                        </CardTitle>
                        <Badge variant="outline" class="font-mono text-[10px]">{{ axisScores.axis2 }} / 7</Badge>
                    </div>
                </CardHeader>
                <CardContent class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-0">
                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.polysymptomatic_onset = !factors.polysymptomatic_onset"
                    >
                        <Checkbox id="f_poly" :checked="factors.polysymptomatic_onset" @update:checked="(val: boolean) => (factors.polysymptomatic_onset = val)" />
                        <Label for="f_poly" class="cursor-pointer font-medium flex-1">
                            Début polysymptomatique / multifocal
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.motor_involvement = !factors.motor_involvement"
                    >
                        <Checkbox id="f_motor" :checked="factors.motor_involvement" @update:checked="(val: boolean) => (factors.motor_involvement = val)" />
                        <Label for="f_motor" class="cursor-pointer font-medium flex-1">
                            Atteinte motrice / pyramidale inaugurale
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.cerebellar_involvement = !factors.cerebellar_involvement"
                    >
                        <Checkbox id="f_cereb" :checked="factors.cerebellar_involvement" @update:checked="(val: boolean) => (factors.cerebellar_involvement = val)" />
                        <Label for="f_cereb" class="cursor-pointer font-medium flex-1">
                            Atteinte cérébelleuse ou tronc cérébral
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.sphincteric_involvement = !factors.sphincteric_involvement"
                    >
                        <Checkbox id="f_sphinc" :checked="factors.sphincteric_involvement" @update:checked="(val: boolean) => (factors.sphincteric_involvement = val)" />
                        <Label for="f_sphinc" class="cursor-pointer font-medium flex-1">
                            Troubles sphinctériens précoces
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.high_initial_edss = !factors.high_initial_edss"
                    >
                        <Checkbox id="f_edss" :checked="factors.high_initial_edss" @update:checked="(val: boolean) => (factors.high_initial_edss = val)" />
                        <Label for="f_edss" class="cursor-pointer font-medium flex-1">
                            Score EDSS initial élevé (≥ 3.0)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.incomplete_first_recovery = !factors.incomplete_first_recovery"
                    >
                        <Checkbox id="f_recov" :checked="factors.incomplete_first_recovery" @update:checked="(val: boolean) => (factors.incomplete_first_recovery = val)" />
                        <Label for="f_recov" class="cursor-pointer font-medium flex-1">
                            Récupération incomplète de la 1ère poussée
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer sm:col-span-2"
                        @click="factors.short_inter_relapse_interval = !factors.short_inter_relapse_interval"
                    >
                        <Checkbox id="f_inter" :checked="factors.short_inter_relapse_interval" @update:checked="(val: boolean) => (factors.short_inter_relapse_interval = val)" />
                        <Label for="f_inter" class="cursor-pointer font-medium flex-1">
                            Intervalle court entre les deux premières poussées (< 1 an)
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <!-- AXE 3: Imagerie IRM Initiale -->
            <Card class="border-border">
                <CardHeader class="pb-2.5">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xs font-semibold flex items-center gap-1.5 uppercase tracking-wider text-foreground">
                            <Crosshair class="size-3.5 text-primary" />
                            <span>3. Imagerie IRM Initiale</span>
                        </CardTitle>
                        <Badge variant="outline" class="font-mono text-[10px]">{{ axisScores.axis3 }} / 4</Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2 text-xs pt-0">
                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.high_t2_lesions = !factors.high_t2_lesions"
                    >
                        <Checkbox id="f_t2" :checked="factors.high_t2_lesions" @update:checked="(val: boolean) => (factors.high_t2_lesions = val)" />
                        <Label for="f_t2" class="cursor-pointer font-medium flex-1">
                            Charge T2 élevée (≥ 9 lésions T2)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.infratentorial_lesions = !factors.infratentorial_lesions"
                    >
                        <Checkbox id="f_infra" :checked="factors.infratentorial_lesions" @update:checked="(val: boolean) => (factors.infratentorial_lesions = val)" />
                        <Label for="f_infra" class="cursor-pointer font-medium flex-1">
                            Lésions sous-tentorielles (Tronc / Cervelet)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.spinal_cord_lesions = !factors.spinal_cord_lesions"
                    >
                        <Checkbox id="f_spin" :checked="factors.spinal_cord_lesions" @update:checked="(val: boolean) => (factors.spinal_cord_lesions = val)" />
                        <Label for="f_spin" class="cursor-pointer font-medium flex-1">
                            Lésions médullaires (Moelle spinale)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.gadolinium_enhancement = !factors.gadolinium_enhancement"
                    >
                        <Checkbox id="f_gd" :checked="factors.gadolinium_enhancement" @update:checked="(val: boolean) => (factors.gadolinium_enhancement = val)" />
                        <Label for="f_gd" class="cursor-pointer font-medium flex-1">
                            Rehaussements multiples au Gadolinium (T1+Gd)
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <!-- AXE 4: Biomarqueurs (LCR & Biologie) -->
            <Card class="border-border">
                <CardHeader class="pb-2.5">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xs font-semibold flex items-center gap-1.5 uppercase tracking-wider text-foreground">
                            <FlaskConical class="size-3.5 text-primary" />
                            <span>4. Biomarqueurs (LCR & Biologie)</span>
                        </CardTitle>
                        <Badge variant="outline" class="font-mono text-[10px]">{{ axisScores.axis4 }} / 3</Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2 text-xs pt-0">
                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.boc_positive = !factors.boc_positive"
                    >
                        <Checkbox id="f_boc" :checked="factors.boc_positive" @update:checked="(val: boolean) => (factors.boc_positive = val)" />
                        <Label for="f_boc" class="cursor-pointer font-medium flex-1">
                            Bandes Oligoclonales (BOC) positives dans le LCR
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.kappa_index_high = !factors.kappa_index_high"
                    >
                        <Checkbox id="f_kap" :checked="factors.kappa_index_high" @update:checked="(val: boolean) => (factors.kappa_index_high = val)" />
                        <Label for="f_kap" class="cursor-pointer font-medium flex-1">
                            Index Kappa élevé (KFLC ≥ 5.8)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.nfl_elevated = !factors.nfl_elevated"
                    >
                        <Checkbox id="f_nfl" :checked="factors.nfl_elevated" @update:checked="(val: boolean) => (factors.nfl_elevated = val)" />
                        <Label for="f_nfl" class="cursor-pointer font-medium flex-1">
                            Neurofilaments (sNfL / cNfL) élevés
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <!-- AXE 5: Paraclinique & Neuro-Ophtalmologie -->
            <Card class="border-border">
                <CardHeader class="pb-2.5">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xs font-semibold flex items-center gap-1.5 uppercase tracking-wider text-foreground">
                            <Eye class="size-3.5 text-primary" />
                            <span>5. Paraclinique & Neuro-Ophtalmo</span>
                        </CardTitle>
                        <Badge variant="outline" class="font-mono text-[10px]">{{ axisScores.axis5 }} / 2</Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2 text-xs pt-0">
                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.pev_abnormal = !factors.pev_abnormal"
                    >
                        <Checkbox id="f_pev" :checked="factors.pev_abnormal" @update:checked="(val: boolean) => (factors.pev_abnormal = val)" />
                        <Label for="f_pev" class="cursor-pointer font-medium flex-1">
                            Potentiels Évoqués Visuels (PEV) altérés (onde P100 retardée)
                        </Label>
                    </div>

                    <div
                        class="flex items-center gap-2.5 p-2 rounded-lg border border-border/60 hover:bg-muted/40 cursor-pointer"
                        @click="factors.oct_rnfl_thinning = !factors.oct_rnfl_thinning"
                    >
                        <Checkbox id="f_oct" :checked="factors.oct_rnfl_thinning" @update:checked="(val: boolean) => (factors.oct_rnfl_thinning = val)" />
                        <Label for="f_oct" class="cursor-pointer font-medium flex-1">
                            OCT : Amincissement de la couche des fibres nerveuses (pRNFL < 80 µm)
                        </Label>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
