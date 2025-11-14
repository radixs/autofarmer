import { describe, expect, it, beforeEach, vi } from 'vitest';
import axios from 'axios';
import { createMeasurementStore } from '../../store';
import { measurementResponseFixture } from '../fixtures/dashboard.js';

vi.mock('axios');

const buildEchoMock = () => ({
    private: vi.fn(() => ({ listen: vi.fn() })),
    leave: vi.fn(),
});

describe('Measurements Vuex store', () => {
    beforeEach(() => {
        window.Echo = buildEchoMock();
        axios.get.mockReset();
        axios.post.mockReset();
        axios.put?.mockReset?.();
    });

    it('fetches measurements and updates subscription payloads', async () => {
        const store = createMeasurementStore();
        axios.get.mockResolvedValue({ data: measurementResponseFixture });

        await store.dispatch('fetchMeasurements');

        expect(store.state.subscriptionId).toBe('demo-subscription');
        expect(store.state.currentMeasurements).toHaveLength(2);
        expect(axios.get).toHaveBeenCalledWith('/api/measurements', expect.any(Object));
    });

    it('stores an error message when the API fails', async () => {
        const store = createMeasurementStore();
        axios.get.mockRejectedValue({ response: { data: { message: 'boom' } } });

        await expect(store.dispatch('fetchMeasurements')).rejects.toBeDefined();
        expect(store.state.error).toBe('boom');
    });

    it('submits manual measurements and refreshes the dashboard data', async () => {
        const store = createMeasurementStore();
        axios.post.mockResolvedValue({});
        axios.get.mockResolvedValue({ data: measurementResponseFixture });

        await store.dispatch('submitManualMeasurements', {
            entries: [
                { name: 'ph', value: '7.4', created_at: '2024-05-01 10:00:00' },
                { name: 'temp', value: '19.5', created_at: '2024-05-01 10:05:00' },
            ],
        });

        expect(axios.post).toHaveBeenCalledTimes(2);
        expect(axios.post).toHaveBeenCalledWith('/api/measurements', expect.objectContaining({ name: 'ph', source: 'manual' }));
        expect(axios.get).toHaveBeenCalled();
    });

    it('throws when attempting to submit without any filled entries', async () => {
        const store = createMeasurementStore();
        await expect(store.dispatch('submitManualMeasurements', { entries: [] })).rejects.toThrow('Please provide at least one measurement value.');
    });

    it('fetches the current sensor mode state', async () => {
        const store = createMeasurementStore();
        axios.get.mockResolvedValue({ data: { data: { enabled: true } } });

        await store.dispatch('fetchSensorMode');

        expect(store.state.sensorEnabled).toBe(true);
        expect(axios.get).toHaveBeenCalledWith('/api/sensor-mode');
    });

    it('updates sensor mode state via the API', async () => {
        const store = createMeasurementStore();
        axios.put.mockResolvedValue({ data: { data: { enabled: false } } });

        await store.dispatch('updateSensorMode', false);

        expect(axios.put).toHaveBeenCalledWith('/api/sensor-mode', { enabled: false });
        expect(store.state.sensorEnabled).toBe(false);
    });
});
