import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ManualEntryForm from '../../components/dashboard/ManualEntryForm.vue';
import { measurementDefinitions } from '../fixtures/dashboard.js';

describe('ManualEntryForm', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-05-01T10:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('tracks filled entries and emits a UTC-normalized payload', async () => {
        const wrapper = mount(ManualEntryForm, {
            props: {
                definitions: measurementDefinitions,
                submitting: false,
            },
        });

        const recordedAt = wrapper.find('input[type="datetime-local"]');
        await recordedAt.setValue('2024-05-01T05:30');
        const inputs = wrapper.findAll('input[type="number"]');
        await inputs[0].setValue('7.2');
        await inputs[1].setValue('19.9');

        await wrapper.find('form').trigger('submit.prevent');

        const emitted = wrapper.emitted('submit');
        expect(emitted).toBeTruthy();
        const payload = emitted[0][0];
        expect(payload.entries).toEqual([
            { name: 'ph', value: 7.2, created_at: '2024-05-01 05:30:00' },
            { name: 'temp', value: 19.9, created_at: '2024-05-01 05:30:00' },
        ]);
    });

    it('disables submission when no entries are filled', async () => {
        const wrapper = mount(ManualEntryForm, {
            props: {
                definitions: measurementDefinitions,
            },
        });

        const submitButton = wrapper.find('button[type="submit"]');
        expect(submitButton.attributes('disabled')).toBeDefined();
        await wrapper.find('form').trigger('submit.prevent');
        expect(wrapper.emitted('submit')).toBeFalsy();
    });

    it('clears values when reset buttons are used', async () => {
        const wrapper = mount(ManualEntryForm, {
            props: {
                definitions: measurementDefinitions,
            },
        });

        const inputs = wrapper.findAll('input[type="number"]');
        await inputs[0].setValue('7.0');
        await inputs[1].setValue('20.4');

        await wrapper.findAll('button').find((btn) => btn.text() === 'Clear values').trigger('click');
        expect(inputs[0].element.value).toBe('');
        expect(inputs[1].element.value).toBe('');
    });
});
