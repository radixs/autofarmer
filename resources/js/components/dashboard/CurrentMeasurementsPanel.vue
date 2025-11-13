<template>
    <section class="flex h-full flex-col rounded-3xl bg-slate-900/70 p-4 shadow-lg shadow-black/40">
        <header class="mb-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Current measurements</p>
            <p class="text-xs text-slate-500">Times shown in {{ timezone }}</p>
        </header>
        <div class="flex-1 overflow-y-auto">
            <table class="min-w-full text-left text-xs text-slate-300">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Metric</th>
                        <th class="py-2 pr-3">Value</th>
                        <th class="py-2 pr-3">Source</th>
                        <th class="py-2 pr-3">Range</th>
                        <th class="py-2 pr-3">Recorded</th>
                        <th class="py-2 pr-3">Elapsed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in decoratedMeasurements"
                        :key="item.name"
                        class="border-b border-slate-800/40 text-sm"
                    >
                        <td class="py-2 pr-3 font-semibold text-white">{{ item.label }}</td>
                        <td class="py-2 pr-3">
                            <span
                                class="font-bold"
                                :class="[item.outOfRange ? 'text-red-400 animate-pulse-fast' : 'text-emerald-300']"
                            >
                                {{ item.value }}
                            </span>
                            <span class="pl-1 text-[11px] text-slate-400">{{ item.unit }}</span>
                        </td>
                        <td class="py-2 pr-3 uppercase text-[10px] tracking-wide text-slate-500">{{ item.source }}</td>
                        <td class="py-2 pr-3 text-[11px] text-slate-400">
                            <span v-if="item.range">{{ item.range.min }} – {{ item.range.max }}</span>
                            <span v-else>—</span>
                        </td>
                        <td class="py-2 pr-3 text-[11px] text-slate-400">
                            <span :class="item.isStale ? 'text-sky-300 animate-pulse-slow font-semibold' : 'text-white'">
                                {{ item.displayTime }}
                            </span>
                        </td>
                        <td class="py-2 pr-3 text-[11px] text-slate-400">{{ item.timeAgo }}</td>
                    </tr>
                    <tr v-if="!decoratedMeasurements.length">
                        <td class="py-4 text-center text-slate-500" colspan="6">No measurements recorded yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    measurements: {
        type: Array,
        default: () => [],
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

const now = ref(Date.now());
let timer = null;

onMounted(() => {
    timer = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});

const definitionMap = computed(() => {
    return props.definitions.reduce((carry, def) => {
        carry[def.slug] = def;
        return carry;
    }, {});
});

const decoratedMeasurements = computed(() => {
    return props.measurements.map((measurement) => decorateMeasurement(measurement));
});

const msInMonth = 1000 * 60 * 60 * 24 * 30;

const decorateMeasurement = (measurement) => {
    const definition = definitionMap.value[measurement.name] ?? {};
    const range = definition.range ?? null;
    const parsedDate = parseUtc(measurement.created_at);
    const age = now.value - parsedDate.getTime();

    const outOfRange = range
        ? measurement.value < range.min || measurement.value > range.max
        : false;

    return {
        ...measurement,
        label: definition.label ?? measurement.name,
        range,
        outOfRange,
        isStale: age > msInMonth,
        displayTime: formatForTimezone(parsedDate, props.timezone),
        timeAgo: formatDuration(age),
    };
};

const parseUtc = (value) => {
    return new Date(value.replace(' ', 'T') + 'Z');
};

const formatForTimezone = (date, timezone) => {
    return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: timezone,
    }).format(date);
};

const formatDuration = (milliseconds) => {
    if (milliseconds < 0) {
        return 'just now';
    }

    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    const parts = [];
    if (days) parts.push(`${days}d`);
    if (hours % 24) parts.push(`${hours % 24}h`);
    if (minutes % 60) parts.push(`${minutes % 60}m`);
    parts.push(`${seconds % 60}s`);

    return parts.slice(0, 4).join(' ');
};
</script>
