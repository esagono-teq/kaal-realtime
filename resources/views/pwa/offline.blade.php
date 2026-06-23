<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('kaal-realtime.pwa.manifest.name', 'KAAL PWA') }} - Offline</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f9fafb;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 400px;
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .icon {
            width: 64px;
            height: 64px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon svg {
            width: 32px;
            height: 32px;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 25px;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        button:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M9.008 9.008A3 3 0 0115 15m-1.5-7.5a1.5 1.5 0 00-1.5 1.5M19.07 4.93a10 10 0 010 14.14m-14.14 0a10 10 0 010-14.14"></path>
            </svg>
        </div>
        <h1>You are offline</h1>
        <p>It looks like you've lost your internet connection. Some features are unavailable, but you can continue using the app. Changes will be synced when you reconnect.</p>
        <button onclick="window.location.reload()">Try Again</button>
    </div>
    
    <script>
        window.addEventListener('online', function() {
            window.location.reload();
        });
    </script>
</body>
</html>
