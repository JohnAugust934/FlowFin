import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Componentes Alpine do FlowFin (registram-se em alpine:init).
import './flowfin/components.js';
import { api } from './flowfin/api.js';
import * as format from './flowfin/format.js';
import { iconSvg } from './flowfin/icons.js';

// Exposto para uso pontual em telas (ex.: formatação inline).
window.FlowFin = { api, format, iconSvg };

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();
