import Alpine from 'alpinejs';
import offlineNotice from './offlineNotice.js';
import liveSearch from './liveSearch.js';
import aiChat from './aiChat.js';

window.Alpine = Alpine;

Alpine.data('offlineNotice', offlineNotice);
Alpine.data('liveSearch', liveSearch);
Alpine.data('aiChat', aiChat);

Alpine.start();
