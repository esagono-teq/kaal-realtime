/**
 * KAAL Realtime PWA Manager
 * Handles Service Worker registration and PWA initialization
 */

// Global variable to capture early beforeinstallprompt events
window.KAAL_EARLY_INSTALL_PROMPT = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.KAAL_EARLY_INSTALL_PROMPT = e;
});
class KaalPwaManager {
    constructor(config) {
        this.config = config || {};
        this.installPromptEvent = null;
        this.init();
    }

    init() {
        if (!this.config.enable) return;

        this.registerServiceWorker();
        this.listenForInstallPrompt();
        this.setupSync();
    }

    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('[KAAL PWA] ServiceWorker registered with scope:', registration.scope);
                        
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    this.notifyUpdateAvailable(newWorker);
                                }
                            });
                        });
                    })
                    .catch(err => {
                        console.error('[KAAL PWA] ServiceWorker registration failed:', err);
                    });
            });
            
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data && event.data.type === 'KAAL_SYNC_CONFLICT') {
                    this.handleSyncConflict(event.data.payload);
                }
            });
        }
    }

    listenForInstallPrompt() {
        const dispatchInstall = (e) => {
            this.installPromptEvent = e;
            window.dispatchEvent(new CustomEvent('kaal:pwa:install-available', { detail: e }));
        };

        // If it already fired early, dispatch it immediately
        if (window.KAAL_EARLY_INSTALL_PROMPT) {
            dispatchInstall(window.KAAL_EARLY_INSTALL_PROMPT);
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            dispatchInstall(e);
        });

        window.addEventListener('appinstalled', () => {
            this.installPromptEvent = null;
            window.KAAL_EARLY_INSTALL_PROMPT = null;
            console.log('[KAAL PWA] App was installed.');
        });
    }

    promptInstall() {
        if (!this.installPromptEvent) return Promise.reject('No install prompt available');
        
        return this.installPromptEvent.prompt().then(() => {
            return this.installPromptEvent.userChoice;
        }).then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('[KAAL PWA] User accepted the install prompt');
            } else {
                console.log('[KAAL PWA] User dismissed the install prompt');
            }
            this.installPromptEvent = null;
        });
    }

    notifyUpdateAvailable(worker) {
        // Dispatch event for UI
        window.dispatchEvent(new CustomEvent('kaal:pwa:update-available', { detail: worker }));
    }

    setupSync() {
        if (!this.config.sync || !this.config.sync.enable) return;
        
        // Let the Service Worker know about models registered for sync
        if (window.KAAL_PWA_SYNC && window.KAAL_PWA_SYNC.length > 0) {
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'KAAL_REGISTER_SYNC_MODELS',
                    models: window.KAAL_PWA_SYNC
                });
            }
        }
    }

    handleSyncConflict(payload) {
        console.warn('[KAAL PWA] Sync conflict detected', payload);
        window.dispatchEvent(new CustomEvent('kaal:pwa:sync-conflict', { detail: payload }));
    }
}

// Initialize when configuration is available
window.addEventListener('DOMContentLoaded', () => {
    if (window.KAAL_PWA_CONFIG) {
        window.KaalPWA = new KaalPwaManager(window.KAAL_PWA_CONFIG);
    }
});
