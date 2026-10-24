{{-- Footer global: about aplikasi + kredit pengembang. Dipakai guest layout & app layout. --}}
<div class="site-footer">
    <div class="site-footer-inner">
        <div class="site-footer-about">
            <strong>Tentang {{ config('app.name', 'Pelanggaran Kuy') }}</strong>
            <p>Sistem informasi sekolah untuk pencatatan pelanggaran dan prestasi siswa,
               monitoring poin dengan zona pembinaan, penerbitan sertifikat, serta
               penyediaan data terstruktur melalui REST API.</p>
        </div>
        <div class="site-footer-dev">
            <strong>Pengembang</strong>
            <p>Dikembangkan oleh <a href="https://github.com/fynnaly" target="_blank" rel="noopener">fynnaly</a>
               &middot; Laravel {{ app()->version() }}</p>
            <p class="site-footer-copy">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('school_name') }}</p>
        </div>
    </div>
</div>

<style>
    .site-footer { border-top: 1px solid var(--border); background: var(--neutral);
                   padding: var(--sp-lg) var(--sp-md); margin-top: auto; }
    .site-footer-inner { max-width: 1280px; margin: 0 auto; display: grid; gap: var(--sp-md);
                         grid-template-columns: 1fr; }
    @media(min-width: 768px) { .site-footer-inner { grid-template-columns: 2fr 1fr; } }
    .site-footer strong { display: block; font-size: .75rem; font-weight: 600; letter-spacing: .06em;
                          text-transform: uppercase; color: var(--on-surface); margin-bottom: 6px; }
    .site-footer p { font-size: .8125rem; color: var(--on-surface-muted); margin: 0 0 4px; line-height: 1.6; }
    .site-footer a { color: var(--tertiary); }
    .site-footer-copy { margin-top: 8px !important; }
</style>
