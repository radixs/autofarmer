import './bootstrap';
import { createApp } from 'vue';
import store from './store';
import MeasurementDashboard from './components/dashboard/MeasurementDashboard.vue';

const app = createApp(MeasurementDashboard);

app.use(store);
app.mount('#app');
