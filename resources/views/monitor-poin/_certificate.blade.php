<div class="page">
    <div class="inner">
        <div class="kop">
            <img src="{{ \App\Models\Setting::get('logo_path') ? asset('storage/'.\App\Models\Setting::get('logo_path')) : asset('image.png') }}" alt="Logo" onerror="this.style.display='none'">
            <div class="kop-text">
                <h1>{{ \App\Models\Setting::get('kop_pemerintah') }}</h1>
                <h1 style="font-size:15px">{{ \App\Models\Setting::get('kop_dinas') }}</h1>
                <p>{{ \App\Models\Setting::get('school_name') }}@if(\App\Models\Setting::get('school_address')) &middot; {{ \App\Models\Setting::get('school_address') }}@endif @if(\App\Models\Setting::get('school_phone')) &middot; Telp. {{ \App\Models\Setting::get('school_phone') }}@endif</p>
            </div>
        </div>
        <div class="title">
            <h2>Sertifikat</h2>
            <p class="sub">Anugerah Genep Waluya {{ $zone['name']==='Istimewa' ? 'Utama' : '' }}</p>
        </div>
        <div class="award">
            <span class="badge {{ $zone['name']==='Luar Biasa' ? 'luar' : '' }}">{{ $zone['name'] }}</span>
        </div>
        <p class="body-text">
            Dengan bangga kami berikan penghargaan kepada siswa berprestasi yang telah mencapai
            zona pembinaan <strong>{{ $zone['name'] }}</strong> pada Program Kerja Kesiswaan
            Tahun Ajaran {{ $student->academic_year_name }}, dengan rincian sebagai berikut:
        </p>
        <table class="data">
            <tr><td>Nama Lengkap</td><td>{{ $student->full_name }}</td></tr>
            <tr><td>NISN</td><td>{{ $student->nisn }}</td></tr>
            <tr><td>NIS</td><td>{{ $student->nis }}</td></tr>
            <tr><td>Kelas</td><td>{{ $student->class_name }}</td></tr>
            <tr><td>Saldo Poin</td><td>{{ number_format($student->current_balance) }}</td></tr>
            <tr><td>Zona Pembinaan</td><td>{{ $zone['name'] }}</td></tr>
        </table>
        <div class="sign">
            <div class="col">
                <div>Mengetahui,</div>
                <div>{{ \App\Models\Setting::get('signer_kepala_label') }}</div>
                @if(\App\Models\Setting::get('ttd_kepala_path'))
                    <img src="{{ asset('storage/'.\App\Models\Setting::get('ttd_kepala_path')) }}" alt="TTD Kepala Sekolah" style="max-height:64px;margin:8px 0">
                @else
                    <div style="height:72px"></div>
                @endif
                <div class="line">{{ \App\Models\Setting::get('signer_kepala_nama') ?: '____________________' }}</div>
                @if(\App\Models\Setting::get('signer_kepala_nip'))<div style="font-size:11px;color:var(--on-surface-muted)">NIP. {{ \App\Models\Setting::get('signer_kepala_nip') }}</div>@endif
            </div>
            <div class="col">
                <div>Bantul, {{ now()->translatedFormat('d F Y') }}</div>
                <div>{{ \App\Models\Setting::get('signer_kesiswaan_label') }}</div>
                @if(\App\Models\Setting::get('ttd_kesiswaan_path'))
                    <img src="{{ asset('storage/'.\App\Models\Setting::get('ttd_kesiswaan_path')) }}" alt="TTD Kesiswaan" style="max-height:64px;margin:8px 0">
                @else
                    <div style="height:72px"></div>
                @endif
                <div class="line">{{ \App\Models\Setting::get('signer_kesiswaan_nama') ?: '____________________' }}</div>
                @if(\App\Models\Setting::get('signer_kesiswaan_nip'))<div style="font-size:11px;color:var(--on-surface-muted)">NIP. {{ \App\Models\Setting::get('signer_kesiswaan_nip') }}</div>@endif
            </div>
        </div>
        <p class="foot">{{ \App\Models\Setting::get('certificate_footer') }}</p>
    </div>
</div>
