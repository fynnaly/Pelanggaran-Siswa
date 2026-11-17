<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div><h1>Tahun Ajaran</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('tahun-ajaran.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="tahun-ajaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="name" label="Tahun Ajaran" :sort="$sort" :order="$order" route="tahun-ajaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="start_date" label="Tanggal Mulai" :sort="$sort" :order="$order" route="tahun-ajaran.index" :params="request()->except('sort','order','page')" /></th><th>Tanggal Selesai</th><th><x-sortable-header column="is_active" label="Status" :sort="$sort" :order="$order" route="tahun-ajaran.index" :params="request()->except('sort','order','page')" /></th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($academicYears as $year)
                        <tr>
                            <td class="text-sm text-muted">{{ $year->id }}</td>
                            <td><strong>{{ $year->name }}</strong></td>
                            <td class="text-sm text-muted">{{ $year->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-sm text-muted">{{ $year->effectiveEndDate()?->format('d/m/Y') ?? '-' }}</td>
                            <td><span class="badge {{ $year->is_active?'badge-success':'' }}" style="{{ !$year->is_active?'background:var(--surface);color:var(--on-surface-muted)':'' }}">{{ $year->is_active?'Aktif':'Nonaktif' }}</span>@if($year->isExpired())<span class="text-sm text-muted" style="margin-left:6px">· Berlalu</span>@endif</td>
                            <td><div class="action-cell">
                                @if(!$year->is_active)
                                    <button type="button" class="action-btn" title="Aktifkan" style="color:var(--success)"
                                        data-activate="{{ $year->id }}" data-name="{{ $year->name }}"><i data-lucide="power" style="width:14px;height:14px"></i></button>
                                @endif
                                <a href="{{ route('tahun-ajaran.edit', $year) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('tahun-ajaran.destroy', $year) }}" method="POST" class="inline" onsubmit="return confirm('Hapus {{ $year->name }}?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($academicYears->hasPages())<div style="display:flex;justify-content:center;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $academicYears->links() }}</div></div>@endif
    </div>

    {{-- Modal aktivasi tahun ajaran: 2 tahap konfirmasi (peringatan ringan → peringatan final) --}}
    <div id="activateModalStep1" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;padding:16px">
        <div style="background:var(--card-bg,#fff);border-radius:12px;max-width:420px;width:100%;padding:24px">
            <h3 style="margin:0 0 8px;font-weight:700;display:flex;align-items:center;gap:8px"><i data-lucide="alert-triangle" class="icon-sm" style="color:#d97706"></i> Aktifkan Tahun Ajaran?</h3>
            <p style="margin:0 0 4px" class="text-sm text-muted">Kamu akan mengaktifkan <strong id="activateName1">-</strong>.</p>
            <p style="margin:0 0 16px" class="text-sm text-muted">Tahun ajaran lain yang aktif akan otomatis dinonaktifkan.</p>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeActivateModals()">Batal</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="goToStep2()">Lanjutkan</button>
            </div>
        </div>
    </div>
    <div id="activateModalStep2" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:201;align-items:center;justify-content:center;padding:16px">
        <div style="background:var(--card-bg,#fff);border-radius:12px;max-width:440px;width:100%;padding:24px">
            <h3 style="margin:0 0 8px;font-weight:700;display:flex;align-items:center;gap:8px"><i data-lucide="alert-octagon" class="icon-sm" style="color:var(--danger)"></i> Konfirmasi Terakhir</h3>
            <p style="margin:0 0 4px" class="text-sm text-muted">Tindakan ini <strong>tidak bisa dibatalkan</strong>. Mengaktifkan <strong id="activateName2">-</strong> akan:</p>
            <ul class="text-sm text-muted" style="margin:8px 0 16px;padding-left:20px">
                <li>Membuat kelas-kelas baru sesuai struktur tahun ajaran sebelumnya</li>
                <li>Menaikkan seluruh siswa aktif ke tingkat berikutnya</li>
                <li>Meluluskan siswa kelas IX (tahun sebelumnya)</li>
            </ul>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeActivateModals()">Kembali</button>
                <form method="POST" action="" id="activateForm" class="inline">@csrf @method('PATCH')<button type="submit" class="btn btn-primary btn-sm" style="background:var(--danger)">Ya, Aktifkan Sekarang</button></form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            let pendingActivateId = null;
            document.querySelectorAll('[data-activate]').forEach(btn => {
                btn.addEventListener('click', () => {
                    pendingActivateId = btn.dataset.activate;
                    document.getElementById('activateName1').textContent = btn.dataset.name;
                    document.getElementById('activateName2').textContent = btn.dataset.name;
                    const m = document.getElementById('activateModalStep1');
                    m.style.display = 'flex';
                });
            });
            window.closeActivateModals = () => {
                document.getElementById('activateModalStep1').style.display = 'none';
                document.getElementById('activateModalStep2').style.display = 'none';
            };
            window.goToStep2 = () => {
                document.getElementById('activateModalStep1').style.display = 'none';
                const form = document.getElementById('activateForm');
                form.action = @js(url('/academic-years')) + '/' + pendingActivateId + '/activate';
                document.getElementById('activateModalStep2').style.display = 'flex';
            };
        });
    </script>
</x-app-layout>
