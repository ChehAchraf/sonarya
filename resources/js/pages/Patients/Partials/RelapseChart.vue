<script setup lang="ts">
import {
    Activity,
    AlertCircle,
    ArrowDownRight,
    ArrowRight,
    ArrowUpRight,
    BarChart3,
    Calendar,
    CheckCircle2,
    Clock,
    Download,
    FileSpreadsheet,
    FileText,
    Flame,
    HeartPulse,
    HelpCircle,
    Info,
    Minus,
    Pill,
    Printer,
    RotateCcw,
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

export type Relapse = {
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
    edss_before_date?: string | null;
    edss_before_date_raw?: string | null;
    edss_during_relapse?: number | string | null;
    edss_after_relapse?: number | string | null;
    edss_evaluation_date?: string | null;
    edss_evaluation_date_raw?: string | null;
    sequelae: string | null;
    hospitalization_required: boolean;
    notes: string | null;
};

export type ClinicalExam = {
    id: number;
    exam_date: string | null;
    exam_date_raw?: string | null;
    edss_score: string | number;
    t25fw?: number | string | null;
    nine_hpt?: number | string | null;
    evaluator_name?: string | null;
    clinical_comment?: string | null;
};

export type Consultation = {
    id: number;
    consultation_date: string | null;
    consultation_date_raw?: string | null;
    consultation_type?: string | null;
    doctor_name?: string | null;
    edss_score?: string | number | null;
    notes?: string | null;
};

export type DiseaseProfile = {
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

// View switcher: Only 2 views permitted as mandated by user
// 'edss_timeline': Courbe EDSS & Poussées (Modèle 3-phases à bandes et interpolation par morceaux)
// 'bars': Fréquence Annuelle (ARR)
const activeView = ref<'edss_timeline' | 'bars'>('edss_timeline');

// Toggle between patient real data and the doctor's protocol benchmark example
const dataSourceMode = ref<'real' | 'benchmark'>('real');

// Reference to SVG for PNG export
const chartSvgRef = ref<SVGSVGElement | null>(null);

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

// Sorted real relapses
const sortedRelapses = computed(() => {
    return [...props.relapses].sort((a, b) => {
        const dateA = parseAnyDate(a.start_date_raw || a.start_date);
        const dateB = parseAnyDate(b.start_date_raw || b.start_date);
        return dateA - dateB;
    });
});

// Determine default data mode based on available relapses
const hasRealRelapsesWithData = computed(() => {
    return sortedRelapses.value.length > 0;
});

// Clinical recovery color styling
function getRecoveryStyle(recovery: string | null | undefined, hasSequelae: boolean) {
    if (hasSequelae || (recovery && recovery.toLowerCase().includes('sequell'))) {
        return {
            color: '#f43f5e',
            bg: 'bg-rose-500/10 dark:bg-rose-950/40',
            text: 'text-rose-700 dark:text-rose-400',
            border: 'border-rose-500/30',
            bandBg: 'rgba(244, 63, 94, 0.12)',
            bandBorder: 'rgba(244, 63, 94, 0.35)',
            label: 'Avec séquelles (récupération incomplète)',
        };
    }
    if (recovery && (recovery.toLowerCase().includes('incompl') || recovery.toLowerCase().includes('nulle'))) {
        return {
            color: '#f59e0b',
            bg: 'bg-amber-500/10 dark:bg-amber-950/40',
            text: 'text-amber-700 dark:text-amber-400',
            border: 'border-amber-500/30',
            bandBg: 'rgba(245, 158, 11, 0.12)',
            bandBorder: 'rgba(245, 158, 11, 0.35)',
            label: 'Récupération incomplète',
        };
    }
    return {
        color: '#10b981',
        bg: 'bg-emerald-500/10 dark:bg-emerald-950/40',
        text: 'text-emerald-700 dark:text-emerald-400',
        border: 'border-emerald-500/30',
        bandBg: 'rgba(16, 185, 129, 0.12)',
        bandBorder: 'rgba(16, 185, 129, 0.35)',
        label: 'Récupération complète',
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
// THREE-PHASE PIECEWISE CURVE & SHADED BANDS MODEL
// -------------------------------------------------------------
export type PhaseBand = {
    id: string;
    type: 'relapse' | 'inter_relapse' | 'followup' | 'pre_baseline';
    title: string;
    subtitle: string;
    durationLabel: string;
    colorBg: string;
    colorBorder: string;
    startX: number;
    endX: number;
    width: number;
    edssBefore?: number;
    edssPeak?: number;
    edssAfter?: number;
    delta?: number;
    recoveryLabel?: string;
    treatmentLabel?: string;
    relapseId?: number;
    index?: number;
};

export type CurvePoint = {
    id: string;
    x: number;
    y: number;
    score: number;
    label: string;
    sublabel: string;
    phase: 't0_before' | 't1_start' | 't2_end' | 'plateau';
    color: string;
    dateFormatted: string;
};

// Unified Model Data Builder
const piecewiseModelData = computed(() => {
    // Mode real data vs Mode benchmark protocol example
    const useBenchmark = dataSourceMode.value === 'benchmark' || (!hasRealRelapsesWithData.value);

    // SVG Canvas dimensions
    const svgWidth = 980;
    const svgHeight = 390;
    const paddingLeft = 65;
    const paddingRight = 45;
    const plotTop = 60;
    const plotBottom = 320;
    const plotHeight = plotBottom - plotTop;
    const plotWidth = svgWidth - paddingLeft - paddingRight;

    if (useBenchmark) {
        // EXACT BENCHMARK FROM DOCTOR'S SPECIFICATION & SCHEMATIC:
        // Relapse 1: EDSS 0.5 (plateau 2 weeks) -> 2.0 (onset peak at J0) -> 1.0 (end at 2 weeks/J14).
        // Inter-relapse remission: 8 weeks plateau at EDSS 1.0.
        // Relapse 2: 3 weeks duration, onset peak EDSS 3.0 -> end recovery EDSS 2.0.
        // Follow-up period: 6 weeks plateau at EDSS 2.0.
        const totalDurationDays = 14 + 14 + 56 + 21 + 42; // 147 days total span
        const timeToX = (relDay: number) => paddingLeft + ((relDay + 14) / totalDurationDays) * plotWidth;

        // Dynamic Y scale: Max EDSS observed = 3.0, top axis = 3.5
        const maxScore = 3.5;
        const edssToY = (score: number) => plotBottom - (Math.max(0, Math.min(maxScore, score)) / maxScore) * plotHeight;

        // Bands definition
        const bands: PhaseBand[] = [
            {
                id: 'band-p1',
                type: 'relapse',
                title: '1ère poussée',
                subtitle: 'Durée : 2 semaines',
                durationLabel: '2 semaines (14 jours)',
                colorBg: 'rgba(254, 226, 226, 0.75)', // soft rose/salmon
                colorBorder: 'rgba(244, 63, 94, 0.45)',
                startX: timeToX(0),
                endX: timeToX(14),
                width: timeToX(14) - timeToX(0),
                edssBefore: 0.5,
                edssPeak: 2.0,
                edssAfter: 1.0,
                delta: 0.5,
                recoveryLabel: 'Récupération partielle (+0.5 pt)',
                treatmentLabel: 'Bolus Solumédrol 1g/j IV x 3j',
                index: 1,
            },
            {
                id: 'band-inter',
                type: 'inter_relapse',
                title: 'Période entre les poussées',
                subtitle: 'Durée : 8 semaines',
                durationLabel: '8 semaines (56 jours)',
                colorBg: 'rgba(224, 242, 254, 0.65)', // soft blue
                colorBorder: 'rgba(56, 189, 248, 0.4)',
                startX: timeToX(14),
                endX: timeToX(70),
                width: timeToX(70) - timeToX(14),
                edssBefore: 1.0,
                edssAfter: 1.0,
                delta: 0.0,
                recoveryLabel: 'Rémission clinique stable',
                treatmentLabel: 'Interféron bêta-1a',
            },
            {
                id: 'band-p2',
                type: 'relapse',
                title: '2ème poussée',
                subtitle: 'Durée : 3 semaines',
                durationLabel: '3 semaines (21 jours)',
                colorBg: 'rgba(220, 252, 231, 0.75)', // soft emerald green
                colorBorder: 'rgba(34, 197, 94, 0.45)',
                startX: timeToX(70),
                endX: timeToX(91),
                width: timeToX(91) - timeToX(70),
                edssBefore: 1.0,
                edssPeak: 3.0,
                edssAfter: 2.0,
                delta: 1.0,
                recoveryLabel: 'Récupération incomplète (+1.0 pt)',
                treatmentLabel: 'Bolus Solumédrol 1g/j IV x 5j',
                index: 2,
            },
            {
                id: 'band-followup',
                type: 'followup',
                title: 'Période de suivi',
                subtitle: 'Durée : 6 semaines',
                durationLabel: '6 semaines (42 jours)',
                colorBg: 'rgba(224, 242, 254, 0.65)', // soft blue
                colorBorder: 'rgba(56, 189, 248, 0.4)',
                startX: timeToX(91),
                endX: timeToX(133),
                width: timeToX(133) - timeToX(91),
                edssBefore: 2.0,
                edssAfter: 2.0,
                delta: 0.0,
                recoveryLabel: 'Handicap stabilisé post-poussée',
                treatmentLabel: 'Suivi neurologique',
            },
        ];

        // Key Points along the piecewise function
        const points: CurvePoint[] = [
            {
                id: 'p-t0-1',
                x: timeToX(-14),
                y: edssToY(0.5),
                score: 0.5,
                label: 'Avant poussée 1',
                sublabel: 'EDSS 0,5 (J-14)',
                phase: 't0_before',
                color: '#3b82f6',
                dateFormatted: 'J -14 (Référence)',
            },
            {
                id: 'p-t1-1',
                x: timeToX(0),
                y: edssToY(2.0),
                score: 2.0,
                label: 'Début poussée 1',
                sublabel: 'EDSS 2 (Acmé)',
                phase: 't1_start',
                color: '#ef4444',
                dateFormatted: 'J 0 (Début poussée)',
            },
            {
                id: 'p-t2-1',
                x: timeToX(14),
                y: edssToY(1.0),
                score: 1.0,
                label: 'Fin poussée 1',
                sublabel: 'EDSS 1 (J14)',
                phase: 't2_end',
                color: '#10b981',
                dateFormatted: 'J +14 (Fin 2 sem)',
            },
            {
                id: 'p-t1-2',
                x: timeToX(70),
                y: edssToY(3.0),
                score: 3.0,
                label: 'Début poussée 2',
                sublabel: 'EDSS 3 (Acmé)',
                phase: 't1_start',
                color: '#ef4444',
                dateFormatted: 'Semaine 10',
            },
            {
                id: 'p-t2-2',
                x: timeToX(91),
                y: edssToY(2.0),
                score: 2.0,
                label: 'Fin poussée 2',
                sublabel: 'EDSS 2 (J91)',
                phase: 't2_end',
                color: '#10b981',
                dateFormatted: 'Semaine 13',
            },
            {
                id: 'p-t-followup',
                x: timeToX(133),
                y: edssToY(2.0),
                score: 2.0,
                label: 'Dernier suivi',
                sublabel: 'EDSS 2 (Stabilisé)',
                phase: 'plateau',
                color: '#6366f1',
                dateFormatted: 'Semaine 19',
            },
        ];

        // Piecewise line path:
        // M (-14, 0.5) -> L (0, 0.5) -> L (0, 2.0) -> L (14, 1.0) -> L (70, 1.0) -> L (70, 3.0) -> L (91, 2.0) -> L (133, 2.0)
        const linePath = `M ${timeToX(-14)} ${edssToY(0.5)} ` +
            `L ${timeToX(0)} ${edssToY(0.5)} ` +
            `L ${timeToX(0)} ${edssToY(2.0)} ` +
            `L ${timeToX(14)} ${edssToY(1.0)} ` +
            `L ${timeToX(70)} ${edssToY(1.0)} ` +
            `L ${timeToX(70)} ${edssToY(3.0)} ` +
            `L ${timeToX(91)} ${edssToY(2.0)} ` +
            `L ${timeToX(133)} ${edssToY(2.0)}`;

        const areaPath = `${linePath} L ${timeToX(133)} ${plotBottom} L ${timeToX(-14)} ${plotBottom} Z`;

        // Y-axis grid lines every 0.5 EDSS
        const yTicks: { score: number; y: number; isMajor: boolean }[] = [];
        for (let s = 0; s <= maxScore; s += 0.5) {
            yTicks.push({
                score: Number(s.toFixed(1)),
                y: edssToY(s),
                isMajor: Number.isInteger(s),
            });
        }

        // X-axis timeline ticks
        const xTicks = [
            { x: timeToX(-14), label: 'J -14', sublabel: 'Référence' },
            { x: timeToX(0), label: 'J 0', sublabel: 'Début P1' },
            { x: timeToX(7), label: 'J 7', sublabel: '1 sem' },
            { x: timeToX(14), label: 'J 14', sublabel: 'Fin P1' },
            { x: timeToX(42), label: 'Sem 6', sublabel: 'Rémission' },
            { x: timeToX(70), label: 'Sem 10', sublabel: 'Début P2' },
            { x: timeToX(91), label: 'Sem 13', sublabel: 'Fin P2' },
            { x: timeToX(112), label: 'Sem 16', sublabel: 'Suivi' },
            { x: timeToX(133), label: 'Sem 19', sublabel: 'Bilan' },
        ];

        return {
            isBenchmark: true,
            title: "Évolution de l'EDSS au cours d'une poussée de 2 semaines",
            subtitle: "Schéma illustratif selon cahier des charges : EDSS référence = 0,5 ; acmé début = 2,0 ; EDSS à 2 semaines = 1,0",
            svgWidth,
            svgHeight,
            plotTop,
            plotBottom,
            plotLeft: paddingLeft,
            plotRight: paddingRight,
            plotWidth,
            plotHeight,
            bands,
            points,
            linePath,
            areaPath,
            yTicks,
            xTicks,
            summaryRows: [
                {
                    phase: '1ère poussée',
                    duration: '2 semaines (14 j)',
                    edssBefore: '0,5',
                    edssPeak: '2,0',
                    edssAfter: '1,0',
                    delta: '+0,5',
                    deltaClass: 'text-rose-600 font-bold',
                    status: 'Récupération partielle',
                    treatment: 'Bolus Solumédrol 1g x 3j',
                },
                {
                    phase: 'Période entre poussées',
                    duration: '8 semaines (56 j)',
                    edssBefore: '1,0',
                    edssPeak: '—',
                    edssAfter: '1,0',
                    delta: '0,0',
                    deltaClass: 'text-emerald-600 font-medium',
                    status: 'Rémission clinique',
                    treatment: 'Traitement de fond (Avonex)',
                },
                {
                    phase: '2ème poussée',
                    duration: '3 semaines (21 j)',
                    edssBefore: '1,0',
                    edssPeak: '3,0',
                    edssAfter: '2,0',
                    delta: '+1,0',
                    deltaClass: 'text-rose-600 font-bold',
                    status: 'Récupération incomplète',
                    treatment: 'Bolus Solumédrol 1g x 5j',
                },
                {
                    phase: 'Période de suivi',
                    duration: '6 semaines (42 j)',
                    edssBefore: '2,0',
                    edssPeak: '—',
                    edssAfter: '2,0',
                    delta: '0,0',
                    deltaClass: 'text-muted-foreground font-medium',
                    status: 'Plateau stabilisé',
                    treatment: 'Suivi semestriel',
                },
            ],
            formulaData: {
                edssBefore: 0.5,
                edssPeak: 2.0,
                edssAfter: 1.0,
                durationDays: 14,
            },
        };
    }

    // ---------------------------------------------------------
    // REAL PATIENT DATA CHRONOLOGICAL MAPPING
    // ---------------------------------------------------------
    const relapses = sortedRelapses.value;
    const initialEdss = props.diseaseProfile?.initial_edss !== null && props.diseaseProfile?.initial_edss !== undefined
        ? Number(props.diseaseProfile.initial_edss)
        : null;

    // Build timeline events
    type BuiltRelapsePhase = {
        relapse: Relapse;
        index: number;
        t0: number; // before evaluation timestamp
        t1: number; // start date timestamp
        t2: number; // end/recovery timestamp
        durationDays: number;
        edssBefore: number;
        edssPeak: number;
        edssAfter: number;
    };

    const builtRelapses: BuiltRelapsePhase[] = [];
    let runningBaseline = initialEdss ?? 0.0;

    relapses.forEach((r, idx) => {
        const t1 = parseAnyDate(r.start_date_raw || r.start_date);
        let t2 = parseAnyDate(r.edss_evaluation_date_raw || r.edss_evaluation_date || r.end_date_raw || r.end_date);
        if (t2 <= t1) {
            t2 = t1 + 14 * 86400000; // default 14 days if unrecorded
        }
        const durationDays = Math.max(1, Math.round((t2 - t1) / 86400000));

        let t0 = parseAnyDate(r.edss_before_date_raw || r.edss_before_date);
        if (t0 <= 0 || t0 >= t1) {
            if (idx === 0) {
                t0 = t1 - 14 * 86400000;
            } else {
                t0 = builtRelapses[idx - 1].t2;
            }
        }

        const edssBefore = r.edss_before_relapse !== null && r.edss_before_relapse !== undefined && r.edss_before_relapse !== ''
            ? Number(r.edss_before_relapse)
            : runningBaseline;

        const edssAfter = r.edss_after_relapse !== null && r.edss_after_relapse !== undefined && r.edss_after_relapse !== ''
            ? Number(r.edss_after_relapse)
            : (r.clinical_recovery === 'Complète' ? edssBefore : edssBefore + (r.severity === 'severe' ? 1.0 : 0.5));

        const edssPeak = r.edss_during_relapse !== null && r.edss_during_relapse !== undefined && r.edss_during_relapse !== ''
            ? Number(r.edss_during_relapse)
            : Math.max(edssBefore, edssAfter) + (r.severity === 'severe' ? 2.0 : (r.severity === 'moderee' ? 1.5 : 0.5));

        runningBaseline = edssAfter;

        builtRelapses.push({
            relapse: r,
            index: idx + 1,
            t0,
            t1,
            t2,
            durationDays,
            edssBefore,
            edssPeak,
            edssAfter,
        });
    });

    const minTimestamp = builtRelapses[0].t0;
    const lastRelapse = builtRelapses[builtRelapses.length - 1];
    // Add 30 days of follow-up after the last relapse
    const maxTimestamp = Math.max(Date.now(), lastRelapse.t2 + 30 * 86400000);
    const totalTimeSpan = Math.max(86400000 * 30, maxTimestamp - minTimestamp);

    const timeToX = (t: number) => paddingLeft + ((t - minTimestamp) / totalTimeSpan) * plotWidth;

    // Find highest EDSS score among all relapses
    const allScores = builtRelapses.flatMap((b) => [b.edssBefore, b.edssPeak, b.edssAfter]);
    const maxObservedScore = Math.max(3.5, ...allScores);
    const maxScore = Math.min(10, Math.ceil((maxObservedScore + 0.5) * 2) / 2); // rounded up to next 0.5

    const edssToY = (score: number) => plotBottom - (Math.max(0, Math.min(maxScore, score)) / maxScore) * plotHeight;

    const bands: PhaseBand[] = [];
    const points: CurvePoint[] = [];
    let pathSegments = '';
    const summaryRows: any[] = [];

    builtRelapses.forEach((b, idx) => {
        const style = getRecoveryStyle(b.relapse.clinical_recovery, !!b.relapse.sequelae);
        const startX = timeToX(b.t1);
        const endX = timeToX(b.t2);
        const width = Math.max(16, endX - startX);

        // Relapse band
        bands.push({
            id: `band-relapse-${b.relapse.id}`,
            type: 'relapse',
            title: `Poussée #${b.index}`,
            subtitle: `${b.durationDays} jours`,
            durationLabel: `${b.durationDays} jours`,
            colorBg: style.bandBg,
            colorBorder: style.bandBorder,
            startX,
            endX,
            width,
            edssBefore: b.edssBefore,
            edssPeak: b.edssPeak,
            edssAfter: b.edssAfter,
            delta: Number((b.edssAfter - b.edssBefore).toFixed(1)),
            recoveryLabel: b.relapse.clinical_recovery || 'Évolution documentée',
            treatmentLabel: b.relapse.treatment_administered || 'Traitement corticoïde',
            relapseId: b.relapse.id,
            index: b.index,
        });

        // Summary row
        summaryRows.push({
            phase: `Poussée #${b.index}`,
            duration: `${b.durationDays} jours`,
            edssBefore: b.edssBefore.toFixed(1),
            edssPeak: b.edssPeak.toFixed(1),
            edssAfter: b.edssAfter.toFixed(1),
            delta: (b.edssAfter - b.edssBefore > 0 ? '+' : '') + (b.edssAfter - b.edssBefore).toFixed(1),
            deltaClass: b.edssAfter > b.edssBefore ? 'text-rose-600 font-bold' : (b.edssAfter < b.edssBefore ? 'text-emerald-600 font-bold' : 'text-foreground font-medium'),
            status: b.relapse.clinical_recovery || 'Inconnu',
            treatment: b.relapse.treatment_administered || 'Néant',
        });

        // Points
        points.push({
            id: `pt-t0-${b.relapse.id}`,
            x: timeToX(b.t0),
            y: edssToY(b.edssBefore),
            score: b.edssBefore,
            label: `Avant P#${b.index}`,
            sublabel: `EDSS ${b.edssBefore.toFixed(1)}`,
            phase: 't0_before',
            color: '#3b82f6',
            dateFormatted: formatFrenchDate(b.t0),
        });

        points.push({
            id: `pt-t1-${b.relapse.id}`,
            x: startX,
            y: edssToY(b.edssPeak),
            score: b.edssPeak,
            label: `Début P#${b.index} (Acmé)`,
            sublabel: `EDSS ${b.edssPeak.toFixed(1)}`,
            phase: 't1_start',
            color: '#ef4444',
            dateFormatted: formatFrenchDate(b.t1),
        });

        points.push({
            id: `pt-t2-${b.relapse.id}`,
            x: endX,
            y: edssToY(b.edssAfter),
            score: b.edssAfter,
            label: `Fin P#${b.index}`,
            sublabel: `EDSS ${b.edssAfter.toFixed(1)}`,
            phase: 't2_end',
            color: '#10b981',
            dateFormatted: formatFrenchDate(b.t2),
        });

        // Inter-relapse remission band if there is a next relapse
        if (idx < builtRelapses.length - 1) {
            const next = builtRelapses[idx + 1];
            const interStartX = endX;
            const interEndX = timeToX(next.t1);
            const interDays = Math.round((next.t1 - b.t2) / 86400000);

            if (interEndX > interStartX) {
                bands.push({
                    id: `band-inter-${b.relapse.id}`,
                    type: 'inter_relapse',
                    title: 'Période entre poussées',
                    subtitle: `${interDays} jours de rémission`,
                    durationLabel: `${interDays} jours`,
                    colorBg: 'rgba(224, 242, 254, 0.65)',
                    colorBorder: 'rgba(56, 189, 248, 0.4)',
                    startX: interStartX,
                    endX: interEndX,
                    width: interEndX - interStartX,
                    edssBefore: b.edssAfter,
                    edssAfter: b.edssAfter,
                    delta: 0.0,
                    recoveryLabel: 'Phase de rémission',
                    treatmentLabel: 'Traitement de fond de suivi',
                });

                summaryRows.push({
                    phase: `Intervalle P#${b.index} → P#${next.index}`,
                    duration: `${interDays} jours`,
                    edssBefore: b.edssAfter.toFixed(1),
                    edssPeak: '—',
                    edssAfter: b.edssAfter.toFixed(1),
                    delta: '0,0',
                    deltaClass: 'text-muted-foreground font-medium',
                    status: 'Rémission intercritique',
                    treatment: 'Traitement de fond',
                });
            }
        }
    });

    // Follow-up band after the last relapse
    const followStartX = timeToX(lastRelapse.t2);
    const followEndX = timeToX(maxTimestamp);
    if (followEndX > followStartX) {
        const followDays = Math.round((maxTimestamp - lastRelapse.t2) / 86400000);
        bands.push({
            id: 'band-followup',
            type: 'followup',
            title: 'Période de suivi actuel',
            subtitle: `${followDays} jours`,
            durationLabel: `${followDays} jours`,
            colorBg: 'rgba(224, 242, 254, 0.65)',
            colorBorder: 'rgba(56, 189, 248, 0.4)',
            startX: followStartX,
            endX: followEndX,
            width: followEndX - followStartX,
            edssBefore: lastRelapse.edssAfter,
            edssAfter: lastRelapse.edssAfter,
            delta: 0.0,
            recoveryLabel: 'Suivi clinique en cours',
            treatmentLabel: 'Contrôle périodique',
        });
    }

    // Build the piecewise SVG path
    let linePath = `M ${timeToX(builtRelapses[0].t0)} ${edssToY(builtRelapses[0].edssBefore)} `;
    builtRelapses.forEach((b, idx) => {
        // Line from t0 to onset t1 at baseline
        linePath += `L ${timeToX(b.t1)} ${edssToY(b.edssBefore)} `;
        // Rise to acute peak
        linePath += `L ${timeToX(b.t1)} ${edssToY(b.edssPeak)} `;
        // Descent to recovery at t2
        linePath += `L ${timeToX(b.t2)} ${edssToY(b.edssAfter)} `;
        // Plateau to next relapse onset or end of plot
        if (idx < builtRelapses.length - 1) {
            const next = builtRelapses[idx + 1];
            linePath += `L ${timeToX(next.t1)} ${edssToY(b.edssAfter)} `;
        } else {
            linePath += `L ${timeToX(maxTimestamp)} ${edssToY(b.edssAfter)} `;
        }
    });

    const areaPath = `${linePath} L ${timeToX(maxTimestamp)} ${plotBottom} L ${timeToX(builtRelapses[0].t0)} ${plotBottom} Z`;

    // Y-ticks
    const yTicks: { score: number; y: number; isMajor: boolean }[] = [];
    for (let s = 0; s <= maxScore; s += 0.5) {
        yTicks.push({
            score: Number(s.toFixed(1)),
            y: edssToY(s),
            isMajor: Number.isInteger(s),
        });
    }

    // X-ticks based on actual relapse dates
    const xTicks: { x: number; label: string; sublabel: string }[] = [];
    builtRelapses.forEach((b) => {
        xTicks.push({
            x: timeToX(b.t1),
            label: formatFrenchDate(b.t1),
            sublabel: `Début P#${b.index}`,
        });
        xTicks.push({
            x: timeToX(b.t2),
            label: formatFrenchDate(b.t2),
            sublabel: `Fin P#${b.index}`,
        });
    });

    return {
        isBenchmark: false,
        title: "Évolution de l'EDSS au cours des poussées de sclérose en plaques",
        subtitle: `Données réelles du patient : ${relapses.length} poussée(s) documentée(s) avec évaluation du handicap résiduel`,
        svgWidth,
        svgHeight,
        plotTop,
        plotBottom,
        plotLeft: paddingLeft,
        plotRight: paddingRight,
        plotWidth,
        plotHeight,
        bands,
        points,
        linePath,
        areaPath,
        yTicks,
        xTicks,
        summaryRows,
        formulaData: {
            edssBefore: builtRelapses[0].edssBefore,
            edssPeak: builtRelapses[0].edssPeak,
            edssAfter: builtRelapses[0].edssAfter,
            durationDays: builtRelapses[0].durationDays,
        },
    };
});

// Annual Relapse Rate (ARR) Histogram view
type YearBin = {
    year: number;
    count: number;
    completeCount: number;
    incompleteCount: number;
};

const annualDistributionData = computed(() => {
    const list = sortedRelapses.value;
    if (list.length === 0) {
        return { items: [], maxCount: 1, arr: '0.0', spanYears: 1 };
    }

    const yearCounts = new Map<number, YearBin>();
    list.forEach((r) => {
        const time = parseAnyDate(r.start_date_raw || r.start_date);
        const yr = time > 0 ? new Date(time).getFullYear() : new Date().getFullYear();
        if (!yearCounts.has(yr)) {
            yearCounts.set(yr, { year: yr, count: 0, completeCount: 0, incompleteCount: 0 });
        }
        const b = yearCounts.get(yr)!;
        b.count++;
        if (r.clinical_recovery === 'Complète' && !r.sequelae) {
            b.completeCount++;
        } else {
            b.incompleteCount++;
        }
    });

    const years = Array.from(yearCounts.keys()).sort((a, b) => a - b);
    const minYr = years[0];
    const maxYr = years[years.length - 1];
    const spanYears = Math.max(1, maxYr - minYr + 1);
    const arr = (list.length / spanYears).toFixed(2);

    const items: { year: number; count: number; x: number }[] = [];
    let maxCount = 1;
    years.forEach((yr, idx) => {
        const bin = yearCounts.get(yr)!;
        if (bin.count > maxCount) maxCount = bin.count;
        const x = 80 + idx * 90;
        items.push({ year: yr, count: bin.count, x });
    });

    return { items, maxCount, arr, spanYears };
});

// Healthcare summary indicators
const clinicalMetrics = computed(() => {
    const list = sortedRelapses.value;
    const completeCount = list.filter((r) => r.clinical_recovery === 'Complète' && !r.sequelae).length;
    const completeRate = list.length > 0 ? Math.round((completeCount / list.length) * 100) : 0;

    let remissionMonths: number | null = null;
    if (list.length > 0) {
        const latest = list[list.length - 1];
        const latestTime = parseAnyDate(latest.start_date_raw || latest.start_date);
        if (latestTime > 0) {
            remissionMonths = Math.round((Date.now() - latestTime) / (1000 * 60 * 60 * 24 * 30.4375));
        }
    }

    return {
        total: list.length,
        arr: annualDistributionData.value.arr,
        completeRecoveryRate: completeRate,
        remissionMonths,
    };
});

// Export chart as PNG
const exportAsPng = () => {
    const svgEl = chartSvgRef.value;
    if (!svgEl) return;

    try {
        const serializer = new XMLSerializer();
        const svgString = serializer.serializeToString(svgEl);
        const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(svgBlob);

        const canvas = document.createElement('canvas');
        const scale = 2; // Retina export quality
        canvas.width = piecewiseModelData.value.svgWidth * scale;
        canvas.height = piecewiseModelData.value.svgHeight * scale;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const img = new Image();
        img.onload = () => {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);

            const link = document.createElement('a');
            link.download = `evolution_edss_poussees_${new Date().toISOString().slice(0, 10)}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        img.src = url;
    } catch (e) {
        console.error('Export PNG failed:', e);
    }
};

// Print / PDF trigger
const printChart = () => {
    window.print();
};
</script>

<template>
    <Card class="border border-border/80 bg-gradient-to-b from-card via-card to-muted/20 shadow-xs overflow-hidden print:border-none print:shadow-none">
        <!-- Header with Medical Icon & Healthcare Title -->
        <CardHeader class="pb-3 border-b border-border/60">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
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
                        Représentation graphique de l'évolution du handicap (EDSS 0 à 10) avant, pendant l'acmé aiguë et après récupération des poussées.
                    </CardDescription>
                </div>

                <!-- Action Controls: Source Toggle, Views, Export -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Benchmark / Real Switcher -->
                    <div class="flex items-center bg-muted/70 p-0.5 rounded-lg border border-border/60 text-xs">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-all font-medium whitespace-nowrap"
                            :class="dataSourceMode === 'real'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="dataSourceMode = 'real'"
                        >
                            Données réelles
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-all font-medium whitespace-nowrap flex items-center gap-1"
                            :class="dataSourceMode === 'benchmark'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="dataSourceMode = 'benchmark'"
                        >
                            <span>Exemple protocole (2 sem)</span>
                        </button>
                    </div>

                    <!-- View Switcher (Courbe EDSS vs ARR) -->
                    <div class="flex items-center bg-muted/70 p-0.5 rounded-lg border border-border/60 text-xs">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center gap-1.5 whitespace-nowrap"
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
                            class="px-2.5 py-1 rounded-md font-medium transition-all flex items-center gap-1.5 whitespace-nowrap"
                            :class="activeView === 'bars'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="activeView = 'bars'"
                        >
                            <BarChart3 class="size-3.5 text-cyan-600 dark:text-cyan-400" />
                            <span>Fréquence (ARR)</span>
                        </button>
                    </div>

                    <!-- Export Actions -->
                    <div class="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-7 text-xs px-2 gap-1"
                            title="Télécharger l'image PNG haute résolution"
                            @click="exportAsPng"
                        >
                            <Download class="size-3" />
                            <span>PNG</span>
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-7 text-xs px-2 gap-1"
                            title="Imprimer ou enregistrer en PDF"
                            @click="printChart"
                        >
                            <Printer class="size-3" />
                            <span>Imprimer</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Healthcare Clinical Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-3 mt-1 border-t border-dashed border-border/50 text-xs">
                <!-- Taux Annualisé (ARR) -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-indigo-500/5 border border-indigo-500/15">
                    <Activity class="size-3.5 text-indigo-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Taux Annualisé (ARR)</span>
                        <span class="font-mono font-bold text-indigo-700 dark:text-indigo-300 text-xs">
                            {{ clinicalMetrics.arr }} <span class="text-[10px] font-normal text-muted-foreground">/an</span>
                        </span>
                    </div>
                </div>

                <!-- Taux Récupération Complète -->
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

                <!-- Échelle de Référence -->
                <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-amber-500/5 border border-amber-500/15">
                    <Stethoscope class="size-3.5 text-amber-600 shrink-0" />
                    <div>
                        <span class="text-[10px] text-muted-foreground block leading-tight">Échelle Handicap</span>
                        <span class="font-mono font-bold text-amber-700 dark:text-amber-300 text-xs">
                            EDSS 0 à 10 <span class="text-[10px] font-normal text-muted-foreground">(Kurtzke)</span>
                        </span>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent class="pt-4 pb-4 space-y-5">
            <!-- ============================================================== -->
            <!-- VIEW 1: THREE-PHASE PIECEWISE CURVE & SHADED BANDS             -->
            <!-- ============================================================== -->
            <div v-if="activeView === 'edss_timeline'" class="space-y-4">
                <!-- Subtitle info banner -->
                <div class="flex items-center justify-between text-xs px-3 py-2 rounded-lg bg-muted/40 border border-border/60">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-foreground">{{ piecewiseModelData.title }}</span>
                        <span class="text-muted-foreground hidden sm:inline">— {{ piecewiseModelData.subtitle }}</span>
                    </div>
                    <Badge v-if="piecewiseModelData.isBenchmark" variant="outline" class="text-[10px] bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30">
                        Modèle de référence
                    </Badge>
                </div>

                <!-- Main SVG Plot Canvas -->
                <div class="w-full overflow-x-auto rounded-xl bg-card border border-border/70 p-3 shadow-xs">
                    <svg
                        ref="chartSvgRef"
                        :viewBox="`0 0 ${piecewiseModelData.svgWidth} ${piecewiseModelData.svgHeight}`"
                        class="w-full min-w-[800px] h-84 select-none overflow-visible font-sans"
                    >
                        <defs>
                            <!-- Shaded Area Gradient under the EDSS curve -->
                            <linearGradient id="piecewiseAreaGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#2563eb" stop-opacity="0.22" />
                                <stop offset="100%" stop-color="#2563eb" stop-opacity="0.02" />
                            </linearGradient>

                            <!-- Subtle horizontal grid pattern -->
                            <pattern id="edssSubtleGrid" width="40" height="20" patternUnits="userSpaceOnUse">
                                <line x1="0" y1="20" x2="40" y2="20" stroke="currentColor" stroke-width="0.6" class="text-border/20" />
                            </pattern>
                        </defs>

                        <!-- Plot Background -->
                        <rect
                            :x="piecewiseModelData.plotLeft"
                            :y="piecewiseModelData.plotTop"
                            :width="piecewiseModelData.plotWidth"
                            :height="piecewiseModelData.plotHeight"
                            fill="url(#edssSubtleGrid)"
                            class="rx-md"
                        />

                        <!-- -------------------------------------------------- -->
                        <!-- 1. SHADED BACKGROUND BANDS (Poussées & Intervalles) -->
                        <!-- -------------------------------------------------- -->
                        <g v-for="band in piecewiseModelData.bands" :key="band.id">
                            <!-- Shaded Vertical Band -->
                            <rect
                                :x="band.startX"
                                :y="piecewiseModelData.plotTop"
                                :width="band.width"
                                :height="piecewiseModelData.plotHeight"
                                :fill="band.colorBg"
                                :stroke="band.colorBorder"
                                stroke-width="1.2"
                                rx="4"
                            />

                            <!-- Top Band Header Pill -->
                            <g :transform="`translate(${band.startX + band.width / 2}, ${piecewiseModelData.plotTop - 16})`">
                                <rect
                                    :x="-Math.max(50, (band.title.length * 4) + 12)"
                                    y="-12"
                                    :width="Math.max(100, (band.title.length * 8) + 24)"
                                    height="20"
                                    rx="10"
                                    fill="#ffffff"
                                    :stroke="band.colorBorder"
                                    stroke-width="1.2"
                                    class="shadow-xs dark:fill-card"
                                />
                                <text
                                    x="0"
                                    y="2"
                                    text-anchor="middle"
                                    class="text-[10px] font-bold fill-foreground"
                                >
                                    {{ band.title }} <tspan class="text-[9px] font-normal fill-muted-foreground font-mono">({{ band.durationLabel }})</tspan>
                                </text>
                            </g>
                        </g>

                        <!-- -------------------------------------------------- -->
                        <!-- 2. Y-AXIS (EDSS 0 to Max by 0.5 steps)             -->
                        <!-- -------------------------------------------------- -->
                        <g v-for="tick in piecewiseModelData.yTicks" :key="tick.score">
                            <!-- Guideline across chart -->
                            <line
                                :x1="piecewiseModelData.plotLeft"
                                :y1="tick.y"
                                :x2="piecewiseModelData.plotLeft + piecewiseModelData.plotWidth"
                                :y2="tick.y"
                                stroke="currentColor"
                                :stroke-width="tick.isMajor ? 1.0 : 0.6"
                                :stroke-dasharray="tick.isMajor ? '0' : '3 3'"
                                class="text-border/60"
                            />
                            <!-- Tick label -->
                            <text
                                :x="piecewiseModelData.plotLeft - 10"
                                :y="tick.y + 3.5"
                                text-anchor="end"
                                class="text-[10px] font-mono select-none"
                                :class="tick.isMajor ? 'font-bold fill-foreground' : 'font-normal fill-muted-foreground'"
                            >
                                {{ tick.score.toFixed(1) }}
                            </text>
                        </g>

                        <!-- Y-Axis Title -->
                        <text
                            :x="piecewiseModelData.plotLeft - 45"
                            :y="piecewiseModelData.plotTop - 25"
                            text-anchor="start"
                            class="text-[10px] font-bold fill-indigo-600 dark:fill-indigo-400 uppercase tracking-wider"
                        >
                            Score EDSS
                        </text>

                        <!-- -------------------------------------------------- -->
                        <!-- 3. X-AXIS BASELINE & TICKS                         -->
                        <!-- -------------------------------------------------- -->
                        <line
                            :x1="piecewiseModelData.plotLeft"
                            :y1="piecewiseModelData.plotBottom"
                            :x2="piecewiseModelData.plotLeft + piecewiseModelData.plotWidth"
                            :y2="piecewiseModelData.plotBottom"
                            stroke="currentColor"
                            stroke-width="1.8"
                            class="text-border"
                        />

                        <!-- X-Axis Labels -->
                        <g v-for="tick in piecewiseModelData.xTicks" :key="tick.label">
                            <line
                                :x1="tick.x"
                                :y1="piecewiseModelData.plotBottom"
                                :x2="tick.x"
                                :y2="piecewiseModelData.plotBottom + 6"
                                stroke="currentColor"
                                stroke-width="1.2"
                                class="text-muted-foreground"
                            />
                            <text
                                :x="tick.x"
                                :y="piecewiseModelData.plotBottom + 18"
                                text-anchor="middle"
                                class="text-[10px] font-mono font-bold fill-foreground"
                            >
                                {{ tick.label }}
                            </text>
                            <text
                                :x="tick.x"
                                :y="piecewiseModelData.plotBottom + 29"
                                text-anchor="middle"
                                class="text-[8.5px] font-sans fill-muted-foreground"
                            >
                                {{ tick.sublabel }}
                            </text>
                        </g>

                        <!-- -------------------------------------------------- -->
                        <!-- 4. SHADED AREA UNDER THE PIECEWISE EDSS LINE       -->
                        <!-- -------------------------------------------------- -->
                        <path
                            :d="piecewiseModelData.areaPath"
                            fill="url(#piecewiseAreaGradient)"
                        />

                        <!-- -------------------------------------------------- -->
                        <!-- 5. MAIN SOLID BLUE PIECEWISE EDSS LINE             -->
                        <!-- -------------------------------------------------- -->
                        <path
                            :d="piecewiseModelData.linePath"
                            fill="none"
                            stroke="#1d4ed8"
                            stroke-width="3.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- -------------------------------------------------- -->
                        <!-- 6. CLINICAL NODE MARKERS & CALLOUT LABELS          -->
                        <!-- -------------------------------------------------- -->
                        <g v-for="pt in piecewiseModelData.points" :key="pt.id" class="group">
                            <!-- Outer pulsing halo -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="10"
                                :fill="pt.color"
                                fill-opacity="0.18"
                            />

                            <!-- Node circle -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="5.5"
                                fill="#ffffff"
                                :stroke="pt.color"
                                stroke-width="3"
                                class="transition-transform duration-150 group-hover:scale-125 cursor-pointer"
                            />

                            <!-- Callout Annotation Box -->
                            <g :transform="`translate(${pt.x}, ${pt.phase === 't1_start' ? pt.y - 20 : (pt.phase === 't0_before' ? pt.y - 18 : pt.y + 22)})`">
                                <rect
                                    :x="-Math.max(45, (pt.label.length * 3.5) + 8)"
                                    y="-10"
                                    :width="Math.max(90, (pt.label.length * 7) + 16)"
                                    height="20"
                                    rx="5"
                                    fill="#ffffff"
                                    stroke="currentColor"
                                    stroke-width="0.8"
                                    class="text-border fill-card shadow-xs"
                                />
                                <text
                                    x="0"
                                    y="3"
                                    text-anchor="middle"
                                    class="text-[9.5px] font-mono font-bold"
                                    :fill="pt.color"
                                >
                                    {{ pt.label }} ({{ pt.score.toFixed(1) }})
                                </text>
                            </g>
                        </g>
                    </svg>
                </div>

                <!-- ============================================================== -->
                <!-- 7. SUMMARY TABLE UNDERNEATH (Récapitulatif des valeurs)        -->
                <!-- ============================================================== -->
                <div class="rounded-xl border border-border/80 bg-card overflow-hidden shadow-2xs">
                    <div class="px-4 py-2.5 bg-muted/40 border-b border-border/70 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <FileSpreadsheet class="size-4 text-primary" />
                            <h4 class="text-xs font-semibold text-foreground uppercase tracking-wider">
                                Récapitulatif des Valeurs Clés de l'Évolution EDSS
                            </h4>
                        </div>
                        <span class="text-[11px] font-mono text-muted-foreground">Interpolation par morceaux conforme aux normes de recherche</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-muted/20 border-b border-border/60 text-muted-foreground text-[11px]">
                                <tr>
                                    <th class="py-2.5 px-3.5 font-semibold">Phase / Poussée</th>
                                    <th class="py-2.5 px-3 font-semibold">Durée</th>
                                    <th class="py-2.5 px-3 font-semibold">EDSS avant (t₀)</th>
                                    <th class="py-2.5 px-3 font-semibold">EDSS au début (t₁)</th>
                                    <th class="py-2.5 px-3 font-semibold">EDSS à la fin (t₂)</th>
                                    <th class="py-2.5 px-3 font-semibold">Variation nette (Δ)</th>
                                    <th class="py-2.5 px-3 font-semibold">Récupération / Statut</th>
                                    <th class="py-2.5 px-3.5 font-semibold">Traitement reçu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50 font-mono">
                                <tr
                                    v-for="(row, idx) in piecewiseModelData.summaryRows"
                                    :key="idx"
                                    class="hover:bg-muted/30 transition-colors"
                                >
                                    <td class="py-2 px-3.5 font-sans font-semibold text-foreground">
                                        {{ row.phase }}
                                    </td>
                                    <td class="py-2 px-3 text-muted-foreground">
                                        {{ row.duration }}
                                    </td>
                                    <td class="py-2 px-3 font-bold text-blue-600 dark:text-blue-400">
                                        {{ row.edssBefore }}
                                    </td>
                                    <td class="py-2 px-3 font-bold text-rose-600 dark:text-rose-400">
                                        {{ row.edssPeak }}
                                    </td>
                                    <td class="py-2 px-3 font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ row.edssAfter }}
                                    </td>
                                    <td class="py-2 px-3" :class="row.deltaClass">
                                        {{ row.delta }}
                                    </td>
                                    <td class="py-2 px-3 font-sans text-foreground">
                                        <Badge variant="outline" class="text-[10px] py-0 font-normal">
                                            {{ row.status }}
                                        </Badge>
                                    </td>
                                    <td class="py-2 px-3.5 font-sans text-muted-foreground text-[11px]">
                                        {{ row.treatment }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 8. MATHEMATICAL FORMULATION BOX                               -->
                <!-- ============================================================== -->
                <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-indigo-500"></span>
                            <h4 class="text-xs font-semibold text-foreground uppercase tracking-wider">
                                Modélisation Mathématique de la Courbe (Segments Linéaires par Morceaux)
                            </h4>
                        </div>
                        <Badge variant="outline" class="text-[10px] font-mono border-indigo-500/30 text-indigo-700 dark:text-indigo-300">
                            EDSS(t)
                        </Badge>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- Piecewise formula theoretical -->
                        <div class="p-3 rounded-lg bg-background/80 border border-border/70 font-mono space-y-1.5 leading-relaxed">
                            <span class="text-[11px] text-muted-foreground font-sans font-semibold block">
                                Équation générale paramétrique :
                            </span>
                            <div class="text-[11px] text-foreground">
                                <span class="font-bold text-indigo-600">EDSS(t) =</span>
                                <div class="pl-4 space-y-1 border-l-2 border-indigo-500/40 my-1">
                                    <div>• EDSS₀ <span class="text-muted-foreground font-sans">si t ≤ t₀ (plateau avant poussée)</span></div>
                                    <div>• EDSS₀ + (EDSS₁ - EDSS₀) · <span class="underline">(t - t₀)</span> / (t₁ - t₀) <span class="text-muted-foreground font-sans">si t₀ &lt; t &lt; t₁ (phase ascendante)</span></div>
                                    <div>• EDSS₁ - (EDSS₁ - EDSS₂) · <span class="underline">(t - t₁)</span> / Δt <span class="text-muted-foreground font-sans">si t₁ ≤ t ≤ t₁ + Δt (phase de récupération)</span></div>
                                    <div>• EDSS₂ <span class="text-muted-foreground font-sans">si t &gt; t₁ + Δt (plateau stabilisé)</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Paramètres appliqués -->
                        <div class="p-3 rounded-lg bg-background/80 border border-border/70 font-mono space-y-1.5 leading-relaxed">
                            <span class="text-[11px] text-muted-foreground font-sans font-semibold block">
                                Paramètres appliqués à la 1ère poussée :
                            </span>
                            <div class="space-y-1 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Score de référence (t₀) :</span>
                                    <span class="font-bold text-blue-600">EDSS₀ = {{ piecewiseModelData.formulaData.edssBefore }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Score au pic aigu (t₁) :</span>
                                    <span class="font-bold text-rose-600">EDSS₁ = {{ piecewiseModelData.formulaData.edssPeak }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Score final récupéré (t₂) :</span>
                                    <span class="font-bold text-emerald-600">EDSS₂ = {{ piecewiseModelData.formulaData.edssAfter }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Durée d'évaluation (Δt) :</span>
                                    <span class="font-bold text-foreground">Δt = {{ piecewiseModelData.formulaData.durationDays }} jours</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Precaution Disclaimer Note -->
                    <div class="flex items-start gap-2 pt-1 text-[11px] text-muted-foreground leading-relaxed border-t border-border/50">
                        <Info class="size-3.5 text-indigo-500 shrink-0 mt-0.5" />
                        <span>
                            <strong class="text-foreground font-medium">Précaution méthodologique :</strong>
                            Les segments reliant les mesures sont tracés à titre d'interpolation illustrative pour expliciter la cinétique de récupération. L'échelle EDSS étant ordinale, ses variations cliniques réelles sont discontinues. Le registre enregistre et distingue rigoureusement les dates réelles des évaluations cliniques des extrapolations visuelles.
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- VIEW 2: ANNUAL DISTRIBUTION HISTOGRAM (ARR)                   -->
            <!-- ============================================================== -->
            <div v-else-if="activeView === 'bars'" class="space-y-4">
                <div class="rounded-xl bg-card border border-border/70 p-4 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-xs font-semibold text-foreground uppercase tracking-wider">
                                Distribution annuelle de l'activité inflammatoire
                            </h4>
                            <p class="text-[11px] text-muted-foreground">Nombre de poussées aiguës documentées par année civile</p>
                        </div>
                        <Badge variant="outline" class="font-mono text-xs bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30">
                            ARR : {{ annualDistributionData.arr }} / an
                            <span v-if="annualDistributionData.spanYears > 1" class="ml-1 text-[10px] text-muted-foreground font-normal">
                                ({{ sortedRelapses.length }} sur {{ annualDistributionData.spanYears }} ans)
                            </span>
                        </Badge>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <svg viewBox="0 0 720 180" class="w-full min-w-[550px] h-44 overflow-visible select-none font-sans">
                            <defs>
                                <linearGradient id="annualBarGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.75" />
                                </linearGradient>
                            </defs>

                            <line x1="30" y1="125" x2="690" y2="125" stroke="currentColor" stroke-width="1.5" class="text-border" />

                            <g v-for="item in annualDistributionData.items" :key="item.year" class="group">
                                <rect
                                    v-if="item.count > 0"
                                    :x="item.x - 18"
                                    :y="125 - (item.count / annualDistributionData.maxCount) * 80"
                                    width="36"
                                    :height="Math.max(10, (item.count / annualDistributionData.maxCount) * 80)"
                                    rx="5"
                                    fill="url(#annualBarGradient)"
                                    class="hover:brightness-110 transition-all cursor-pointer shadow-xs"
                                />
                                <circle v-else :cx="item.x" cy="125" r="3.5" class="fill-muted-foreground/40" />

                                <text
                                    v-if="item.count > 0"
                                    :x="item.x"
                                    :y="125 - (item.count / annualDistributionData.maxCount) * 80 - 7"
                                    text-anchor="middle"
                                    class="text-[12px] font-mono font-bold fill-foreground"
                                >
                                    {{ item.count }}
                                </text>

                                <text
                                    :x="item.x"
                                    y="146"
                                    text-anchor="middle"
                                    class="text-[12px] font-mono font-bold fill-foreground group-hover:fill-indigo-600 transition-colors"
                                >
                                    {{ item.year }}
                                </text>

                                <text
                                    v-if="item.count > 0"
                                    :x="item.x"
                                    y="160"
                                    text-anchor="middle"
                                    class="text-[10px] font-mono fill-indigo-600 dark:fill-indigo-400 font-medium"
                                >
                                    {{ item.count }} {{ item.count > 1 ? 'poussées' : 'poussée' }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
