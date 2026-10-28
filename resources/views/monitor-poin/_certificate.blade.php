@php
    $zoneName = $zone['name'] ?? 'Normal';
    $isIstimewa = $zoneName === 'Istimewa';
    $isLuarBiasa = $zoneName === 'Luar Biasa';
    $isNormal = $zoneName === 'Normal';
    $isPrestasi = $isIstimewa || $isLuarBiasa;
    $isPembinaan = in_array($zoneName, ['Pembinaan 1', 'Pembinaan 2', 'Pembinaan 3', 'Pembinaan 4', 'Intervensi Khusus'], true);

    $titleDoc = match (true) {
        $isIstimewa => 'SERTIFIKAT PENGHARGAAN',
        $isLuarBiasa => 'SERTIFIKAT PENGHARGAAN',
        $isNormal => 'SURAT KETERANGAN KEDISIPLINAN',
        default => 'LEMBAR PEMBINAAN KESISWAAN',
    };

    $subTitleDoc = match (true) {
        $isIstimewa => 'Anugerah Genep Waluya Utama',
        $isLuarBiasa => 'Anugerah Genep Waluya',
        $isNormal => 'Predikat Disiplin & Perilaku Baik',
        $zoneName === 'Intervensi Khusus' => 'Lembar Tindak Lanjut Intervensi Khusus',
        default => 'Lembar Pemantauan & Pendampingan Karakter',
    };

    $badgeStyle = match (true) {
        $isIstimewa => 'background:#1e3a5f;color:#ffffff;border:1px solid #1e3a5f;',
        $isLuarBiasa => 'background:#059669;color:#ffffff;border:1px solid #059669;',
        $isNormal => 'background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;',
        $zoneName === 'Intervensi Khusus' => 'background:#fee2e2;color:#991b1b;border:1px solid #fecaca;',
        default => 'background:#fef3c7;color:#92400e;border:1px solid #fde68a;',
    };

    $schoolName = \App\Models\Setting::get('school_name', 'SMK Baktinusantara 666');
    $kopPemerintah = \App\Models\Setting::get('kop_pemerintah', 'PEMERINTAH DAERAH PROVINSI JAWA BARAT');
    $kopDinas = \App\Models\Setting::get('kop_dinas', 'DINAS PENDIDIKAN');
    $schoolAddress = \App\Models\Setting::get('school_address');
    $schoolPhone = \App\Models\Setting::get('school_phone');
    $certificateFooter = \App\Models\Setting::get('certificate_footer', 'Sertifikat ini diterbitkan secara elektronik oleh Sistem Informasi Pelanggaran & Prestasi Siswa.');

    $signerKepalaLabel = \App\Models\Setting::get('signer_kepala_label', 'Kepala Sekolah');
    $signerKepalaNama = \App\Models\Setting::get('signer_kepala_nama', '');
    $signerKepalaNip = \App\Models\Setting::get('signer_kepala_nip', '');
    $ttdKepalaPath = \App\Models\Setting::get('ttd_kepala_path');

    $signerKesiswaanLabel = \App\Models\Setting::get('signer_kesiswaan_label', 'Wakasek Kesiswaan');
    $signerKesiswaanNama = \App\Models\Setting::get('signer_kesiswaan_nama', '');
    $signerKesiswaanNip = \App\Models\Setting::get('signer_kesiswaan_nip', '');
    $ttdKesiswaanPath = \App\Models\Setting::get('ttd_kesiswaan_path');

    $logoPath = \App\Models\Setting::get('logo_path');
    $logoSrc = $logoPath ? asset('storage/'.$logoPath) : asset('image.ico');
@endphp

