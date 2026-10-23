<script setup lang="ts">
import {
    Activity,
    AlertCircle,
    BarChart3,
    Calendar,
    CheckCircle2,
    Clock,
    Flame,
    HeartPulse,
    Info,
    Pill,
    ShieldCheck,
    Sparkles,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Relapse = {
    id: number;
    start_date: string | null;
    start_date_raw?: string | null;
    end_date: string | null;
    end_date_raw?: string | null;
    duration_days?: number | null;
    symptoms: string;
    is_before_diagnosis: boolean;
    treatment_administered: string | null;
    administration_route: string | null;
    clinical_recovery: string | null;
    sequelae: string | null;
    hospitalization_required: boolean;
    notes: string | null;
};

type Props = {
    relapses: Relapse[];
    diagnosisDate?: string | null;
};

const props = defineProps<Props>();

// Active chart display mode: 'curve' (chronological wave) or 'bars' (annual distribution)
const activeView = ref<'curve' | 'bars'>('curve');

// Selected relapse for detailed card view
const selectedRelapseId = ref<number | null>(null);

// Chronologically sorted relapses (earliest to newest)
const sortedRelapses = computed(() => {
    return [...props.relapses].sort((a, b) => {
        const dateA = a.start_date_raw
            ? new Date(a.start_date_raw).getTime()
            : a.start_date
            ? parseFrenchDate(a.start_date)
            : 0;
        const dateB = b.start_date_raw
            ? new Date(b.start_date_raw).getTime()
            : b.start_date
            ? parseFrenchDate(b.start_date)
            : 0;
        return dateA - dateB;
    });
});

function parseFrenchDate(d: string): number {
    const parts = d.split('/');
    if (parts.length === 3) {
        return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0])).getTime();
    }
    return new Date(d).getTime() || 0;
}

// Automatically select the latest relapse by default
const activeSelectedRelapse = computed(() => {
    if (selectedRelapseId.value !== null) {
        return sortedRelapses.value.find(r => r.id === selectedRelapseId.value) || null;
    }
    return sortedRelapses.value[sortedRelapses.value.length - 1] || null;
});

// Healthcare Recovery Color Mapping
function getRecoveryStyle(recovery: string | null | undefined, hasSequelae: boolean) {
    if (hasSequelae || (recovery && recovery.toLowerCase().includes('sequell'))) {
        return {
            color: '#f43f5e', // Rose / Crimson
            stroke: 'stroke-rose-500',
            fill: 'fill-rose-500',
            bg: 'bg-rose-500/10 dark:bg-rose-950/40',
            text: 'text-rose-700 dark:text-rose-400',
            border: 'border-rose-500/30',
            label: 'Avec séquelles',
            peakY: 36, // Higher peak in chart
            severityLevel: 3,
        };
    }
    if (recovery && recovery.toLowerCase().includes('incompl')) {
        return {
            color: '#f59e0b', // Amber / Warning
            stroke: 'stroke-amber-500',
            fill: 'fill-amber-500',
            bg: 'bg-amber-500/10 dark:bg-amber-950/40',
            text: 'text-amber-700 dark:text-amber-400',
            border: 'border-amber-500/30',
            label: 'Récupération incomplète',
            peakY: 60, // Medium peak
            severityLevel: 2,
        };
    }
    // Default Complete
    return {
        color: '#10b981', // Emerald / Complete
        stroke: 'stroke-emerald-500',
        fill: 'fill-emerald-500',
        bg: 'bg-emerald-500/10 dark:bg-emerald-950/40',
        text: 'text-emerald-700 dark:text-emerald-400',
        border: 'border-emerald-500/30',
        label: 'Récupération complète',
        peakY: 82, // Standard peak
        severityLevel: 1,
    };
}

// Calculate interval in months between two dates
function calculateMonthInterval(dateStr1: string, dateStr2: string): number {
    const t1 = parseFrenchDate(dateStr1);
    const t2 = parseFrenchDate(dateStr2);
    const diffMs = Math.abs(t2 - t1);
    const months = Math.round(diffMs / (1000 * 60 * 60 * 24 * 30.4375));
    return months;
}

