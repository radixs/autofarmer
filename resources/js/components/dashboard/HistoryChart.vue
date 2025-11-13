<template>
    <section class="flex h-full flex-col rounded-3xl bg-slate-900/60 p-5 shadow-lg shadow-black/40">
        <header class="mb-2">
            <p class="text-xs uppercase tracking-wide text-slate-400">Graph</p>
            <p class="text-lg font-semibold text-white">{{ title }}</p>
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
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
} from 'chart.js';
import { Line } from 'vue-chartjs';

Chart.register(LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend);

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
        collections.push({
            type: 'line',
            label: `${getLabel(slug)} avg`,
            data: rows.map((row) => row.value_avg),
            borderColor: color,
            backgroundColor: color,
            tension: 0.3,
            fill: false,
        });
        collections.push({
            type: 'scatter',
            label: `${getLabel(slug)} max`,
            data: rows.map((row, idx) => ({ x: labels.value[idx], y: row.value_max })),
            pointBorderColor: color,
            pointBackgroundColor: color,
            pointRadius: 4,
            showLine: false,
        });
        collections.push({
            type: 'scatter',
            label: `${getLabel(slug)} min`,
            data: rows.map((row, idx) => ({ x: labels.value[idx], y: row.value_min })),
            pointBorderColor: '#0f172a',
            pointBackgroundColor: '#0f172a',
            pointRadius: 4,
            showLine: false,
        });
    });

    return collections;
});

const chartData = computed(() => ({
    labels: labels.value,
    datasets: datasets.value,
}));

const chartOptions = {
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
        y: {
            ticks: { color: '#94a3b8' },
            grid: { color: '#1e293b' },
        },
    },
};

const title = computed(() => `${props.filters.interval.charAt(0).toUpperCase() + props.filters.interval.slice(1)} trend`);

function getLabel(slug) {
    return definitionMap.value[slug]?.label ?? slug;
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
