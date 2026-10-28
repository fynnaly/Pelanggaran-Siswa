<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sertifikat {{ $student->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Figtree', sans-serif; color: #0f172a; background: #e2e8f0; -webkit-font-smoothing: antialiased; }
        .font-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }

        /* Sheet container */
        .cert-sheet { width: 297mm; min-height: 210mm; margin: 20px auto; background: #ffffff; box-shadow: 0 8px 30px rgba(15,23,42,0.12); box-sizing: border-box; }
        .cert-outer-frame { height: 100%; border: 3px double #1e3a5f; padding: 4mm; box-sizing: border-box; background: #ffffff; }
        .cert-inner-frame { min-height: calc(210mm - 8mm); border: 1px solid #cbd5e1; padding: 6mm 10mm; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; }

        /* Kop Surat */
        .cert-kop { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; margin-bottom: 6px; }
        .cert-logo { width: 54px; height: 54px; object-fit: contain; flex-shrink: 0; }
        .cert-kop-center { flex: 1; text-align: center; }
        .cert-gov-title { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; }
        .cert-dept-title { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; }
        .cert-school-title { font-size: 17px; font-weight: 800; letter-spacing: 0.03em; color: #1e3a5f; text-transform: uppercase; margin: 1px 0; }
        .cert-school-meta { font-size: 10px; color: #64748b; }

        /* Judul Dokumen */
        .cert-heading { text-align: center; margin: 4px 0 2px; }
        .cert-title-text { font-size: 19px; font-weight: 800; letter-spacing: 0.12em; color: #1e3a5f; text-transform: uppercase; margin: 0; }
        .cert-subtitle-text { font-size: 12px; font-weight: 600; color: #475569; margin-top: 1px; letter-spacing: 0.03em; }

        /* Badge Zona */
        .cert-badge-wrap { text-align: center; margin: 4px 0; }
        .cert-badge { display: inline-block; padding: 3px 14px; border-radius: 9999px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        /* Narasi */
        .cert-narrative { font-size: 11.5px; line-height: 1.5; text-align: center; max-width: 240mm; margin: 0 auto 6px; color: #334155; }

        /* Tabel Data */
        .cert-data-table { width: 100%; max-width: 250mm; margin: 0 auto 6px; border-collapse: collapse; font-size: 11px; }
        .cert-data-table td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; }
        .dt-label { width: 18%; color: #64748b; font-weight: 600; }
        .dt-sep { width: 2%; text-align: center; color: #94a3b8; }
        .dt-value { width: 30%; color: #0f172a; }
        .cert-subtext { font-size: 9.5px; color: #64748b; font-weight: normal; margin-left: 4px; }

        /* Blok Tanda Tangan */
        .cert-sign-grid { display: flex; justify-content: space-between; padding: 0 16mm; margin-top: auto; }
        .cert-sign-col { width: 38%; text-align: center; font-size: 11px; color: #1e293b; }
        .cert-sign-meta { font-size: 10.5px; color: #64748b; margin-bottom: 1px; }
        .cert-sign-role { font-weight: 700; font-size: 11px; }
        .cert-ttd-slot { height: 48px; display: flex; align-items: center; justify-content: center; }
        .cert-ttd-img { max-height: 44px; object-fit: contain; }
        .cert-sign-name { font-weight: 700; border-top: 1px solid #0f172a; padding-top: 3px; display: inline-block; min-width: 150px; font-size: 11px; }
        .cert-sign-nip { font-size: 9.5px; color: #64748b; margin-top: 1px; }

        /* Footer */
        .cert-footer-line { text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px dashed #e2e8f0; padding-top: 3px; margin-top: 4px; }

        /* Tombol Aksi Layar */
        .no-print { position: fixed; top: 14px; right: 14px; display: flex; gap: 8px; z-index: 100; }
        .btn-ui { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; font: 600 12px 'Figtree', sans-serif; cursor: pointer; text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .btn-ui-primary { background: #059669; color: #ffffff; border-color: #059669; }

        /* Print Media — Pasti 1 Lembar A4 Landscape */
        @media print {
            body { background: #ffffff !important; margin: 0 !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .cert-sheet { width: 297mm !important; height: 210mm !important; max-height: 210mm !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; page-break-after: always; break-after: page; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .cert-outer-frame { border: 3px double #1e3a5f !important; padding: 4mm !important; height: 100% !important; }
            .cert-inner-frame { border: 1px solid #cbd5e1 !important; height: calc(210mm - 8mm) !important; padding: 6mm 10mm !important; }
            @page { size: A4 landscape; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('monitor-poin.index') }}" class="btn-ui">Kembali</a>
        <button class="btn-ui btn-ui-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    @include('monitor-poin._certificate')
</body>
</html>
