@props([
    'hasPrerequisites' => true,
    'title' => 'Data Referensi Belum Tersedia',
    'message' => 'Sebelum melanjutkan, sistem memerlukan data referensi prasyarat agar proses pencatatan berjalan lancar.',
    'prerequisites' => [],
])

@if(! $hasPrerequisites)
<div x-data="{ open: true }" x-init="$nextTick(() => { if (window.lucide) lucide.createIcons(); })">
    <template x-teleport="body">
        <div x-show="open" x-cloak class="modal-overlay" style="display:flex">
            <div style="position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1001" @click="open = false"></div>
            <div x-transition style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-lg);padding:var(--sp-lg);max-width:520px;width:100%;position:relative;z-index:1002;box-shadow:var(--sh-md);margin:auto">
                <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:var(--sp-sm)">
                    <div style="width:40px;height:40px;border-radius:var(--r-full);background:var(--ws);color:var(--wt);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i data-lucide="alert-triangle" class="icon-sm"></i>
                    </div>
                    <div>
                        <h3 style="font-size:1.125rem;font-weight:700;margin:0;color:var(--on-surface)">{{ $title }}</h3>
                        <p class="text-sm text-muted" style="margin:4px 0 0;line-height:1.4">{{ $message }}</p>
                    </div>
                </div>

                <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:var(--sp-sm) var(--sp-md);margin:var(--sp-md) 0">
                    <span class="field-label" style="margin-bottom:8px">Status Kesiapan Data:</span>
                    <div style="display:flex;flex-direction:column;gap:8px">
                        @php $firstMissingUrl = null; $firstMissingAction = null; @endphp
                        @foreach($prerequisites as $item)
                            @php
                                $isReady = $item['ready'] ?? false;
                                if (! $isReady && ! $firstMissingUrl && isset($item['url'])) {
                                    $firstMissingUrl = $item['url'];
                                    $firstMissingAction = $item['action'] ?? 'Lengkapi Data';
                                }
                            @endphp
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:.875rem">
                                <div style="display:flex;align-items:center;gap:8px">
                                    @if($isReady)
                                        <span style="width:20px;height:20px;border-radius:var(--r-full);background:var(--bs);color:var(--bt);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <i data-lucide="check" style="width:12px;height:12px"></i>
                                        </span>
                                        <span style="color:var(--on-surface)">{{ $item['label'] }}</span>
                                    @else
                                        <span style="width:20px;height:20px;border-radius:var(--r-full);background:var(--ds);color:var(--dt);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <i data-lucide="x" style="width:12px;height:12px"></i>
                                        </span>
                                        <span style="color:var(--danger);font-weight:500">{{ $item['label'] }} (Belum ada)</span>
                                    @endif
                                </div>
                                @if(! $isReady && isset($item['url']))
                                    <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:.75rem">
                                        {{ $item['action'] ?? 'Buat' }}
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:flex-end;gap:var(--sp-sm);margin-top:var(--sp-md)">
                    <button type="button" class="btn btn-secondary btn-sm" @click="open = false">
                        Tetap di Halaman
                    </button>
                    @if($firstMissingUrl)
                        <a href="{{ $firstMissingUrl }}" class="btn btn-primary btn-sm">
                            {{ $firstMissingAction }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
@endif
