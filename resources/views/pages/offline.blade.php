<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Offline - {{ config('app.name') }}</title>
    <meta name="theme-color" content="#1f2937">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-32x32.png">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .offline-container {
            text-align: center;
            max-width: 400px;
            padding: 2rem;
        }
        
        .offline-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            opacity: 0.8;
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 300;
        }
        
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .retry-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .retry-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .connection-status {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .online {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .offline {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M23.64 7c-.45-.34-4.93-4-11.64-4C5.28 3 .81 6.66.36 7l10.08 12.56c.8 1 2.32 1 3.12 0L23.64 7zM3.53 10.95l8.47 10.61 8.47-10.61c-2.55-1.93-5.78-3.04-8.47-3.04s-5.92 1.11-8.47 3.04z"/>
            </svg>
        </div>
        
        <h1>You're Offline</h1>
        <p>It looks like you're not connected to the internet. Check your connection and try again.</p>
        
        <button class="retry-btn" onclick="window.location.reload()">
            Try Again
        </button>
        
        <div id="connection-status" class="connection-status offline">
            <span id="status-text">No internet connection</span>
        </div>
    </div>

    <script>
        function updateConnectionStatus() {
            const statusEl = document.getElementById('connection-status');
            const statusText = document.getElementById('status-text');
            
            if (navigator.onLine) {
                statusEl.className = 'connection-status online';
                statusText.textContent = 'Connection restored! You can try again.';
            } else {
                statusEl.className = 'connection-status offline';
                statusText.textContent = 'No internet connection';
            }
        }

        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        updateConnectionStatus();
        
        // Auto reload when back online
        window.addEventListener('online', () => {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    </script>
</body>
</html>