import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import MeasurementDashboard from '../../components/dashboard/MeasurementDashboard.vue';
import { measurementDefinitions, measurementHistoryPayload, manualEntriesFixture } from '../fixtures/dashboard.js';

const clicks = {
    export: 0,
    submit: 0,
};

const ManualEntryFormStub = defineComponent({
    props: ['definitions', 'submitting', 'feedback', 'error'],
    emits: ['submit'],
    template: '<button class="manual-entry-stub" @click="$emit(\'submit\', { entries })">manual</button>',
    setup() {
        const entries = manualEntriesFixture;
        return { entries };
    },
});

const HistoryTableStub = defineComponent({
    props: ['history', 'filters', 'definitions', 'loading', 'timezone'],
    emits: ['change-interval', 'change-page', 'change-dates', 'change-names', 'export'],
    template: `
        <div class="history-table-stub">
            <button class="emit-export" @click="$emit('export'); clicks.export++">export</button>
            <button class="emit-interval" @click="$emit('change-interval', 'weekly')">interval</button>
        </div>
    `,
    setup() {
        return { clicks };
    },
});

const stubStore = {
    state: {
        currentMeasurements: [],
        measurementHistory: measurementHistoryPayload,
        filters: { interval: 'hourly' },
        measurementDefinitions: measurementDefinitions,
        timezone: 'UTC',
        loading: false,
        error: '',
    },
    dispatch: vi.fn((action) => {
        if (action === 'submitManualMeasurements') {
            return Promise.resolve();
        }

        return Promise.resolve();
    }),
};

vi.mock('vuex', () => ({
    useStore: () => stubStore,
}));

describe('MeasurementDashboard', () => {
    it('dispatches initialization, manual submissions, exports, and interval changes', async () => {
        const wrapper = mount(MeasurementDashboard, {
            global: {
                stubs: {
                    CurrentMeasurementsPanel: { template: '<div class="current-panel-stub" />', props: ['measurements', 'definitions', 'timezone'] },
                    ManualEntryForm: ManualEntryFormStub,
                    HistoryTable: HistoryTableStub,
                    HistoryChart: { template: '<div class="history-chart-stub" />', props: ['history', 'filters', 'definitions', 'timezone'] },
                },
            },
        });

        expect(stubStore.dispatch).toHaveBeenCalledWith('init');

        await wrapper.find('.manual-entry-stub').trigger('click');
        expect(stubStore.dispatch).toHaveBeenCalledWith('submitManualMeasurements', { entries: manualEntriesFixture });

        await wrapper.find('.history-table-stub .emit-interval').trigger('click');
        expect(stubStore.dispatch).toHaveBeenCalledWith('changeInterval', 'weekly');

        await wrapper.find('.history-table-stub .emit-export').trigger('click');
        expect(URL.createObjectURL).toHaveBeenCalled();
    });
});
