<div id="kaal-pwa-status-container" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #fff; padding: 12px 16px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #eee; z-index: 9998;" {{ $attributes }}>
    <div style="display: flex; align-items: center; gap: 8px;">
        <div id="kaal-pwa-status-icon" style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
        <div id="kaal-pwa-status-text" style="font-size: 13px; font-weight: 500; color: #333;">Connected & Synced</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('kaal-pwa-status-container');
        const icon = document.getElementById('kaal-pwa-status-icon');
        const text = document.getElementById('kaal-pwa-status-text');
        
        // Let's only show status when there's activity or offline
        
        function setStatus(state, message) {
            container.style.display = 'block';
            text.textContent = message;
            
            if (state === 'offline') {
                icon.style.background = '#ef4444'; // red
            } else if (state === 'syncing') {
                icon.style.background = '#f59e0b'; // yellow
            } else if (state === 'online') {
                icon.style.background = '#10b981'; // green
                setTimeout(() => {
                    if (navigator.onLine) {
                        container.style.display = 'none';
                    }
                }, 3000);
            }
        }
        
        window.addEventListener('online', () => setStatus('online', 'Connected. Syncing...'));
        window.addEventListener('offline', () => setStatus('offline', 'Offline. Changes saved locally.'));
        window.addEventListener('kaal:pwa:sync-conflict', () => setStatus('syncing', 'Sync Conflict Detected.'));
    });
</script>
