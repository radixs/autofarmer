import { describe, expect, it, vi, beforeEach } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import HistoryChart from '../../components/dashboard/HistoryChart.vue';
import { measurementDefinitions, historyRowsFixture } from '../fixtures/dashboard.js';

let registerSpy;
let receivedProps = null;

vi.mock('chart.js', () => ({
    Chart: { register: (...args) => registerSpy && registerSpy(...args) },
    LineElement: {},
    PointElement: {},
    LineController: {},
    ScatterController: {},
    LinearScale: {},
    CategoryScale: {},
    Tooltip: {},
    Legend: {},
}));

vi.mock('vue-chartjs', () => ({
    Line: defineComponent({
        props: ['data', 'options'],
        setup(props) {
            receivedProps = props;
            return () => h('div', { class: 'chart-stub' });
        },
    }),
}));

describe('HistoryChart', () => {
    beforeEach(() => {
        registerSpy = vi.fn();
        receivedProps = null;
    });

    it('constructs datasets and axes per measurement series', () => {
        mount(HistoryChart, {
            props: {
                history: { data: historyRowsFixture },
                filters: { interval: 'hourly' },
                definitions: measurementDefinitions,
                timezone: 'UTC',
            },
        });

        expect(registerSpy).toHaveBeenCalled();
        expect(receivedProps).toBeTruthy();
        expect(receivedProps.data.datasets).toHaveLength(historyRowsFixture.length * 3);
        const avgDataset = receivedProps.data.datasets.find((dataset) => dataset.label.includes('avg'));
        expect(avgDataset.data).toEqual([7.1]);
        const axisKeys = Object.keys(receivedProps.options.scales);
        expect(axisKeys.some((key) => key.includes('axis-ph'))).toBe(true);
    });
});
