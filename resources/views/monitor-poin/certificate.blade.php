<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sertifikat {{ $student->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e3a5f; --tertiary: #059669; --accent: #f59e0b;
            --on-surface: #1e293b; --on-surface-muted: #64748b; --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Figtree', sans-serif; color: var(--on-surface); background: #f1f5f9; }
        .page { width: 297mm; min-height: 210mm; margin: 0 auto; padding: 14mm; background: #fff; position: relative; overflow: hidden; }
        .page::before { content:''; position:absolute; inset:0; border:2px solid var(--primary); pointer-events:none; }
        .page::after { content:''; position:absolute; inset:6mm; border:1px solid var(--border); pointer-events:none; }
        .inner { position:relative; z-index:1; padding: 10mm 12mm; height:100%; display:flex; flex-direction:column; }
        .kop { display:flex; align-items:center; gap:12px; border-bottom:3px double var(--primary); padding-bottom:10px; margin-bottom:18px; }
        .kop img { width:52px; height:52px; object-fit:contain; }
        .kop-text h1 { font-size:18px; font-weight:800; color:var(--primary); letter-spacing:.02em; }
        .kop-text p { font-size:11px; color:var(--on-surface-muted); }
        .title { text-align:center; margin:14px 0 4px; }
        .title h2 { font-size:22px; font-weight:800; color:var(--primary); letter-spacing:.14em; text-transform:uppercase; }
        .title .sub { font-size:12px; color:var(--on-surface-muted); margin-top:2px; }
        .award { text-align:center; margin:16px 0 20px; }
        .award .badge { display:inline-block; padding:8px 22px; border-radius:999px; font-weight:800; font-size:14px; color:#fff; background:var(--primary); letter-spacing:.04em; }
        .award .badge.luar { background:var(--tertiary); }
        .award .badge { background:var(--primary); }
        .body-text { font-size:13px; line-height:1.7; text-align:center; max-width:220mm; margin:0 auto 18px; }
        .data { width:100%; max-width:200mm; margin:0 auto 22px; border-collapse:collapse; font-size:13px; }
        .data td { padding:7px 10px; border-bottom:1px solid var(--border); }
        .data td:first-child { width:38%; color:var(--on-surface-muted); font-weight:600; }
        .data td:last-child { font-weight:700; }
        .sign { display:flex; justify-content:space-between; margin-top:auto; padding:0 6mm; font-size:12px; text-align:center; }
        .sign .col { width:32%; }
        .sign .line { margin-top:56px; border-top:1px solid var(--on-surface); padding-top:4px; font-weight:600; }
        .foot { text-align:center; font-size:10px; color:var(--on-surface-muted); margin-top:14px; }
        .no-print { position:fixed; top:12px; right:12px; display:flex; gap:8px; z-index:10; }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:8px; border:1px solid var(--border); background:#fff; color:var(--on-surface); font:600 13px 'Figtree',sans-serif; cursor:pointer; text-decoration:none; }
        .btn-primary { background:var(--tertiary); color:#fff; border-color:var(--tertiary); }
        @media print {
            body { background:#fff; }
            .no-print { display:none !important; }
            .page { width:auto; min-height:auto; margin:0; padding:0; }
            .page::before, .page::after { display:none; }
            .inner { padding:12mm 14mm; }
            @page { size:A4 landscape; margin:0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('monitor-poin.index') }}" class="btn"><i data-lucide="arrow-left" style="width:14px;height:14px"></i> Kembali</a>
        <button class="btn btn-primary" onclick="window.print()"><i data-lucide="printer" style="width:14px;height:14px"></i> Cetak</button>
    </div>
    @include('monitor-poin._certificate')
    <script src="{{ asset('vendor/lucide/lucide.min.js') }}" onload="lucide.createIcons()"></script>
</body>
</html>
