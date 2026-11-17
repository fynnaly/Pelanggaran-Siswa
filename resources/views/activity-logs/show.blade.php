<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Detail Log Aktivitas</h1>
                <p class="text-muted text-sm">{{ $activityLog->description }}</p>
            </div>
            <a href="{{ route('logs.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
        </div>
    </x-slot>

    @php
        $props = $activityLog->properties ?? [];
        $issues = is_array($props['issues'] ?? null) ? $props['issues'] : [];
        $accounts = is_array($props['accounts'] ?? null) ? $props['accounts'] : [];
        $classes = is_array($props['classes'] ?? null) ? $props['classes'] : [];
        $meta = collect($props)->except(['issues', 'accounts', 'classes', 'status'])->all();
    @endphp

    <div class="main-wrap">
        <div class="card" style="margin-bottom:var(--sp-lg)">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-md)">
                <div>
                    <div class="text-xs text-muted">Waktu</div>
                    <div style="font-weight:600">{{ $activityLog->created_at->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H:i:s') }} WIB</div>
                </div>
                <div>
                    <div class="text-xs text-muted">Aksi</div>
                    <div><span class="badge badge-{{ $activityLog->tone() }}">{{ $activityLog->label() }}</span></div>
                </div>
                <div>
                    <div class="text-xs text-muted">Pelaku</div>
                    <div style="font-weight:600">{{ $activityLog->user?->name ?? 'Sistem' }}</div>
                    <div class="text-xs text-muted">{{ $activityLog->user?->email ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-muted">Alamat IP</div>
                    <div style="font-family:monospace">{{ $activityLog->ip_address ?? '-' }}</div>
                </div>
            </div>
            <div style="margin-top:var(--sp-md);padding-top:var(--sp-md);border-top:1px solid var(--border)">
                <div class="text-xs text-muted">Ringkasan</div>
                <div style="font-weight:600;font-size:1.05rem">{{ $activityLog->description }}</div>
            </div>
        </div>

        @if($meta !== [])
            <div class="card" style="margin-bottom:var(--sp-lg)">
                <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:var(--sp-md)">Rincian</h2>
                <div class="table-wrap">
                    <table>
                        <tbody>
                            @foreach($meta as $key => $value)
                                <tr>
                                    <th style="width:220px;text-transform:capitalize">{{ str_replace('_', ' ', $key) }}</th>
                                    <td>
                                        @if(is_array($value))
                                            <code style="font-size:.8125rem">{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</code>
                                        @elseif(is_bool($value))
                                            {{ $value ? 'Ya' : 'Tidak' }}
                                        @else
                                            {{ $value ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($classes !== [])
            <div class="card" style="margin-bottom:var(--sp-lg)">
                <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:var(--sp-md)">Kelas Baru Dibuat ({{ count($classes) }})</h2>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    @foreach($classes as $class)
                        <span class="badge badge-neutral">{{ is_array($class) ? ($class['name'] ?? '-') : $class }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if($issues !== [])
            <div class="card" style="margin-bottom:var(--sp-lg);border-color:var(--danger)">
                <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:4px;color:var(--danger)">
                    Baris Bermasalah / Dilewati ({{ count($issues) }}{{ ($props['issues_total'] ?? count($issues)) > count($issues) ? ' dari '.$props['issues_total'] : '' }})
                </h2>
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-md)">
                    Baris berikut tidak masuk ke database. Perbaiki file lalu import ulang baris tersebut.
                </p>
                <div class="table-wrap" style="max-height:420px;overflow:auto">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issues as $i => $issue)
                                <tr>
                                    <td class="text-sm text-muted">{{ $i + 1 }}</td>
                                    <td class="text-sm" style="font-family:monospace">{{ is_array($issue) ? json_encode($issue, JSON_UNESCAPED_UNICODE) : $issue }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($accounts !== [])
            <div class="card" style="margin-bottom:var(--sp-lg)">
                <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:var(--sp-md)">Data yang Dibuat / Diproses ({{ count($accounts) }})</h2>
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-md)">Password tidak pernah dicatat di log.</p>
                <div class="table-wrap" style="max-height:420px;overflow:auto">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Nama</th>
                                <th>Email / Identitas</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $i => $account)
                                <tr>
                                    <td class="text-sm text-muted">{{ $i + 1 }}</td>
                                    <td style="font-weight:600">{{ is_array($account) ? ($account['name'] ?? '-') : $account }}</td>
                                    <td class="text-sm text-muted">
                                        {{ is_array($account) ? ($account['email'] ?? '-') : '-' }}
                                        @if(is_array($account) && ! empty($account['identity']))
                                            <span class="text-muted"> &middot; {{ $account['identity'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(is_array($account) && ! empty($account['role']))
                                            <span class="badge badge-neutral">{{ $account['role'] }}</span>
                                        @else
                                            <span class="text-sm text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <details class="card">
            <summary style="cursor:pointer;font-weight:600">Lihat data mentah (JSON)</summary>
            <pre style="margin-top:var(--sp-md);font-size:.8125rem;overflow:auto">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
