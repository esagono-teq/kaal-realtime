<div id="kaal-pwa-install-container" style="display: none;" {{ $attributes }}>
    <div class="kaal-pwa-install-banner" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between; gap: 15px; z-index: 9999; border: 1px solid #eee;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="background: #f0f0f0; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #333;">
                App
            </div>
            <div>
                <div style="font-weight: 600; font-size: 14px; color: #111;">{{ config('kaal-realtime.pwa.manifest.name', 'Install App') }}</div>
                <div style="font-size: 12px; color: #666;">Get the best offline experience</div>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button id="kaal-pwa-install-dismiss" style="background: transparent; border: none; color: #666; font-size: 13px; cursor: pointer; padding: 5px 10px;">Not now</button>
            <button id="kaal-pwa-install-btn" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 13px; cursor: pointer; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">Install</button>
        </div>
    </div>
</div>

<script>
    window.addEventListener('kaal:pwa:install-available', function(e) {
        const container = document.getElementById('kaal-pwa-install-container');
        if (!container) return;
        
        // Check if user previously dismissed
        if (localStorage.getItem('kaal_pwa_install_dismissed') === 'true') {
            return;
        }
        
        container.style.display = 'block';
        
        document.getElementById('kaal-pwa-install-btn').addEventListener('click', function() {
            if (window.KaalPWA) {
                window.KaalPWA.promptInstall().then(() => {
                    container.style.display = 'none';
                });
            }
        });
        
        document.getElementById('kaal-pwa-install-dismiss').addEventListener('click', function() {
            container.style.display = 'none';
            localStorage.setItem('kaal_pwa_install_dismissed', 'true');
        });
    });
</script>
