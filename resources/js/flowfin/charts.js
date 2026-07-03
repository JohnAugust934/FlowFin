// Gráficos do FlowFin (Chart.js) com defaults da marca: fonte Inter, grid
// sutil, tooltips em R$ e cores cientes do tema claro/escuro. Registra só os
// controladores usados para manter o bundle enxuto.

import {
    Chart,
    DoughnutController,
    LineController,
    ArcElement,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { centsToBRL } from './format.js';

Chart.register(
    DoughnutController,
    LineController,
    ArcElement,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
    Filler,
);

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isDark = () => document.documentElement.classList.contains('dark');

function themeColors() {
    return isDark()
        ? { text: '#9CA3AF', grid: 'rgba(255, 255, 255, 0.06)', border: '#161c2d' }
        : { text: '#6B7280', grid: 'rgba(17, 24, 39, 0.06)', border: '#ffffff' };
}

Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 12;

function baseTooltip() {
    return {
        backgroundColor: isDark() ? 'rgba(22, 28, 45, 0.95)' : 'rgba(255, 255, 255, 0.96)',
        titleColor: isDark() ? '#F3F4F6' : '#1F2937',
        bodyColor: isDark() ? '#D1D5DB' : '#4B5563',
        borderColor: isDark() ? 'rgba(255, 255, 255, 0.08)' : 'rgba(17, 24, 39, 0.08)',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 10,
        displayColors: true,
        boxPadding: 4,
    };
}

/**
 * Rosca "Para onde foi o dinheiro": uma fatia por categoria (valores em centavos).
 * O total fica no centro, desenhado por um plugin próprio (funciona nos dois temas).
 */
export function renderCategoryDonut(canvas, categories) {
    const t = themeColors();
    const centerText = {
        id: 'flowfinCenterText',
        afterDraw(chart) {
            const { ctx, chartArea } = chart;
            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;
            const total = chart.data.datasets[0].data.reduce((s, v) => s + v, 0);
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = isDark() ? '#9CA3AF' : '#6B7280';
            ctx.font = "500 11px 'Inter', sans-serif";
            ctx.fillText('Total de saídas', x, y - 12);
            ctx.fillStyle = isDark() ? '#F3F4F6' : '#1F2937';
            ctx.font = "700 17px 'Sora Variable', 'Inter', sans-serif";
            ctx.fillText(centsToBRL(total), x, y + 8);
            ctx.restore();
        },
    };

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: categories.map((c) => c.name),
            datasets: [{
                data: categories.map((c) => c.total),
                backgroundColor: categories.map((c) => c.color),
                borderColor: t.border,
                borderWidth: 2,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            animation: reducedMotion() ? false : { duration: 600 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...baseTooltip(),
                    callbacks: {
                        label: (ctx) => ` ${centsToBRL(ctx.parsed)}`,
                    },
                },
            },
        },
        plugins: [centerText],
    });
}

/**
 * Linha de evolução dos últimos meses: entrou (verde) vs. saiu (vermelho).
 * `series` = [{ month: "aaaa-mm", entrou, saiu }] em centavos.
 */
export function renderHistoryLine(canvas, series) {
    const t = themeColors();
    const labels = series.map((s) => {
        const [y, m] = s.month.split('-').map(Number);
        return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');
    });

    const line = (label, data, color, fillColor) => ({
        label,
        data,
        borderColor: color,
        backgroundColor: fillColor,
        fill: true,
        tension: 0.35,
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: color,
        pointBorderColor: t.border,
        pointBorderWidth: 1.5,
    });

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                line('Entrou', series.map((s) => s.entrou), '#10B981', 'rgba(16, 185, 129, 0.08)'),
                line('Saiu', series.map((s) => s.saiu), '#EF4444', 'rgba(239, 68, 68, 0.06)'),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion() ? false : { duration: 600 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: t.text, usePointStyle: true, pointStyle: 'circle', boxWidth: 6, padding: 16 },
                },
                tooltip: {
                    ...baseTooltip(),
                    callbacks: {
                        label: (ctx) => ` ${ctx.dataset.label}: ${centsToBRL(ctx.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: t.text },
                },
                y: {
                    grid: { color: t.grid },
                    border: { display: false },
                    ticks: {
                        color: t.text,
                        maxTicksLimit: 5,
                        callback: (v) => centsToBRL(v).replace(',00', ''),
                    },
                },
            },
        },
    });
}

/**
 * Contagem animada de um valor em centavos (~600ms). Chama `onUpdate` com o
 * valor parcial a cada frame; com reduced-motion, aplica o valor final direto.
 */
export function countUp(from, to, onUpdate, duration = 600) {
    if (reducedMotion() || from === to) {
        onUpdate(to);
        return;
    }
    const start = performance.now();
    const ease = (p) => 1 - Math.pow(1 - p, 3); // ease-out cúbico
    const frame = (now) => {
        const p = Math.min(1, (now - start) / duration);
        onUpdate(Math.round(from + (to - from) * ease(p)));
        if (p < 1) requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
}
