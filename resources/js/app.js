import './bootstrap';

import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

Fancybox.bind('[data-fancybox]', {
    Thumbs: {
        type: 'classic',
    },
});
