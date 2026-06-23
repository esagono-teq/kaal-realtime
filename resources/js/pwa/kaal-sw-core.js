/**
 * KAAL Realtime Service Worker Core
 */

class KaalServiceWorker {
    constructor(config) {
        this.config = config || {};
        this.cacheName = 'kaal-pwa-cache-v1';
        this.offlineQueueName = 'kaal-offline-queue';
    }

    register() {
        self.addEventListener('install', event => this.onInstall(event));
        self.addEventListener('activate', event => this.onActivate(event));
        self.addEventListener('fetch', event => this.onFetch(event));
        self.addEventListener('sync', event => this.onSync(event));
        self.addEventListener('push', event => this.onPush(event));
        self.addEventListener('notificationclick', event => this.onNotificationClick(event));
        self.addEventListener('message', event => this.onMessage(event));
    }

    onInstall(event) {
        console.log('[KAAL SW] Installing...');
        self.skipWaiting();
        
        event.waitUntil(
            caches.open(this.cacheName).then(cache => {
                const urlsToCache = [];
                if (this.config.offline && this.config.offline.fallback_page) {
                    urlsToCache.push(this.config.offline.fallback_page);
                } else {
                    urlsToCache.push('/offline');
                }
                
                // Ensure unique URLs
                const uniqueUrls = [...new Set(urlsToCache)];
                return cache.addAll(uniqueUrls);
            })
        );
    }

    onActivate(event) {
        console.log('[KAAL SW] Activating...');
        event.waitUntil(self.clients.claim());
    }

    onFetch(event) {
        const url = new URL(event.request.url);
        
        // Handle Realtime API requests offline
        if (url.pathname.startsWith('/kaal/') && event.request.method === 'POST') {
            if (!navigator.onLine) {
                return event.respondWith(this.queueOfflineRequest(event.request));
            }
        }

        // Apply caching strategies
        event.respondWith(
            this.handleCachingStrategy(event.request, url)
        );
    }

    handleCachingStrategy(request, url) {
        // Default Strategy: Network First
        return fetch(request).catch(error => {
            return caches.match(request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                // Return offline fallback for navigation requests
                if (request.mode === 'navigate') {
                    const fallback = (this.config.offline && this.config.offline.fallback_page) 
                        ? this.config.offline.fallback_page 
                        : '/offline';
                    return caches.match(fallback);
                }
                
                throw error;
            });
        });
    }

    async queueOfflineRequest(request) {
        console.log('[KAAL SW] Queuing request offline:', request.url);
        
        // Clone request because it can only be consumed once
        const clonedRequest = request.clone();
        const body = await clonedRequest.text();
        
        // Store in IndexedDB (Using a simplified approach here)
        // A full implementation would use idb-keyval or similar
        const reqData = {
            url: request.url,
            method: request.method,
            headers: [...request.headers.entries()],
            body: body,
            timestamp: Date.now()
        };
        
        await this.saveToIndexedDB(this.offlineQueueName, reqData);
        
        // Register background sync if supported
        if ('sync' in self.registration) {
            await self.registration.sync.register('kaal-sync-queue');
        }

        return new Response(JSON.stringify({ offline_queued: true }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }

    onSync(event) {
        if (event.tag === 'kaal-sync-queue') {
            console.log('[KAAL SW] Background Sync Triggered');
            event.waitUntil(this.flushOfflineQueue());
        }
    }

    async flushOfflineQueue() {
        const queue = await this.getAllFromIndexedDB(this.offlineQueueName);
        if (!queue || queue.length === 0) return;

        console.log(`[KAAL SW] Flushing ${queue.length} items from offline queue`);

        for (const item of queue) {
            try {
                await fetch(item.url, {
                    method: item.method,
                    headers: new Headers(item.headers),
                    body: item.body
                });
                await this.deleteFromIndexedDB(this.offlineQueueName, item.id);
            } catch (err) {
                console.error('[KAAL SW] Sync failed for item', item, err);
                break; // Stop syncing if offline again
            }
        }
    }

    onPush(event) {
        if (!event.data) return;

        try {
            const data = event.data.json();
            const options = {
                body: data.body,
                icon: data.icon || '/vendor/kaal-realtime/pwa/icon-192x192.png',
                badge: data.badge || '/vendor/kaal-realtime/pwa/badge.png',
                data: data.url
            };

            event.waitUntil(
                self.registration.showNotification(data.title, options)
            );
        } catch (e) {
            console.error('[KAAL SW] Error processing push event', e);
        }
    }

    onNotificationClick(event) {
        event.notification.close();
        
        if (event.notification.data) {
            event.waitUntil(
                clients.openWindow(event.notification.data)
            );
        }
    }

    onMessage(event) {
        if (event.data && event.data.type === 'KAAL_REGISTER_SYNC_MODELS') {
            this.syncModels = event.data.models;
            console.log('[KAAL SW] Registered models for sync:', this.syncModels);
        }
    }

    // --- Simple IndexedDB Helper Methods ---
    
    getDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('KaalPwaDB', 1);
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);
            request.onupgradeneeded = event => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.offlineQueueName)) {
                    db.createObjectStore(this.offlineQueueName, { keyPath: 'id', autoIncrement: true });
                }
            };
        });
    }

    async saveToIndexedDB(storeName, data) {
        const db = await this.getDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.add(data);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getAllFromIndexedDB(storeName) {
        const db = await this.getDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async deleteFromIndexedDB(storeName, id) {
        const db = await this.getDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }
}
