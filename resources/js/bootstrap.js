import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.baseURL = import.meta.env.VITE_APP_URL ?? window.location.origin;

window.Pusher = Pusher;

const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 6001);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY ?? 'autofarmer-key';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    cluster: 'autofarmer',
    disableStats: true,
});
