<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 style="display:flex;align-items:center;gap:8px">
                    <i data-lucide="edit-3" style="width:26px;height:26px;color:var(--primary)"></i>
                    Edit / Ganti Nametag NFC Siswa
                </h1>
                <p class="text-muted text-sm">Ubah kode unik chip nametag NFC yang tertaut pada siswa.</p>
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

        {{-- Kartu Info Siswa --}}
        <div class="card" style="background:var(--bs);border:1px solid rgba(0,0,0,0.06);border-radius:12px;padding:var(--sp-md);margin-bottom:var(--sp-md);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:var(--sp-sm)">
            <div>
                <div class="text-xs text-muted" style="text-transform:uppercase;font-weight:700;letter-spacing:0.05em">Siswa Tertaut</div>
                <div style="font-size:1.15rem;font-weight:700;color:var(--primary);margin-top:2px">
                    <a href="{{ route('students.show', $student) }}" target="_blank" class="hover:underline" style="display:inline-flex;align-items:center;gap:5px" title="Buka detail siswa di tab baru">
                        {{ $student->full_name }}
                        <i data-lucide="external-link" style="width:14px;height:14px"></i>
                    </a>
                </div>
                <div class="text-sm text-muted mono" style="margin-top:2px">
                    NIS: {{ $student->nis }} &bull; NISN: {{ $student->nisn }} &bull; Kelas: {{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}
                </div>
            </div>
            <div>
                <span class="badge {{ $student->nfc_uid ? 'badge-success' : 'badge-neutral' }}">
                    {{ $student->nfc_uid ? 'Sudah Terpasang' : 'Belum Ada Nametag' }}
                </span>
            </div>
        </div>

        {{-- Form Edit UID --}}
        <div class="card" style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:var(--sp-lg)">
            <form action="{{ route('nametag-nfc.update', $student) }}" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom:var(--sp-lg)">
                    <label for="nfc_uid" style="display:block;font-weight:600;margin-bottom:6px">
                        Kode Unik / UID Nametag NFC
                    </label>
                    <input type="text" name="nfc_uid" id="nfc_uid" 
                           value="{{ old('nfc_uid', $student->nfc_uid) }}" 
                           placeholder="Kosongkan jika ingin melepas nametag ini" 
                           class="input mono" 
                           style="width:100%;font-size:1.05rem;font-weight:700;letter-spacing:0.04em" 
                           autofocus autocomplete="off">
                    <small class="text-muted" style="display:block;margin-top:6px">
                        Masukkan UID baru untuk mengganti kartu nametag, atau kosongkan nilai input untuk melepas nametag dari siswa ini.
                    </small>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-sm);border-top:1px solid var(--border);padding-top:var(--sp-md)">
                    @if($student->nfc_uid)
                        <button type="button" 
                                onclick="if(confirm('Lepaskan nametag NFC dari siswa ini?')){ document.getElementById('deleteForm').submit(); }" 
                                class="btn btn-secondary btn-sm" 
                                style="color:var(--danger)">
                            <i data-lucide="trash-2" class="icon-sm"></i> Lepaskan Nametag
                        </button>
                    @else
                        <div></div>
                    @endif

                    <div style="display:flex;gap:var(--sp-sm)">
                        <a href="{{ route('nametag-nfc.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px">
                            <i data-lucide="check" class="icon-sm"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>

            @if($student->nfc_uid)
                <form id="deleteForm" action="{{ route('nametag-nfc.destroy', $student) }}" method="POST" style="display:none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
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
