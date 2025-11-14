export const measurementDefinitions = [
    { slug: 'ph', label: 'pH', unit: 'pH', range: { min: 6.5, max: 7.5 } },
    { slug: 'temp', label: 'Temperature', unit: '°C', range: { min: 19, max: 21 } },
];

export const currentMeasurementsFixture = [
    {
        name: 'ph',
        label: 'pH',
        unit: 'pH',
        value: 7.6,
        source: 'sensor',
        created_at: '2024-05-01 10:00:00',
    },
    {
        name: 'temp',
        label: 'Temperature',
        unit: '°C',
        value: 19.5,
        source: 'manual',
        created_at: '2024-05-01 11:45:00',
    },
];

export const historyRowsFixture = [
    {
        id: 1,
        name: 'ph',
        unit: 'pH',
        value_avg: 7.1,
        value_min: 6.9,
        value_max: 7.3,
        source: 'sensor',
        range_start_at: '2024-05-01 09:00:00',
        range_end_at: '2024-05-01 09:59:59',
    },
    {
        id: 2,
        name: 'temp',
        unit: '°C',
        value_avg: 20.2,
        value_min: 19.8,
        value_max: 20.6,
        source: 'manual',
        range_start_at: '2024-05-01 10:00:00',
        range_end_at: '2024-05-01 10:59:59',
    },
];

export const measurementHistoryPayload = {
    interval: 'hourly',
    data: historyRowsFixture,
    meta: { current_page: 1, last_page: 1, per_page: 20, total: historyRowsFixture.length },
};

export const measurementResponseFixture = {
    subscriptionId: 'demo-subscription',
    currentMeasurements: currentMeasurementsFixture,
    measurementHistory: measurementHistoryPayload,
};

export const manualEntriesFixture = [
    { name: 'ph', value: '7.35', created_at: '2024-05-01 10:00:00' },
    { name: 'temp', value: '19.8', created_at: '2024-05-01 10:00:00' },
];