// Chart 1: Chronological Wave Data (SVG coordinates)
type ChartPoint = {
    id: number;
    index: number;
    x: number;
    y: number;
    baselineY: number;
    date: string;
    duration: number | null;
    recoveryStyle: ReturnType<typeof getRecoveryStyle>;
    symptoms: string;
    hasCorticoids: boolean;
    interval?: { months: number; midX: number } | null;
    raw: Relapse;
};

const chronologicalChartData = computed(() => {
    const list = sortedRelapses.value;
    if (list.length === 0) return null;

    const width = Math.max(800, list.length * 140);
    const height = 230;
    const paddingX = 80;
    const baselineY = 160;
    const availableW = width - paddingX * 2;

    const points: ChartPoint[] = list.map((r, i) => {
        const x = list.length === 1
            ? width / 2
            : paddingX + (i * (availableW / (list.length - 1)));
        
        const style = getRecoveryStyle(r.clinical_recovery, !!r.sequelae);
        const y = style.peakY;

        let intervalObj: { months: number; midX: number } | null = null;
        if (i > 0 && r.start_date && list[i - 1].start_date) {
            const months = calculateMonthInterval(list[i - 1].start_date!, r.start_date!);
            const prevX = paddingX + ((i - 1) * (availableW / (list.length - 1)));
            intervalObj = {
                months,
                midX: (prevX + x) / 2,
            };
        }

        const hasCorticoids = !!(
            r.treatment_administered &&
            (r.treatment_administered.toLowerCase().includes('solu') ||
             r.treatment_administered.toLowerCase().includes('cortic') ||
             r.treatment_administered.toLowerCase().includes('flash') ||
             r.treatment_administered.toLowerCase().includes('bolus'))
        );

        return {
            id: r.id,
            index: i + 1,
            x,
            y,
            baselineY,
            date: r.start_date || 'Date non précisée',
            duration: r.duration_days ?? null,
            recoveryStyle: style,
            symptoms: r.symptoms,
            hasCorticoids,
            interval: intervalObj,
            raw: r,
        };
    });

    // Build SVG smooth path through peaks with bezier curves
    let curvePath = '';
    let areaPath = '';

    if (points.length === 1) {
        curvePath = `M ${points[0].x - 40} ${points[0].y} Q ${points[0].x} ${points[0].y - 15} ${points[0].x + 40} ${points[0].y}`;
        areaPath = `M ${points[0].x - 40} ${baselineY} L ${points[0].x - 40} ${points[0].y} Q ${points[0].x} ${points[0].y - 15} ${points[0].x + 40} ${points[0].y} L ${points[0].x + 40} ${baselineY} Z`;
    } else {
        // Multi-point spline
        curvePath = `M ${points[0].x} ${points[0].y}`;
        for (let i = 0; i < points.length - 1; i++) {
            const current = points[i];
            const next = points[i + 1];
            const midX = (current.x + next.x) / 2;
            const dipY = baselineY - 20; // gentle valley between relapses
            curvePath += ` C ${midX} ${current.y}, ${midX} ${dipY}, ${next.x} ${next.y}`;
        }

        areaPath = `${curvePath} L ${points[points.length - 1].x} ${baselineY} L ${points[0].x} ${baselineY} Z`;
    }

    return {
        width,
        height,
        baselineY,
        points,
        curvePath,
        areaPath,
    };
});

// Chart 2: Annual Distribution Data (Histogram per calendar year with smart gap compression)
type YearBin = {
    year: number;
    count: number;
    completeCount: number;
    incompleteCount: number;
    sequelaeCount: number;
    hospitalizedCount: number;
    relapses: Relapse[];
};

type AnnualItem = {
    id: string;
    type: 'year' | 'gap';
    year?: number;
    count?: number;
    completeCount?: number;
    incompleteCount?: number;
    sequelaeCount?: number;
    hospitalizedCount?: number;
    gapYears?: number;
    fromYear?: number;
    toYear?: number;
    relapses?: Relapse[];
    x: number;
};

