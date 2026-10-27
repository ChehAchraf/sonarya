<script setup lang="ts">
import {
    Activity,
    AlertCircle,
    ArrowDownRight,
    ArrowUpRight,
    BarChart3,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    Flame,
    HeartPulse,
    HelpCircle,
    Info,
    Minus,
    Pill,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    Stethoscope,
    TrendingDown,
    TrendingUp,
    UserCheck,
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

type ClinicalExam = {
    id: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    edss_score: string | number;
    t25fw?: number | string | null;
    nine_hpt?: number | string | null;
    evaluator_name?: string | null;
    clinical_comment?: string | null;
};

type Consultation = {
    id: number;
    consultation_date: string | null;
    consultation_date_raw?: string | null;
    consultation_type?: string | null;
    doctor_name?: string | null;
    edss_score?: string | number | null;
    notes?: string | null;
};

type DiseaseProfile = {
    initial_edss?: number | string | null;
    diagnosis_date?: string | null;
    diagnosis_date_raw?: string | null;
    first_symptoms_date?: string | null;
};

type Props = {
    relapses: Relapse[];
    clinicalExams?: ClinicalExam[];
    consultations?: Consultation[];
    diseaseProfile?: DiseaseProfile | null;
    diagnosisDate?: string | null;
};

const props = withDefaults(defineProps<Props>(), {
    clinicalExams: () => [],
    consultations: () => [],
    diseaseProfile: null,
    diagnosisDate: null,
});

// Active display view: 'edss_timeline' (Dual axis EDSS + Relapses), 'curve' (Wave), 'bars' (Annual ARR)
const activeView = ref<'edss_timeline' | 'curve' | 'bars'>('edss_timeline');

// Selection state for inspector panel
type SelectedItem =
    | { type: 'relapse'; data: Relapse; index: number }
    | { type: 'edss'; data: EDSSPoint }
    | null;

const selectedItem = ref<SelectedItem>(null);

function parseAnyDate(d: string | null | undefined): number {
    if (!d) return 0;
    if (d.includes('-')) {
        const parts = d.split('-');
        if (parts.length === 3) {
            return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])).getTime();
        }
    }
    if (d.includes('/')) {
        const parts = d.split('/');
        if (parts.length === 3) {
            return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0])).getTime();
        }
    }
    const t = new Date(d).getTime();
    return isNaN(t) ? 0 : t;
}

function formatFrenchDate(timestamp: number): string {
    if (!timestamp || isNaN(timestamp)) return '—';
    const d = new Date(timestamp);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

// Chronologically sorted relapses
const sortedRelapses = computed(() => {
    return [...props.relapses].sort((a, b) => {
        const dateA = parseAnyDate(a.start_date_raw || a.start_date);
        const dateB = parseAnyDate(b.start_date_raw || b.start_date);
        return dateA - dateB;
    });
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
            peakY: 36,
            severityLevel: 3,
        };
    }
    if (recovery && (recovery.toLowerCase().includes('incompl') || recovery.toLowerCase().includes('nulle'))) {
        return {
            color: '#f59e0b', // Amber
            stroke: 'stroke-amber-500',
            fill: 'fill-amber-500',
            bg: 'bg-amber-500/10 dark:bg-amber-950/40',
            text: 'text-amber-700 dark:text-amber-400',
            border: 'border-amber-500/30',
            label: 'Récupération incomplète',
            peakY: 60,
            severityLevel: 2,
        };
    }
    return {
        color: '#10b981', // Emerald
        stroke: 'stroke-emerald-500',
        fill: 'fill-emerald-500',
        bg: 'bg-emerald-500/10 dark:bg-emerald-950/40',
        text: 'text-emerald-700 dark:text-emerald-400',
        border: 'border-emerald-500/30',
        label: 'Récupération complète',
        peakY: 82,
        severityLevel: 1,
    };
}

