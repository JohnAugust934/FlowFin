import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Alternância de tema por classe `dark` no <html> (claro/escuro/sistema).
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Classes geradas dinamicamente na página de demonstração (interpoladas no Blade)
    // não são detectadas pelo scanner do Tailwind — por isso são preservadas aqui.
    safelist: [
        {
            pattern: /(bg|text|border)-(brand|emerald|neutral)-(50|100|200|300|400|500|600|700|800|900)/,
        },
    ],

    theme: {
        extend: {
            fontFamily: {
                // Inter é a fonte da marca; mantém os fallbacks padrão do Tailwind.
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                // Sora é a fonte display: valores monetários e títulos de tela.
                display: ['Sora Variable', 'Inter', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // Azul da marca (confiança/estabilidade) — gradiente #2563EB → #1E3A8A.
                brand: {
                    50: '#EFF6FF',
                    100: '#DBEAFE',
                    200: '#BFDBFE',
                    300: '#93C5FD',
                    400: '#60A5FA',
                    500: '#3B82F6',
                    600: '#2563EB', // tom primário
                    700: '#1D4ED8',
                    800: '#1E40AF',
                    900: '#1E3A8A', // fim escuro do gradiente
                },

                // Verde esmeralda (crescimento/prosperidade) — gradiente #10B981 → #059669.
                emerald: {
                    50: '#ECFDF5',
                    100: '#D1FAE5',
                    200: '#A7F3D0',
                    300: '#6EE7B7',
                    400: '#34D399',
                    500: '#10B981', // tom primário
                    600: '#059669', // fim escuro do gradiente
                    700: '#047857',
                    800: '#065F46',
                    900: '#064E3B',
                },

                // Cinza neutro para texto secundário.
                neutral: {
                    50: '#F9FAFB',
                    100: '#F3F4F6',
                    200: '#E5E7EB',
                    300: '#D1D5DB',
                    400: '#9CA3AF',
                    500: '#6B7280', // cinza secundário de referência
                    600: '#4B5563',
                    700: '#374151',
                    800: '#1F2937',
                    900: '#111827',
                },

                // Cores semafóricas para orçamentos/metas.
                success: {
                    light: '#D1FAE5',
                    DEFAULT: '#10B981', // ok
                    dark: '#059669',
                },
                warning: {
                    light: '#FEF3C7',
                    DEFAULT: '#F59E0B', // atenção (~80%)
                    dark: '#D97706',
                },
                danger: {
                    light: '#FEE2E2',
                    DEFAULT: '#EF4444', // estourado (~100%+)
                    dark: '#DC2626',
                },
            },

            // Gradientes reutilizáveis da marca (usados como `bg-gradient-brand` etc.).
            backgroundImage: {
                'gradient-brand': 'linear-gradient(135deg, #2563EB 0%, #1E3A8A 100%)',
                'gradient-emerald': 'linear-gradient(45deg, #10B981 0%, #059669 100%)',
                'gradient-brand-emerald': 'linear-gradient(135deg, #2563EB 0%, #059669 100%)',
            },

            boxShadow: {
                card: '0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.06)',
                // Sombra suave e em camadas para as superfícies de vidro (iOS 26).
                glass: '0 8px 32px -8px rgba(16, 24, 40, 0.18), 0 2px 8px -2px rgba(16, 24, 40, 0.10)',
                'glass-dark': '0 8px 32px -8px rgba(0, 0, 0, 0.55), 0 2px 8px -2px rgba(0, 0, 0, 0.40)',
            },
        },
    },

    plugins: [forms],
};
