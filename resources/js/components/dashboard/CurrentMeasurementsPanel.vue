<template>
    <section class="flex h-full flex-col rounded-3xl bg-slate-900/70 p-5 shadow-lg shadow-black/40">
        <header class="mb-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Current measurements</p>
            <p class="text-lg font-semibold text-white">Live snapshot</p>
            <p class="text-xs text-slate-500">Times shown in {{ timezone }}</p>
        </header>
        <div class="flex-1 space-y-3 overflow-y-auto pr-2">
            <article
                v-for="item in decoratedMeasurements"
                :key="item.name"
                class="rounded-2xl border border-slate-800/80 bg-slate-900/80 p-3"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ item.label }}</p>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ item.source }}</p>
                    </div>
                    <p
                        class="text-xl font-bold"
                        :class="[
                            item.outOfRange ? 'text-red-400 animate-pulse-fast' : 'text-emerald-300',
                        ]"
                    >
                        {{ item.value }}<span class="text-xs font-medium text-slate-400"> {{ item.unit }}</span>
                    </p>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                    <span>
                        Recorded
                        <span :class="item.isStale ? 'text-sky-300 animate-pulse-slow font-semibold' : 'text-white'">
                            {{ item.displayTime }}
                        </span>
                    </span>
                    <span>•</span>
                    <span>{{ item.timeAgo }} ago</span>
                    <span v-if="item.range" class="rounded-full bg-slate-800 px-2 py-[1px]">
                        {{ item.range.min }} – {{ item.range.max }} {{ item.unit }}
                    </span>
                </div>
            </article>
            <p v-if="!decoratedMeasurements.length" class="text-sm text-slate-500">No measurements recorded yet.</p>
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
