import Alpine from 'alpinejs';
import offlineNotice from './offlineNotice.js';

window.Alpine = Alpine;

Alpine.data('offlineNotice', offlineNotice);

Alpine.start();