<div class="cert-sheet">
    <div class="cert-outer-frame">
        <div class="cert-inner-frame">
            {{-- Kop Surat Resmi --}}
            <div class="cert-kop">
                <img src="{{ $logoSrc }}" alt="Logo Instansi" class="cert-logo" onerror="this.style.display='none'">
                <div class="cert-kop-center">
                    <div class="cert-gov-title">{{ $kopPemerintah }}</div>
                    <div class="cert-dept-title">{{ $kopDinas }}</div>
                    <div class="cert-school-title">{{ $schoolName }}</div>
                    <div class="cert-school-meta">
                        @if($schoolAddress) {{ $schoolAddress }} @endif
                        @if($schoolPhone) &middot; Telp: {{ $schoolPhone }} @endif
                    </div>
                </div>
            </div>

            {{-- Judul Dokumen & Subtitle --}}
            <div class="cert-heading">
                <h2 class="cert-title-text">{{ $titleDoc }}</h2>
                <div class="cert-subtitle-text">{{ $subTitleDoc }}</div>
            </div>

            {{-- Badge Zona --}}
            <div class="cert-badge-wrap">
                <span class="cert-badge" style="{{ $badgeStyle }}">
                    ZONA: {{ strtoupper($zoneName) }} &middot; {{ number_format($student->current_balance) }} POIN
                </span>
            </div>

            {{-- Kalimat Narasi Pengantar --}}
            <div class="cert-narrative">
                @if($isPrestasi)
                    Diberikan sebagai bentuk apresiasi dan penghargaan istimewa atas kedisiplinan prima, keluhuran budi pekerti, serta capaian prestasi yang membanggakan pada Tahun Pelajaran <strong>{{ $student->academic_year_name }}</strong>, kepada:
                @elseif($isNormal)
                    Menerangkan dengan sebenarnya bahwa siswa di bawah ini senantiasa mematuhi tata tertib satuan pendidikan dan memelihara stabilitas kepribadian berkarakter baik pada Tahun Pelajaran <strong>{{ $student->academic_year_name }}</strong>:
                @else
                    Lembar rekapitulasi evaluasi kedisiplinan dan pemantauan tata tertib siswa pada Tahun Pelajaran <strong>{{ $student->academic_year_name }}</strong> untuk tindak lanjut pembinaan karakter terarah, kepada:
                @endif
            </div>

            {{-- Tabel Data Siswa --}}
            <table class="cert-data-table">
                <tbody>
                    <tr>
                        <td class="dt-label">Nama Siswa</td>
                        <td class="dt-sep">:</td>
                        <td class="dt-value"><strong>{{ strtoupper($student->full_name) }}</strong></td>
                        <td class="dt-label">Kelas / Rombel</td>
                        <td class="dt-sep">:</td>
                        <td class="dt-value">{{ $student->class_name }}</td>
                    </tr>
                    <tr>
                        <td class="dt-label">Nomor Induk (NISN)</td>
                        <td class="dt-sep">:</td>
                        <td class="dt-value font-mono">{{ $student->nisn }}</td>
                        <td class="dt-label">Nomor Induk Siswa (NIS)</td>
                        <td class="dt-sep">:</td>
                        <td class="dt-value font-mono">{{ $student->nis ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="dt-label">Akumulasi Saldo Poin</td>
                        <td class="dt-sep">:</td>
                        <td class="dt-value">
                            <strong>{{ number_format($student->current_balance) }} Poin</strong>
                            <span class="cert-subtext">(Saldo pembuka: 2.000)</span>
                        </td>
                        <td class="dt-label">Kategori / Status</td>
                        <td class="dt-sep">:</td>
                        <td class="dt-value">
                            <strong>{{ $zoneName }}</strong>
                            @if(isset($zone['action']) && $zone['action'])
                                <span class="cert-subtext">({{ $zone['action'] }})</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Blok Tanda Tangan / Pengesahan 2 Kolom --}}
            <div class="cert-sign-grid">
                {{-- Kolom Kiri: Kepala Sekolah --}}
                <div class="cert-sign-col">
                    <div class="cert-sign-meta">Mengetahui,</div>
                    <div class="cert-sign-role">{{ $signerKepalaLabel }}</div>
                    <div class="cert-ttd-slot">
                        @if($ttdKepalaPath)
                            <img src="{{ asset('storage/'.$ttdKepalaPath) }}" alt="TTD {{ $signerKepalaLabel }}" class="cert-ttd-img">
                        @endif
                    </div>
                    <div class="cert-sign-name">{{ $signerKepalaNama ?: '____________________________' }}</div>
                    @if($signerKepalaNip)
                        <div class="cert-sign-nip">NIP. {{ $signerKepalaNip }}</div>
                    @endif
                </div>

                {{-- Kolom Kanan: Kesiswaan --}}
                <div class="cert-sign-col">
                    <div class="cert-sign-meta">{{ now()->translatedFormat('d F Y') }}</div>
                    <div class="cert-sign-role">{{ $signerKesiswaanLabel }}</div>
                    <div class="cert-ttd-slot">
                        @if($ttdKesiswaanPath)
                            <img src="{{ asset('storage/'.$ttdKesiswaanPath) }}" alt="TTD {{ $signerKesiswaanLabel }}" class="cert-ttd-img">
                        @endif
                    </div>
                    <div class="cert-sign-name">{{ $signerKesiswaanNama ?: '____________________________' }}</div>
                    @if($signerKesiswaanNip)
                        <div class="cert-sign-nip">NIP. {{ $signerKesiswaanNip }}</div>
                    @endif
                </div>
            </div>

            {{-- Catatan Kaki Legalitas --}}
            <div class="cert-footer-line">
                {{ $certificateFooter }}
            </div>
        </div>
    </div>
</div>
