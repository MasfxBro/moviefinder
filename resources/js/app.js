import Alpine from 'alpinejs';
import offlineNotice from './offlineNotice.js';
import liveSearch from './liveSearch.js';

window.Alpine = Alpine;

Alpine.data('offlineNotice', offlineNotice);
Alpine.data('liveSearch', liveSearch);

Alpine.start();
