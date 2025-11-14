import { afterEach, vi } from 'vitest';

process.env.TZ = 'UTC';

class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
}

if (! globalThis.ResizeObserver) {
    globalThis.ResizeObserver = ResizeObserver;
}

if (! globalThis.matchMedia) {
    globalThis.matchMedia = () => ({
        matches: false,
        addEventListener: () => {},
        removeEventListener: () => {},
    });
}

if (! globalThis.URL.createObjectURL) {
    globalThis.URL.createObjectURL = vi.fn(() => 'blob://local');
}

if (! globalThis.URL.revokeObjectURL) {
    globalThis.URL.revokeObjectURL = vi.fn();
}

if (globalThis.HTMLAnchorElement) {
    globalThis.HTMLAnchorElement.prototype.click = vi.fn();
}

afterEach(() => {
    vi.clearAllMocks();
});
