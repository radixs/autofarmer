import './bootstrap';
import { createApp } from 'vue';
import store from './store';
import EntriesBoard from './components/EntriesBoard.vue';

const app = createApp(EntriesBoard);

app.use(store);
app.mount('#app');
