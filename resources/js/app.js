import Alpine from 'alpinejs';
import offlineNotice from './offlineNotice.js';
import liveSearch from './liveSearch.js';
import aiChat from './aiChat.js';
import previewPlayer from './previewPlayer.js';
import watchlistToggle from './watchlist.js';
import updateNotice from './updateNotice.js';
import guidedTour from './guidedTour.js';

window.Alpine = Alpine;

Alpine.data('offlineNotice', offlineNotice);
Alpine.data('liveSearch', liveSearch);
Alpine.data('aiChat', aiChat);
Alpine.data('previewPlayer', previewPlayer);
Alpine.data('watchlistToggle', watchlistToggle);
Alpine.data('updateNotice', updateNotice);
Alpine.data('guidedTour', guidedTour);

Alpine.start();
