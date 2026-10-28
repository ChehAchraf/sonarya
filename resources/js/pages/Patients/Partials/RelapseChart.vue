<script setup lang="ts">
import {
    Activity,
    BarChart3,
    ChevronDown,
    ChevronUp,
    Download,
    FileSpreadsheet,
    Info,
    Printer,
    TrendingUp,
    X,
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

// View switcher: Only 2 clean views
// 'edss_timeline': Courbe EDSS & Poussées
// 'bars': Fréquence Annuelle (ARR)
const activeView = ref<'edss_timeline' | 'bars'>('edss_timeline');

// Toggle between patient real data and benchmark protocol example
const dataSourceMode = ref<'real' | 'benchmark'>('real');

// Collapsible section for methodology and formula
const showMethodologyDetails = ref(false);

// Selected element for details inspection on click
export type SelectedPhaseInfo = {
    id: string;
    title: string;
    date: string;
    duration: string;
    edssBefore: number | string;
    edssPeak?: number | string;
    edssAfter: number | string;
    recovery?: string;
    treatment?: string;
};

const selectedPhaseInfo = ref<SelectedPhaseInfo | null>(null);

function togglePhaseSelection(info: SelectedPhaseInfo) {
    if (selectedPhaseInfo.value?.id === info.id) {
        selectedPhaseInfo.value = null;
    } else {
        selectedPhaseInfo.value = info;
    }
}

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

const hasRealRelapsesWithData = computed(() => {
    return sortedRelapses.value.length > 0;
});

// Styling helpers
function getRecoveryStyle(recovery: string | null | undefined, hasSequelae: boolean) {
    if (hasSequelae || (recovery && recovery.toLowerCase().includes('sequell'))) {
        return {
            color: '#f43f5e',
            bandBg: 'rgba(244, 63, 94, 0.12)',
            bandBorder: 'rgba(244, 63, 94, 0.35)',
            label: 'Récupération incomplète avec séquelles',
        };
    }
    if (recovery && (recovery.toLowerCase().includes('incompl') || recovery.toLowerCase().includes('nulle'))) {
        return {
            color: '#f59e0b',
            bandBg: 'rgba(245, 158, 11, 0.12)',
            bandBorder: 'rgba(245, 158, 11, 0.35)',
            label: 'Récupération incomplète',
        };
    }
    return {
        color: '#10b981',
        bandBg: 'rgba(16, 185, 129, 0.12)',
        bandBorder: 'rgba(16, 185, 129, 0.35)',
        label: 'Récupération complète',
    };
}

// -------------------------------------------------------------
// UNIFIED PIECEWISE CURVE & SHADED BANDS MODEL
// -------------------------------------------------------------
export type PhaseBand = {
    id: string;
    type: 'relapse' | 'inter_relapse' | 'followup';
    title: string;
    shortTitle: string;
    durationLabel: string;
    colorBg: string;
    colorBorder: string;
    startX: number;
    endX: number;
    width: number;
    rawDate?: string;
    edssBefore: number;
    edssPeak?: number;
    edssAfter: number;
    delta: number;
    recoveryLabel?: string;
    treatmentLabel?: string;
};

export type CurvePoint = {
    id: string;
    x: number;
    y: number;
    score: number;
    badgeLabel: string;
    phase: 't0_before' | 't1_start' | 't2_end' | 'plateau';
    color: string;
    dateFormatted: string;
    showLabel: boolean;
};

const piecewiseModelData = computed(() => {
    const useBenchmark = dataSourceMode.value === 'benchmark' || (!hasRealRelapsesWithData.value);

    const paddingLeft = 55;
    const paddingRight = 40;
    const plotTop = 45;
    const plotBottom = 260;
    const plotHeight = plotBottom - plotTop;

    if (useBenchmark) {
        // BENCHMARK PROTOCOL EXAMPLE (Cahier des charges)
        // Clean normalized width of 900px
        const svgWidth = 900;
        const svgHeight = 310;
        const plotWidth = svgWidth - paddingLeft - paddingRight;

        // Visual layout with generous room per phase
        const xT0 = paddingLeft + 15;
        const xP1Start = paddingLeft + 90;
        const xP1End = xP1Start + 130;
        const xInterEnd = xP1End + 210;
        const xP2Start = xInterEnd;
        const xP2End = xP2Start + 140;
        const xFollowEnd = xP2End + 170;

        const maxScore = 3.5;
        const edssToY = (score: number) => plotBottom - (Math.max(0, Math.min(maxScore, score)) / maxScore) * plotHeight;

        const bands: PhaseBand[] = [
            {
                id: 'b-p1',
                type: 'relapse',
                title: '1ère poussée',
                shortTitle: 'Poussée #1 (2 sem)',
                durationLabel: '2 semaines (14 jours)',
                colorBg: 'rgba(254, 226, 226, 0.75)',
                colorBorder: 'rgba(244, 63, 94, 0.45)',
                startX: xP1Start,
                endX: xP1End,
                width: xP1End - xP1Start,
                edssBefore: 0.5,
                edssPeak: 2.0,
                edssAfter: 1.0,
                delta: 0.5,
                recoveryLabel: 'Récupération partielle (+0.5 pt)',
                treatmentLabel: 'Bolus Solumédrol 1g/j IV x 3j',
                rawDate: 'J0 → J14',
            },
            {
                id: 'b-inter',
                type: 'inter_relapse',
                title: 'Période entre poussées',
                shortTitle: 'Rémission (8 sem)',
                durationLabel: '8 semaines (56 jours)',
                colorBg: 'rgba(224, 242, 254, 0.65)',
                colorBorder: 'rgba(56, 189, 248, 0.4)',
                startX: xP1End,
                endX: xInterEnd,
                width: xInterEnd - xP1End,
                edssBefore: 1.0,
                edssAfter: 1.0,
                delta: 0.0,
                recoveryLabel: 'Rémission clinique stable',
                treatmentLabel: 'Avonex (Interféron)',
                rawDate: 'J14 → J70',
            },
            {
                id: 'b-p2',
                type: 'relapse',
                title: '2ème poussée',
                shortTitle: 'Poussée #2 (3 sem)',
                durationLabel: '3 semaines (21 jours)',
                colorBg: 'rgba(220, 252, 231, 0.75)',
                colorBorder: 'rgba(34, 197, 94, 0.45)',
                startX: xP2Start,
                endX: xP2End,
                width: xP2End - xP2Start,
                edssBefore: 1.0,
                edssPeak: 3.0,
                edssAfter: 2.0,
                delta: 1.0,
                recoveryLabel: 'Récupération incomplète (+1.0 pt)',
                treatmentLabel: 'Bolus Solumédrol 1g/j IV x 5j',
                rawDate: 'J70 → J91',
            },
            {
                id: 'b-follow',
                type: 'followup',
                title: 'Période de suivi',
                shortTitle: 'Suivi (6 sem)',
                durationLabel: '6 semaines (42 jours)',
                colorBg: 'rgba(224, 242, 254, 0.65)',
                colorBorder: 'rgba(56, 189, 248, 0.4)',
                startX: xP2End,
                endX: xFollowEnd,
                width: xFollowEnd - xP2End,
                edssBefore: 2.0,
                edssAfter: 2.0,
                delta: 0.0,
                recoveryLabel: 'Plateau résiduel stabilisé',
                treatmentLabel: 'Contrôle neurologique',
                rawDate: 'J91 → J133',
            },
        ];

        const points: CurvePoint[] = [
            {
                id: 'pt-0',
                x: xT0,
                y: edssToY(0.5),
                score: 0.5,
                badgeLabel: '0,5',
                phase: 't0_before',
                color: '#2563eb',
                dateFormatted: 'J -14',
                showLabel: true,
            },
            {
                id: 'pt-1-peak',
                x: xP1Start,
                y: edssToY(2.0),
                score: 2.0,
                badgeLabel: 'Acmé 2,0',
                phase: 't1_start',
                color: '#e11d48',
                dateFormatted: 'J 0',
                showLabel: true,
            },
            {
                id: 'pt-1-end',
                x: xP1End,
                y: edssToY(1.0),
                score: 1.0,
                badgeLabel: '1,0',
                phase: 't2_end',
                color: '#059669',
                dateFormatted: 'J 14',
                showLabel: true,
            },
            {
                id: 'pt-2-peak',
                x: xP2Start,
                y: edssToY(3.0),
                score: 3.0,
                badgeLabel: 'Acmé 3,0',
                phase: 't1_start',
                color: '#e11d48',
                dateFormatted: 'J 70',
                showLabel: true,
            },
            {
                id: 'pt-2-end',
                x: xP2End,
                y: edssToY(2.0),
                score: 2.0,
                badgeLabel: '2,0',
                phase: 't2_end',
                color: '#059669',
                dateFormatted: 'J 91',
                showLabel: true,
            },
            {
                id: 'pt-follow-end',
                x: xFollowEnd,
                y: edssToY(2.0),
                score: 2.0,
                badgeLabel: '2,0',
                phase: 'plateau',
                color: '#4f46e5',
                dateFormatted: 'J 133',
                showLabel: false,
            },
        ];

        const linePath = `M ${xT0} ${edssToY(0.5)} ` +
            `L ${xP1Start} ${edssToY(0.5)} ` +
            `L ${xP1Start} ${edssToY(2.0)} ` +
            `L ${xP1End} ${edssToY(1.0)} ` +
            `L ${xP2Start} ${edssToY(1.0)} ` +
            `L ${xP2Start} ${edssToY(3.0)} ` +
            `L ${xP2End} ${edssToY(2.0)} ` +
            `L ${xFollowEnd} ${edssToY(2.0)}`;

        const areaPath = `${linePath} L ${xFollowEnd} ${plotBottom} L ${xT0} ${plotBottom} Z`;

        const yTicks: { score: number; y: number; isMajor: boolean }[] = [];
        for (let s = 0; s <= maxScore; s += 0.5) {
            yTicks.push({
                score: Number(s.toFixed(1)),
                y: edssToY(s),
                isMajor: Number.isInteger(s),
            });
        }

        const xTicks = [
            { x: xT0, label: 'J -14', sublabel: 'Référence' },
            { x: xP1Start, label: 'J 0', sublabel: 'Début P1' },
            { x: xP1End, label: 'J 14', sublabel: 'Fin P1 (2 sem)' },
            { x: (xP1End + xP2Start) / 2, label: 'Sem 6', sublabel: 'Rémission' },
            { x: xP2Start, label: 'J 70', sublabel: 'Début P2' },
            { x: xP2End, label: 'J 91', sublabel: 'Fin P2 (3 sem)' },
            { x: xFollowEnd, label: 'J 133', sublabel: 'Suivi' },
        ];

        return {
            isBenchmark: true,
            title: "Modèle de référence du protocole (2 semaines)",
            subtitle: "Illustration théorique : EDSS référence = 0,5 ; acmé début = 2,0 ; EDSS final à 2 semaines = 1,0",
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
                    treatment: 'Interféron bêta-1a',
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
                    status: 'Handicap stabilisé',
                    treatment: 'Contrôle périodique',
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

    // -------------------------------------------------------------
    // REAL PATIENT DATA WITH SMART HYBRID CLINICAL SPACING
    // -------------------------------------------------------------
    const relapses = sortedRelapses.value;
    const initialEdss = props.diseaseProfile?.initial_edss !== null && props.diseaseProfile?.initial_edss !== undefined
        ? Number(props.diseaseProfile.initial_edss)
        : null;

    type BuiltRelapse = {
        relapse: Relapse;
        index: number;
        startDateStr: string;
        endDateStr: string;
        durationDays: number;
        gapBeforeDays: number;
        edssBefore: number;
        edssPeak: number;
        edssAfter: number;
    };

    let runningBaseline = initialEdss ?? 0.0;
    const builtRelapses: BuiltRelapse[] = [];

    relapses.forEach((r, idx) => {
        const t1 = parseAnyDate(r.start_date_raw || r.start_date);
        let t2 = parseAnyDate(r.edss_evaluation_date_raw || r.edss_evaluation_date || r.end_date_raw || r.end_date);
        if (t2 <= t1) {
            t2 = t1 + (r.duration_days ? r.duration_days * 86400000 : 14 * 86400000);
        }
        const durationDays = Math.max(1, Math.round((t2 - t1) / 86400000));

        let gapBeforeDays = 30;
        if (idx > 0) {
            const prevT2 = parseAnyDate(relapses[idx - 1].end_date_raw || relapses[idx - 1].end_date);
            if (prevT2 > 0 && t1 > prevT2) {
                gapBeforeDays = Math.max(15, Math.round((t1 - prevT2) / 86400000));
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
            startDateStr: r.start_date || formatFrenchDate(t1),
            endDateStr: r.end_date || formatFrenchDate(t2),
            durationDays,
            gapBeforeDays,
            edssBefore,
            edssPeak,
            edssAfter,
        });
    });

    // Smart clinical spacing: Give every acute relapse a minimum width of 110px
    // and every remission gap a minimum width of 115px to ensure ZERO label collision!
    const relapseVisualWidth = 115;
    const gapVisualWidth = 120;
    const followVisualWidth = 110;

    const totalCalculatedWidth = paddingLeft + 25 + (builtRelapses.length * relapseVisualWidth) + ((builtRelapses.length - 1) * gapVisualWidth) + followVisualWidth + paddingRight;
    const svgWidth = Math.max(900, totalCalculatedWidth);
    const svgHeight = 310;
    const plotWidth = svgWidth - paddingLeft - paddingRight;

    // Y scale
    const allScores = builtRelapses.flatMap((b) => [b.edssBefore, b.edssPeak, b.edssAfter]);
    const maxObservedScore = Math.max(3.5, ...allScores);
    const maxScore = Math.min(10, Math.ceil((maxObservedScore + 0.5) * 2) / 2);
    const edssToY = (score: number) => plotBottom - (Math.max(0, Math.min(maxScore, score)) / maxScore) * plotHeight;

    const bands: PhaseBand[] = [];
    const points: CurvePoint[] = [];
    const xTicks: { x: number; label: string; sublabel: string }[] = [];
    const summaryRows: any[] = [];

    let currentX = paddingLeft + 20;
    let linePath = `M ${currentX} ${edssToY(builtRelapses[0].edssBefore)} `;

    // Baseline start point
    points.push({
        id: 'real-pt-t0',
        x: currentX,
        y: edssToY(builtRelapses[0].edssBefore),
        score: builtRelapses[0].edssBefore,
        badgeLabel: builtRelapses[0].edssBefore.toFixed(1),
        phase: 't0_before',
        color: '#2563eb',
        dateFormatted: 'Référence',
        showLabel: true,
    });
    xTicks.push({
        x: currentX,
        label: 'Base',
        sublabel: builtRelapses[0].edssBefore.toFixed(1),
    });

    builtRelapses.forEach((b, idx) => {
        const style = getRecoveryStyle(b.relapse.clinical_recovery, !!b.relapse.sequelae);
        const startX = currentX + 30; // slight baseline plateau before peak
        const endX = startX + relapseVisualWidth;

        // Band for acute relapse
        bands.push({
            id: `real-band-${b.relapse.id}`,
            type: 'relapse',
            title: `Poussée #${b.index}`,
            shortTitle: `P#${b.index} (${b.durationDays}j)`,
            durationLabel: `${b.durationDays} jours`,
            colorBg: style.bandBg,
            colorBorder: style.bandBorder,
            startX,
            endX,
            width: endX - startX,
            rawDate: `${b.startDateStr} → ${b.endDateStr}`,
            edssBefore: b.edssBefore,
            edssPeak: b.edssPeak,
            edssAfter: b.edssAfter,
            delta: Number((b.edssAfter - b.edssBefore).toFixed(1)),
            recoveryLabel: b.relapse.clinical_recovery || 'Documentée',
            treatmentLabel: b.relapse.treatment_administered || 'Solumédrol',
        });

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

        // Path logic:
        // Flat to startX, rise to peak, descent to endX
        linePath += `L ${startX} ${edssToY(b.edssBefore)} `;
        linePath += `L ${startX} ${edssToY(b.edssPeak)} `;
        linePath += `L ${endX} ${edssToY(b.edssAfter)} `;

        // Acute Peak Point
        points.push({
            id: `real-pt-peak-${b.relapse.id}`,
            x: startX,
            y: edssToY(b.edssPeak),
            score: b.edssPeak,
            badgeLabel: `Pic ${b.edssPeak.toFixed(1)}`,
            phase: 't1_start',
            color: '#e11d48',
            dateFormatted: b.startDateStr,
            showLabel: true,
        });

        // Recovery End Point
        points.push({
            id: `real-pt-end-${b.relapse.id}`,
            x: endX,
            y: edssToY(b.edssAfter),
            score: b.edssAfter,
            badgeLabel: b.edssAfter.toFixed(1),
            phase: 't2_end',
            color: '#059669',
            dateFormatted: b.endDateStr,
            showLabel: true,
        });

        // X-Ticks
        xTicks.push({
            x: startX,
            label: b.startDateStr,
            sublabel: `Début P#${b.index}`,
        });
        xTicks.push({
            x: endX,
            label: b.endDateStr,
            sublabel: `Fin P#${b.index}`,
        });

        // Inter-relapse gap if there is a next relapse
        if (idx < builtRelapses.length - 1) {
            const gapStartX = endX;
            const gapEndX = gapStartX + gapVisualWidth;
            const next = builtRelapses[idx + 1];

            bands.push({
                id: `real-inter-${b.relapse.id}`,
                type: 'inter_relapse',
                title: 'Période de rémission',
                shortTitle: `Rémission (${next.gapBeforeDays}j)`,
                durationLabel: `${next.gapBeforeDays} jours`,
                colorBg: 'rgba(224, 242, 254, 0.65)',
                colorBorder: 'rgba(56, 189, 248, 0.4)',
                startX: gapStartX,
                endX: gapEndX,
                width: gapEndX - gapStartX,
                rawDate: `${b.endDateStr} → ${next.startDateStr}`,
                edssBefore: b.edssAfter,
                edssAfter: b.edssAfter,
                delta: 0.0,
                recoveryLabel: 'Phase de rémission intercritique',
                treatmentLabel: 'Traitement de fond',
            });

            summaryRows.push({
                phase: `Intervalle P#${b.index} → P#${next.index}`,
                duration: `${next.gapBeforeDays} jours`,
                edssBefore: b.edssAfter.toFixed(1),
                edssPeak: '—',
                edssAfter: b.edssAfter.toFixed(1),
                delta: '0,0',
                deltaClass: 'text-muted-foreground font-medium',
                status: 'Rémission intercritique',
                treatment: 'Traitement de fond',
            });

            // Flat line through remission
            linePath += `L ${gapEndX} ${edssToY(b.edssAfter)} `;
            currentX = gapEndX;
        } else {
            // Follow-up period after last relapse
            const followStartX = endX;
            const followEndX = followStartX + followVisualWidth;

            bands.push({
                id: 'real-followup',
                type: 'followup',
                title: 'Période de suivi',
                shortTitle: 'Suivi actuel',
                durationLabel: 'En cours',
                colorBg: 'rgba(224, 242, 254, 0.65)',
                colorBorder: 'rgba(56, 189, 248, 0.4)',
                startX: followStartX,
                endX: followEndX,
                width: followEndX - followStartX,
                edssBefore: b.edssAfter,
                edssAfter: b.edssAfter,
                delta: 0.0,
                recoveryLabel: 'Suivi clinique actuel',
                treatmentLabel: 'Suivi neurologique',
            });

            linePath += `L ${followEndX} ${edssToY(b.edssAfter)} `;
            currentX = followEndX;
        }
    });

    const areaPath = `${linePath} L ${currentX} ${plotBottom} L ${paddingLeft + 20} ${plotBottom} Z`;

    const yTicks: { score: number; y: number; isMajor: boolean }[] = [];
    for (let s = 0; s <= maxScore; s += 0.5) {
        yTicks.push({
            score: Number(s.toFixed(1)),
            y: edssToY(s),
            isMajor: Number.isInteger(s),
        });
    }

    return {
        isBenchmark: false,
        title: "Évolution de l'EDSS au cours des poussées",
        subtitle: `Patient : ${relapses.length} poussée(s) répertoriée(s) — cinétique du handicap aigu et résiduel`,
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

// Annual Relapse Rate (ARR)
const annualDistributionData = computed(() => {
    const list = sortedRelapses.value;
    if (list.length === 0) {
        return { items: [], maxCount: 1, arr: '0.0', spanYears: 1 };
    }

    const yearCounts = new Map<number, number>();
    list.forEach((r) => {
        const time = parseAnyDate(r.start_date_raw || r.start_date);
        const yr = time > 0 ? new Date(time).getFullYear() : new Date().getFullYear();
        yearCounts.set(yr, (yearCounts.get(yr) || 0) + 1);
    });

    const years = Array.from(yearCounts.keys()).sort((a, b) => a - b);
    const minYr = years[0];
    const maxYr = years[years.length - 1];
    const spanYears = Math.max(1, maxYr - minYr + 1);
    const arr = (list.length / spanYears).toFixed(2);

    const items: { year: number; count: number; x: number }[] = [];
    let maxCount = 1;
    years.forEach((yr, idx) => {
        const count = yearCounts.get(yr) || 0;
        if (count > maxCount) maxCount = count;
        const x = 70 + idx * 85;
        items.push({ year: yr, count, x });
    });

    return { items, maxCount, arr, spanYears };
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
        const scale = 2;
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
            link.download = `evolution_edss_${new Date().toISOString().slice(0, 10)}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        img.src = url;
    } catch (e) {
        console.error('Export PNG failed:', e);
    }
};

const printChart = () => {
    window.print();
};
</script>

<template>
    <Card class="border border-border/80 bg-card shadow-xs overflow-hidden print:border-none print:shadow-none">
        <!-- Clean, Compact Header with Action Buttons -->
        <CardHeader class="pb-3 border-b border-border/60">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <Activity class="size-4" />
                        </div>
                        <CardTitle class="text-sm sm:text-base font-semibold tracking-tight text-foreground flex items-center gap-2">
                            <span>Illustration Chronologique des Poussées & Évolution EDSS</span>
                            <Badge variant="outline" class="text-[11px] font-mono font-normal">
                                {{ relapses.length }} {{ relapses.length > 1 ? 'poussées' : 'poussée' }}
                            </Badge>
                        </CardTitle>
                    </div>
                    <CardDescription class="text-xs text-muted-foreground">
                        Cinétique du handicap EDSS (0 à 10) : état basal, acmé aiguë et niveau de récupération.
                    </CardDescription>
                </div>

                <!-- Action Controls Bar -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Source Mode: Real vs Protocol Benchmark -->
                    <div class="flex items-center bg-muted/60 p-0.5 rounded-lg border border-border/60 text-xs">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-all whitespace-nowrap"
                            :class="dataSourceMode === 'real'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="dataSourceMode = 'real'"
                        >
                            Données réelles
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-all whitespace-nowrap"
                            :class="dataSourceMode === 'benchmark'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="dataSourceMode = 'benchmark'"
                        >
                            Exemple protocole (2 sem)
                        </button>
                    </div>

                    <!-- View Switcher Tabs -->
                    <div class="flex items-center bg-muted/60 p-0.5 rounded-lg border border-border/60 text-xs">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1.5 whitespace-nowrap"
                            :class="activeView === 'edss_timeline'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="activeView = 'edss_timeline'"
                        >
                            <TrendingUp class="size-3.5 text-indigo-600 dark:text-indigo-400" />
                            <span>Courbe EDSS</span>
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1.5 whitespace-nowrap"
                            :class="activeView === 'bars'
                                ? 'bg-background text-foreground shadow-2xs font-semibold'
                                : 'text-muted-foreground hover:text-foreground'"
                            @click="activeView = 'bars'"
                        >
                            <BarChart3 class="size-3.5 text-cyan-600 dark:text-cyan-400" />
                            <span>Fréquence (ARR)</span>
                        </button>
                    </div>

                    <!-- Export Buttons -->
                    <div class="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-7 text-xs px-2 gap-1"
                            title="Exporter en image PNG"
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
                            title="Imprimer"
                            @click="printChart"
                        >
                            <Printer class="size-3" />
                            <span>Imprimer</span>
                        </Button>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent class="pt-4 pb-4 space-y-4">
            <!-- ============================================================== -->
            <!-- VIEW 1: THREE-PHASE PIECEWISE CURVE & SHADED BANDS             -->
            <!-- ============================================================== -->
            <div v-if="activeView === 'edss_timeline'" class="space-y-3">
                <!-- Selected Phase Details Banner (Activated on Click) -->
                <div
                    v-if="selectedPhaseInfo"
                    class="px-3.5 py-2 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-xs flex flex-wrap items-center justify-between gap-2 shadow-2xs transition-all animate-in fade-in"
                >
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="font-bold bg-background text-indigo-700 dark:text-indigo-300 border-indigo-500/40">
                            {{ selectedPhaseInfo.title }}
                        </Badge>
                        <span class="text-foreground font-medium">({{ selectedPhaseInfo.duration }})</span>
                        <span class="text-muted-foreground font-mono text-[11px]">{{ selectedPhaseInfo.date }}</span>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs">
                        <span class="text-muted-foreground font-sans">EDSS :</span>
                        <span class="text-blue-600 dark:text-blue-400 font-bold">{{ selectedPhaseInfo.edssBefore }}</span>
                        <template v-if="selectedPhaseInfo.edssPeak !== undefined">
                            <span class="text-muted-foreground">→</span>
                            <span class="text-rose-600 dark:text-rose-400 font-bold">{{ selectedPhaseInfo.edssPeak }} (acmé)</span>
                        </template>
                        <span class="text-muted-foreground">→</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ selectedPhaseInfo.edssAfter }} (post)</span>
                        <span v-if="selectedPhaseInfo.treatment" class="text-muted-foreground font-sans text-[11px] ml-1">
                            • {{ selectedPhaseInfo.treatment }}
                        </span>
                        <button
                            type="button"
                            @click="selectedPhaseInfo = null"
                            class="text-muted-foreground hover:text-foreground p-0.5 rounded-full hover:bg-muted ml-2 transition-colors cursor-pointer"
                            title="Fermer"
                        >
                            <X class="size-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Main SVG Plot Canvas with Horizontal Scroll on Mobile -->
                <div class="w-full overflow-x-auto rounded-xl bg-card/50 border border-border/70 p-2 sm:p-3 shadow-xs">
                    <svg
                        ref="chartSvgRef"
                        :viewBox="`0 0 ${piecewiseModelData.svgWidth} ${piecewiseModelData.svgHeight}`"
                        class="w-full min-w-[780px] h-72 select-none overflow-visible font-sans"
                    >
                        <defs>
                            <!-- Shaded Area Gradient under the EDSS curve -->
                            <linearGradient id="cleanAreaGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#2563eb" stop-opacity="0.20" />
                                <stop offset="100%" stop-color="#2563eb" stop-opacity="0.01" />
                            </linearGradient>

                            <pattern id="cleanGrid" width="30" height="20" patternUnits="userSpaceOnUse">
                                <line x1="0" y1="20" x2="30" y2="20" stroke="currentColor" stroke-width="0.5" class="text-border/20" />
                            </pattern>
                        </defs>

                        <!-- Plot Background -->
                        <rect
                            :x="piecewiseModelData.plotLeft"
                            :y="piecewiseModelData.plotTop"
                            :width="piecewiseModelData.plotWidth"
                            :height="piecewiseModelData.plotHeight"
                            fill="url(#cleanGrid)"
                            rx="4"
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
                                :stroke="selectedPhaseInfo?.id === band.id ? '#2563eb' : band.colorBorder"
                                :stroke-width="selectedPhaseInfo?.id === band.id ? 2.5 : 1.2"
                                rx="3"
                                class="transition-all hover:opacity-90 cursor-pointer"
                                @click="togglePhaseSelection({
                                    id: band.id,
                                    title: band.title,
                                    date: band.rawDate || '',
                                    duration: band.durationLabel,
                                    edssBefore: band.edssBefore,
                                    edssPeak: band.edssPeak,
                                    edssAfter: band.edssAfter,
                                    recovery: band.recoveryLabel,
                                    treatment: band.treatmentLabel,
                                })"
                            />

                            <!-- Band Header Pill (Compact and non-colliding) -->
                            <g
                                :transform="`translate(${band.startX + band.width / 2}, ${piecewiseModelData.plotTop - 12})`"
                                class="cursor-pointer"
                                @click="togglePhaseSelection({
                                    id: band.id,
                                    title: band.title,
                                    date: band.rawDate || '',
                                    duration: band.durationLabel,
                                    edssBefore: band.edssBefore,
                                    edssPeak: band.edssPeak,
                                    edssAfter: band.edssAfter,
                                    recovery: band.recoveryLabel,
                                    treatment: band.treatmentLabel,
                                })"
                            >
                                <rect
                                    :x="-Math.min(band.width / 2 - 2, 55)"
                                    y="-9"
                                    :width="Math.min(band.width - 4, 110)"
                                    height="18"
                                    rx="9"
                                    fill="#ffffff"
                                    :stroke="band.colorBorder"
                                    stroke-width="1"
                                    class="shadow-xs dark:fill-card"
                                />
                                <text
                                    x="0"
                                    y="3"
                                    text-anchor="middle"
                                    class="text-[9.5px] font-bold fill-foreground"
                                >
                                    {{ band.width >= 100 ? band.shortTitle : (band.type === 'relapse' ? `P#${band.id.replace(/\D/g, '') || ''}` : 'Rém.') }}
                                </text>
                            </g>
                        </g>

                        <!-- -------------------------------------------------- -->
                        <!-- 2. Y-AXIS (EDSS 0 to Max by 0.5 steps)             -->
                        <!-- -------------------------------------------------- -->
                        <g v-for="tick in piecewiseModelData.yTicks" :key="tick.score">
                            <line
                                :x1="piecewiseModelData.plotLeft"
                                :y1="tick.y"
                                :x2="piecewiseModelData.plotLeft + piecewiseModelData.plotWidth"
                                :y2="tick.y"
                                stroke="currentColor"
                                :stroke-width="tick.isMajor ? 0.9 : 0.5"
                                :stroke-dasharray="tick.isMajor ? '0' : '3 3'"
                                class="text-border/50"
                            />
                            <text
                                :x="piecewiseModelData.plotLeft - 8"
                                :y="tick.y + 3.5"
                                text-anchor="end"
                                class="text-[9.5px] font-mono select-none"
                                :class="tick.isMajor ? 'font-bold fill-foreground' : 'font-normal fill-muted-foreground'"
                            >
                                {{ tick.score.toFixed(1) }}
                            </text>
                        </g>

                        <!-- Y-Axis Title -->
                        <text
                            :x="piecewiseModelData.plotLeft - 40"
                            :y="piecewiseModelData.plotTop - 16"
                            text-anchor="start"
                            class="text-[9px] font-bold fill-indigo-600 dark:fill-indigo-400 uppercase tracking-wider"
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
                            stroke-width="1.5"
                            class="text-border"
                        />

                        <!-- Clean, Non-colliding X-Axis Labels -->
                        <g v-for="tick in piecewiseModelData.xTicks" :key="tick.label">
                            <line
                                :x1="tick.x"
                                :y1="piecewiseModelData.plotBottom"
                                :x2="tick.x"
                                :y2="piecewiseModelData.plotBottom + 5"
                                stroke="currentColor"
                                stroke-width="1"
                                class="text-muted-foreground"
                            />
                            <text
                                :x="tick.x"
                                :y="piecewiseModelData.plotBottom + 16"
                                text-anchor="middle"
                                class="text-[9px] font-mono font-bold fill-foreground"
                            >
                                {{ tick.label }}
                            </text>
                            <text
                                :x="tick.x"
                                :y="piecewiseModelData.plotBottom + 26"
                                text-anchor="middle"
                                class="text-[8px] font-sans fill-muted-foreground"
                            >
                                {{ tick.sublabel }}
                            </text>
                        </g>

                        <!-- -------------------------------------------------- -->
                        <!-- 4. SHADED AREA UNDER THE PIECEWISE EDSS LINE       -->
                        <!-- -------------------------------------------------- -->
                        <path
                            :d="piecewiseModelData.areaPath"
                            fill="url(#cleanAreaGradient)"
                        />

                        <!-- -------------------------------------------------- -->
                        <!-- 5. MAIN SOLID BLUE PIECEWISE EDSS LINE             -->
                        <!-- -------------------------------------------------- -->
                        <path
                            :d="piecewiseModelData.linePath"
                            fill="none"
                            stroke="#1d4ed8"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- -------------------------------------------------- -->
                        <!-- 6. CLINICAL NODE MARKERS & COMPACT SCORE BADGES    -->
                        <!-- -------------------------------------------------- -->
                        <g v-for="pt in piecewiseModelData.points" :key="pt.id" class="group">
                            <!-- Clean Node Circle -->
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="5"
                                fill="#ffffff"
                                :stroke="selectedPhaseInfo?.id === pt.id ? '#2563eb' : pt.color"
                                :stroke-width="selectedPhaseInfo?.id === pt.id ? 4 : 2.8"
                                class="transition-transform duration-150 hover:scale-135 cursor-pointer"
                                @click="togglePhaseSelection({
                                    id: pt.id,
                                    title: pt.badgeLabel,
                                    date: pt.dateFormatted,
                                    duration: pt.phase === 't1_start' ? 'Acmé aiguë' : (pt.phase === 't2_end' ? 'Post-récupération' : 'État de base'),
                                    edssBefore: pt.score,
                                    edssAfter: pt.score,
                                })"
                            />

                            <!-- Compact Minimalist Badge (Only if space allows) -->
                            <g
                                v-if="pt.showLabel"
                                :transform="`translate(${pt.x}, ${pt.phase === 't1_start' ? pt.y - 14 : pt.y + 16})`"
                                class="cursor-pointer"
                                @click="togglePhaseSelection({
                                    id: pt.id,
                                    title: pt.badgeLabel,
                                    date: pt.dateFormatted,
                                    duration: pt.phase === 't1_start' ? 'Acmé aiguë' : (pt.phase === 't2_end' ? 'Post-récupération' : 'État de base'),
                                    edssBefore: pt.score,
                                    edssAfter: pt.score,
                                })"
                            >
                                <rect
                                    :x="-18"
                                    y="-7"
                                    width="36"
                                    height="14"
                                    rx="4"
                                    fill="#ffffff"
                                    stroke="currentColor"
                                    stroke-width="0.8"
                                    class="text-border fill-card shadow-2xs"
                                />
                                <text
                                    x="0"
                                    y="3.5"
                                    text-anchor="middle"
                                    class="text-[8.5px] font-mono font-bold"
                                    :fill="pt.color"
                                >
                                    {{ pt.score.toFixed(1) }}
                                </text>
                            </g>
                        </g>
                    </svg>
                </div>

                <!-- Minimalist Clean Legend -->
                <div class="flex flex-wrap items-center justify-between text-[11px] text-muted-foreground px-1 gap-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-blue-600"></span>
                            <span>Score basal (t₀)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-rose-600"></span>
                            <span>Pic aigu / Acmé (t₁)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-emerald-600"></span>
                            <span>Récupération (t₂)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-2 rounded-xs bg-rose-500/20 border border-rose-500/40"></span>
                            <span>Poussée</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-2 rounded-xs bg-sky-500/20 border border-sky-500/40"></span>
                            <span>Rémission</span>
                        </span>
                    </div>

                    <span class="italic text-[10px]">
                        Cliquez sur une poussée ou un point pour afficher ses détails cliniques
                    </span>
                </div>

                <!-- ============================================================== -->
                <!-- 7. COLLAPSIBLE ACCORDION: TABLEAU RÉCAPITULATIF & FORMULE      -->
                <!-- ============================================================== -->
                <div class="border-t border-border/60 pt-2">
                    <button
                        type="button"
                        @click="showMethodologyDetails = !showMethodologyDetails"
                        class="flex items-center justify-between w-full p-2.5 rounded-lg bg-muted/30 hover:bg-muted/50 border border-border/60 text-xs font-semibold text-foreground transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <FileSpreadsheet class="size-3.5 text-primary" />
                            <span>Récapitulatif des valeurs & modélisation mathématique EDSS(t)</span>
                        </span>
                        <span class="flex items-center gap-1 text-[11px] font-normal text-muted-foreground">
                            <span>{{ showMethodologyDetails ? 'Masquer' : 'Afficher les détails méthodologiques' }}</span>
                            <ChevronUp v-if="showMethodologyDetails" class="size-3.5" />
                            <ChevronDown v-else class="size-3.5" />
                        </span>
                    </button>

                    <!-- Collapsible Container -->
                    <div v-if="showMethodologyDetails" class="mt-3 space-y-3 animate-in fade-in duration-200">
                        <!-- Summary Table -->
                        <div class="rounded-lg border border-border/70 overflow-hidden shadow-2xs">
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-muted/40 border-b border-border/60 text-muted-foreground text-[10.5px]">
                                        <tr>
                                            <th class="py-2 px-3 font-semibold">Phase / Poussée</th>
                                            <th class="py-2 px-2.5 font-semibold">Durée</th>
                                            <th class="py-2 px-2.5 font-semibold">EDSS avant (t₀)</th>
                                            <th class="py-2 px-2.5 font-semibold">EDSS pic (t₁)</th>
                                            <th class="py-2 px-2.5 font-semibold">EDSS fin (t₂)</th>
                                            <th class="py-2 px-2.5 font-semibold">Variation (Δ)</th>
                                            <th class="py-2 px-2.5 font-semibold">Récupération</th>
                                            <th class="py-2 px-3 font-semibold">Traitement</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/50 font-mono text-[11px]">
                                        <tr
                                            v-for="(row, idx) in piecewiseModelData.summaryRows"
                                            :key="idx"
                                            class="hover:bg-muted/20 transition-colors"
                                        >
                                            <td class="py-1.5 px-3 font-sans font-semibold text-foreground">
                                                {{ row.phase }}
                                            </td>
                                            <td class="py-1.5 px-2.5 text-muted-foreground">
                                                {{ row.duration }}
                                            </td>
                                            <td class="py-1.5 px-2.5 font-bold text-blue-600 dark:text-blue-400">
                                                {{ row.edssBefore }}
                                            </td>
                                            <td class="py-1.5 px-2.5 font-bold text-rose-600 dark:text-rose-400">
                                                {{ row.edssPeak }}
                                            </td>
                                            <td class="py-1.5 px-2.5 font-bold text-emerald-600 dark:text-emerald-400">
                                                {{ row.edssAfter }}
                                            </td>
                                            <td class="py-1.5 px-2.5" :class="row.deltaClass">
                                                {{ row.delta }}
                                            </td>
                                            <td class="py-1.5 px-2.5 font-sans text-foreground">
                                                <Badge variant="outline" class="text-[9.5px] py-0 font-normal">
                                                    {{ row.status }}
                                                </Badge>
                                            </td>
                                            <td class="py-1.5 px-3 font-sans text-muted-foreground text-[10.5px]">
                                                {{ row.treatment }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mathematical Piecewise Box -->
                        <div class="rounded-lg border border-indigo-500/20 bg-indigo-500/5 p-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-foreground text-[11px] uppercase tracking-wider">
                                    Fonction Mathématique Linéaire par Morceaux
                                </span>
                                <Badge variant="outline" class="text-[9.5px] font-mono border-indigo-500/30 text-indigo-700 dark:text-indigo-300">
                                    EDSS(t)
                                </Badge>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="p-2.5 rounded bg-background/80 border border-border/70 font-mono text-[10.5px] space-y-1">
                                    <div class="font-bold text-indigo-600">EDSS(t) =</div>
                                    <div class="pl-3 space-y-0.5 border-l-2 border-indigo-500/40">
                                        <div>• EDSS₀ <span class="text-muted-foreground font-sans">si t ≤ t₀ (plateau avant poussée)</span></div>
                                        <div>• EDSS₀ + (EDSS₁ - EDSS₀) · <span class="underline">(t - t₀)</span> / (t₁ - t₀) <span class="text-muted-foreground font-sans">si t₀ &lt; t &lt; t₁</span></div>
                                        <div>• EDSS₁ - (EDSS₁ - EDSS₂) · <span class="underline">(t - t₁)</span> / Δt <span class="text-muted-foreground font-sans">si t₁ ≤ t ≤ t₁ + Δt</span></div>
                                        <div>• EDSS₂ <span class="text-muted-foreground font-sans">si t &gt; t₁ + Δt (plateau stabilisé)</span></div>
                                    </div>
                                </div>

                                <div class="p-2.5 rounded bg-background/80 border border-border/70 font-mono text-[10.5px] space-y-1">
                                    <span class="text-[10px] text-muted-foreground font-sans font-semibold block">Paramètres de la 1ère poussée :</span>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">EDSS avant (t₀) :</span>
                                        <span class="font-bold text-blue-600">{{ piecewiseModelData.formulaData.edssBefore }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">EDSS pic aigu (t₁) :</span>
                                        <span class="font-bold text-rose-600">{{ piecewiseModelData.formulaData.edssPeak }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">EDSS final (t₂) :</span>
                                        <span class="font-bold text-emerald-600">{{ piecewiseModelData.formulaData.edssAfter }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Durée (Δt) :</span>
                                        <span class="font-bold text-foreground">{{ piecewiseModelData.formulaData.durationDays }} jours</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-1.5 pt-1 text-[10.5px] text-muted-foreground border-t border-border/50">
                                <Info class="size-3 text-indigo-500 shrink-0 mt-0.5" />
                                <span>
                                    <strong>Précaution clinique :</strong> L'échelle EDSS est ordinale. Les segments de droite sont purement illustratifs pour matérialiser la pente d'aggravation et de récupération. Les scores réels correspondent aux points mesurés.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- VIEW 2: ANNUAL DISTRIBUTION HISTOGRAM (ARR)                   -->
            <!-- ============================================================== -->
            <div v-else-if="activeView === 'bars'" class="space-y-3">
                <div class="rounded-xl bg-card border border-border/70 p-4 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
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
                        <svg viewBox="0 0 720 160" class="w-full min-w-[500px] h-40 overflow-visible select-none font-sans">
                            <defs>
                                <linearGradient id="annualBarGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.75" />
                                </linearGradient>
                            </defs>

                            <line x1="30" y1="115" x2="690" y2="115" stroke="currentColor" stroke-width="1.5" class="text-border" />

                            <g v-for="item in annualDistributionData.items" :key="item.year" class="group">
                                <rect
                                    v-if="item.count > 0"
                                    :x="item.x - 16"
                                    :y="115 - (item.count / annualDistributionData.maxCount) * 75"
                                    width="32"
                                    :height="Math.max(10, (item.count / annualDistributionData.maxCount) * 75)"
                                    rx="4"
                                    fill="url(#annualBarGradient)"
                                    class="hover:brightness-110 transition-all cursor-pointer shadow-xs"
                                />
                                <circle v-else :cx="item.x" cy="115" r="3" class="fill-muted-foreground/40" />

                                <text
                                    v-if="item.count > 0"
                                    :x="item.x"
                                    :y="115 - (item.count / annualDistributionData.maxCount) * 75 - 6"
                                    text-anchor="middle"
                                    class="text-[11px] font-mono font-bold fill-foreground"
                                >
                                    {{ item.count }}
                                </text>

                                <text
                                    :x="item.x"
                                    y="134"
                                    text-anchor="middle"
                                    class="text-[11px] font-mono font-bold fill-foreground group-hover:fill-indigo-600 transition-colors"
                                >
                                    {{ item.year }}
                                </text>

                                <text
                                    v-if="item.count > 0"
                                    :x="item.x"
                                    y="146"
                                    text-anchor="middle"
                                    class="text-[9.5px] font-mono fill-indigo-600 dark:fill-indigo-400 font-medium"
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