const annualDistributionData = computed(() => {
    const list = sortedRelapses.value;
    if (list.length === 0) {
        return { items: [], maxCount: 1, arr: '0.00', spanYears: 0 };
    }

    const binsMap = new Map<number, YearBin>();

    // Determine min and max year
    let minYear = new Date().getFullYear();
    let maxYear = new Date().getFullYear();
    let hasValidYears = false;

    list.forEach(r => {
        let yr: number | null = null;
        if (r.start_date_raw) {
            yr = new Date(r.start_date_raw).getFullYear();
        } else if (r.start_date) {
            const p = r.start_date.split('/');
            if (p.length === 3) yr = Number(p[2]);
        }

        if (yr && !isNaN(yr)) {
            if (!hasValidYears) {
                minYear = yr;
                maxYear = yr;
                hasValidYears = true;
            } else {
                minYear = Math.min(minYear, yr);
                maxYear = Math.max(maxYear, yr);
            }

            if (!binsMap.has(yr)) {
                binsMap.set(yr, {
                    year: yr,
                    count: 0,
                    completeCount: 0,
                    incompleteCount: 0,
                    sequelaeCount: 0,
                    hospitalizedCount: 0,
                    relapses: [],
                });
            }

            const bin = binsMap.get(yr)!;
            bin.count++;
            bin.relapses.push(r);
            if (r.hospitalization_required) bin.hospitalizedCount++;

            if (r.sequelae) {
                bin.sequelaeCount++;
            } else if (r.clinical_recovery && r.clinical_recovery.toLowerCase().includes('incompl')) {
                bin.incompleteCount++;
            } else {
                bin.completeCount++;
            }
        }
    });

    if (!hasValidYears) {
        return { items: [], maxCount: 1, arr: '0.00', spanYears: 0 };
    }

    const diseaseSpanYears = Math.max(1, maxYear - minYear + 1);
    const arr = (list.length / diseaseSpanYears).toFixed(2);

    const sortedActiveYears = Array.from(binsMap.keys()).sort((a, b) => a - b);
    const maxCount = Math.max(...Array.from(binsMap.values()).map(b => b.count), 1);

    type RawElement =
        | { type: 'year'; bin: YearBin }
        | { type: 'gap'; fromYear: number; toYear: number; gapYears: number };

    const rawElements: RawElement[] = [];

    // If span <= 7 years, show contiguous calendar years
    if (diseaseSpanYears <= 7) {
        for (let y = minYear; y <= maxYear; y++) {
            if (binsMap.has(y)) {
                rawElements.push({ type: 'year', bin: binsMap.get(y)! });
            } else {
                rawElements.push({
                    type: 'year',
                    bin: {
                        year: y,
                        count: 0,
                        completeCount: 0,
                        incompleteCount: 0,
                        sequelaeCount: 0,
                        hospitalizedCount: 0,
                        relapses: [],
                    },
                });
            }
        }
    } else {
        // More than 7 years span: Display active years with clean remission gaps between them
        for (let i = 0; i < sortedActiveYears.length; i++) {
            const yr = sortedActiveYears[i];
            const bin = binsMap.get(yr)!;
            rawElements.push({ type: 'year', bin });

            // If there's a subsequent active year with gap > 1 year
            if (i < sortedActiveYears.length - 1) {
                const nextYr = sortedActiveYears[i + 1];
                const gap = nextYr - yr - 1;
                if (gap > 0) {
                    rawElements.push({
                        type: 'gap',
                        fromYear: yr,
                        toYear: nextYr,
                        gapYears: gap,
                    });
                }
            }
        }
    }

    // Calculate responsive X coordinates
    const svgWidth = 720;
    const paddingX = rawElements.length <= 2 ? 160 : (rawElements.length <= 4 ? 110 : 60);
    const availableW = svgWidth - paddingX * 2;
    const len = rawElements.length;

    const items: AnnualItem[] = rawElements.map((el, idx) => {
        const x = len === 1
            ? svgWidth / 2
            : paddingX + (idx * (availableW / (len - 1)));

        if (el.type === 'year') {
            return {
                id: `year-${el.bin.year}`,
                type: 'year',
                year: el.bin.year,
                count: el.bin.count,
                completeCount: el.bin.completeCount,
                incompleteCount: el.bin.incompleteCount,
                sequelaeCount: el.bin.sequelaeCount,
                hospitalizedCount: el.bin.hospitalizedCount,
                relapses: el.bin.relapses,
                x,
            };
        } else {
            return {
                id: `gap-${el.fromYear}-${el.toYear}`,
                type: 'gap',
                fromYear: el.fromYear,
                toYear: el.toYear,
                gapYears: el.gapYears,
                x,
            };
        }
    });

    return {
        items,
        maxCount,
        arr,
        spanYears: diseaseSpanYears,
    };
});

