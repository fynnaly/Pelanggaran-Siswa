<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="overflow-x:hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Pelanggaran Kuy') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="{{ asset('image.ico') }}">

    <script>
        (function() {
            const s = localStorage.getItem('theme');
            if (s === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.add('light');
            }
        })();
    </script>

    <style>
        /* ── Design System Tokens ── */
        :root {
            --primary: #1e3a5f; --secondary: #64748b; --tertiary: #059669; --accent: #f59e0b;
            --neutral: #ffffff; --surface: #f8fafc; --border: #e2e8f0; --danger: #dc2626;
            --on-primary: #fff; --on-tertiary: #fff; --on-surface: #1e293b; --on-surface-muted: #64748b;
            --glass-bg: rgba(255,255,255,.72); --glass-border: rgba(255,255,255,.55);
            --glass-shadow: rgba(15,23,42,.06);
            --bs: #dcfce7; --bt: #166534; --ws: #fef3c7; --wt: #92400e; --ds: #fee2e2; --dt: #991b1b;
            --sh-sm: 0 1px 2px rgba(15,23,42,.06); --sh-md: 0 4px 12px rgba(15,23,42,.08);
            --sh-g: 0 4px 16px rgba(15,23,42,.06);
            --r-sm: 8px; --r-md: 12px; --r-lg: 20px; --r-full: 9999px;
            --sp-xs: 4px; --sp-sm: 8px; --sp-md: 16px; --sp-lg: 24px; --sp-xl: 40px;
        }
        .dark {
            --neutral: #1c1c1f; --surface: #111114; --border: #252830;
            --on-surface: #e8eaed; --on-surface-muted: #8b919a; --primary: #5b8abf;
            --glass-bg: rgba(30,32,40,.78); --glass-border: rgba(255,255,255,.08);
            --glass-shadow: rgba(0,0,0,.4); --sh-g: 0 4px 16px rgba(0,0,0,.4);
            --bs: #064e3b; --bt: #6ee7b7; --ws: #78350f; --wt: #fcd34d;
            --ds: #7f1d1d; --dt: #fca5a5;
        }

        /* ── Base ── */
        [x-cloak] { display: none !important; }
        body { font-family: 'Figtree', sans-serif; font-size: 1rem; line-height: 1.6;
               color: var(--on-surface); background: var(--surface); transition: background .2s, color .2s;
               overflow-x: hidden; }
        h1 { font-size: 2.25rem; font-weight: 700; line-height: 1.15; letter-spacing: -.01em; }
        h2 { font-size: 1.5rem; font-weight: 600; line-height: 1.25; }
        h3 { font-size: 1.125rem; font-weight: 600; line-height: 1.35; }
        a { color: var(--tertiary); text-decoration: none; transition: color .15s; }

        /* ── Utilities ── */
        .label { font-size: .75rem; font-weight: 600; letter-spacing: .06em; line-height: 1.4; text-transform: uppercase; color: var(--on-surface-muted); }
        .text-sm { font-size: .875rem; line-height: 1.5; }
        .text-muted { color: var(--on-surface-muted); }
        .mono { font-family: ui-monospace, monospace; font-size: .8125rem; line-height: 1.5; }

        /* ── Layout ── */
        .main-wrap { max-width: 1280px; margin: 0 auto; padding: var(--sp-xl) var(--sp-md); overflow-x: hidden; }
        .page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:var(--sp-md); margin-bottom: var(--sp-xl); width:100%; }
        .page-header h1 { margin-bottom: var(--sp-xs); }
        .page-header > div:first-child{flex:1; min-width:200px;}

        /* ── Mobile ── */
        @media(max-width:639px){
            .card{padding:var(--sp-md)!important}
            .card-glass{padding:var(--sp-sm)!important}
            .stat-value{font-size:1.5rem}
            h1{font-size:1.5rem}
            h2{font-size:1.125rem}
            .page-header h1{margin-bottom:0}
            .layout-2col{grid-template-columns:1fr!important}
            .layout-2col > *{min-width:0}
            .grid-4{grid-template-columns:repeat(2,1fr)!important}
            .table-wrap{margin:0 calc(-1*var(--sp-sm));padding:0 var(--sp-xs);border-radius:0;overflow-x:auto;-webkit-overflow-scrolling:touch}
            table{font-size:.8125rem;min-width:600px}
            th,td{padding:8px 10px;font-size:.75rem}
            .main-wrap{padding:var(--sp-md) var(--sp-sm)!important;overflow-x:hidden}
            .input{width:100%!important;max-width:none!important}
            .toolbar{flex-direction:column;align-items:stretch}
            .toolbar form{width:100%;display:flex;flex-direction:column;gap:var(--sp-sm)}
            .toolbar .btn{width:100%;justify-content:center}
            .filter-group{justify-content:flex-start}
            .page-header{flex-direction:column;align-items:stretch}
            .page-header .btn{width:100%;justify-content:center}
            .page-header > div:last-child{width:100%;display:flex;flex-wrap:wrap;gap:var(--sp-sm)}
            .page-header > div:last-child .btn{flex:1;min-width:120px}
            .grid-4,.grid-2{grid-template-columns:1fr!important}
            .grid-4 > div,.grid-2 > div{width:100%!important;margin:0 auto}
            .list-item{flex-wrap:wrap}
        }
        .main-wrap > .card { margin-left: auto; margin-right: auto; }
        .breadcrumb { font-size: .875rem; color: var(--on-surface-muted); margin-bottom: var(--sp-sm); }
        .section { margin-top: var(--sp-xl); }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--sp-lg); flex-wrap: wrap; gap: var(--sp-sm); }
        .grid { display: grid; gap: var(--sp-lg); }
        .grid-4 { grid-template-columns: 1fr; }
        .grid-2 { grid-template-columns: 1fr; }
        @media(min-width:768px) { .grid-4 { grid-template-columns: repeat(2,1fr); } .grid-2 { grid-template-columns: 1fr 1fr; } }
        @media(min-width:1280px) { .grid-4 { grid-template-columns: repeat(4,1fr); } }
        .layout-2col { display: grid; gap: var(--sp-lg); grid-template-columns: 1fr; }
        @media(min-width:1024px) { .layout-2col { grid-template-columns: 2fr 1fr; } }

        /* ── Sidebar offset ── */
        .app-shell { min-height: 100vh; overflow-x: hidden; }
        @media(min-width:1024px) { .app-shell { margin-left: 256px; } }
        .app-header { background: var(--neutral); border-bottom: 1px solid var(--border); padding: var(--sp-md) var(--sp-lg); }
        .app-header > div { display: flex; flex-direction: column; }

        /* ── Cards ── */
        .card { background: var(--neutral); border-radius: var(--r-lg); padding: var(--sp-lg); border: 1px solid var(--border); transition: box-shadow .2s; }
        .card-glass { background: var(--glass-bg); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
                       border: 1px solid var(--glass-border); border-radius: var(--r-lg); padding: var(--sp-md); box-shadow: var(--sh-g); }

        /* ── Stats ── */
        .stat-value { font-size: 2rem; font-weight: 700; line-height: 1.15; letter-spacing: -.01em; margin: var(--sp-sm) 0 var(--sp-xs); }
        .stat-label { font-size: .75rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--on-surface-muted); }
        .stat-change { font-size: .875rem; margin-top: var(--sp-xs); }
        .stat-change.positive { color: var(--tertiary); }
        .stat-change.negative { color: var(--danger); }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; gap: var(--sp-sm); padding: 12px 20px; border-radius: var(--r-sm);
               border: none; font-family: inherit; font-size: .875rem; font-weight: 600; cursor: pointer; transition: background .15s; }
        .btn-primary { background: var(--tertiary); color: var(--on-tertiary); }
        .btn-primary svg, .btn-primary i { stroke: var(--on-tertiary); color: var(--on-tertiary); }
        .btn-secondary { background: var(--surface); color: var(--on-surface); border: 1px solid var(--border); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 8px 14px; font-size: .8125rem; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; border-radius: var(--r-lg); border: 1px solid var(--border); background: var(--neutral); -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th { text-align: left; padding: 12px 16px; font-size: .75rem; font-weight: 600; letter-spacing: .06em;
             text-transform: uppercase; color: var(--on-surface-muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 12px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        /* ── Toolbar ── */
        .toolbar { display: flex; flex-wrap: wrap; gap: var(--sp-sm); align-items: center; margin-bottom: var(--sp-lg); }
        .filter-group { display: flex; gap: var(--sp-sm); flex-wrap: wrap; }

        /* ── Input ── */
        .input { width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: var(--r-sm); border: 1px solid var(--border);
                 background: var(--neutral); color: var(--on-surface); font-family: inherit; font-size: .875rem;
                 transition: border-color .15s, box-shadow .15s; }
        .input:focus { outline: none; border-color: var(--tertiary); box-shadow: 0 0 0 3px rgba(5,150,105,.15); }
        .input::placeholder { color: var(--on-surface-muted); }
        select.input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
                       background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; cursor: pointer; }
        .field { margin-bottom: var(--sp-lg); }
        .field-label { display: block; font-size: .75rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; margin-bottom: var(--sp-xs); color: var(--on-surface); }
        .field-input { width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: var(--r-sm); border: 1px solid var(--border);
                       background: var(--neutral); color: var(--on-surface); font-family: inherit; font-size: .875rem;
                       transition: border-color .15s, box-shadow .15s; }
        .field-input:focus { outline: none; border-color: var(--tertiary); box-shadow: 0 0 0 3px rgba(5,150,105,.15); }
        .field-input::placeholder { color: var(--on-surface-muted); }
        select.field-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
                             background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; cursor: pointer; }
        textarea.field-input { min-height: 120px; resize: vertical; line-height: 1.5; }
        .help-text { font-size: .8125rem; color: var(--on-surface-muted); margin-top: var(--sp-xs); display: block; }
        .text-danger { color: var(--danger); }

        /* ── Badge ── */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: var(--r-full); font-size: .75rem; font-weight: 600; letter-spacing: .04em; white-space: nowrap; }
        .badge-success { background: var(--bs); color: var(--bt); }
        .badge-warning { background: var(--ws); color: var(--wt); }
        .badge-danger { background: var(--ds); color: var(--dt); }

        /* ── List (dashboard) ── */
        .list-item { display: flex; align-items: center; gap: var(--sp-md); padding: var(--sp-md) 0; }
        .list-item + .list-item { border-top: 1px solid var(--border); }
        .list-avatar { width: 40px; height: 40px; border-radius: var(--r-full); flex-shrink: 0; background: var(--primary); color: var(--on-primary);
                       display: flex; align-items: center; justify-content: center; font-size: .875rem; font-weight: 700; }
        .list-info { flex: 1; min-width: 0; }
        .list-name { font-weight: 600; font-size: .875rem; }
        .list-meta { font-size: .8125rem; color: var(--on-surface-muted); }
        .list-score { font-family: ui-monospace, monospace; font-size: .875rem; font-weight: 700; white-space: nowrap; }
        .list-score.danger { color: var(--danger); }
        .list-score.warning { color: var(--accent); }

        /* ── Pagination ── */
        .pagination { display: flex; align-items: center; gap: var(--sp-xs); margin-top: var(--sp-lg); justify-content: center; flex-wrap: wrap; }
        .page-btn { min-width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
                    border-radius: var(--r-sm); border: 1px solid var(--border); background: var(--neutral);
                    color: var(--on-surface); font-size: .875rem; cursor: pointer; transition: background .15s; }
        .page-btn.active { background: var(--primary); color: var(--on-primary); border-color: var(--primary); }

        /* ── Skeleton ── */
        .skeleton { background: #e2e8f0; border-radius: var(--r-sm); animation: pulse 1.5s ease-in-out infinite; }
        .dark .skeleton { background: #1e293b; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .5; } }

        /* ── Action buttons in table ── */
        .action-cell { display: flex; gap: var(--sp-xs); }
        .action-btn { width: 32px; height: 32px; border-radius: var(--r-sm); border: 1px solid var(--border);
                      background: var(--neutral); color: var(--on-surface-muted); cursor: pointer;
                      display: flex; align-items: center; justify-content: center; font-size: .875rem; transition: all .15s; }

        /* ── Checkbox ── */
        .checkbox { width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--border); cursor: pointer; accent-color: var(--tertiary); }
        .checkbox-input { width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--border); cursor: pointer; accent-color: var(--tertiary); }
        .checkbox-row { display: flex; align-items: center; gap: var(--sp-sm); }

        /* ── Flash messages ── */
        .alert { display:flex; align-items:center; gap:8px; border-radius: var(--r-sm); padding: var(--sp-md); font-size: .875rem; margin-bottom: var(--sp-md); }
        .alert-success { background: var(--bs); border: 1px solid var(--bt); color: var(--bt); }
        .alert-warning { background: var(--ws); border: 1px solid var(--wt); color: var(--wt); }
        .alert-error { background: var(--ds); border: 1px solid var(--dt); color: var(--dt); }

        /* ── Icon (Lucide SVG inline, stroke-only) ── */
        .icon{width:18px;height:18px;stroke:currentColor;stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;flex-shrink:0}
        .icon-sm{width:14px;height:14px;stroke:currentColor;stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;flex-shrink:0}
        .icon-lg{width:22px;height:22px;stroke:currentColor;stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round;vertical-align:middle;flex-shrink:0}
        .card-form{max-width:640px;margin:0 auto}

        /* ── Recovery recommendation tabs ── */
        .recovery-tabs{display:flex;gap:var(--sp-sm);flex-wrap:wrap;margin-bottom:var(--sp-md)}
        .recovery-tab{display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--r-sm);
                       border:1px solid var(--border);cursor:pointer;font-size:.875rem;font-weight:600;
                       transition:all .15s;background:var(--neutral);color:var(--on-surface)}
        .recovery-tab.active-ringan{background:var(--bs);color:var(--bt);border-color:var(--bt)}
        .recovery-tab.active-sedang{background:var(--ws);color:var(--wt);border-color:var(--wt)}
        .recovery-tab.active-besar{background:var(--ds);color:var(--dt);border-color:var(--dt)}
        .recovery-cards{display:grid;gap:var(--sp-sm)}
        .recovery-card{display:flex;align-items:center;gap:var(--sp-md);padding:var(--sp-md);
                        background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm)}
        .recovery-card-icon{width:40px;height:40px;border-radius:var(--r-sm);background:var(--bs);color:var(--bt);
                             display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .recovery-card-body{flex:1;min-width:0}
        .recovery-card-title{font-weight:600;font-size:.875rem}
        .recovery-empty{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);
                         padding:var(--sp-md);text-align:center}

        /* ── Hover: desktop only ── */
        @media(min-width:768px){
            a:hover{color:#047857}
            .card:hover{box-shadow:var(--sh-md)}
            .btn-primary:hover{background:#047857;color:var(--on-tertiary)}
            .btn-primary:hover svg,.btn-primary:hover i{stroke:var(--on-tertiary)}
            .btn-secondary:hover{background:var(--border)}
            .btn-danger:hover{background:#b91c1c}
            tr:hover td{background:var(--surface)}
            .page-btn:hover:not(.active){background:var(--surface)}
            .action-btn:hover{background:var(--surface);color:var(--on-surface);border-color:var(--on-surface-muted)}
            .recovery-tab:hover{background:var(--surface)}
        }
    </style>
</head>
<body class="antialiased" style="font-family:'Figtree',sans-serif;background:var(--surface);color:var(--on-surface);overflow-x:hidden">
    <div class="min-h-screen">
        @include('layouts.navigation')

        <div class="app-shell">
            @isset($header)
                <div class="app-header">
                    <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:var(--sp-md);">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </div>

    <script src="{{ asset('vendor/lucide/lucide.min.js') }}" onload="lucide.createIcons()"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" crossorigin="anonymous"></script>
    <script>
        function toggleTheme() {
            const h = document.documentElement;
            const d = h.classList.toggle('dark');
            h.classList.toggle('light', !d);
            localStorage.setItem('theme', d ? 'dark' : 'light');
            document.querySelectorAll('.icon-theme').forEach(el => {
                el.setAttribute('data-lucide', d ? 'moon' : 'sun');
            });
            lucide.createIcons();
        }
    </script>
</body>
</html>
