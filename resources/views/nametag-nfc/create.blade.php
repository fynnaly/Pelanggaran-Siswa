<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 style="display:flex;align-items:center;gap:8px">
                    <i data-lucide="plus-circle" style="width:26px;height:26px;color:var(--primary)"></i>
                    Pasangkan Nametag NFC Baru
                </h1>
                <p class="text-muted text-sm">Pilih siswa dan daftarkan kode unik (UID) chip nametag NFC fisiknya.</p>
            </div>
            <div>
                <a href="{{ route('nametag-nfc.index') }}" class="btn btn-secondary btn-sm">
                    <i data-lucide="arrow-left" class="icon-sm"></i> Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap" style="max-width:680px;margin:0 auto">
        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom:var(--sp-md)">
                <i data-lucide="alert-circle" class="icon-sm"></i>
                <div>
                    <strong style="display:block;margin-bottom:4px">Terjadi kesalahan input:</strong>
                    <ul style="margin:0;padding-left:18px">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card" style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:var(--sp-lg)">
            <form action="{{ route('nametag-nfc.store') }}" method="POST">
                @csrf

                <div style="margin-bottom:var(--sp-md)">
                    <label for="student_id" style="display:block;font-weight:600;margin-bottom:6px">
                        Pilih Siswa <span style="color:var(--danger)">*</span>
                    </label>
                    <select name="student_id" id="student_id" class="input" style="width:100%" required>
                        <option value="">-- Pilih Siswa yang Belum Memiliki Nametag --</option>
                        @foreach($studentsWithoutNfc as $s)
                            <option value="{{ $s->id }}" {{ old('student_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->full_name }} &bull; NIS: {{ $s->nis }} &bull; Kelas: {{ $s->currentEnrollment?->schoolClass?->name ?? 'Tanpa Kelas' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="display:block;margin-top:4px">
                        Daftar hanya menampilkan siswa aktif yang belum dipasangkan dengan nametag mana pun.
                    </small>
                </div>

                <div style="margin-bottom:var(--sp-lg)">
                    <label for="nfc_uid" style="display:block;font-weight:600;margin-bottom:6px">
                        Kode Unik / UID Nametag NFC <span style="color:var(--danger)">*</span>
                    </label>
                    <div style="position:relative">
                        <input type="text" name="nfc_uid" id="nfc_uid" 
                               value="{{ old('nfc_uid') }}" 
                               placeholder="Contoh: 04A23B891F6080" 
                               class="input mono" 
                               style="width:100%;font-size:1.05rem;font-weight:700;letter-spacing:0.04em" 
                               required autofocus autocomplete="off">
                    </div>
                    <small class="text-muted" style="display:block;margin-top:6px">
                        Tempelkan kartu nametag ke reader USB / ketikkan kode hex UID unik yang tercetak pada kartu NFC siswa.
                    </small>
                </div>

                <div style="display:flex;align-items:center;justify-content:flex-end;gap:var(--sp-sm);border-top:1px solid var(--border);padding-top:var(--sp-md)">
                    <a href="{{ route('nametag-nfc.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px">
                        <i data-lucide="check" class="icon-sm"></i> Simpan Pemasangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</x-app-layout>
