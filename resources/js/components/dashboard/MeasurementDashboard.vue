<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="mx-auto flex h-screen max-w-screen-2xl flex-col gap-6 p-6">
            <header class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Autofarmer</p>
                    <h1 class="text-3xl font-semibold text-white">Aquarium telemetry console</h1>
                </div>
                <p class="text-sm text-slate-400">Local timezone: {{ timezone }}</p>
            </header>

            <div class="flex flex-1 gap-6 overflow-hidden">
                <div class="flex w-1/4 flex-col gap-6">
                    <CurrentMeasurementsPanel
                        :measurements="currentMeasurements"
                        :definitions="definitions"
                        :timezone="timezone"
                    />
                    <ManualEntryForm
                        :definitions="definitions"
                        :submitting="formSubmitting"
                        :feedback="formFeedback"
                        :error="formError"
                        @submit="handleManualSubmit"
                    />
                </div>
                <div class="flex w-3/4 flex-col gap-6">
                    <HistoryTable
                        :history="measurementHistory"
                        :filters="filters"
                        :definitions="definitions"
                        :loading="loading"
                        :timezone="timezone"
                        @change-interval="handleInterval"
                        @change-page="handlePage"
                        @change-dates="handleDates"
                        @change-names="handleNames"
                        @export="handleExport"
                    />
                    <HistoryChart
                        :history="measurementHistory"
                        :filters="filters"
                        :definitions="definitions"
                        :timezone="timezone"
                    />
                </div>
            </div>

            <p v-if="error" class="rounded-2xl bg-red-500/20 px-4 py-2 text-sm text-red-200">
                {{ error }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useStore } from 'vuex';
import CurrentMeasurementsPanel from './CurrentMeasurementsPanel.vue';
import ManualEntryForm from './ManualEntryForm.vue';
import HistoryTable from './HistoryTable.vue';
import HistoryChart from './HistoryChart.vue';

const store = useStore();

const currentMeasurements = computed(() => store.state.currentMeasurements);
const measurementHistory = computed(() => store.state.measurementHistory);
const filters = computed(() => store.state.filters);
const definitions = computed(() => store.state.measurementDefinitions);
const timezone = computed(() => store.state.timezone);
const loading = computed(() => store.state.loading);
const error = computed(() => store.state.error);

const formSubmitting = ref(false);
const formFeedback = ref('');
const formError = ref('');
let feedbackTimer = null;

onMounted(() => {
    store.dispatch('init');
});

onBeforeUnmount(() => {
    store.dispatch('disconnectChannel');
    if (feedbackTimer) {
        clearTimeout(feedbackTimer);
    }
});

const handleManualSubmit = async ({ entries }) => {
    formSubmitting.value = true;
    formError.value = '';
    formFeedback.value = '';

    try {
        await store.dispatch('submitManualMeasurements', { entries });
        formFeedback.value = 'Measurements stored successfully.';
        if (feedbackTimer) {
            clearTimeout(feedbackTimer);
        }
        feedbackTimer = window.setTimeout(() => (formFeedback.value = ''), 5000);
    } catch (error) {
        formError.value = error?.response?.data?.message ?? error.message ?? 'Unable to submit measurements.';
    } finally {
        formSubmitting.value = false;
    }
};

const handleInterval = (interval) => {
    store.dispatch('changeInterval', interval);
};

const handlePage = (page) => {
    store.dispatch('changePage', page);
};

const handleDates = ({ date_from, date_to }) => {
    store.dispatch('updateDateRange', { date_from, date_to });
};

const handleNames = (names) => {
    store.dispatch('updateNames', names);
};

const handleExport = () => {
    const rows = measurementHistory.value.data ?? [];
    if (! rows.length) {
        return;
    }

    const headers = ['ID', 'From', 'To', 'Measurement', 'Avg', 'Max', 'Min', 'Source'];
    const csvRows = [headers.join(',')];

    rows.forEach((row) => {
        csvRows.push(
            [
                row.id,
                row.range_start_at,
                row.range_end_at,
                row.name,
                row.value_avg,
                row.value_max,
                row.value_min,
                row.source ?? 'mixed',
            ].join(',')
        );
    });

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `measurements-${filters.value.interval}.csv`;
    link.click();
    window.URL.revokeObjectURL(url);
};
</script>
