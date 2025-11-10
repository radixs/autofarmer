<template>
    <div class="mx-auto max-w-5xl p-6 space-y-6">
        <header class="space-y-1">
            <p class="text-sm uppercase tracking-wide text-slate-500">Autofarmer</p>
            <h1 class="text-3xl font-semibold text-slate-900">
                Test Entry Console
            </h1>
            <p class="text-sm text-slate-500">
                Entries refresh automatically every {{ pollSeconds }} seconds.
                <span v-if="formattedLastUpdated">
                    Last update: {{ formattedLastUpdated }}.
                </span>
            </p>
        </header>

        <section class="grid gap-6 md:grid-cols-2">
            <form @submit.prevent="handleSubmit" class="space-y-4 rounded-xl bg-white p-5 shadow-sm">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700" for="title">Title</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        required
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Pump status update"
                    />
                </div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700" for="payload">Payload (JSON)</label>
                        <button
                            type="button"
                            class="text-xs font-semibold text-indigo-600 hover:underline"
                            @click="applyPayloadTemplate"
                        >
                            Use template
                        </button>
                    </div>
                    <textarea
                        id="payload"
                        v-model="form.payload"
                        rows="6"
                        required
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono focus:border-indigo-500 focus:outline-none"
                        placeholder='{"value": 42}'
                    ></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700" for="source">Source</label>
                        <input
                            id="source"
                            v-model="form.source"
                            type="text"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                            placeholder="gui"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700" for="status">Status</label>
                        <input
                            id="status"
                            v-model="form.status"
                            type="text"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                            placeholder="new"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700" for="recorded_at">Recorded at</label>
                        <input
                            id="recorded_at"
                            v-model="form.recorded_at"
                            type="datetime-local"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <button
                        :disabled="submitting"
                        type="submit"
                        class="w-full rounded-md bg-indigo-600 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                    >
                        {{ submitting ? 'Submitting...' : 'Create test entry' }}
                    </button>
                    <p v-if="notification" class="text-sm text-slate-600">
                        {{ notification }}
                    </p>
                    <p v-if="error" class="text-sm text-red-600">
                        {{ error }}
                    </p>
                </div>
            </form>

            <section class="rounded-xl bg-white p-5 shadow-sm">
                <header class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Entry stream</p>
                        <p class="text-xs text-slate-500">
                            {{ entries.length }} total records
                        </p>
                    </div>
                    <button
                        class="text-xs font-semibold text-indigo-600 hover:underline"
                        @click="refreshNow"
                    >
                        Refresh
                    </button>
                </header>

                <div class="mt-4 max-h-[420px] space-y-3 overflow-y-auto pr-2">
                    <p v-if="loading" class="text-sm text-slate-500">Loading entries…</p>
                    <p v-else-if="!entries.length" class="text-sm text-slate-500">
                        Nothing stored yet. Submit something on the left or hit the API.
                    </p>

                    <article
                        v-for="entry in entries"
                        :key="entry.id"
                        class="rounded-lg border border-slate-100 bg-slate-50 p-3"
                    >
                        <header class="flex items-center justify-between text-sm">
                            <p class="font-semibold text-slate-800">
                                {{ entry.title }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ formatDate(entry.created_at) }}
                            </p>
                        </header>
                        <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">
                            Source: {{ entry.source }} &middot; Status: {{ entry.status }}
                        </p>
                        <pre class="mt-2 overflow-x-auto rounded bg-white p-2 text-xs text-slate-700">{{ formatPayload(entry.payload) }}</pre>
                    </article>
                </div>
            </section>
        </section>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useStore } from 'vuex';

const store = useStore();
const entries = computed(() => store.state.entries);
const loading = computed(() => store.state.loading);
const error = computed(() => store.state.error);
const formattedLastUpdated = computed(() => {
    if (!store.state.lastFetchedAt) {
        return '';
    }

    return new Date(store.state.lastFetchedAt).toLocaleTimeString();
});

const pollSeconds = 4;
let pollTimer = null;

const form = reactive({
    title: '',
    payload: JSON.stringify({ metric: 'moisture', value: 42 }, null, 2),
    source: 'gui',
    status: 'new',
    recorded_at: '',
});

const submitting = ref(false);
const notification = ref('');

const applyPayloadTemplate = () => {
    form.payload = JSON.stringify(
        {
            metric: 'temperature',
            value: 21.4,
            unit: 'C',
        },
        null,
        2
    );
};

const handleSubmit = async () => {
    let payload;

    try {
        payload = JSON.parse(form.payload || '{}');
    } catch (err) {
        notification.value = 'Payload must be valid JSON.';
        return;
    }

    submitting.value = true;
    notification.value = '';

    try {
        await store.dispatch('addEntry', {
            title: form.title,
            payload,
            source: form.source || 'gui',
            status: form.status || 'new',
            recorded_at: form.recorded_at || null,
        });
        notification.value = 'Entry stored successfully.';
        form.title = '';
    } catch (err) {
        notification.value = 'Unable to store entry. Check API logs.';
    } finally {
        submitting.value = false;
    }
};

const refreshNow = () => {
    store.dispatch('fetchEntries').catch(() => {
        notification.value = 'Refresh failed.';
    });
};

const startPolling = () => {
    pollTimer = window.setInterval(() => {
        store.dispatch('fetchEntries').catch(() => {
            notification.value = 'Live refresh failed.';
        });
    }, pollSeconds * 1000);
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const formatDate = (value) => {
    return value ? new Date(value).toLocaleString() : 'Unknown';
};

const formatPayload = (value) => {
    if (!value) {
        return '{}';
    }
    try {
        return JSON.stringify(value, null, 2);
    } catch (error) {
        return value;
    }
};

onMounted(() => {
    refreshNow();
    startPolling();
});

onBeforeUnmount(() => {
    stopPolling();
});
</script>
