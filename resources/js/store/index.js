import axios from 'axios';
import { createStore } from 'vuex';

export default createStore({
    state: () => ({
        entries: [],
        loading: false,
        error: null,
        lastFetchedAt: null,
    }),
    mutations: {
        setEntries(state, entries) {
            state.entries = entries;
        },
        setLoading(state, isLoading) {
            state.loading = isLoading;
        },
        setError(state, message) {
            state.error = message;
        },
        setLastFetchedAt(state, timestamp) {
            state.lastFetchedAt = timestamp;
        },
    },
    getters: {
        entriesCount(state) {
            return state.entries.length;
        },
    },
    actions: {
        async fetchEntries({ commit }) {
            commit('setLoading', true);

            try {
                const { data } = await axios.get('/api/test-entries');
                commit('setEntries', data.data ?? []);
                commit('setLastFetchedAt', new Date().toISOString());
                commit('setError', null);
            } catch (error) {
                commit(
                    'setError',
                    error.response?.data?.message ?? 'Unable to fetch entries.'
                );
                throw error;
            } finally {
                commit('setLoading', false);
            }
        },
        async addEntry({ dispatch, commit }, payload) {
            try {
                await axios.post('/api/test-entries', payload);
                await dispatch('fetchEntries');
            } catch (error) {
                commit(
                    'setError',
                    error.response?.data?.message ?? 'Unable to create entry.'
                );
                throw error;
            }
        },
    },
});
