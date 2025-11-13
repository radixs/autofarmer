<template>
    <section class="flex h-full flex-col rounded-3xl bg-slate-900/80 p-5 shadow-xl shadow-black/40">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Measurement history</p>
                <p class="text-lg font-semibold text-white">{{ activeLabel }} overview</p>
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-slate-400">
                <label class="flex items-center gap-2">
                    From
                    <input
                        type="datetime-local"
                        class="rounded-xl border border-slate-800 bg-slate-950/70 px-2 py-1 text-white focus:border-emerald-400 focus:outline-none"
                        :value="dateFromValue"
                        @change="emitDateChange($event.target.value, filters.date_to)"
                    />
                </label>
                <label class="flex items-center gap-2">
                    To
                    <input
                        type="datetime-local"
                        class="rounded-xl border border-slate-800 bg-slate-950/70 px-2 py-1 text-white focus:border-emerald-400 focus:outline-none"
                        :value="dateToValue"
                        @change="emitDateChange(filters.date_from, $event.target.value)"
                    />
                </label>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <button
                    v-for="option in intervals"
                    :key="option.slug"
                    class="rounded-full px-4 py-1 text-sm"
                    :class="[
                        option.value === filters.interval
                            ? 'bg-emerald-500 text-slate-950'
                            : 'bg-slate-800/80 text-slate-400 hover:bg-slate-700/80',
                    ]"
                    @click="emitInterval(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                <label class="font-semibold">Filter</label>
                <select
                    multiple
                    class="h-10 min-w-[180px] rounded-xl border border-slate-800 bg-slate-950/70 px-2 py-1 text-white focus:border-emerald-400 focus:outline-none"
                    :value="filters.names"
                    @change="onNamesChange($event.target.selectedOptions)"
                >
                    <option v-for="definition in definitions" :key="definition.slug" :value="definition.slug">
                        {{ definition.label }}
                    </option>
                </select>
                <button
                    type="button"
                    class="rounded-full border border-emerald-400 px-3 py-1 text-emerald-300 hover:bg-emerald-400 hover:text-slate-950"
                    @click="emitExport"
                >
                    Export CSV
                </button>
            </div>
        </div>

        <div class="mt-4 flex-1 overflow-hidden">
            <div class="h-full overflow-auto rounded-2xl border border-slate-800/80">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-950/60 text-left text-xs uppercase text-slate-400">
                        <tr>
                            <th v-for="column in columns" :key="column.key" class="px-4 py-3">
                                <button class="flex items-center gap-1" @click="toggleSort(column.key)">
                                    <span>{{ column.label }}</span>
                                    <span v-if="sort.key === column.key">{{ sort.direction === 'asc' ? '▲' : '▼' }}</span>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td class="px-4 py-6 text-center text-slate-500" colspan="7">Loading...</td>
                        </tr>
                        <tr v-else-if="!sortedRows.length">
                            <td class="px-4 py-6 text-center text-slate-500" colspan="7">No data for selected range.</td>
                        </tr>
                        <tr v-for="row in sortedRows" :key="row.id" class="border-b border-slate-800/50">
                            <td class="px-4 py-3 text-slate-200">{{ row.id }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ formatLocal(row.range_start_at) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ formatLocal(row.range_end_at) }}</td>
                            <td class="px-4 py-3 text-white">{{ getLabel(row.name) }}</td>
                            <td class="px-4 py-3 text-emerald-300">{{ row.value_avg }}</td>
                            <td class="px-4 py-3 text-amber-300">{{ row.value_max }}</td>
                            <td class="px-4 py-3 text-sky-300">{{ row.value_min }}</td>
                            <td class="px-4 py-3 text-xs uppercase text-slate-500">{{ row.source ?? 'mixed' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
            <button
                class="rounded-full border border-slate-700 px-3 py-1 hover:border-slate-500"
                :disabled="meta.current_page <= 1"
                @click="emitPage(meta.current_page - 1)"
            >
                Previous
            </button>
            <p>
                Page {{ meta.current_page }} of {{ meta.last_page }} • {{ meta.total }} rows
            </p>
            <button
                class="rounded-full border border-slate-700 px-3 py-1 hover:border-slate-500"
                :disabled="meta.current_page >= meta.last_page"
                @click="emitPage(meta.current_page + 1)"
            >
                Next
            </button>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';

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
    loading: {
        type: Boolean,
        default: false,
    },
    timezone: {
        type: String,
        default: 'UTC',
    },
});

const emit = defineEmits(['change-interval', 'change-page', 'change-dates', 'change-names', 'export']);

const sort = ref({ key: 'range_start_at', direction: 'desc' });

const intervals = [
    { value: 'hourly', label: 'Hourly' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
];

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'range_start_at', label: 'From' },
    { key: 'range_end_at', label: 'To' },
    { key: 'name', label: 'Measurement' },
    { key: 'value_avg', label: 'Avg' },
    { key: 'value_max', label: 'Max' },
    { key: 'value_min', label: 'Min' },
    { key: 'source', label: 'Source' },
];

const meta = computed(() => props.history.meta ?? { current_page: 1, last_page: 1, total: 0, per_page: 20 });

const sortedRows = computed(() => {
    const rows = [...(props.history.data ?? [])];
    return rows.sort((a, b) => {
        const dir = sort.value.direction === 'asc' ? 1 : -1;
        if (a[sort.value.key] < b[sort.value.key]) return -1 * dir;
        if (a[sort.value.key] > b[sort.value.key]) return 1 * dir;
        return 0;
    });
});

const dateFromValue = computed(() => props.filters.date_from?.replace(' ', 'T'));
const dateToValue = computed(() => props.filters.date_to?.replace(' ', 'T'));

const toggleSort = (key) => {
    if (sort.value.key === key) {
        sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = { key, direction: 'desc' };
    }
};

const emitInterval = (interval) => {
    if (interval !== props.filters.interval) {
        emit('change-interval', interval);
    }
};

const emitPage = (page) => {
    emit('change-page', page);
};

const emitDateChange = (from, to) => {
    emit('change-dates', {
        date_from: from ? toUtc(from) : props.filters.date_from,
        date_to: to ? toUtc(to) : props.filters.date_to,
    });
};

const onNamesChange = (selected) => {
    const names = Array.from(selected).map((option) => option.value);
    emit('change-names', names);
};

const emitExport = () => {
    emit('export');
};

const getLabel = (slug) => {
    return props.definitions.find((item) => item.slug === slug)?.label ?? slug;
};

const formatLocal = (value) => {
    const date = new Date(value.replace(' ', 'T') + 'Z');
    return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: props.timezone,
    }).format(date);
};

const toUtc = (value) => {
    if (! value) {
        return null;
    }

    const date = new Date(value);
    return (
        date.getUTCFullYear() +
        '-' +
        String(date.getUTCMonth() + 1).padStart(2, '0') +
        '-' +
        String(date.getUTCDate()).padStart(2, '0') +
        ' ' +
        String(date.getUTCHours()).padStart(2, '0') +
        ':' +
        String(date.getUTCMinutes()).padStart(2, '0') +
        ':' +
        String(date.getUTCSeconds()).padStart(2, '0')
    );
};

const activeLabel = computed(() => intervals.find((option) => option.value === props.filters.interval)?.label ?? 'Hourly');
</script>
