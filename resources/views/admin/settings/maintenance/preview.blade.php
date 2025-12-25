<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode Preview - {{ config('app.name') }}</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            color: #333;
        }
        
        .maintenance-container {
            background: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            margin: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .maintenance-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .maintenance-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        h1 { 
            color: #333; 
            margin-bottom: 1rem;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .message {
            color: #666; 
            line-height: 1.8; 
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .duration-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid #667eea;
        }
        
        .duration-info strong {
            color: #667eea;
        }
        
        .progress-container {
            margin: 2rem 0;
        }
        
        .progress-bar {
            background: #e9ecef;
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            margin: 1rem 0;
            position: relative;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            width: 0%;
            border-radius: 6px;
            animation: progress 8s ease-in-out infinite;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(
                90deg, 
                transparent, 
                rgba(255,255,255,0.4), 
                transparent
            );
            animation: shimmer 2s ease-in-out infinite;
        }
        
        @keyframes progress {
            0%, 100% { width: 15%; }
            50% { width: 75%; }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(200%); }
        }
        
        .contact-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
            border: 1px solid #dee2e6;
        }
        
        .contact-info h4 {
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .contact-email {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 5px;
            display: inline-block;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .contact-email:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }
        
        .footer-text {
            margin-top: 2rem;
            color: #868e96;
            font-size: 0.9rem;
        }
        
        .preview-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1000;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @media (max-width: 768px) {
            .maintenance-container {
                margin: 0.5rem;
                padding: 2rem 1.5rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .maintenance-icon {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="preview-badge">PREVIEW MODE</div>
    
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        
        <h1>Under Maintenance</h1>
        
        <div class="message">
            {{ $message ?? 'We are currently performing scheduled maintenance on our systems to improve your experience. We apologize for any inconvenience this may cause.' }}
        </div>
        
        @if(!empty($duration))
        <div class="duration-info">
            <strong>Estimated Duration:</strong> {{ $duration }}
        </div>
        @endif
        
        @if($show_progress ?? false)
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <small style="color: #666;">Maintenance in progress...</small>
        </div>
        @endif
        
        @if(!empty($contact_email))
        <div class="contact-info">
            <h4>Need Immediate Assistance?</h4>
            <p style="margin: 0.5rem 0; color: #666;">
                If you have any urgent concerns, please don't hesitate to reach out to us.
            </p>
            <a href="mailto:{{ $contact_email }}" class="contact-email">
                📧 {{ $contact_email }}
            </a>
        </div>
        @endif
        
        <div class="footer-text">
            <p>Thank you for your patience while we make improvements.</p>
            <p><strong>{{ config('app.name') }}</strong> • {{ now()->format('M j, Y') }}</p>
        </div>
    </div>

    <script>
        // Add some interactive elements for preview
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handler to preview badge
            document.querySelector('.preview-badge').addEventListener('click', function() {
                if (confirm('Close preview and return to admin panel?')) {
                    window.close();
                }
            });
            
            // Add some hover effects
            const container = document.querySelector('.maintenance-container');
            container.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            container.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>