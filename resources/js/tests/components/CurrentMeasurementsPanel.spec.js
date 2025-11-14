import { describe, expect, it, vi, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import CurrentMeasurementsPanel from '../../components/dashboard/CurrentMeasurementsPanel.vue';
import { measurementDefinitions, currentMeasurementsFixture } from '../fixtures/dashboard.js';

describe('CurrentMeasurementsPanel', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-05-01T12:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders decorated measurement rows with range, elapsed time, and timezone display', () => {
        const wrapper = mount(CurrentMeasurementsPanel, {
            props: {
                measurements: currentMeasurementsFixture,
                definitions: measurementDefinitions,
                timezone: 'UTC',
            },
        });

        const rows = wrapper.findAll('tbody tr');
        expect(rows).toHaveLength(2);
        expect(rows[0].text()).toContain('pH');
        expect(rows[0].text()).toContain('sensor');
        expect(rows[0].find('span.font-bold').classes()).toContain('text-red-400');
        expect(rows[1].find('span.font-bold').classes()).toContain('text-emerald-300');
        expect(rows[0].findAll('td')[5].text()).toContain('2h');
        expect(wrapper.html()).toContain('Times shown in UTC');
    });

    it('shows placeholder text when no measurements exist', () => {
        const wrapper = mount(CurrentMeasurementsPanel, {
            props: {
                measurements: [],
                definitions: measurementDefinitions,
            },
        });

        expect(wrapper.text()).toContain('No measurements recorded yet.');
    });
});