// Healthcare Clinical Summary Indicators
const clinicalMetrics = computed(() => {
    const list = sortedRelapses.value;
    if (list.length === 0) {
        return {
            total: 0,
            arr: '0.0',
            meanIntervalMonths: null,
            latestDate: null,
            remissionMonths: null,
            completeRecoveryRate: 0,
        };
    }

    // Mean interval between relapses
    let totalInterval = 0;
    let intervalsCount = 0;
    for (let i = 1; i < list.length; i++) {
        if (list[i].start_date && list[i - 1].start_date) {
            const months = calculateMonthInterval(list[i - 1].start_date!, list[i].start_date!);
            totalInterval += months;
            intervalsCount++;
        }
    }
    const meanInterval = intervalsCount > 0 ? (totalInterval / intervalsCount).toFixed(1) : null;

    // Latest relapse date & Remission duration
    const latest = list[list.length - 1];
    let remissionMonths: number | null = null;
    if (latest.start_date) {
        const latestTime = parseFrenchDate(latest.start_date);
        const now = new Date().getTime();
        if (latestTime > 0 && now >= latestTime) {
            remissionMonths = Math.round((now - latestTime) / (1000 * 60 * 60 * 24 * 30.4375));
        }
    }

    const completeCount = list.filter(r => r.clinical_recovery === 'Complète' && !r.sequelae).length;
    const completeRate = Math.round((completeCount / list.length) * 100);

    return {
        total: list.length,
        arr: annualDistributionData.value.arr,
        meanIntervalMonths: meanInterval,
        latestDate: latest.start_date,
        remissionMonths,
        completeRecoveryRate: completeRate,
    };
});
</script>