function getSeverityBadge(severity: string | null | undefined) {
    if (severity === 'severe') {
        return { label: 'Sévère', color: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30' };
    }
    if (severity === 'legere') {
        return { label: 'Légère', color: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' };
    }
    return { label: 'Modérée', color: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30' };
}

// -------------------------------------------------------------
// EDSS POINTS GATHERING & LONGITUDINAL EVALUATION
// -------------------------------------------------------------
export type EDSSPoint = {
    id: string;
    timestamp: number;
    dateFormatted: string;
    score: number;
    source: 'initial' | 'exam' | 'consultation' | 'relapse_post' | 'relapse_pre';
    sourceLabel: string;
    evaluator?: string | null;
    notes?: string | null;
    relapseId?: number;
    x: number;
    y: number;
};

const rawEdssPoints = computed<Omit<EDSSPoint, 'x' | 'y'>[]>(() => {
    const list: Omit<EDSSPoint, 'x' | 'y'>[] = [];

    // 1. Initial EDSS from disease profile
    if (props.diseaseProfile?.initial_edss !== null && props.diseaseProfile?.initial_edss !== undefined && props.diseaseProfile.initial_edss !== '') {
        const score = Number(props.diseaseProfile.initial_edss);
        if (!isNaN(score)) {
            const time = parseAnyDate(props.diagnosisDate || props.diseaseProfile.diagnosis_date || props.diseaseProfile.first_symptoms_date);
            if (time > 0) {
                list.push({
                    id: 'initial-edss',
                    timestamp: time,
                    dateFormatted: formatFrenchDate(time),
                    score,
                    source: 'initial',
                    sourceLabel: 'Évaluation Initiale (Diagnostic)',
                    evaluator: 'Bilan inaugural',
                    notes: 'Score EDSS de référence documenté au diagnostic initial.',
                });
            }
        }
    }

    // 2. Clinical Exams EDSS
    props.clinicalExams.forEach((exam) => {
        if (exam.edss_score !== null && exam.edss_score !== undefined && exam.edss_score !== '') {
            const score = Number(exam.edss_score);
            const time = parseAnyDate(exam.exam_date_raw || exam.exam_date);
            if (!isNaN(score) && time > 0) {
                list.push({
                    id: `exam-${exam.id}`,
                    timestamp: time,
                    dateFormatted: exam.exam_date || formatFrenchDate(time),
                    score,
                    source: 'exam',
                    sourceLabel: 'Examen Clinique Approfondi',
                    evaluator: exam.evaluator_name || 'Neurologue examinateur',
                    notes: exam.clinical_comment || (exam.t25fw ? `T25FW: ${exam.t25fw}s` : null),
                });
            }
        }
    });

    // 3. Consultations EDSS
    props.consultations.forEach((c) => {
        if (c.edss_score !== null && c.edss_score !== undefined && c.edss_score !== '') {
            const score = Number(c.edss_score);
            const time = parseAnyDate(c.consultation_date_raw || c.consultation_date);
            if (!isNaN(score) && time > 0) {
                list.push({
                    id: `consult-${c.id}`,
                    timestamp: time,
                    dateFormatted: c.consultation_date || formatFrenchDate(time),
                    score,
                    source: 'consultation',
                    sourceLabel: 'Consultation de Suivi',
                    evaluator: c.doctor_name || 'Médecin consultant',
                    notes: c.notes,
                });
            }
        }
    });

    // 4. Relapse EDSS (Before & After if evaluated)
    props.relapses.forEach((r) => {
        if (r.edss_before_relapse !== null && r.edss_before_relapse !== undefined && r.edss_before_relapse !== '') {
            const score = Number(r.edss_before_relapse);
            const time = parseAnyDate(r.start_date_raw || r.start_date);
            if (!isNaN(score) && time > 0) {
                list.push({
                    id: `relapse-pre-${r.id}`,
                    timestamp: time - 86400000, // 1 day before
                    dateFormatted: formatFrenchDate(time),
                    score,
                    source: 'relapse_pre',
                    sourceLabel: `EDSS Avant Poussée (#${r.id})`,
                    evaluator: 'État de base pré-poussée',
                    notes: `Niveau de handicap stable précédant la poussée du ${r.start_date || ''}.`,
                    relapseId: r.id,
                });
            }
        }

        if (r.edss_after_relapse !== null && r.edss_after_relapse !== undefined && r.edss_after_relapse !== '') {
            const score = Number(r.edss_after_relapse);
            const time = parseAnyDate(r.edss_evaluation_date_raw || r.edss_evaluation_date || r.end_date_raw || r.end_date);
            if (!isNaN(score) && time > 0) {
                list.push({
                    id: `relapse-post-${r.id}`,
                    timestamp: time,
                    dateFormatted: r.edss_evaluation_date || formatFrenchDate(time),
                    score,
                    source: 'relapse_post',
                    sourceLabel: `EDSS Post-Poussée (#${r.id})`,
                    evaluator: 'Évaluation après récupération',
                    notes: `Score stabilisé suite à la poussée. Récupération: ${r.clinical_recovery || 'Inconnue'}.`,
                    relapseId: r.id,
                });
            }
        }
    });

    // Sort chronologically and dedup close timestamps if identical score
    return list.sort((a, b) => a.timestamp - b.timestamp);
});

// Longitudinal disability evolution delta (Initial to Latest)
const edssEvolutionSummary = computed(() => {
    const points = rawEdssPoints.value;
    if (points.length === 0) {
        return {
            hasData: false,
            initialScore: null,
            latestScore: null,
            delta: null,
            status: 'Non renseigné',
            statusColor: 'text-muted-foreground',
        };
    }
    const initialScore = points[0].score;
    const latestScore = points[points.length - 1].score;
    const delta = Number((latestScore - initialScore).toFixed(1));

    let status = 'Handicap stable';
    let statusColor = 'text-emerald-600 dark:text-emerald-400';
    if (delta > 0) {
        status = `Progression confirmée (+${delta})`;
        statusColor = 'text-rose-600 dark:text-rose-400';
    } else if (delta < 0) {
        status = `Amélioration clinique (${delta})`;
        statusColor = 'text-sky-600 dark:text-sky-400';
    }

    return {
        hasData: true,
        initialScore,
        latestScore,
        delta,
        status,
        statusColor,
    };
});

// -------------------------------------------------------------
// VIEW 1: COMBINED CHRONOLOGICAL CHART (EDSS & RELAPSES)
// -------------------------------------------------------------
const combinedTimelineData = computed(() => {
    const relapses = sortedRelapses.value;
    const edssList = rawEdssPoints.value;

    if (relapses.length === 0 && edssList.length === 0) {
        return null;
    }

    // Determine global time span
    const allTimestamps: number[] = [
        ...relapses.map((r) => parseAnyDate(r.start_date_raw || r.start_date)),
        ...edssList.map((e) => e.timestamp),
    ].filter((t) => t > 0);

    if (allTimestamps.length === 0) return null;

    let minT = Math.min(...allTimestamps);
    let maxT = Math.max(...allTimestamps);

    // Padding if single event or close dates
    if (maxT - minT < 1000 * 60 * 60 * 24 * 60) {
        minT -= 1000 * 60 * 60 * 24 * 45;
        maxT += 1000 * 60 * 60 * 24 * 45;
    } else {
        const pad = (maxT - minT) * 0.06;
        minT -= pad;
        maxT += pad;
    }

    const totalSpan = maxT - minT;

    // SVG geometry
    const eventCount = relapses.length + edssList.length;
    const svgWidth = Math.max(860, eventCount * 85);
    const svgHeight = 350;
    const paddingLeft = 70;
    const paddingRight = 60;
    const plotTop = 75;
    const plotBottom = 280;
    const plotHeight = plotBottom - plotTop;
    const plotWidth = svgWidth - paddingLeft - paddingRight;

    function timeToX(t: number): number {
        return paddingLeft + ((t - minT) / totalSpan) * plotWidth;
    }

    function edssToY(score: number): number {
        const clamped = Math.max(0, Math.min(10, score));
        return plotBottom - (clamped / 10.0) * plotHeight;
    }

    // Plotted EDSS Points
    const mappedEdssPoints: EDSSPoint[] = edssList.map((pt) => ({
        ...pt,
        x: timeToX(pt.timestamp),
        y: edssToY(pt.score),
    }));

    // SVG path connecting EDSS evaluations
    let edssCurvePath = '';
    let edssAreaPath = '';

    if (mappedEdssPoints.length === 1) {
        const p = mappedEdssPoints[0];
        edssCurvePath = `M ${p.x - 30} ${p.y} L ${p.x + 30} ${p.y}`;
        edssAreaPath = `M ${p.x - 30} ${plotBottom} L ${p.x - 30} ${p.y} L ${p.x + 30} ${p.y} L ${p.x + 30} ${plotBottom} Z`;
    } else if (mappedEdssPoints.length > 1) {
        // Step-linear curve: Step-curve is standard in neurological EDSS
        // to show sustained disability between evaluations
        edssCurvePath = `M ${mappedEdssPoints[0].x} ${mappedEdssPoints[0].y}`;
        for (let i = 1; i < mappedEdssPoints.length; i++) {
            const prev = mappedEdssPoints[i - 1];
            const curr = mappedEdssPoints[i];
            const midX = (prev.x + curr.x) / 2;
            edssCurvePath += ` C ${midX} ${prev.y}, ${midX} ${curr.y}, ${curr.x} ${curr.y}`;
        }

        const last = mappedEdssPoints[mappedEdssPoints.length - 1];
        const first = mappedEdssPoints[0];
        edssAreaPath = `${edssCurvePath} L ${last.x} ${plotBottom} L ${first.x} ${plotBottom} Z`;
    }

    // Plotted Relapse Markers
    const mappedRelapses = relapses.map((r, idx) => {
        const time = parseAnyDate(r.start_date_raw || r.start_date);
        const x = timeToX(time);
        const style = getRecoveryStyle(r.clinical_recovery, !!r.sequelae);
        const severity = (r.severity as 'legere' | 'moderee' | 'severe') || 'moderee';
        const severityBadge = getSeverityBadge(severity);

        const edssBefore = r.edss_before_relapse !== null && r.edss_before_relapse !== undefined && r.edss_before_relapse !== '' ? Number(r.edss_before_relapse) : null;
        const edssAfter = r.edss_after_relapse !== null && r.edss_after_relapse !== undefined && r.edss_after_relapse !== '' ? Number(r.edss_after_relapse) : null;
        const edssDelta = edssBefore !== null && edssAfter !== null ? Number((edssAfter - edssBefore).toFixed(1)) : null;

        const hasCorticoids = !!(
            r.treatment_administered &&
            (r.treatment_administered.toLowerCase().includes('solu') ||
             r.treatment_administered.toLowerCase().includes('cortic') ||
             r.treatment_administered.toLowerCase().includes('bolus') ||
             r.treatment_administered.toLowerCase().includes('flash'))
        );

        return {
            id: r.id,
            index: idx + 1,
            time,
            x,
            stemTopY: 30,
            baselineY: plotBottom,
            edssBeforeY: edssBefore !== null ? edssToY(edssBefore) : null,
            edssAfterY: edssAfter !== null ? edssToY(edssAfter) : null,
            edssBefore,
            edssAfter,
            edssDelta,
            hasCorticoids,
            recoveryStyle: style,
            severity,
            severityBadge,
            raw: r,
        };
    });

    // Generate axis year tick labels
    const startYear = new Date(minT).getFullYear();
    const endYear = new Date(maxT).getFullYear();
    const yearTicks: { year: number; x: number }[] = [];

    for (let yr = startYear; yr <= endYear; yr++) {
        const tickTime = new Date(yr, 0, 1).getTime();
        if (tickTime >= minT && tickTime <= maxT) {
            yearTicks.push({
                year: yr,
                x: timeToX(tickTime),
            });
        }
    }

    // Standard clinical EDSS grid lines (0, 2, 3, 4, 6, 7, 8, 10)
    const edssGridLines = [
        { score: 10, label: '10 - Décès SEP', y: edssToY(10), major: true, color: 'text-rose-500/40' },
        { score: 8, label: '8.0 - Alité', y: edssToY(8), major: false, color: 'text-rose-500/30' },
        { score: 6, label: '6.0 - Aide unilatérale (100m)', y: edssToY(6), major: true, color: 'text-amber-500/40' },
        { score: 4, label: '4.0 - Autonome (>500m)', y: edssToY(4), major: false, color: 'text-blue-500/30' },
        { score: 3, label: '3.0 - Handicap modéré', y: edssToY(3), major: false, color: 'text-emerald-500/30' },
        { score: 2, label: '2.0 - Handicap minime', y: edssToY(2), major: false, color: 'text-emerald-500/30' },
        { score: 0, label: '0.0 - Normal', y: edssToY(0), major: true, color: 'text-border' },
    ];

    return {
        svgWidth,
        svgHeight,
        paddingLeft,
        paddingRight,
        plotTop,
        plotBottom,
        plotHeight,
        plotWidth,
        mappedEdssPoints,
        edssCurvePath,
        edssAreaPath,
        mappedRelapses,
        yearTicks,
        edssGridLines,
    };
});

// Auto-select latest event by default
const activeSelection = computed(() => {
    if (selectedItem.value) return selectedItem.value;
    const relapses = sortedRelapses.value;
    if (relapses.length > 0) {
        return {
            type: 'relapse' as const,
            data: relapses[relapses.length - 1],
            index: relapses.length,
        };
    }
    const edss = rawEdssPoints.value;
    if (edss.length > 0) {
        return {
            type: 'edss' as const,
            data: { ...edss[edss.length - 1], x: 0, y: 0 },
        };
    }
    return null;
});

// -------------------------------------------------------------
// VIEW 2: WAVE VIEW (Existing Logic Preserved)
// -------------------------------------------------------------
function calculateMonthInterval(dateStr1: string, dateStr2: string): number {
    const t1 = parseAnyDate(dateStr1);
    const t2 = parseAnyDate(dateStr2);
    const diffMs = Math.abs(t2 - t1);
    return Math.round(diffMs / (1000 * 60 * 60 * 24 * 30.4375));
}

const chronologicalChartData = computed(() => {
    const list = sortedRelapses.value;
    if (list.length === 0) return null;

    const width = Math.max(800, list.length * 140);
    const height = 230;
    const paddingX = 80;
    const baselineY = 160;
    const availableW = width - paddingX * 2;

    const points = list.map((r, i) => {
        const x = list.length === 1 ? width / 2 : paddingX + i * (availableW / (list.length - 1));
        const style = getRecoveryStyle(r.clinical_recovery, !!r.sequelae);
        const y = style.peakY;

        let intervalObj: { months: number; midX: number } | null = null;
        if (i > 0 && r.start_date && list[i - 1].start_date) {
            const months = calculateMonthInterval(list[i - 1].start_date!, r.start_date!);
            const prevX = paddingX + (i - 1) * (availableW / (list.length - 1));
            intervalObj = { months, midX: (prevX + x) / 2 };
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

    let curvePath = '';
    let areaPath = '';

    if (points.length === 1) {
        curvePath = `M ${points[0].x - 40} ${points[0].y} Q ${points[0].x} ${points[0].y - 15} ${points[0].x + 40} ${points[0].y}`;
        areaPath = `M ${points[0].x - 40} ${baselineY} L ${points[0].x - 40} ${points[0].y} Q ${points[0].x} ${points[0].y - 15} ${points[0].x + 40} ${points[0].y} L ${points[0].x + 40} ${baselineY} Z`;
    } else {
        curvePath = `M ${points[0].x} ${points[0].y}`;
        for (let i = 0; i < points.length - 1; i++) {
            const current = points[i];
            const next = points[i + 1];
            const midX = (current.x + next.x) / 2;
            const dipY = baselineY - 20;
            curvePath += ` C ${midX} ${current.y}, ${midX} ${dipY}, ${next.x} ${next.y}`;
        }
        areaPath = `${curvePath} L ${points[points.length - 1].x} ${baselineY} L ${points[0].x} ${baselineY} Z`;
    }

    return { width, height, baselineY, points, curvePath, areaPath };
});

// -------------------------------------------------------------
// VIEW 3: ANNUAL DISTRIBUTION (ARR)
// -------------------------------------------------------------
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
    let minYear = new Date().getFullYear();
    let maxYear = new Date().getFullYear();
    let hasValidYears = false;

    list.forEach((r) => {
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
    const maxCount = Math.max(...Array.from(binsMap.values()).map((b) => b.count), 1);

    type RawElement =
        | { type: 'year'; bin: YearBin }
        | { type: 'gap'; fromYear: number; toYear: number; gapYears: number };

    const rawElements: RawElement[] = [];

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
        for (let i = 0; i < sortedActiveYears.length; i++) {
            const yr = sortedActiveYears[i];
            const bin = binsMap.get(yr)!;
            rawElements.push({ type: 'year', bin });

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

    const svgWidth = 720;
    const paddingX = rawElements.length <= 2 ? 160 : rawElements.length <= 4 ? 110 : 60;
    const availableW = svgWidth - paddingX * 2;
    const len = rawElements.length;

    const items: AnnualItem[] = rawElements.map((el, idx) => {
        const x = len === 1 ? svgWidth / 2 : paddingX + idx * (availableW / (len - 1));

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

    return { items, maxCount, arr, spanYears: diseaseSpanYears };
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

    const latest = list[list.length - 1];
    let remissionMonths: number | null = null;
    if (latest.start_date) {
        const latestTime = parseAnyDate(latest.start_date_raw || latest.start_date);
        const now = Date.now();
        if (latestTime > 0 && now >= latestTime) {
            remissionMonths = Math.round((now - latestTime) / (1000 * 60 * 60 * 24 * 30.4375));
        }
    }

    const completeCount = list.filter((r) => r.clinical_recovery === 'Complète' && !r.sequelae).length;
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
                            <span>Illustration Chronologique des Poussées & Évolution EDSS</span>
                            <Badge variant="outline" class="text-xs font-mono font-normal bg-background">
                                {{ relapses.length }} {{ relapses.length > 1 ? 'poussées' : 'poussée' }}
                            </Badge>
                        </CardTitle>
                    </div>
                    <CardDescription class="text-xs text-muted-foreground">
                        Mise en relation graphique de la survenue des poussées cliniques avec le niveau de handicap évalué par l'échelle EDSS (0 à 10)
                    </CardDescription>
                </div>

                <!-- View Switcher Tabs -->
                <div class="flex items-center bg-muted/70 p-1 rounded-lg border border-border/60 w-full sm:w-auto text-xs overflow-x-auto">
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center justify-center gap-1.5 flex-1 sm:flex-initial whitespace-nowrap"
                        :class="activeView === 'edss_timeline'
                            ? 'bg-background text-foreground shadow-2xs font-semibold'
                            : 'text-muted-foreground hover:text-foreground'"
                        @click="activeView = 'edss_timeline'"
                    >
                        <TrendingUp class="size-3.5 text-indigo-600 dark:text-indigo-400" />
                        <span>Courbe EDSS & Poussées</span>
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center justify-center gap-1.5 flex-1 sm:flex-initial whitespace-nowrap"
                        :class="activeView === 'curve'
                            ? 'bg-background text-foreground shadow-2xs font-semibold'
                            : 'text-muted-foreground hover:text-foreground'"
                        @click="activeView = 'curve'"
                    >
                        <HeartPulse class="size-3.5 text-teal-600 dark:text-teal-400" />
                        <span>Dynamique des Poussées</span>
                    </button>
                    <button
                        type="button"
                        class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center justify-center gap-1.5 flex-1 sm:flex-initial whitespace-nowrap"
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

            <!-- Healthcare Clinical Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-3 mt-1 border-t border-dashed border-border/50 text-xs">
                <!-- EDSS Évolution Longitudinale -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-indigo-500/5 border border-indigo-500/15">
                    <TrendingUp class="size-3.5 text-indigo-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Évolution EDSS</span>
                        <span class="font-mono font-bold text-xs" :class="edssEvolutionSummary.statusColor">
                            <template v-if="edssEvolutionSummary.hasData">
                                {{ edssEvolutionSummary.initialScore ?? '—' }} → {{ edssEvolutionSummary.latestScore ?? '—' }}
                                <span class="text-[10px] font-normal">({{ edssEvolutionSummary.delta !== null && edssEvolutionSummary.delta > 0 ? '+' : '' }}{{ edssEvolutionSummary.delta }})</span>
                            </template>
                            <span v-else class="text-muted-foreground font-normal">Non documenté</span>
                        </span>
                    </div>
                </div>

                <!-- Taux Annualisé (ARR) -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-teal-500/5 border border-teal-500/15">
                    <Activity class="size-3.5 text-teal-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Taux Annualisé (ARR)</span>
                        <span class="font-mono font-bold text-teal-700 dark:text-teal-300 text-xs">
                            {{ clinicalMetrics.arr }} <span class="text-[10px] font-normal text-muted-foreground">/an</span>
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
            <!-- ============================================================== -->
            <!-- VIEW 1: COMBINED CHRONOLOGICAL EDSS & RELAPSE GRAPH            -->
            <!-- ============================================================== -->
            <div v-if="activeView === 'edss_timeline'" class="space-y-4">
                <div v-if="combinedTimelineData" class="w-full overflow-x-auto rounded-lg bg-card/60 border border-border/60 p-2">
                    <svg
                        :viewBox="`0 0 ${combinedTimelineData.svgWidth} ${combinedTimelineData.svgHeight}`"
                        class="w-full min-w-[750px] h-72 select-none overflow-visible"
                    >
                        <defs>
                            <!-- EDSS Curve Area Gradient -->
                            <linearGradient id="edssAreaGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6366f1" stop-opacity="0.25" />
                                <stop offset="70%" stop-color="#0d9488" stop-opacity="0.08" />
                                <stop offset="100%" stop-color="#0d9488" stop-opacity="0.0" />
                            </linearGradient>

                            <!-- Mild grid pattern -->
                            <pattern id="edssGrid" width="40" height="20" patternUnits="userSpaceOnUse">
                                <line x1="0" y1="20" x2="40" y2="20" stroke="currentColor" stroke-width="0.8" class="text-border/20" />
                            </pattern>
                        </defs>

                        <!-- Plot Background -->
                        <rect
                            :x="combinedTimelineData.paddingLeft"
                            :y="combinedTimelineData.plotTop"
                            :width="combinedTimelineData.plotWidth"
                            :height="combinedTimelineData.plotHeight"
                            fill="url(#edssGrid)"
                            class="rx-md"
                        />

                        <!-- Y-Axis (EDSS 0 to 10) Grid Lines & Labels -->
                        <g v-for="grid in combinedTimelineData.edssGridLines" :key="grid.score">
                            <line
                                :x1="combinedTimelineData.paddingLeft"
                                :y1="grid.y"
                                :x2="combinedTimelineData.paddingLeft + combinedTimelineData.plotWidth"
                                :y2="grid.y"
                                stroke="currentColor"
                                :stroke-width="grid.major ? 1.2 : 0.7"
                                :stroke-dasharray="grid.score === 0 ? '0' : '3 3'"
                                :class="grid.color"
                            />
                            <text
                                :x="combinedTimelineData.paddingLeft - 8"
                                :y="grid.y + 3"
                                text-anchor="end"
                                class="text-[10px] font-mono fill-muted-foreground select-none"
                                :class="grid.major ? 'font-semibold fill-foreground' : ''"
                            >
                                {{ grid.score.toFixed(1) }}
                            </text>
                        </g>

                        <!-- Y-Axis Title -->
                        <text
                            :x="combinedTimelineData.paddingLeft - 45"
                            :y="combinedTimelineData.plotTop - 12"
                            text-anchor="start"
                            class="text-[10px] font-bold fill-indigo-600 dark:fill-indigo-400 uppercase tracking-wider"
                        >
                            Score EDSS (Handicap)
                        </text>

                        <!-- Year Axis Ticks & Timeline Bar -->
                        <line
                            :x1="combinedTimelineData.paddingLeft"
                            :y1="combinedTimelineData.plotBottom"
                            :x2="combinedTimelineData.paddingLeft + combinedTimelineData.plotWidth"
                            :y2="combinedTimelineData.plotBottom"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="text-border"
                        />

                        <g v-for="tick in combinedTimelineData.yearTicks" :key="tick.year">
                            <line
                                :x1="tick.x"
                                :y1="combinedTimelineData.plotBottom"
                                :x2="tick.x"
                                :y2="combinedTimelineData.plotBottom + 6"
                                stroke="currentColor"
                                stroke-width="1"
                                class="text-muted-foreground"
                            />
                            <text
                                :x="tick.x"
                                :y="combinedTimelineData.plotBottom + 20"
                                text-anchor="middle"
                                class="text-[11px] font-mono font-bold fill-muted-foreground"
                            >
                                {{ tick.year }}
                            </text>
                        </g>

                        <!-- Shaded Area Under EDSS Curve -->
                        <path
                            v-if="combinedTimelineData.edssAreaPath"
                            :d="combinedTimelineData.edssAreaPath"
                            fill="url(#edssAreaGradient)"
                        />

                        <!-- Main EDSS Step Curve Line -->
                        <path
                            v-if="combinedTimelineData.edssCurvePath"
                            :d="combinedTimelineData.edssCurvePath"
                            fill="none"
                            stroke="#6366f1"
                            stroke-width="2.6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- -------------------------------------------------- -->
                        <!-- DISTINCT RELAPSE MARKERS (Piliers & Drapeaux Poussées) -->
                        <!-- -------------------------------------------------- -->
                        <g
                            v-for="relapse in combinedTimelineData.mappedRelapses"
                            :key="`relapse-marker-${relapse.id}`"
                            class="cursor-pointer group"
                            @click="selectedItem = { type: 'relapse', data: relapse.raw, index: relapse.index }"
                        >
                            <!-- Vertical Drop Stem (Pillar) on exact relapse date -->
                            <line
                                :x1="relapse.x"
                                :y1="relapse.stemTopY + 18"
                                :x2="relapse.x"
                                :y2="relapse.baselineY"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-dasharray="3 3"
                                :class="activeSelection?.type === 'relapse' && activeSelection.data.id === relapse.id
                                    ? 'text-primary stroke-[2.2]'
                                    : 'text-border/80 group-hover:text-primary/70'"
                            />

                            <!-- EDSS Before -> After Delta Accent Column on the timeline -->
                            <g v-if="relapse.edssBeforeY !== null && relapse.edssAfterY !== null">
                                <!-- Connecting vertical bar between pre and post EDSS -->
                                <line
                                    :x1="relapse.x"
                                    :y1="relapse.edssBeforeY"
                                    :x2="relapse.x"
                                    :y2="relapse.edssAfterY"
                                    :stroke="relapse.recoveryStyle.color"
                                    stroke-width="4.5"
                                    stroke-linecap="round"
                                    class="opacity-75"
                                />
                                <!-- Badge for EDSS variation -->
                                <rect
                                    :x="relapse.x + 4"
                                    :y="(relapse.edssBeforeY + relapse.edssAfterY) / 2 - 9"
                                    width="42"
                                    height="18"
                                    rx="4"
                                    class="fill-card stroke-border shadow-xs"
                                    stroke-width="0.8"
                                />
                                <text
                                    :x="relapse.x + 25"
                                    :y="(relapse.edssBeforeY + relapse.edssAfterY) / 2 + 3"
                                    text-anchor="middle"
                                    class="text-[9px] font-mono font-bold"
                                    :class="relapse.edssDelta !== null && relapse.edssDelta > 0 ? 'fill-rose-600' : 'fill-emerald-600'"
                                >
                                    {{ relapse.edssDelta !== null && relapse.edssDelta > 0 ? `+${relapse.edssDelta}` : `${relapse.edssDelta}` }}
                                </text>
                            </g>

                            <!-- Top Pin / Banner representing the acute Relapse -->
                            <g :transform="`translate(${relapse.x}, ${relapse.stemTopY})`">
                                <!-- Marker Capsule -->
                                <rect
                                    :x="-38"
                                    y="0"
                                    width="76"
                                    height="22"
                                    rx="6"
                                    :fill="relapse.recoveryStyle.color"
                                    fill-opacity="0.18"
                                    :stroke="relapse.recoveryStyle.color"
                                    stroke-width="1.3"
                                    class="transition-all duration-200 group-hover:scale-105"
                                    :class="activeSelection?.type === 'relapse' && activeSelection.data.id === relapse.id ? 'stroke-[2] shadow-sm' : ''"
                                />

                                <!-- Relapse Index & Flame icon -->
                                <text
                                    x="0"
                                    y="14"
                                    text-anchor="middle"
                                    class="text-[10px] font-mono font-bold"
                                    :fill="relapse.recoveryStyle.color"
                                >
                                    Poussée #{{ relapse.index }}
                                </text>

                                <!-- Corticotherapy Pill Icon Badge -->
                                <g v-if="relapse.hasCorticoids" transform="translate(26, -6)">
                                    <circle cx="5" cy="5" r="6.5" class="fill-sky-500 stroke-card" stroke-width="1.2" />
                                    <text x="5" y="8" text-anchor="middle" class="text-[7px] font-bold fill-white font-mono">S</text>
                                </g>
                            </g>

                            <!-- Date Label below the baseline -->
                            <text
                                :x="relapse.x"
                                :y="relapse.baselineY + 16"
                                text-anchor="middle"
                                class="text-[10px] font-mono font-semibold transition-colors"
                                :class="activeSelection?.type === 'relapse' && activeSelection.data.id === relapse.id
                                    ? 'fill-primary font-bold'
                                    : 'fill-foreground group-hover:fill-primary'"
                            >
                                {{ relapse.raw.start_date || '—' }}
                            </text>

                            <!-- Sévérité sub-label -->
                            <text
                                :x="relapse.x"
                                :y="relapse.baselineY + 28"
                                text-anchor="middle"
                                class="text-[8.5px] font-medium"
                                :fill="relapse.recoveryStyle.color"
                            >
                                {{ relapse.severityBadge.label }}
                            </text>

                            <!-- Invisible wide click hitbox -->
                            <rect
                                :x="relapse.x - 25"
                                :y="relapse.stemTopY"
                                width="50"
                                :height="combinedTimelineData.plotHeight + 40"
                                fill="transparent"
                            />
                        </g>

                        <!-- -------------------------------------------------- -->
                        <!-- EVALUATED EDSS POINTS (Nodes on the Step-Curve)   -->
                        <!-- -------------------------------------------------- -->
                        <g
                            v-for="pt in combinedTimelineData.mappedEdssPoints"
                            :key="`edss-node-${pt.id}`"
                            class="cursor-pointer group"
                            @click="selectedItem = { type: 'edss', data: pt }"
                        >
                            <!-- Halo when selected -->
                            <circle
                                v-if="activeSelection?.type === 'edss' && activeSelection.data.id === pt.id"
                                :cx="pt.x"
                                :cy="pt.y"
                                r="12"
                                fill="#6366f1"
                                fill-opacity="0.25"
                                class="animate-pulse"
                            />

                            <!-- Outer node circle -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                :r="activeSelection?.type === 'edss' && activeSelection.data.id === pt.id ? 7 : 5"
                                fill="#ffffff"
                                stroke="#6366f1"
                                stroke-width="2.5"
                                class="transition-all duration-200 group-hover:scale-125"
                            />

                            <!-- Score badge tag directly above/near point -->
                            <g :transform="`translate(${pt.x}, ${pt.y - 12})`">
                                <rect
                                    :x="-16"
                                    :y="-14"
                                    width="32"
                                    height="15"
                                    rx="4"
                                    class="fill-indigo-600 text-white shadow-2xs"
                                />
                                <text
                                    x="0"
                                    y="-3"
                                    text-anchor="middle"
                                    class="text-[9px] font-mono font-bold fill-white"
                                >
                                    {{ pt.score.toFixed(1) }}
                                </text>
                            </g>

                            <!-- Hitbox -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="16"
                                fill="transparent"
                            />
                        </g>
                    </svg>
                </div>

                <!-- Comprehensive Chart Legend -->
                <div class="flex items-center justify-between flex-wrap gap-2 text-xs pt-1 border-t border-border/50 text-muted-foreground">
                    <div class="flex items-center gap-4 flex-wrap">
                        <span class="text-[11px] font-medium text-foreground">Repères cliniques :</span>
                        
                        <!-- EDSS Curve -->
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-1 bg-indigo-500 rounded-full inline-block"></span>
                            <span class="text-[11px] text-indigo-700 dark:text-indigo-400 font-medium">Courbe EDSS (0 à 10)</span>
                        </div>

                        <!-- Complete -->
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span class="text-[11px]">Poussée Récup. Complète</span>
                        </div>

                        <!-- Incomplete -->
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-amber-500 inline-block"></span>
                            <span class="text-[11px]">Poussée Incomplète</span>
                        </div>

                        <!-- Sequelae -->
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-rose-500 inline-block"></span>
                            <span class="text-[11px]">Poussée Avec Séquelles</span>
                        </div>

                        <!-- Corticotherapy -->
                        <div class="flex items-center gap-1.5">
                            <span class="size-3 rounded-full bg-sky-500 text-white text-[8px] font-bold font-mono inline-flex items-center justify-center">S</span>
                            <span class="text-[11px]">Solumédrol</span>
                        </div>
                    </div>

                    <span class="text-[11px] italic">
                        Cliquez sur une poussée ou un point EDSS pour inspecter le contexte clinique
                    </span>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- VIEW 2: DYNAMIC WAVE CURVE (Preserved)                         -->
            <!-- ============================================================== -->
            <div v-else-if="activeView === 'curve'" class="space-y-4">
                <div v-if="chronologicalChartData" class="w-full overflow-x-auto rounded-lg bg-card/60 border border-border/60 p-2">
                    <svg
                        :viewBox="`0 0 ${chronologicalChartData.width} ${chronologicalChartData.height}`"
                        class="w-full min-w-[650px] h-48 select-none overflow-visible"
                    >
                        <defs>
                            <linearGradient id="relapseHealthcareGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#0d9488" stop-opacity="0.32" />
                                <stop offset="50%" stop-color="#06b6d4" stop-opacity="0.14" />
                                <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.0" />
                            </linearGradient>

                            <pattern id="relapseGrid" width="40" height="20" patternUnits="userSpaceOnUse">
                                <line x1="0" y1="20" x2="40" y2="20" stroke="currentColor" stroke-width="1" class="text-border/25" />
                            </pattern>
                        </defs>

                        <rect x="30" y="20" width="740" height="140" fill="url(#relapseGrid)" />

                        <!-- Reference Lines -->
                        <line x1="40" y1="36" x2="760" y2="36" stroke="currentColor" stroke-dasharray="3 3" class="text-rose-500/30" />
                        <text x="35" y="40" text-anchor="end" class="text-[9px] fill-rose-500 font-medium">Séquelles</text>

                        <line x1="40" y1="60" x2="760" y2="60" stroke="currentColor" stroke-dasharray="3 3" class="text-amber-500/30" />
                        <text x="35" y="64" text-anchor="end" class="text-[9px] fill-amber-500 font-medium">Incomplète</text>

                        <line x1="40" y1="82" x2="760" y2="82" stroke="currentColor" stroke-dasharray="3 3" class="text-emerald-500/30" />
                        <text x="35" y="86" text-anchor="end" class="text-[9px] fill-emerald-500 font-medium">Complète</text>

                        <!-- Baseline -->
                        <line x1="40" :y1="chronologicalChartData.baselineY" x2="760" :y2="chronologicalChartData.baselineY" stroke="currentColor" stroke-width="1.5" class="text-border" />
                        <text x="35" :y="chronologicalChartData.baselineY + 4" text-anchor="end" class="text-[9px] fill-muted-foreground font-mono">Ligne de base</text>

                        <!-- Shaded Area Under Curve -->
                        <path :d="chronologicalChartData.areaPath" fill="url(#relapseHealthcareGradient)" />

                        <!-- Main Curve -->
                        <path
                            :d="chronologicalChartData.curvePath"
                            fill="none"
                            stroke="#0d9488"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="transition-all duration-300"
                        />

                        <!-- Relapse Markers -->
                        <g
                            v-for="pt in chronologicalChartData.points"
                            :key="pt.id"
                            class="cursor-pointer group"
                            @click="selectedItem = { type: 'relapse', data: pt.raw, index: pt.index }"
                        >
                            <line
                                :x1="pt.x"
                                :y1="pt.y"
                                :x2="pt.x"
                                :y2="pt.baselineY"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-dasharray="2 2"
                                :class="activeSelection?.type === 'relapse' && activeSelection.data.id === pt.id ? 'text-primary' : 'text-border group-hover:text-primary/70'"
                            />

                            <!-- Interval pill -->
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

                            <!-- Halo -->
                            <circle
                                v-if="activeSelection?.type === 'relapse' && activeSelection.data.id === pt.id"
                                :cx="pt.x"
                                :cy="pt.y"
                                r="11"
                                :fill="pt.recoveryStyle.color"
                                fill-opacity="0.22"
                                class="animate-pulse"
                            />

                            <!-- Peak Circle -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                :r="activeSelection?.type === 'relapse' && activeSelection.data.id === pt.id ? 7 : 5.5"
                                :fill="pt.recoveryStyle.color"
                                stroke="#ffffff"
                                stroke-width="2"
                                class="transition-all duration-200 group-hover:scale-125"
                            />

                            <!-- Corticoids -->
                            <g v-if="pt.hasCorticoids" :transform="`translate(${pt.x - 5}, ${pt.y - 20})`">
                                <circle cx="5" cy="5" r="7" class="fill-sky-500/20 stroke-sky-500" stroke-width="1" />
                                <text x="5" y="8" text-anchor="middle" class="text-[8px] font-bold fill-sky-700 dark:fill-sky-300 font-mono">S</text>
                            </g>

                            <!-- Date Label -->
                            <text
                                :x="pt.x"
                                :y="pt.baselineY + 18"
                                text-anchor="middle"
                                class="text-[11px] font-mono font-semibold transition-colors"
                                :class="activeSelection?.type === 'relapse' && activeSelection.data.id === pt.id ? 'fill-primary font-bold' : 'fill-foreground group-hover:fill-primary'"
                            >
                                {{ pt.date }}
                            </text>

                            <!-- Subtext -->
                            <text
                                :x="pt.x"
                                :y="pt.baselineY + 32"
                                text-anchor="middle"
                                class="text-[9px] font-mono fill-muted-foreground"
                            >
                                Poussée #{{ pt.index }} <tspan v-if="pt.duration">({{ pt.duration }}j)</tspan>
                            </text>

                            <circle :cx="pt.x" :cy="pt.y" r="18" fill="transparent" />
                        </g>
                    </svg>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- VIEW 3: ANNUAL DISTRIBUTION HISTOGRAM (ARR)                   -->
            <!-- ============================================================== -->
            <div v-else-if="activeView === 'bars'" class="space-y-4">
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

                    <div class="w-full overflow-x-auto">
                        <svg viewBox="0 0 720 180" class="w-full min-w-[550px] h-44 overflow-visible select-none">
                            <defs>
                                <linearGradient id="annualBarGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#0d9488" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#0f766e" stop-opacity="0.75" />
                                </linearGradient>
                            </defs>

                            <line x1="30" y1="125" x2="690" y2="125" stroke="currentColor" stroke-width="1.5" class="text-border" />

                            <g v-for="item in annualDistributionData.items" :key="item.id" class="group">
                                <g v-if="item.type === 'year'">
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
                                    <circle v-else :cx="item.x" cy="125" r="3.5" class="fill-muted-foreground/40" />

                                    <text
                                        v-if="(item.count ?? 0) > 0"
                                        :x="item.x"
                                        :y="125 - ((item.count ?? 0) / annualDistributionData.maxCount) * 80 - 7"
                                        text-anchor="middle"
                                        class="text-[12px] font-mono font-bold fill-foreground"
                                    >
                                        {{ item.count }}
                                    </text>

                                    <text
                                        :x="item.x"
                                        y="146"
                                        text-anchor="middle"
                                        class="text-[12px] font-mono font-bold fill-foreground group-hover:fill-teal-600 transition-colors"
                                    >
                                        {{ item.year }}
                                    </text>

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

                                <g v-else-if="item.type === 'gap'">
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
                                    <rect
                                        :x="item.x - 65"
                                        y="112"
                                        width="130"
                                        height="26"
                                        rx="13"
                                        class="fill-card stroke-teal-500/40 shadow-xs"
                                        stroke-width="1.2"
                                    />
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

            <!-- ============================================================== -->
            <!-- CLINICAL DETAIL INSPECTOR PANEL (Relapse or EDSS Event)        -->
            <!-- ============================================================== -->
            <!-- CASE A: RELAPSE CLINICAL INSPECTION -->
            <div
                v-if="activeSelection?.type === 'relapse'"
                class="rounded-xl border p-4 bg-card shadow-2xs transition-all duration-200"
                :class="getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).border"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-border/60">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="p-2 rounded-lg"
                            :class="getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).bg"
                        >
                            <Flame class="size-4" :class="getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).text" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-foreground">
                                    Poussée #{{ activeSelection.index }} du {{ activeSelection.data.start_date || 'Date non précisée' }}
                                </h4>
                                <Badge
                                    :variant="activeSelection.data.is_before_diagnosis ? 'outline' : 'secondary'"
                                    class="text-[10px] font-normal"
                                >
                                    {{ activeSelection.data.is_before_diagnosis ? 'Inaugurale' : 'Sous suivi' }}
                                </Badge>
                                <!-- Sévérité badge -->
                                <span
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold font-mono"
                                    :class="getSeverityBadge(activeSelection.data.severity).color"
                                >
                                    {{ getSeverityBadge(activeSelection.data.severity).label }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                <span v-if="activeSelection.data.duration_days">Durée : {{ activeSelection.data.duration_days }} jours</span>
                                <span v-if="activeSelection.data.end_date"> • Résolution vers le {{ activeSelection.data.end_date }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Recovery & Hospitalization Badges -->
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                            :class="[
                                getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).bg,
                                getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).text,
                                getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).border
                            ]"
                        >
                            {{ getRecoveryStyle(activeSelection.data.clinical_recovery, !!activeSelection.data.sequelae).label }}
                        </span>
                        <Badge
                            v-if="activeSelection.data.hospitalization_required"
                            variant="destructive"
                            class="text-[11px]"
                        >
                            Hospitalisation
                        </Badge>
                    </div>
                </div>

                <!-- Longitudinal EDSS Impact of Relapse Strip -->
                <div class="p-3 rounded-lg bg-muted/40 border border-border/60 my-3">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <TrendingUp class="size-3.5 text-indigo-600" />
                            <span class="font-semibold text-foreground">Évaluation du handicap EDSS lié à la poussée :</span>
                        </div>
                        <span v-if="activeSelection.data.edss_evaluation_date" class="text-[11px] text-muted-foreground font-mono">
                            Évalué le {{ activeSelection.data.edss_evaluation_date }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs">
                        <!-- EDSS Avant -->
                        <div class="p-2 rounded-md bg-background border border-border/70">
                            <span class="text-[10px] text-muted-foreground block">EDSS Avant Poussée</span>
                            <span class="font-mono font-bold text-sm text-foreground">
                                {{ activeSelection.data.edss_before_relapse !== null && activeSelection.data.edss_before_relapse !== undefined ? activeSelection.data.edss_before_relapse : 'Non documenté' }}
                            </span>
                        </div>

                        <!-- EDSS Après -->
                        <div class="p-2 rounded-md bg-background border border-border/70">
                            <span class="text-[10px] text-muted-foreground block">EDSS Après Récupération</span>
                            <span class="font-mono font-bold text-sm text-foreground">
                                {{ activeSelection.data.edss_after_relapse !== null && activeSelection.data.edss_after_relapse !== undefined ? activeSelection.data.edss_after_relapse : 'Non documenté' }}
                            </span>
                        </div>

                        <!-- Impact Net -->
                        <div class="p-2 rounded-md bg-background border border-border/70">
                            <span class="text-[10px] text-muted-foreground block">Conséquence sur le Handicap</span>
                            <div class="font-mono font-bold text-xs flex items-center gap-1">
                                <template v-if="activeSelection.data.edss_before_relapse !== null && activeSelection.data.edss_after_relapse !== null">
                                    <span :class="Number(activeSelection.data.edss_after_relapse) > Number(activeSelection.data.edss_before_relapse) ? 'text-rose-600' : 'text-emerald-600'">
                                        {{ Number(activeSelection.data.edss_after_relapse) - Number(activeSelection.data.edss_before_relapse) > 0 ? '+' : '' }}{{ (Number(activeSelection.data.edss_after_relapse) - Number(activeSelection.data.edss_before_relapse)).toFixed(1) }} EDSS
                                    </span>
                                    <span class="text-[10px] font-normal text-muted-foreground">
                                        {{ Number(activeSelection.data.edss_after_relapse) > Number(activeSelection.data.edss_before_relapse) ? '(Aggravation résiduelle)' : '(Récupération complète)' }}
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground font-normal italic">
                                    Évaluation comparative indisponible
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Symptoms & Treatments Detail Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <!-- Left: Clinical symptoms -->
                    <div class="p-3 rounded-lg bg-muted/40 border border-border/50 space-y-1">
                        <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide block">
                            Manifestations Cliniques & Conséquences
                        </span>
                        <p class="text-xs font-medium text-foreground">
                            {{ activeSelection.data.symptoms }}
                        </p>
                        <div v-if="activeSelection.data.sequelae" class="text-xs text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1 pt-1">
                            <AlertCircle class="size-3.5 shrink-0" />
                            <span>Séquelles résiduelles : {{ activeSelection.data.sequelae }}</span>
                        </div>
                    </div>

                    <!-- Right: Treatment Administered -->
                    <div class="p-3 rounded-lg bg-muted/40 border border-border/50 space-y-1">
                        <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide block">
                            Prise en Charge & Traitement Administré
                        </span>
                        <div v-if="activeSelection.data.treatment_administered" class="flex items-center gap-2">
                            <Pill class="size-3.5 text-sky-600 shrink-0" />
                            <span class="font-medium text-foreground">
                                {{ activeSelection.data.treatment_administered }}
                            </span>
                            <span v-if="activeSelection.data.administration_route" class="text-muted-foreground text-[11px]">
                                ({{ activeSelection.data.administration_route }})
                            </span>
                        </div>
                        <div v-else class="text-muted-foreground italic">
                            Aucun traitement d'urgence documenté
                        </div>
                        <p v-if="activeSelection.data.notes" class="text-[11px] text-muted-foreground pt-1">
                            Note : {{ activeSelection.data.notes }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- CASE B: EDSS CLINICAL EVALUATION INSPECTION -->
            <div
                v-else-if="activeSelection?.type === 'edss'"
                class="rounded-xl border border-indigo-500/40 p-4 bg-indigo-500/5 shadow-2xs transition-all duration-200"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-border/60">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-indigo-500/15 text-indigo-600 dark:text-indigo-400">
                            <Stethoscope class="size-4" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-foreground">
                                    Évaluation EDSS : {{ activeSelection.data.score.toFixed(1) }} / 10.0
                                </h4>
                                <Badge variant="outline" class="text-[10px] font-mono bg-background text-indigo-700 dark:text-indigo-300 border-indigo-500/30">
                                    {{ activeSelection.data.sourceLabel }}
                                </Badge>
                            </div>
                            <p class="text-xs text-muted-foreground font-mono">
                                Date réelle de consultation / bilan : {{ activeSelection.data.dateFormatted }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground">Examinateur :</span>
                        <span class="text-xs font-semibold text-foreground">
                            {{ activeSelection.data.evaluator || 'Praticien hospitalier' }}
                        </span>
                    </div>
                </div>

                <div class="pt-3 text-xs space-y-2">
                    <p v-if="activeSelection.data.notes" class="text-xs text-foreground">
                        {{ activeSelection.data.notes }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground italic">
                        Aucune remarque particulière documentée pour cette évaluation clinique.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
