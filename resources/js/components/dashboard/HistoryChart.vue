<template>
    <section class="flex h-full flex-col rounded-3xl bg-slate-900/60 p-5 shadow-lg shadow-black/40">
        <header class="mb-2">
            <p class="text-xs uppercase tracking-wide text-slate-400">Graph</p>
        </header>
        <div class="flex-1">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import {
    Chart,
    LineElement,
    PointElement,
    LineController,
    ScatterController,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
} from 'chart.js';
import { Line } from 'vue-chartjs';

Chart.register(LineController, ScatterController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend);

const props = defineProps({
    history: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    definitions: {
        type: Array,
        default: () => [],
    },
    timezone: {
        type: String,
        default: 'UTC',
    },
});

const palette = ['#10b981', '#38bdf8', '#f472b6', '#facc15', '#f97316', '#c084fc', '#67e8f9', '#2dd4bf', '#fcd34d'];

const definitionMap = computed(() => props.definitions.reduce((carry, item) => ((carry[item.slug] = item), carry), {}));

const axisMeta = computed(() => {
    const meta = {};

    (props.history.data ?? []).forEach((row) => {
        const definition = definitionMap.value[row.name];
        if (! definition) {
            return;
        }

        const unit = definition.unit || '';
        if (! meta[unit]) {
            meta[unit] = {
                axisId: axisIdForUnit(unit),
                unit,
                range: definition.range ?? null,
                positionIndex: Object.keys(meta).length,
            };
        }
    });

    return meta;
});

const labels = computed(() => {
    return (props.history.data ?? []).map((row) => formatLocal(row.range_start_at));
});

const datasets = computed(() => {
    const grouped = {};

    (props.history.data ?? []).forEach((row) => {
        if (! grouped[row.name]) {
            grouped[row.name] = [];
        }
        grouped[row.name].push(row);
    });

    const collections = [];
    Object.entries(grouped).forEach(([slug, rows], index) => {
        const color = palette[index % palette.length];
        const definition = definitionMap.value[slug];
        const unit = definition?.unit ?? '';
        const axis = axisMeta.value[unit] ?? { axisId: axisIdForUnit(unit) };
        collections.push({
            type: 'line',
            label: `${getLabel(slug)} avg`,
            data: rows.map((row) => row.value_avg),
            borderColor: color,
            backgroundColor: color,
            tension: 0.3,
            fill: false,
            yAxisID: axis.axisId,
        });
        collections.push({
            type: 'scatter',
            label: `${getLabel(slug)} max`,
            data: rows.map((row, idx) => ({ x: labels.value[idx], y: row.value_max })),
            pointBorderColor: color,
            pointBackgroundColor: color,
            pointRadius: 4,
            showLine: false,
            yAxisID: axis.axisId,
        });
        collections.push({
            type: 'scatter',
            label: `${getLabel(slug)} min`,
            data: rows.map((row, idx) => ({ x: labels.value[idx], y: row.value_min })),
            pointBorderColor: toRgba(color, 0.35),
            pointBackgroundColor: toRgba(color, 0.35),
            pointRadius: 4,
            showLine: false,
            yAxisID: axis.axisId,
        });
    });

    return collections;
});

const chartData = computed(() => ({
    labels: labels.value,
    datasets: datasets.value,
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            labels: {
                color: '#cbd5f5',
            },
        },
        tooltip: {
            callbacks: {
                label: (context) => `${context.dataset.label}: ${context.parsed.y}`,
            },
        },
    },
    scales: {
        x: {
            ticks: { color: '#94a3b8' },
            grid: { color: '#1e293b' },
        },
        ...buildAxisScales(),
    },
}));

function getLabel(slug) {
    return definitionMap.value[slug]?.label ?? slug;
}

function axisIdForUnit(unit) {
    return `axis-${unit.toLowerCase().replace(/[^a-z0-9]+/g, '-') || 'other'}`;
}

function buildAxisScales() {
    const config = {};
    const axes = Object.values(axisMeta.value);

    axes.forEach((axis, index) => {
        const position = index % 2 === 0 ? 'left' : 'right';
        const range = axis.range;
        const span = range ? range.max - range.min || 1 : 1;

        config[axis.axisId] = {
            type: 'linear',
            display: true,
            position,
            ticks: { color: '#94a3b8' },
            grid: {
                color: index === 0 ? '#1e293b' : 'transparent',
                drawOnChartArea: index === 0,
            },
            title: {
                display: true,
                text: axis.unit || 'value',
                color: '#cbd5f5',
            },
            suggestedMin: range ? range.min - span * 0.5 : undefined,
            suggestedMax: range ? range.max + span * 0.5 : undefined,
        };
    });

    if (! axes.length) {
        config.y = {
            type: 'linear',
            ticks: { color: '#94a3b8' },
            grid: { color: '#1e293b' },
        };
    }

    return config;
}

function toRgba(hex, alpha) {
    const bigint = parseInt(hex.slice(1), 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function formatLocal(value) {
    const date = new Date(value.replace(' ', 'T') + 'Z');
    return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: props.timezone,
    }).format(date);
}
</script>
