<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Log Aktivitas</h1>
                <p class="text-muted text-sm">Jejak perubahan data dan hasil import yang dilakukan pengelola.</p>
            </div>
            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="users" class="icon-sm"></i> Pengguna</a>
        </div>
    </x-slot>

    <div class="main-wrap">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--sp-md);margin-bottom:var(--sp-lg)">
            <div class="card">
                <p class="text-sm text-muted">Aktivitas hari ini</p>
                <p style="font-size:1.75rem;font-weight:700;line-height:1.2">{{ $todayCount }}</p>
            </div>
            <div class="card">
                <p class="text-sm text-muted">Total tercatat</p>
                <p style="font-size:1.75rem;font-weight:700;line-height:1.2">{{ $logs->total() }}</p>
            </div>
            <div class="card">
                <p class="text-sm text-muted">Import dengan masalah</p>
                <p style="font-size:1.75rem;font-weight:700;line-height:1.2;color:var(--danger)">{{ $importIssues }}</p>
            </div>
        </div>

        <form action="{{ route('logs.index') }}" method="GET" class="toolbar">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari ringkasan atau nama pengguna..." class="input" style="flex:2 1 220px;min-width:170px">
            <select name="action" class="input" style="width:auto;min-width:170px">
                <option value="">Semua aksi</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ $action === $a ? 'selected' : '' }}>{{ \App\Models\ActivityLog::labelFor($a) }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $from }}" class="input" style="width:auto" title="Dari tanggal">
            <input type="date" name="to" value="{{ $to }}" class="input" style="width:auto" title="Sampai tanggal">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search" class="icon-sm"></i> Filter</button>
            @if($q !== '' || $action || $from || $to)
                <a href="{{ route('logs.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:170px">Waktu</th>
                        <th style="width:170px">Aksi</th>
                        <th>Ringkasan</th>
                        <th style="width:170px">Pelaku</th>
                        <th style="width:80px">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-sm">
                                {{ $log->created_at->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i') }} WIB
                            </td>
                            <td>
                                <span class="badge badge-{{ $log->tone() }}">{{ $log->label() }}</span>
                            </td>
                            <td>
                                <div style="font-weight:600">{{ $log->description }}</div>
                                @if($log->summary() !== '')
                                    <div class="text-xs text-muted" style="margin-top:2px">{{ $log->summary() }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                {{ $log->user?->name ?? 'Sistem' }}
                                <div class="text-xs text-muted">{{ $log->ip_address ?? '-' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('logs.show', $log) }}" class="action-btn" title="Lihat detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                                <i data-lucide="inbox" style="width:40px;height:40px;display:block;margin:0 auto var(--sp-sm);opacity:.4"></i>
                                Belum ada aktivitas yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $logs->links() }}</div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
