import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import HistoryTable from '../../components/dashboard/HistoryTable.vue';
import { measurementDefinitions, historyRowsFixture } from '../fixtures/dashboard.js';

const baseFilters = {
    date_from: '2024-05-01 00:00:00',
    date_to: '2024-05-02 00:00:00',
    names: [],
    interval: 'hourly',
    per_page: 20,
    page: 1,
};

const buildWrapper = (overrides = {}) => {
    return mount(HistoryTable, {
        props: {
            history: {
                data: historyRowsFixture,
                meta: { current_page: 1, last_page: 2, per_page: 20, total: 40 },
            },
            filters: { ...baseFilters, ...overrides },
            definitions: measurementDefinitions,
            loading: false,
            timezone: 'UTC',
        },
    });
};

describe('HistoryTable', () => {
    it('sorts rows by selected column and toggles interval filters', async () => {
        const wrapper = buildWrapper();
        let firstRow = wrapper.findAll('tbody tr')[0];
        expect(firstRow.findAll('td')[3].text()).toBe('Temperature');

        const idHeader = wrapper.findAll('thead th button')[0];
        await idHeader.trigger('click');
        firstRow = wrapper.findAll('tbody tr')[0];
        expect(firstRow.findAll('td')[3].text()).toBe('Temperature');

        await idHeader.trigger('click');
        firstRow = wrapper.findAll('tbody tr')[0];
        expect(firstRow.findAll('td')[3].text()).toBe('pH');

        const dailyButton = wrapper.findAll('button').find((btn) => btn.text() === 'Daily');
        await dailyButton.trigger('click');
        const emitted = wrapper.emitted('change-interval');
        expect(emitted[0]).toEqual(['daily']);
    });

    it('emits date, name, export, and page updates', async () => {
        const wrapper = buildWrapper();
        const inputs = wrapper.findAll('input[type="datetime-local"]');
        await inputs[0].setValue('2024-05-01T03:15');
        await inputs[0].trigger('change');
        const dateEvent = wrapper.emitted('change-dates');
        expect(dateEvent[0][0]).toEqual({ date_from: '2024-05-01 03:15:00', date_to: baseFilters.date_to });

        const select = wrapper.find('select');
        const tempOption = select.element.options[1];
        tempOption.selected = true;
        await select.trigger('change');
        const namesEvent = wrapper.emitted('change-names');
        expect(namesEvent[0][0]).toEqual(['temp']);

        const exportButton = wrapper.findAll('button').find((btn) => btn.text().includes('Export CSV'));
        await exportButton.trigger('click');
        expect(wrapper.emitted('export')).toBeTruthy();

        const nextButton = wrapper.findAll('button').find((btn) => btn.text().includes('Next'));
        await nextButton.trigger('click');
        expect(wrapper.emitted('change-page')[0]).toEqual([2]);
    });
});
