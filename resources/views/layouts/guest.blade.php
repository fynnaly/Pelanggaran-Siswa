<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Pelanggaran Siswa') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="icon" type="image/x-icon" href="{{ asset('image.ico') }}">

        <script>
            (function() {
                const s = localStorage.getItem('theme');
                if (s === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                } else {
                    document.documentElement.classList.add('light');
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <style>
            /* ── Design System Tokens (sinkron app.blade.php) ── */
            :root {
                --primary: #1e3a5f; --secondary: #64748b; --tertiary: #059669; --tertiary-hover: #047857;
                --neutral: #ffffff; --surface: #f8fafc; --surface-muted: #f1f5f9;
                --border: #e2e8f0; --danger: #dc2626; --on-primary: #fff; --on-tertiary: #fff;
                --on-surface: #1e293b; --on-surface-muted: #64748b;
                --sh-sm: 0 1px 2px rgba(15,23,42,.06); --sh-md: 0 4px 12px rgba(15,23,42,.08);
                --r-sm: 8px; --r-md: 12px; --r-lg: 16px;
                --sp-xs: 4px; --sp-sm: 8px; --sp-md: 16px; --sp-lg: 24px; --sp-xl: 32px;
            }
            .dark {
                --neutral: #1c1c1f; --surface: #111114; --surface-muted: #202024;
                --border: #252830; --danger: #ef4444; --primary: #5b8abf;
                --tertiary: #10b981; --tertiary-hover: #059669;
                --on-surface: #e8eaed; --on-surface-muted: #8b919a;
                --sh-md: 0 4px 16px rgba(0,0,0,.4);
            }

            * { box-sizing: border-box; }
            html, body { min-height: 100%; margin: 0; padding: 0; }
            body {
                font-family: 'Figtree', sans-serif;
                font-size: 0.875rem;
                line-height: 1.5;
                background-color: var(--surface);
                color: var(--on-surface);
                transition: background-color .2s ease, color .2s ease;
                -webkit-font-smoothing: antialiased;
            }
            a { color: var(--tertiary); text-decoration: none; }
            a:hover { text-decoration: underline; }

            .guest-wrap {
                min-height: 100vh;
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: var(--sp-xl) var(--sp-md);
                position: relative;
            }

            /* Floating top-right controls */
            .top-bar {
                position: absolute;
                top: 20px;
                right: 20px;
                display: flex;
                align-items: center;
                gap: var(--sp-sm);
                z-index: 10;
            }
            .btn-theme {
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: var(--r-sm);
                border: 1px solid var(--border);
                background: var(--neutral);
                color: var(--on-surface-muted);
                cursor: pointer;
                transition: color .15s, border-color .15s;
            }
            .btn-theme:hover {
                color: var(--on-surface);
                border-color: var(--tertiary);
            }

            /* Central Card */
            .guest-card {
                width: 100%;
                max-width: 400px;
                background: var(--neutral);
                border: 1px solid var(--border);
                border-radius: var(--r-lg);
                padding: var(--sp-xl);
                box-shadow: var(--sh-md);
            }

            /* Institutional Brand Header */
            .brand-header {
                text-align: center;
                margin-bottom: var(--sp-lg);
            }
            .brand-title {
                font-size: 1.25rem;
                font-weight: 700;
                margin: 0;
                color: var(--on-surface);
                letter-spacing: -0.01em;
            }
            .brand-subtitle {
                font-size: 0.8125rem;
                color: var(--on-surface-muted);
                margin: 4px 0 0;
            }

            /* Form Elements */
            .field {
                margin-bottom: var(--sp-md);
            }
            .field-label {
                display: block;
                font-size: 0.8125rem;
                font-weight: 600;
                margin-bottom: 6px;
                color: var(--on-surface);
            }
            .field-input {
                width: 100%;
                box-sizing: border-box;
                padding: 10px 14px;
                border-radius: var(--r-sm);
                border: 1px solid var(--border);
                background: var(--surface);
                color: var(--on-surface);
                font-family: inherit;
                font-size: 0.875rem;
                transition: border-color .15s, box-shadow .15s;
            }
            .field-input:focus {
                outline: none;
                border-color: var(--tertiary);
                box-shadow: 0 0 0 3px rgba(5,150,105,.15);
            }
            .dark .field-input:focus {
                box-shadow: 0 0 0 3px rgba(16,185,129,.2);
            }
            .password-box {
                position: relative;
                display: flex;
                align-items: center;
            }
            .password-box .field-input {
                padding-right: 42px;
            }
            .toggle-pwd-btn {
                position: absolute;
                right: 8px;
                width: 30px;
                height: 30px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: none;
                color: var(--on-surface-muted);
                cursor: pointer;
                border-radius: 4px;
                padding: 0;
            }
            .toggle-pwd-btn:hover {
                color: var(--on-surface);
            }

            /* Options row */
            .options-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-top: 2px;
                margin-bottom: var(--sp-lg);
                font-size: 0.8125rem;
            }
            .remember-label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: var(--on-surface-muted);
                cursor: pointer;
                user-select: none;
            }
            .remember-label input {
                width: 15px;
                height: 15px;
                accent-color: var(--tertiary);
                cursor: pointer;
                margin: 0;
            }
            .forgot-link {
                color: var(--on-surface-muted);
                font-size: 0.8125rem;
            }
            .forgot-link:hover {
                color: var(--tertiary);
            }

            /* Button */
            .btn-primary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                padding: 11px 16px;
                border-radius: var(--r-sm);
                border: none;
                font-family: inherit;
                font-size: 0.875rem;
                font-weight: 600;
                cursor: pointer;
                background: var(--tertiary);
                color: var(--on-tertiary);
                transition: background-color .15s;
            }
            .btn-primary:hover {
                background: var(--tertiary-hover);
            }

            /* Alerts */
            .alert-error {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                background: #fee2e2;
                border: 1px solid #fca5a5;
                color: #991b1b;
                border-radius: var(--r-sm);
                padding: 10px 12px;
                font-size: 0.8125rem;
                margin-bottom: var(--sp-md);
            }
            .dark .alert-error {
                background: rgba(239,68,68,.12);
                border-color: rgba(239,68,68,.3);
                color: #fca5a5;
            }
            .alert-success {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                background: #dcfce7;
                border: 1px solid #86efac;
                color: #166534;
                border-radius: var(--r-sm);
                padding: 10px 12px;
                font-size: 0.8125rem;
                margin-bottom: var(--sp-md);
            }
            .dark .alert-success {
                background: rgba(16,185,129,.12);
                border-color: rgba(16,185,129,.3);
                color: #6ee7b7;
            }
            .alert-icon {
                width: 16px;
                height: 16px;
                flex-shrink: 0;
                margin-top: 1px;
            }

            /* Footer */
            .guest-footer {
                width: 100%;
                max-width: 400px;
                padding-top: var(--sp-lg);
                text-align: center;
                font-size: 0.75rem;
                color: var(--on-surface-muted);
            }

            .icon-sm { width: 16px; height: 16px; }
            @media (max-width: 480px) {
                .guest-card {
                    padding: var(--sp-lg);
                    border: none;
                    background: transparent;
                    box-shadow: none;
                }
                .guest-wrap {
                    padding: var(--sp-md);
                }
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="guest-wrap">
            <div class="top-bar">
                <button type="button" class="btn-theme" onclick="toggleTheme()" aria-label="Ganti tema" title="Ganti tema">
                    <i data-lucide="sun" class="icon-theme"></i>
                </button>
            </div>

            <div class="guest-card">
                <div class="brand-header">
                    <h1 class="brand-title">{{ config('app.name', 'Pelanggaran Siswa') }}</h1>
                    <p class="brand-subtitle">{{ \App\Models\Setting::get('school_name', 'SMK Baktinusantara 666') }}</p>
                </div>

                @if(session('status'))
                    <div class="alert-success" role="status">
                        <i data-lucide="check-circle" class="alert-icon"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                {{ $slot }}
            </div>

            <div class="guest-footer">
                <span>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('school_name', 'SMK Baktinusantara 666') }}</span>
            </div>
        </div>

        <script src="{{ asset('vendor/lucide/lucide.min.js') }}" onload="lucide.createIcons(); updateThemeIcon();"></script>
        <script>
            function toggleTheme() {
                const h = document.documentElement;
                const isDark = h.classList.toggle('dark');
                h.classList.toggle('light', !isDark);
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                updateThemeIcon();
            }
            function updateThemeIcon() {
                const isDark = document.documentElement.classList.contains('dark');
                document.querySelectorAll('.icon-theme').forEach(el => {
                    el.setAttribute('data-lucide', isDark ? 'moon' : 'sun');
                });
                if (window.lucide) lucide.createIcons();
            }
        </script>
    </body>
</html>
