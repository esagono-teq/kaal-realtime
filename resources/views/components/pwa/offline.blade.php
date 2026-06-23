<div id="kaal-pwa-offline-indicator" style="display: none; position: fixed; top: 0; left: 0; width: 100%; background: #ef4444; color: #fff; text-align: center; padding: 8px; font-size: 13px; font-weight: 500; z-index: 10000; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" {{ $attributes }}>
    You are currently offline. Some features may be limited, but your changes are being saved locally.
</div>

<script>
    function updateOfflineIndicator() {
        const indicator = document.getElementById('kaal-pwa-offline-indicator');
        if (!indicator) return;
        
        if (!navigator.onLine) {
            indicator.style.display = 'block';
        } else {
            indicator.style.display = 'none';
        }
    }
    
    window.addEventListener('online', updateOfflineIndicator);
    window.addEventListener('offline', updateOfflineIndicator);
    
    // Initial check
    updateOfflineIndicator();
</script>
