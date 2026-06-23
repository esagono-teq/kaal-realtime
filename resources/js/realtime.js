/**
 * KAAL Realtime Frontend Runtime (Custom WebSocket Client)
 */

document.addEventListener('DOMContentLoaded', () => {
    const wsUrl = import.meta.env.VITE_KAAL_GATEWAY_WS || 'wss://ws.kaalrealtime.com';
    const ws = new WebSocket(wsUrl);

    // ─── Presence state ────────────────────────────────────────────────────────
    // Map<room, { id, name }[]>  — kept in sync via gateway events
    const presenceState = new Map();

    // Read the KAAL user identity injected by the blade layout (optional).
    // Apps can set window.KAAL_USER = { id: ..., name: ... } in their layout.
    function getKaalUser() {
        return window.KAAL_USER || { id: null, name: 'Guest' };
    }

    // ─── Connection & Auth ─────────────────────────────────────────────────────
    ws.onopen = () => {
        window.dispatchEvent(new CustomEvent('kaal:online'));
        ws.send(JSON.stringify({
            action: 'authenticate',
            app_id: import.meta.env.VITE_KAAL_APP_ID,
            key:    import.meta.env.VITE_KAAL_APP_KEY,
            secret: import.meta.env.VITE_KAAL_APP_SECRET,
        }));
    };

    ws.onmessage = (message) => {
        let payload;
        try { payload = JSON.parse(message.data); } catch (e) { return; }

        // ── Authenticated → subscribe to model/cluster channels + join presence rooms
        if (payload.type === 'authenticated') {
            console.log('KAAL WS: Authenticated.');
            ws.send(JSON.stringify({ action: 'subscribe', channel: 'kaal-realtime.models' }));
            ws.send(JSON.stringify({ action: 'subscribe', channel: 'kaal-realtime.clusters' }));
            // Join all presence rooms declared on the page
            initPresenceRooms();
        }

        if (payload.type === 'subscribed') {
            console.log('KAAL WS: Subscribed to channel:', payload.channel);
        }

        // ── Model event → refresh realtime blocks
        if (payload.type === 'event' && payload.channel === 'kaal-realtime.models') {
            console.log('KAAL EVENT (Model)', payload);
            const eventData = payload.data;
            if (!eventData || !eventData.model) return;

            document.querySelectorAll('[data-realtime-id]').forEach(block => {
                const modelsAttr = block.getAttribute('data-models');
                if (modelsAttr && modelsAttr.split(',').includes(eventData.model)) {
                    refreshManualBlock(block);
                }
            });

            document.querySelectorAll('[data-kaal-fragment]').forEach(block => {
                const modelsAttr = block.getAttribute('data-kaal-models');
                if (modelsAttr && modelsAttr.split(',').includes(eventData.model)) {
                    refreshAutoBlock(block);
                }
            });
        }

        // ── Cluster event → refresh cluster blocks
        if (payload.type === 'event' && payload.channel === 'kaal-realtime.clusters') {
            console.log('KAAL EVENT (Cluster)', payload);
            const eventData = payload.data;
            if (!eventData || !eventData.cluster) return;

            const clusterName = eventData.cluster;
            document.querySelectorAll(`[data-realtime-id][data-kaal-parent-cluster="${clusterName}"]`).forEach(block => {
                refreshManualBlock(block);
            });
            document.querySelectorAll(`[data-kaal-fragment][data-kaal-parent-cluster="${clusterName}"]`).forEach(block => {
                refreshAutoBlock(block);
            });
        }

        // ── Presence: initial state on join
        if (payload.type === 'presence_state') {
            const { room, users } = payload;
            presenceState.set(room, users || []);
            renderPresence(room);
        }

        // ── Presence: someone joined
        if (payload.type === 'event' && payload.event === 'presence_joined') {
            const { room, users } = payload.data || {};
            if (!room) return;
            presenceState.set(room, users || []);
            renderPresence(room);
            window.dispatchEvent(new CustomEvent('kaal:presence_joined', { detail: payload.data }));
        }

        // ── Presence: someone left
        if (payload.type === 'event' && payload.event === 'presence_left') {
            const { room, users } = payload.data || {};
            if (!room) return;
            presenceState.set(room, users || []);
            renderPresence(room);
            window.dispatchEvent(new CustomEvent('kaal:presence_left', { detail: payload.data }));
        }
    };

    ws.onerror = (error) => {
        console.error('KAAL WS Error:', error);
    };

    ws.onclose = () => {
        console.log('KAAL WS Disconnected.');
        window.dispatchEvent(new CustomEvent('kaal:offline'));
    };

    // Leave all rooms gracefully when the tab/window closes
    window.addEventListener('beforeunload', () => {
        for (const room of presenceState.keys()) {
            ws.send(JSON.stringify({ action: 'presence_leave', room }));
        }
    });

    // ─── Presence helpers ──────────────────────────────────────────────────────

    /**
     * Find all [data-kaal-presence] blocks on the page and join each room.
     */
    function initPresenceRooms() {
        document.querySelectorAll('[data-kaal-presence]').forEach(el => {
            const room = el.getAttribute('data-kaal-presence');
            if (!room || presenceState.has(room)) return;

            // Seed local state from the server-rendered JSON (so UI is correct before WS events)
            try {
                const initial = JSON.parse(el.getAttribute('data-kaal-presence-users') || '[]');
                presenceState.set(room, initial);
            } catch (_) {
                presenceState.set(room, []);
            }

            // Send join action — gateway will respond with presence_state + broadcast presence_joined
            ws.send(JSON.stringify({
                action: 'presence_join',
                room,
                user: getKaalUser(),
            }));

            console.log(`KAAL Presence: joining room "${room}"`);
        });
    }

    /**
     * Update the DOM for a presence room after a state change.
     * Supports two UI patterns:
     *
     *  1. Indicator dots:   [data-kaal-presence-indicator][data-id="userId"]
     *     → turns green (online) or gray (offline) based on presence state.
     *
     *  2. Dynamic list:     [data-kaal-presence-list] inside the presence block
     *     → re-renders a <li> per online user automatically.
     */
    function renderPresence(room) {
        const users = presenceState.get(room) || [];
        const onlineIds = new Set(users.map(u => String(u.id)));

        document.querySelectorAll(`[data-kaal-presence="${CSS.escape(room)}"]`).forEach(block => {

            // Pattern 1 — indicator dots
            block.querySelectorAll('[data-kaal-presence-indicator]').forEach(dot => {
                const id = dot.getAttribute('data-id');
                const isOnline = onlineIds.has(String(id));
                dot.classList.toggle('bg-green-500', isOnline);
                dot.classList.toggle('bg-gray-300',  !isOnline);
                dot.setAttribute('title', isOnline ? 'Online' : 'Offline');
            });

            // Pattern 2 — dynamic list
            const list = block.querySelector('[data-kaal-presence-list]');
            if (list) {
                list.innerHTML = users.length
                    ? users.map(u => `
                        <li class="flex items-center gap-2 py-1">
                            <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                            <span>${escapeHtml(u.name || 'Anonymous')}</span>
                        </li>`).join('')
                    : '<li class="text-gray-400 text-sm">No one else is here</li>';
            }
        });

        // Also update any standalone indicators on the page (outside presence blocks)
        document.querySelectorAll(`[data-kaal-presence-indicator][data-room="${CSS.escape(room)}"]`).forEach(dot => {
            const id = dot.getAttribute('data-id');
            const isOnline = onlineIds.has(String(id));
            dot.classList.toggle('bg-green-500', isOnline);
            dot.classList.toggle('bg-gray-300',  !isOnline);
        });

        window.dispatchEvent(new CustomEvent('kaal:presence_updated', {
            detail: { room, users }
        }));
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ─── Form submission (kaal-submit) ─────────────────────────────────────────
    document.addEventListener('submit', async (e) => {
        if (!e.target || !e.target.hasAttribute('kaal-submit')) return;

        e.preventDefault();
        const form = e.target;

        // Clear existing errors
        form.querySelectorAll('.kaal-error').forEach(el => el.innerText = '');

        // Trigger loading state
        form.classList.add('kaal-loading');
        window.dispatchEvent(new CustomEvent('kaal:loading', { detail: { form } }));

        const loadingElements = form.querySelectorAll('[kaal-loading-disable], [kaal-loading-text], [kaal-loading-class]');
        loadingElements.forEach(el => {
            el.dataset.kaalOriginalText = el.innerHTML;
            if (el.hasAttribute('kaal-loading-disable')) el.disabled = true;
            if (el.hasAttribute('kaal-loading-text'))    el.innerHTML = el.getAttribute('kaal-loading-text');
            if (el.hasAttribute('kaal-loading-class'))   el.classList.add(...el.getAttribute('kaal-loading-class').split(' '));
        });

        const method = (form.method || 'POST').toUpperCase();
        const action = form.action || window.location.href;

        try {
            const response = await fetch(action, {
                method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            let data = {};
            try { data = await response.json(); } catch (_) {}

            if (response.ok) {
                window.dispatchEvent(new CustomEvent('kaal:success', { detail: data }));
                if (data.message) {
                    window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'success', message: data.message } }));
                } else if (form.hasAttribute('kaal-success')) {
                    window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'success', message: form.getAttribute('kaal-success') } }));
                }
                if (form.hasAttribute('kaal-reset')) form.reset();
            } else if (response.status === 422) {
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorEl = form.querySelector(`.kaal-error[data-error-for="${field}"]`);
                        if (errorEl) errorEl.innerText = data.errors[field][0] || 'Validation error';
                    });
                }
                window.dispatchEvent(new CustomEvent('kaal:error', { detail: data }));
                if (data.message) {
                    window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'error', message: data.message } }));
                } else if (form.hasAttribute('kaal-error')) {
                    window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'error', message: form.getAttribute('kaal-error') } }));
                }
            } else {
                const errorMsg = data.message || `Request failed with status ${response.status}`;
                window.dispatchEvent(new CustomEvent('kaal:error', { detail: { message: errorMsg } }));
                if (form.hasAttribute('kaal-error')) {
                    window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'error', message: form.getAttribute('kaal-error') } }));
                } else {
                    window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'error', message: errorMsg } }));
                }
            }
        } catch (error) {
            console.error('KAAL Form Submit Error:', error);
            window.dispatchEvent(new CustomEvent('kaal:error', { detail: { message: error.message } }));
            if (form.hasAttribute('kaal-error')) {
                window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'error', message: form.getAttribute('kaal-error') } }));
            } else {
                window.dispatchEvent(new CustomEvent('kaal:toast', { detail: { type: 'error', message: error.message } }));
            }
        } finally {
            form.classList.remove('kaal-loading');
            loadingElements.forEach(el => {
                if (el.hasAttribute('kaal-loading-disable')) el.disabled = false;
                if (el.hasAttribute('kaal-loading-text'))    el.innerHTML = el.dataset.kaalOriginalText;
                if (el.hasAttribute('kaal-loading-class'))   el.classList.remove(...el.getAttribute('kaal-loading-class').split(' '));
            });
            window.dispatchEvent(new CustomEvent('kaal:loaded', { detail: { form } }));
        }
    });

    // ─── Block refresh helpers ─────────────────────────────────────────────────
    async function refreshManualBlock(block) {
        const id = block.getAttribute('data-realtime-id');
        if (!id) return;
        console.log('KAAL REFRESH MANUAL', id);

        try {
            const blockUrl = new URL(`/kaal/realtime/refresh/${id}`, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            for (const [key, value] of currentParams) blockUrl.searchParams.set(key, value);

            const response = await fetch(blockUrl.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            });

            if (!response.ok) return console.error(`KAAL: Failed to refresh manual block [${id}]`);
            preserveAndReplace(block, await response.text());
        } catch (error) {
            console.error('KAAL Fetch Error:', error);
        }
    }

    async function refreshAutoBlock(block) {
        const fragmentId = block.getAttribute('data-kaal-fragment');
        const signedUrl  = block.getAttribute('data-kaal-url');
        if (!fragmentId || !signedUrl) return;

        console.log('KAAL REFRESH AUTO', fragmentId);

        try {
            const blockUrl = new URL(signedUrl, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            for (const [key, value] of currentParams) blockUrl.searchParams.set(key, value);

            const response = await fetch(blockUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                    'X-KAAL-FRAGMENT': fragmentId,
                },
            });

            if (response.status === 403) {
                console.warn('[KAAL] Fragment URL expired. Reloading page.');
                location.reload();
                return;
            }

            if (!response.ok) return console.error(`KAAL: Failed to refresh auto block [${fragmentId}]`);
            preserveAndReplace(block, await response.text());
        } catch (error) {
            console.error('KAAL Fetch Error:', error);
        }
    }

    // ─── DOM diffing / preserve & ignore ──────────────────────────────────────
    function getUniqueSelector(el, block) {
        if (el.id) return `#${el.id}`;
        const path = [];
        let parent = el;
        while (parent && parent !== block) {
            const tagName  = parent.tagName.toLowerCase();
            const siblings = Array.from(parent.parentNode.children);
            const index    = siblings.indexOf(parent);
            path.unshift(`${tagName}:nth-child(${index + 1})`);
            parent = parent.parentNode;
        }
        return path.join(' > ');
    }

    function preserveAndReplace(block, newHtml) {
        const activeElement = document.activeElement;
        const isFocusInside = block.contains(activeElement);
        let activeElementId = null, activeElementSelector = null;
        let selectionStart = null, selectionEnd = null;

        if (isFocusInside && activeElement) {
            if (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA') {
                try {
                    selectionStart = activeElement.selectionStart;
                    selectionEnd   = activeElement.selectionEnd;
                } catch (_) {}
            }
            activeElementId       = activeElement.id;
            activeElementSelector = getUniqueSelector(activeElement, block);
        }

        // Cache preserve/ignore elements from old DOM
        const oldPreservesMap = new Map();
        const oldPreservesCount = {};
        block.querySelectorAll('[data-kaal-preserve]').forEach(el => {
            const id  = el.getAttribute('data-kaal-preserve');
            oldPreservesCount[id] = (oldPreservesCount[id] || 0) + 1;
            oldPreservesMap.set(`${id}:${oldPreservesCount[id] - 1}`, el);
        });

        const oldIgnoresMap = new Map();
        const oldIgnoresCount = {};
        block.querySelectorAll('[data-kaal-ignore]').forEach(el => {
            const id  = el.getAttribute('data-kaal-ignore');
            oldIgnoresCount[id] = (oldIgnoresCount[id] || 0) + 1;
            oldIgnoresMap.set(`${id}:${oldIgnoresCount[id] - 1}`, el);
        });

        // Parse new HTML
        const parser = new DOMParser();
        const doc    = parser.parseFromString(newHtml, 'text/html');

        // Splice old preserve/ignore nodes into new DOM
        const newPreservesCount = {};
        doc.body.querySelectorAll('[data-kaal-preserve]').forEach(newEl => {
            const id  = newEl.getAttribute('data-kaal-preserve');
            newPreservesCount[id] = (newPreservesCount[id] || 0) + 1;
            const oldEl = oldPreservesMap.get(`${id}:${newPreservesCount[id] - 1}`);
            if (oldEl) newEl.replaceWith(oldEl);
        });

        const newIgnoresCount = {};
        doc.body.querySelectorAll('[data-kaal-ignore]').forEach(newEl => {
            const id  = newEl.getAttribute('data-kaal-ignore');
            newIgnoresCount[id] = (newIgnoresCount[id] || 0) + 1;
            const oldEl = oldIgnoresMap.get(`${id}:${newIgnoresCount[id] - 1}`);
            if (oldEl) newEl.replaceWith(oldEl);
        });

        // Replace block contents
        const strategy = block.getAttribute('data-kaal-strategy') || 'replace';
        if (strategy === 'replace' || strategy === 'morph') {
            block.innerHTML = '';
            while (doc.body.firstChild) block.appendChild(doc.body.firstChild);
        } else if (strategy === 'append') {
            while (doc.body.firstChild) block.appendChild(doc.body.firstChild);
        }

        // Restore focus
        if (isFocusInside && activeElement) {
            let elementToFocus = activeElementId ? block.querySelector(`#${activeElementId}`) : null;
            if (!elementToFocus && activeElementSelector) elementToFocus = block.querySelector(activeElementSelector);
            if (!elementToFocus && document.body.contains(activeElement)) elementToFocus = activeElement;

            if (elementToFocus) {
                elementToFocus.focus();
                if (selectionStart !== null && selectionEnd !== null) {
                    try { elementToFocus.setSelectionRange(selectionStart, selectionEnd); } catch (_) {}
                }
            }
        }
    }

    // ─── Infinite Scroll ───────────────────────────────────────────────────────
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const nextUrl = entry.target.dataset.nextUrl;
                if (nextUrl && !entry.target.hasAttribute('kaal-fetching')) {
                    entry.target.setAttribute('kaal-fetching', 'true');
                    fetch(nextUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
                        .then(r => r.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc    = parser.parseFromString(html, 'text/html');
                            const newContainer = doc.querySelector('[data-next-url]');
                            if (newContainer && newContainer.dataset.nextUrl) {
                                entry.target.dataset.nextUrl = newContainer.dataset.nextUrl;
                            } else {
                                entry.target.removeAttribute('data-next-url');
                            }
                            const items = newContainer ? newContainer.children : doc.body.children;
                            Array.from(items).forEach(child => entry.target.appendChild(child));
                        })
                        .finally(() => entry.target.removeAttribute('kaal-fetching'));
                }
            }
        });
    });
    document.querySelectorAll('[kaal-infinite-scroll]').forEach(el => observer.observe(el));

    // ─── Presence heartbeat (keep server-side cache alive) ────────────────────
    // The gateway owns live state; this just keeps the Laravel Cache fresh
    // for server-side Presence::users() calls in Blade.
    setInterval(() => {
        const rooms = new Set();
        document.querySelectorAll('[data-kaal-presence]').forEach(el => {
            rooms.add(el.getAttribute('data-kaal-presence'));
        });
        rooms.forEach(room => {
            fetch('/kaal/presence/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ room }),
            }).catch(() => {});
        });
    }, 10000);

    // ─── Server Actions (kaal-action) ──────────────────────────────────────────
    document.addEventListener('click', (e) => {
        const actionBtn = e.target.closest('[kaal-action]');
        if (!actionBtn) return;

        const action   = actionBtn.getAttribute('kaal-action');
        const payload  = { ...actionBtn.dataset };
        const origText = actionBtn.innerHTML;

        if (actionBtn.hasAttribute('kaal-loading-disable')) actionBtn.disabled = true;
        if (actionBtn.hasAttribute('kaal-loading-text'))    actionBtn.innerHTML = actionBtn.getAttribute('kaal-loading-text');

        fetch(`/kaal/realtime/action/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(payload),
        }).finally(() => {
            if (actionBtn.hasAttribute('kaal-loading-disable')) actionBtn.disabled = false;
            if (actionBtn.hasAttribute('kaal-loading-text'))    actionBtn.innerHTML = origText;
        });
    });

    // ─── Debug overlay ─────────────────────────────────────────────────────────
    if (window.KAAL_DEBUG) {
        initDebugPanel();
    }

    const debugStats = { refreshes: 0, lastRefresh: 'Never', events: [] };

    function initDebugPanel() {
        const root = document.getElementById('kaal-debug-root');
        if (!root) return;

        root.innerHTML = `
            <div id="kaal-debug-panel" style="position:fixed;bottom:20px;right:20px;width:350px;background:#1e1e2e;color:#cdd6f4;border:1px solid #313244;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.5);font-family:monospace;font-size:12px;z-index:999999;overflow:hidden;">
                <div style="background:#11111b;padding:10px;border-bottom:1px solid #313244;display:flex;justify-content:space-between;align-items:center;">
                    <strong style="color:#89b4fa;font-size:14px;">KAAL Debugger</strong>
                    <span id="kaal-debug-status" style="color:#a6e3a1;">&#9679; Online</span>
                </div>
                <div style="padding:10px;max-height:400px;overflow-y:auto;" id="kaal-debug-content">
                    <div style="margin-bottom:10px;">
                        <h4 style="color:#f38ba8;margin:0 0 5px 0;">Clusters</h4>
                        <div id="kaal-debug-clusters"></div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <h4 style="color:#f38ba8;margin:0 0 5px 0;">Presence</h4>
                        <div id="kaal-debug-presence"></div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <h4 style="color:#f38ba8;margin:0 0 5px 0;">Performance</h4>
                        <div>Refreshes: <span id="kaal-debug-refreshes">0</span></div>
                        <div>Last Refresh: <span id="kaal-debug-last-refresh">Never</span></div>
                        <div>Memory: <span id="kaal-debug-memory">N/A</span></div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <h4 style="color:#f38ba8;margin:0 0 5px 0;">Recent Events</h4>
                        <ul id="kaal-debug-events" style="margin:0;padding-left:15px;color:#f9e2af;"></ul>
                    </div>
                </div>
            </div>
        `;

        setInterval(updateDebugUI, 1000);
    }

    function updateDebugUI() {
        if (!window.KAAL_DEBUG) return;

        // Clusters
        const clusters = {};
        document.querySelectorAll('[data-kaal-cluster]').forEach(el => {
            const name   = el.getAttribute('data-kaal-cluster');
            const blocks = el.querySelectorAll('[data-realtime-id], [data-kaal-fragment]').length;
            clusters[name] = blocks;
        });
        const clustersHtml = Object.entries(clusters).map(([name, count]) =>
            `<div>${name} <span style="color:#6c7086">(${count} blocks)</span></div>`
        ).join('');
        const cEl = document.getElementById('kaal-debug-clusters');
        if (cEl) cEl.innerHTML = clustersHtml || 'None';

        // Presence
        const presenceHtml = [...presenceState.entries()].map(([room, users]) =>
            `<div>${room} <span style="color:#6c7086">(${users.length} online)</span></div>`
        ).join('');
        const pEl = document.getElementById('kaal-debug-presence');
        if (pEl) pEl.innerHTML = presenceHtml || 'None';

        // Performance
        const rEl = document.getElementById('kaal-debug-refreshes');
        if (rEl) rEl.innerText = debugStats.refreshes;
        const lrEl = document.getElementById('kaal-debug-last-refresh');
        if (lrEl) lrEl.innerText = debugStats.lastRefresh;
        const mEl = document.getElementById('kaal-debug-memory');
        if (mEl && window.performance && window.performance.memory) {
            mEl.innerText = Math.round(performance.memory.usedJSHeapSize / 1048576) + ' MB';
        }

        // Events
        const eventsHtml = debugStats.events.map(e => `<li>${e}</li>`).join('');
        const eEl = document.getElementById('kaal-debug-events');
        if (eEl) eEl.innerHTML = eventsHtml || '<li>None</li>';
    }

    function logDebugEvent(name) {
        if (!window.KAAL_DEBUG) return;
        debugStats.events.unshift(name);
        if (debugStats.events.length > 5) debugStats.events.pop();
        updateDebugUI();
    }
});