<template>
    <Card class="border border-border/80 bg-gradient-to-b from-card via-card to-muted/20 shadow-xs overflow-hidden">
        <!-- Header with Medical Icon & Healthcare Title -->
        <CardHeader class="pb-3 border-b border-border/60">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20">
                            <Activity class="size-4" />
                        </div>
                        <CardTitle class="text-base font-semibold tracking-tight text-foreground flex items-center gap-2">
                            <span>Cartographie Chronologique des Poussées</span>
                            <Badge variant="outline" class="text-xs font-mono font-normal bg-background">
                                {{ relapses.length }} {{ relapses.length > 1 ? 'événements' : 'événement' }}
                            </Badge>
                        </CardTitle>
                    </div>
                    <CardDescription class="text-xs text-muted-foreground">
                        Visualisation dynamique du rythme des poussées, du mode de récupération et du taux annualisé (ARR)
                    </CardDescription>
                </div>

                <!-- View Switcher Tabs (Healthcare Clean Style) -->
                <div class="flex items-center bg-muted/70 p-1 rounded-lg border border-border/60 self-start sm:self-auto text-xs">
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center gap-1.5"
                        :class="activeView === 'curve'
                            ? 'bg-background text-foreground shadow-2xs font-semibold'
                            : 'text-muted-foreground hover:text-foreground'"
                        @click="activeView = 'curve'"
                    >
                        <HeartPulse class="size-3.5 text-teal-600 dark:text-teal-400" />
                        <span>Courbe Chronologique</span>
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center gap-1.5"
                        :class="activeView === 'bars'
                            ? 'bg-background text-foreground shadow-2xs font-semibold'
                            : 'text-muted-foreground hover:text-foreground'"
                        @click="activeView = 'bars'"
                    >
                        <BarChart3 class="size-3.5 text-cyan-600 dark:text-cyan-400" />
                        <span>Fréquence Annuelle (ARR)</span>
                    </button>
                </div>
            </div>

            <!-- Healthcare Quick Clinical Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-3 mt-1 border-t border-dashed border-border/50 text-xs">
                <!-- Taux Annualisé (ARR) -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-teal-500/5 border border-teal-500/15">
                    <TrendingUp class="size-3.5 text-teal-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Taux Annualisé (ARR)</span>
                        <span class="font-mono font-bold text-teal-700 dark:text-teal-300 text-xs">
                            {{ clinicalMetrics.arr }} <span class="text-[10px] font-normal text-muted-foreground">/an</span>
                        </span>
                    </div>
                </div>

                <!-- Intervalle Moyen -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-cyan-500/5 border border-cyan-500/15">
                    <Clock class="size-3.5 text-cyan-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Intervalle Moyen</span>
                        <span class="font-mono font-bold text-cyan-700 dark:text-cyan-300 text-xs">
                            {{ clinicalMetrics.meanIntervalMonths ? `${clinicalMetrics.meanIntervalMonths} mois` : 'Événement unique' }}
                        </span>
                    </div>
                </div>

                <!-- Taux de Récupération Complète -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-emerald-500/5 border border-emerald-500/15">
                    <ShieldCheck class="size-3.5 text-emerald-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Récupération Complète</span>
                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-300 text-xs">
                            {{ clinicalMetrics.completeRecoveryRate }}%
                        </span>
                    </div>
                </div>

                <!-- Rémission Actuelle -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-sky-500/5 border border-sky-500/15">
                    <Sparkles class="size-3.5 text-sky-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Dernière Poussée</span>
                        <span class="font-mono font-bold text-sky-700 dark:text-sky-300 text-xs">
                            {{ clinicalMetrics.remissionMonths !== null ? `Il y a ${clinicalMetrics.remissionMonths} mois` : 'Non renseignée' }}
                        </span>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent class="pt-4 pb-4 space-y-4">
            <!-- VIEW 1: COURBE CHRONOLOGIQUE DYNAMIQUE -->
            <div v-if="activeView === 'curve'" class="space-y-4">
                <div v-if="chronologicalChartData" class="w-full overflow-x-auto rounded-lg bg-card/60 border border-border/60 p-2">
                    <svg
                        :viewBox="`0 0 ${chronologicalChartData.width} ${chronologicalChartData.height}`"
                        class="w-full min-w-[650px] h-48 select-none overflow-visible"
                    >
                        <defs>
                            <!-- Healthcare Teal Gradient for Area Fill -->
                            <linearGradient id="relapseHealthcareGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#0d9488" stop-opacity="0.32" />
                                <stop offset="50%" stop-color="#06b6d4" stop-opacity="0.14" />
                                <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.0" />
                            </linearGradient>

                            <!-- Subtle horizontal grid pattern -->
                            <pattern id="relapseGrid" width="40" height="20" patternUnits="userSpaceOnUse">
                                <line x1="0" y1="20" x2="40" y2="20" stroke="currentColor" stroke-width="1" class="text-border/25" />
                            </pattern>
                        </defs>

                        <!-- Background grid -->
                        <rect x="30" y="20" width="740" height="140" fill="url(#relapseGrid)" />

                        <!-- Reference Lines with Medical Labels -->
                        <line x1="40" y1="36" x2="760" y2="36" stroke="currentColor" stroke-dasharray="3 3" class="text-rose-500/30" />
                        <text x="35" y="40" text-anchor="end" class="text-[9px] fill-rose-500 font-medium">Séquelles</text>

                        <line x1="40" y1="60" x2="760" y2="60" stroke="currentColor" stroke-dasharray="3 3" class="text-amber-500/30" />
                        <text x="35" y="64" text-anchor="end" class="text-[9px] fill-amber-500 font-medium">Incomplète</text>

                        <line x1="40" y1="82" x2="760" y2="82" stroke="currentColor" stroke-dasharray="3 3" class="text-emerald-500/30" />
                        <text x="35" y="86" text-anchor="end" class="text-[9px] fill-emerald-500 font-medium">Complète</text>

                        <!-- Baseline Time Axis -->
                        <line x1="40" :y1="chronologicalChartData.baselineY" x2="760" :y2="chronologicalChartData.baselineY" stroke="currentColor" stroke-width="1.5" class="text-border" />
                        <text x="35" :y="chronologicalChartData.baselineY + 4" text-anchor="end" class="text-[9px] fill-muted-foreground font-mono">Ligne de base</text>

                        <!-- Shaded Area Under Curve -->
                        <path
                            :d="chronologicalChartData.areaPath"
                            fill="url(#relapseHealthcareGradient)"
                        />

                        <!-- Main Curve / Wave -->
                        <path
                            :d="chronologicalChartData.curvePath"
                            fill="none"
                            stroke="#0d9488"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="transition-all duration-300"
                        />

                        <!-- Relapse Markers, Stems, Badges & Interactive Targets -->
                        <g
                            v-for="pt in chronologicalChartData.points"
                            :key="pt.id"
                            class="cursor-pointer group"
                            @click="selectedRelapseId = pt.id"
                        >
                            <!-- Vertical Drop Stem (Pillar) -->
                            <line
                                :x1="pt.x"
                                :y1="pt.y"
                                :x2="pt.x"
                                :y2="pt.baselineY"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-dasharray="2 2"
                                :class="activeSelectedRelapse?.id === pt.id ? 'text-primary' : 'text-border group-hover:text-primary/70'"
                            />

                            <!-- Interval pill between previous and current relapse (centered at midX) -->
                            <g v-if="pt.interval">
                                <rect
                                    :x="pt.interval.midX - 24"
                                    :y="chronologicalChartData.baselineY - 10"
                                    width="48"
                                    height="18"
                                    rx="9"
                                    class="fill-card/90 stroke-border/70 shadow-xs"
                                    stroke-width="0.8"
                                />
                                <text
                                    :x="pt.interval.midX"
                                    :y="chronologicalChartData.baselineY + 2"
                                    text-anchor="middle"
                                    class="text-[9px] font-mono font-medium fill-muted-foreground"
                                >
                                    +{{ pt.interval.months }}m
                                </text>
                            </g>

                            <!-- Outer Pulsing Halo when active -->
                            <circle
                                v-if="activeSelectedRelapse?.id === pt.id"
                                :cx="pt.x"
                                :cy="pt.y"
                                r="11"
                                :fill="pt.recoveryStyle.color"
                                fill-opacity="0.22"
                                class="animate-pulse"
                            />

                            <!-- Main Peak Circle (Colored by Recovery Status) -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                :r="activeSelectedRelapse?.id === pt.id ? 7 : 5.5"
                                :fill="pt.recoveryStyle.color"
                                stroke="#ffffff"
                                stroke-width="2"
                                class="transition-all duration-200 group-hover:scale-125"
                            />

                            <!-- Corticosteroid Solumédrol Icon Flag -->
                            <g v-if="pt.hasCorticoids" :transform="`translate(${pt.x - 5}, ${pt.y - 20})`">
                                <circle cx="5" cy="5" r="7" class="fill-sky-500/20 stroke-sky-500" stroke-width="1" />
                                <text x="5" y="8" text-anchor="middle" class="text-[8px] font-bold fill-sky-700 dark:fill-sky-300 font-mono">S</text>
                            </g>

                            <!-- Date Label Below Axis -->
                            <text
                                :x="pt.x"
                                :y="pt.baselineY + 18"
                                text-anchor="middle"
                                class="text-[11px] font-mono font-semibold transition-colors"
                                :class="activeSelectedRelapse?.id === pt.id ? 'fill-primary font-bold' : 'fill-foreground group-hover:fill-primary'"
                            >
                                {{ pt.date }}
                            </text>

                            <!-- Relapse index & duration badge below date -->
                            <text
                                :x="pt.x"
                                :y="pt.baselineY + 32"
                                text-anchor="middle"
                                class="text-[9px] font-mono fill-muted-foreground"
                            >
                                Poussée #{{ pt.index }} <tspan v-if="pt.duration">({{ pt.duration }}j)</tspan>
                            </text>

                            <!-- Click Target (Invisible Expanded Hitbox) -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="18"
                                fill="transparent"
                            />
                        </g>
                    </svg>
                </div>

                <!-- Legend & Status Explanations -->
                <div class="flex items-center justify-between flex-wrap gap-2 text-xs pt-1 border-t border-border/50 text-muted-foreground">
                    <div class="flex items-center gap-4 flex-wrap">
                        <span class="text-[11px] font-medium text-foreground">Légende clinique :</span>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span class="text-[11px]">Complète</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-amber-500 inline-block"></span>
                            <span class="text-[11px]">Incomplète</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-rose-500 inline-block"></span>
                            <span class="text-[11px]">Avec séquelles</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-3 rounded-full bg-sky-500/20 border border-sky-500 text-sky-700 dark:text-sky-300 text-[8px] font-bold font-mono inline-flex items-center justify-center">S</span>
                            <span class="text-[11px]">Corticothérapie (Solumédrol)</span>
                        </div>
                    </div>

                    <span class="text-[11px] italic">
                        Cliquez sur n'importe quel point pour inspecter les détails cliniques
                    </span>
                </div>
            </div>

            <!-- VIEW 2: HISTOGRAMME DE DISTRIBUTION ANNUELLE (ARR) -->
            <div v-else class="space-y-4">
                <div class="rounded-lg bg-card/60 border border-border/60 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-xs font-semibold text-foreground">Distribution annuelle de l'activité inflammatoire</h4>
                            <p class="text-[11px] text-muted-foreground">Nombre de poussées aiguës documentées par année civile</p>
                        </div>
                        <Badge variant="outline" class="font-mono text-xs bg-teal-500/10 text-teal-700 dark:text-teal-300 border-teal-500/30">
                            ARR : {{ annualDistributionData.arr }} / an
                            <span v-if="annualDistributionData.spanYears > 1" class="ml-1 text-[10px] text-muted-foreground font-normal">
                                ({{ sortedRelapses.length }} sur {{ annualDistributionData.spanYears }} ans)
                            </span>
                        </Badge>
                    </div>

                    <!-- SVG Bar Chart with Zero Overlap Guarantee -->
                    <div class="w-full overflow-x-auto">
                        <svg viewBox="0 0 720 180" class="w-full min-w-[550px] h-44 overflow-visible select-none">
                            <defs>
                                <linearGradient id="annualBarGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#0d9488" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#0f766e" stop-opacity="0.75" />
                                </linearGradient>
                            </defs>

                            <!-- Baseline -->
                            <line x1="30" y1="125" x2="690" y2="125" stroke="currentColor" stroke-width="1.5" class="text-border" />

                            <!-- Elements (Year Bars & Remission Gaps) -->
                            <g
                                v-for="item in annualDistributionData.items"
                                :key="item.id"
                                class="group"
                            >
                                <!-- CASE 1: ACTIVE YEAR OR CALENDAR YEAR BAR -->
                                <g v-if="item.type === 'year'">
                                    <!-- Bar Stem / Body if count > 0 -->
                                    <rect
                                        v-if="(item.count ?? 0) > 0"
                                        :x="item.x - 18"
                                        :y="125 - ((item.count ?? 0) / annualDistributionData.maxCount) * 80"
                                        width="36"
                                        :height="Math.max(10, ((item.count ?? 0) / annualDistributionData.maxCount) * 80)"
                                        rx="5"
                                        fill="url(#annualBarGradient)"
                                        class="hover:brightness-110 transition-all cursor-pointer shadow-xs"
                                    />

                                    <!-- Zero marker dot for quiet year in short span -->
                                    <circle
                                        v-else
                                        :cx="item.x"
                                        cy="125"
                                        r="3.5"
                                        class="fill-muted-foreground/40"
                                    />

                                    <!-- Count Number on Top of Bar -->
                                    <text
                                        v-if="(item.count ?? 0) > 0"
                                        :x="item.x"
                                        :y="125 - ((item.count ?? 0) / annualDistributionData.maxCount) * 80 - 7"
                                        text-anchor="middle"
                                        class="text-[12px] font-mono font-bold fill-foreground"
                                    >
                                        {{ item.count }}
                                    </text>

                                    <!-- Year Label Below Axis -->
                                    <text
                                        :x="item.x"
                                        y="146"
                                        text-anchor="middle"
                                        class="text-[12px] font-mono font-bold fill-foreground group-hover:fill-teal-600 transition-colors"
                                    >
                                        {{ item.year }}
                                    </text>

                                    <!-- Subtext for active year only (Zero text collision) -->
                                    <text
                                        v-if="(item.count ?? 0) > 0"
                                        :x="item.x"
                                        y="160"
                                        text-anchor="middle"
                                        class="text-[10px] font-mono fill-teal-600 dark:fill-teal-400 font-medium"
                                    >
                                        {{ item.count }} {{ (item.count ?? 0) > 1 ? 'poussées' : 'poussée' }}
                                    </text>
                                </g>

                                <!-- CASE 2: REMISSION GAP INDICATOR (Between Far Years) -->
                                <g v-else-if="item.type === 'gap'">
                                    <!-- Dashed line for long remission -->
                                    <line
                                        :x1="item.x - 70"
                                        y1="125"
                                        :x2="item.x + 70"
                                        y2="125"
                                        stroke="#0d9488"
                                        stroke-dasharray="4 3"
                                        stroke-width="1.5"
                                        class="opacity-60"
                                    />

                                    <!-- Capsule Badge for Remission Years -->
                                    <rect
                                        :x="item.x - 65"
                                        y="112"
                                        width="130"
                                        height="26"
                                        rx="13"
                                        class="fill-card stroke-teal-500/40 shadow-xs"
                                        stroke-width="1.2"
                                    />

                                    <!-- Remission Text inside Capsule -->
                                    <text
                                        :x="item.x"
                                        y="129"
                                        text-anchor="middle"
                                        class="text-[10px] font-semibold fill-teal-700 dark:fill-teal-300 font-mono"
                                    >
                                        {{ item.gapYears }} ans de rémission
                                    </text>
                                </g>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- INTERACTIVE RELAPSE CLINICAL DETAILS CARD (Healthcare Card) -->
            <div
                v-if="activeSelectedRelapse"
                class="rounded-xl border p-4 bg-card shadow-2xs transition-all duration-200"
                :class="getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).border"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-border/60">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="p-2 rounded-lg"
                            :class="getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).bg"
                        >
                            <Flame class="size-4" :class="getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).text" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-foreground">
                                    Poussée du {{ activeSelectedRelapse.start_date || 'Date non précisée' }}
                                </h4>
                                <Badge
                                    :variant="activeSelectedRelapse.is_before_diagnosis ? 'outline' : 'secondary'"
                                    class="text-[10px] font-normal"
                                >
                                    {{ activeSelectedRelapse.is_before_diagnosis ? 'Inaugurale' : 'Sous suivi' }}
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                <span v-if="activeSelectedRelapse.duration_days">Durée : {{ activeSelectedRelapse.duration_days }} jours</span>
                                <span v-if="activeSelectedRelapse.end_date"> • Résolution vers le {{ activeSelectedRelapse.end_date }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Recovery Badge -->
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                            :class="[
                                getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).bg,
                                getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).text,
                                getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).border
                            ]"
                        >
                            {{ getRecoveryStyle(activeSelectedRelapse.clinical_recovery, !!activeSelectedRelapse.sequelae).label }}
                        </span>
                        <Badge
                            v-if="activeSelectedRelapse.hospitalization_required"
                            variant="destructive"
                            class="text-[11px]"
                        >
                            Hospitalisation
                        </Badge>
                    </div>
                </div>

                <!-- Symptoms & Treatments Detail Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3 text-xs">
                    <!-- Left: Clinical symptoms -->
                    <div class="p-3 rounded-lg bg-muted/40 border border-border/50 space-y-1">
                        <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide block">
                            Symptômes Cliniques Présentés
                        </span>
                        <p class="text-xs font-medium text-foreground">
                            {{ activeSelectedRelapse.symptoms }}
                        </p>
                        <div v-if="activeSelectedRelapse.sequelae" class="text-xs text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1 pt-1">
                            <AlertCircle class="size-3.5 shrink-0" />
                            <span>Séquelles persistantes : {{ activeSelectedRelapse.sequelae }}</span>
                        </div>
                    </div>

                    <!-- Right: Treatment Administered -->
                    <div class="p-3 rounded-lg bg-muted/40 border border-border/50 space-y-1">
                        <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide block">
                            Traitement de la Poussée
                        </span>
                        <div v-if="activeSelectedRelapse.treatment_administered" class="flex items-center gap-2">
                            <Pill class="size-3.5 text-sky-600 shrink-0" />
                            <span class="font-medium text-foreground">
                                {{ activeSelectedRelapse.treatment_administered }}
                            </span>
                            <span v-if="activeSelectedRelapse.administration_route" class="text-muted-foreground text-[11px]">
                                ({{ activeSelectedRelapse.administration_route }})
                            </span>
                        </div>
                        <div v-else class="text-muted-foreground italic">
                            Aucun traitement d'urgence documenté
                        </div>
                        <p v-if="activeSelectedRelapse.notes" class="text-[11px] text-muted-foreground pt-1">
                            Note : {{ activeSelectedRelapse.notes }}
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
