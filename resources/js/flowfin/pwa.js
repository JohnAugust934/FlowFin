// Registro do Service Worker e gestão do prompt de instalação (Task 5.1).
//
// - Registra `/sw.js` após o load (não bloqueia a primeira pintura nem interfere
//   no script anti-flash de tema, que roda no <head> antes de tudo).
// - Captura o evento `beforeinstallprompt` para oferecer "Instalar app" na hora
//   certa (a UI decide quando mostrar). Expõe `window.FlowFin.pwa`.

const pwa = {
    _deferredPrompt: null,

    /** Há um prompt de instalação nativo disponível? */
    canInstall() {
        return this._deferredPrompt !== null;
    },

    /** Dispara o prompt nativo de instalação. Resolve com 'accepted' | 'dismissed' | 'unavailable'. */
    async promptInstall() {
        if (!this._deferredPrompt) return 'unavailable';
        this._deferredPrompt.prompt();
        const { outcome } = await this._deferredPrompt.userChoice;
        this._deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('flowfin:pwa-installable', { detail: { available: false } }));
        return outcome;
    },
};

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            // Falha de registro não pode quebrar o app — apenas perde o offline.
            console.warn('Service Worker não registrado:', err);
        });
    });
}

function watchInstallPrompt() {
    window.addEventListener('beforeinstallprompt', (e) => {
        // Impede o mini-infobar padrão; mostramos nossa própria UI no momento certo.
        e.preventDefault();
        pwa._deferredPrompt = e;
        window.dispatchEvent(new CustomEvent('flowfin:pwa-installable', { detail: { available: true } }));
    });

    window.addEventListener('appinstalled', () => {
        pwa._deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('flowfin:pwa-installed'));
    });
}

registerServiceWorker();
watchInstallPrompt();

export { pwa };
