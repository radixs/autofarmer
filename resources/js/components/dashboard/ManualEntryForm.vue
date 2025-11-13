<template>
    <section class="rounded-3xl bg-slate-900/70 p-5 shadow-inner shadow-black/50">
        <header class="mb-4">
            <p class="text-xs uppercase tracking-wide text-slate-400">Manual input</p>
        </header>
        <form class="space-y-4" @submit.prevent="handleSubmit">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                    >Recorded at (local, converted to UTC)</label
                >
                <input
                    v-model="recordedAt"
                    type="datetime-local"
                    :class="[
                        'mt-1 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none',
                        isDefaultDate ? 'text-slate-500' : 'text-white',
                    ]"
                    @input="markCustomDate"
                />
            </div>
            <div class="grid max-h-64 grid-cols-1 gap-3 overflow-y-auto pr-2 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="item in definitions"
                    :key="item.slug"
                    class="rounded-2xl border border-slate-800/70 bg-slate-950/60 p-3 shadow-inner"
                >
                    <div class="mb-1 flex items-center justify-between text-xs text-slate-400">
                        <label class="text-sm font-medium text-slate-100">{{ item.label }}</label>
                        <span class="text-[11px] uppercase tracking-wide text-slate-500">{{ item.unit }}</span>
                    </div>
                    <input
                        v-model="formValues[item.slug]"
                        type="number"
                        step="0.001"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:outline-none"
                    />
                </div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-400">
                <p>
                    Filled fields: <span class="text-white">{{ filledCount }}</span>
                </p>
                <div class="flex gap-2">
                    <button type="button" class="text-slate-500 hover:text-slate-200" @click="resetForm">
                        Reset form
                    </button>
                    <button type="button" class="text-slate-500 hover:text-slate-200" @click="resetValuesOnly">
                        Clear values
                    </button>
                </div>
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
const defaultLocalDateTime = () => {
    const now = new Date();
    now.setSeconds(0, 0);
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(
        2,
        '0'
    )}T${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
};

const recordedAt = ref(defaultLocalDateTime());
const isDefaultDate = ref(true);

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

const markCustomDate = () => {
    isDefaultDate.value = false;
};

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
    recordedAt.value = defaultLocalDateTime();
    isDefaultDate.value = true;
};

const resetValuesOnly = () => {
    Object.keys(formValues).forEach((key) => {
        formValues[key] = '';
    });
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
