<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Pelanggaran Kuy') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="icon" type="image/x-icon" href="{{ asset('image.ico') }}">

        <script>
            (function() {
                const s = localStorage.getItem('theme');
                document.documentElement.classList.add(s === 'light' ? 'light' : 'dark');
            })();
        </script>

        <style>
            /* Design system tokens — sama dengan layouts/app.blade.php */
            :root {
                --primary: #1e3a5f; --tertiary: #059669; --neutral: #ffffff; --surface: #f8fafc;
                --border: #e2e8f0; --danger: #dc2626; --on-primary: #fff; --on-tertiary: #fff;
                --on-surface: #1e293b; --on-surface-muted: #64748b;
                --sh-md: 0 4px 12px rgba(15,23,42,.08); --r-sm: 8px; --r-lg: 20px;
                --sp-sm: 8px; --sp-md: 16px; --sp-lg: 24px; --sp-xl: 40px;
            }
            .dark {
                --neutral: #1c1c1f; --surface: #111114; --border: #252830;
                --on-surface: #e8eaed; --on-surface-muted: #8b919a; --primary: #5b8abf;
            }
            body { font-family: 'Figtree', sans-serif; line-height: 1.6; background: var(--surface);
                   color: var(--on-surface); transition: background .2s, color .2s; overflow-x: hidden; }
            a { color: var(--tertiary); text-decoration: none; }
            .guest-wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center;
                          justify-content: center; padding: var(--sp-xl) var(--sp-md) 0; }
            .guest-card { width: 100%; max-width: 420px; background: var(--neutral); border: 1px solid var(--border);
                          border-radius: var(--r-lg); padding: var(--sp-xl) var(--sp-lg); box-shadow: var(--sh-md); }
            .guest-brand { text-align: center; margin-bottom: var(--sp-lg); }
            .guest-brand img { width: 64px; height: 64px; object-fit: contain; margin-bottom: var(--sp-sm); }
            .guest-brand h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
            .guest-brand p { font-size: .875rem; color: var(--on-surface-muted); margin: 4px 0 0; }
            .field { margin-bottom: var(--sp-md); }
            .field-label { display: block; font-size: .75rem; font-weight: 600; letter-spacing: .06em;
                           text-transform: uppercase; margin-bottom: 4px; color: var(--on-surface-muted); }
            .field-input { width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: var(--r-sm);
                           border: 1px solid var(--border); background: var(--neutral); color: var(--on-surface);
                           font-family: inherit; font-size: .875rem; transition: border-color .15s, box-shadow .15s; }
            .field-input:focus { outline: none; border-color: var(--tertiary); box-shadow: 0 0 0 3px rgba(5,150,105,.15); }
            .btn { display: inline-flex; align-items: center; justify-content: center; gap: var(--sp-sm); width: 100%;
                   padding: 12px 20px; border-radius: var(--r-sm); border: none; font-family: inherit;
                   font-size: .875rem; font-weight: 600; cursor: pointer; background: var(--tertiary); color: var(--on-tertiary); }
            .btn:hover { background: #047857; }
            .check-row { display: flex; align-items: center; gap: var(--sp-sm); font-size: .875rem;
                         color: var(--on-surface-muted); margin-bottom: var(--sp-md); }
            .check-row input { width: 16px; height: 16px; accent-color: var(--tertiary); }
            .alert-success { background: #dcfce7; border: 1px solid #166534; color: #166534; border-radius: var(--r-sm);
                             padding: var(--sp-sm) var(--sp-md); font-size: .875rem; margin-bottom: var(--sp-md); }
            .dark .alert-success { background: #064e3b; color: #6ee7b7; border-color: #6ee7b7; }
            .alert-error { background: #fee2e2; border: 1px solid #991b1b; color: #991b1b; border-radius: var(--r-sm);
                           padding: var(--sp-sm) var(--sp-md); font-size: .875rem; margin-bottom: var(--sp-md); }
            .dark .alert-error { background: #7f1d1d; color: #fca5a5; border-color: #fca5a5; }
            .help-link { text-align: right; font-size: .8125rem; margin-top: 4px; }
            .guest-foot { width: 100%; max-width: 420px; padding: var(--sp-lg) 0 var(--sp-md);
                          text-align: center; font-size: .75rem; color: var(--on-surface-muted); line-height: 1.7; }
            .guest-foot strong { color: var(--on-surface); font-weight: 600; }
            .guest-foot a { color: var(--tertiary); }
        </style>
    </head>
    <body class="antialiased">
        <div class="guest-wrap">
            <div class="guest-brand">
                <a href="/">
                    @if(\App\Models\Setting::get('logo_path'))
                        <img src="{{ asset('storage/'.\App\Models\Setting::get('logo_path')) }}" alt="Logo" onerror="this.style.display='none'">
                    @endif
                    <h1>{{ config('app.name', 'Pelanggaran Kuy') }}</h1>
                    <p>{{ \App\Models\Setting::get('school_name') }}</p>
                </a>
            </div>

            <div class="guest-card">
                @if(session('status'))
                    <div class="alert-success">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
