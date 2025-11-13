<template>
    <section class="rounded-3xl bg-slate-900/70 p-5 shadow-inner shadow-black/50">
        <header class="mb-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Manual input</p>
            <p class="text-lg font-semibold text-white">Log a reading</p>
        </header>
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                    >Recorded at (local, converted to UTC)</label
                >
                <input
                    v-model="recordedAt"
                    type="datetime-local"
                    class="mt-1 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:outline-none"
                />
            </div>
            <div class="grid max-h-64 grid-cols-1 gap-3 overflow-y-auto pr-2">
                <div v-for="item in definitions" :key="item.slug" class="flex items-center justify-between gap-3">
                    <label class="text-sm text-slate-300">{{ item.label }}</label>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="formValues[item.slug]"
                            type="number"
                            step="0.001"
                            class="w-28 rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-1 text-sm text-white focus:border-emerald-400 focus:outline-none"
                        />
                        <span class="text-xs text-slate-500">{{ item.unit }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-400">
                <p>
                    Filled fields: <span class="text-white">{{ filledCount }}</span>
                </p>
                <button type="button" class="text-slate-500 hover:text-slate-200" @click="resetForm">
                    Clear all
                </button>
            </div>
            <button
                class="w-full rounded-xl bg-emerald-500 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:bg-slate-600"
                :disabled="!filledCount || submitting"
                type="submit"
            >
                {{ submitting ? 'Submitting…' : 'Create measurements' }}
            </button>
            <p v-if="feedback" class="text-xs text-emerald-300">{{ feedback }}</p>
            <p v-if="error" class="text-xs text-red-300">{{ error }}</p>
        </form>
    </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    definitions: {
        type: Array,
        default: () => [],
    },
    submitting: {
        type: Boolean,
        default: false,
    },
    feedback: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['submit']);

const formValues = reactive({});
const recordedAt = ref('');

watch(
    () => props.definitions,
    (items) => {
        items.forEach((item) => {
            if (formValues[item.slug] === undefined) {
                formValues[item.slug] = '';
            }
        });
    },
    { immediate: true }
);

const filledEntries = computed(() => {
    return props.definitions
        .filter((item) => formValues[item.slug] !== '' && formValues[item.slug] !== null)
        .map((item) => ({
            name: item.slug,
            value: formValues[item.slug],
            created_at: recordedAt.value ? toUtcString(recordedAt.value) : undefined,
        }));
});

const filledCount = computed(() => filledEntries.value.length);

const handleSubmit = () => {
    if (! filledEntries.value.length) {
        return;
    }

    emit('submit', {
        entries: filledEntries.value,
    });
};

const resetForm = () => {
    Object.keys(formValues).forEach((key) => {
        formValues[key] = '';
    });
    recordedAt.value = '';
};

const toUtcString = (value) => {
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
</script>
