import axios from 'axios';
import { createStore } from 'vuex';
import measurements from '../../data/measurement_ranges.json';

const pad = (value) => String(value).padStart(2, '0');

const formatUtc = (date) => {
    return [
        date.getUTCFullYear(),
        pad(date.getUTCMonth() + 1),
        pad(date.getUTCDate()),
    ].join('-') + ' ' + [pad(date.getUTCHours()), pad(date.getUTCMinutes()), pad(date.getUTCSeconds())].join(':');
};

const startOfUtcDay = (date) => new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), 0, 0, 0));
const endOfUtcDay = (date) => new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), 23, 59, 59));

const defaultFilters = () => {
    const now = new Date();
    const dateFrom = startOfUtcDay(new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000));
    const dateTo = endOfUtcDay(now);

    return {
        date_from: formatUtc(dateFrom),
        date_to: formatUtc(dateTo),
        names: [],
        interval: 'hourly',
        per_page: 20,
        page: 1,
    };
};

const measurementMap = measurements.reduce((carry, item) => {
    carry[item.slug] = item;
    return carry;
}, {});

export default createStore({
    state: () => ({
        filters: defaultFilters(),
        subscriptionId: null,
        currentMeasurements: [],
        measurementHistory: {
            interval: 'hourly',
            data: [],
            meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
        },
        loading: false,
        error: null,
        measurementDefinitions: measurements,
        measurementMap,
        channelName: null,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
    }),
    mutations: {
        setFilters(state, payload) {
            state.filters = { ...state.filters, ...payload };
        },
        setSubscriptionId(state, id) {
            state.subscriptionId = id;
        },
        setMeasurementPayload(state, payload) {
            state.currentMeasurements = payload.currentMeasurements ?? [];
            state.measurementHistory = payload.measurementHistory ?? state.measurementHistory;
        },
        setLoading(state, isLoading) {
            state.loading = isLoading;
        },
        setError(state, message) {
            state.error = message;
        },
        setChannelName(state, name) {
            state.channelName = name;
        },
        resetFilters(state) {
            state.filters = defaultFilters();
        },
    },
    actions: {
        async init({ dispatch }) {
            await dispatch('fetchMeasurements');
        },
        async fetchMeasurements({ state, commit, dispatch }, overrides = {}) {
            const filters = { ...state.filters, ...overrides };
            commit('setFilters', filters);
            commit('setLoading', true);

            try {
                const params = { ...filters };
                if (state.subscriptionId) {
                    params.subscription_id = state.subscriptionId;
                }

                const { data } = await axios.get('/api/measurements', { params });
                commit('setSubscriptionId', data.subscriptionId);
                commit('setMeasurementPayload', data);
                commit('setError', null);
                await dispatch('registerChannel');
            } catch (error) {
                commit('setError', error.response?.data?.message ?? 'Unable to load measurements.');
                throw error;
            } finally {
                commit('setLoading', false);
            }
        },
        async registerChannel({ state, commit }) {
            if (! window.Echo || ! state.subscriptionId) {
                return;
            }

            if (state.channelName) {
                window.Echo.leave(state.channelName);
                commit('setChannelName', null);
            }

            const channelName = `measurements.${state.subscriptionId}`;
            window.Echo.private(channelName).listen('.measurement.updated', (event) => {
                if (event.subscriptionId !== state.subscriptionId) {
                    return;
                }

                if (event.payload) {
                    commit('setMeasurementPayload', event.payload);
                }
            });

            commit('setChannelName', channelName);
        },
        disconnectChannel({ state, commit }) {
            if (state.channelName && window.Echo) {
                window.Echo.leave(state.channelName);
                commit('setChannelName', null);
            }
        },
        async submitManualMeasurements({ dispatch }, payload) {
            const entries = payload.entries.filter((entry) => entry.value !== '' && entry.value !== null);

            if (! entries.length) {
                throw new Error('Please provide at least one measurement value.');
            }

            for (const entry of entries) {
                const payload = {
                    name: entry.name,
                    value: Number(entry.value),
                    source: 'manual',
                };

                if (entry.created_at) {
                    payload.created_at = entry.created_at;
                }

                await axios.post('/api/measurements', payload);
            }

            await dispatch('fetchMeasurements');
        },
        changeInterval({ dispatch }, interval) {
            dispatch('fetchMeasurements', { interval, page: 1 });
        },
        changePage({ dispatch }, page) {
            dispatch('fetchMeasurements', { page });
        },
        updateDateRange({ dispatch }, { date_from, date_to }) {
            dispatch('fetchMeasurements', { date_from, date_to, page: 1 });
        },
        updateNames({ dispatch }, names) {
            dispatch('fetchMeasurements', { names, page: 1 });
        },
    },
});
