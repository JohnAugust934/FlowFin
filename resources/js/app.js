import Alpine from 'alpinejs';

// Componentes Alpine do FlowFin (registram-se em alpine:init).
import './flowfin/components.js';
import { api } from './flowfin/api.js';
import * as format from './flowfin/format.js';
import { iconSvg } from './flowfin/icons.js';
import { offlineQueue } from './flowfin/offline-queue.js';
import { pwa } from './flowfin/pwa.js';

// Liga os gatilhos de sincronização offline (reconexão, retorno ao app, load).
offlineQueue.init();

// Exposto para uso pontual em telas (ex.: formatação inline) e na casca do app.
window.FlowFin = { api, format, iconSvg, offlineQueue, pwa };

window.Alpine = Alpine;

Alpine.start();
