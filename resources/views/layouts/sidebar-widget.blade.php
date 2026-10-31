@php
    $activeAcademicYear = \App\Models\AcademicYear::where('is_active', true)->first();
@endphp
<div class="mb-4">
    <div class="mx-auto rounded-2xl bg-gray-50 p-4 text-center dark:bg-white/5 border border-gray-100 dark:border-gray-800">
        <span class="text-xs uppercase tracking-wider font-semibold text-gray-400 dark:text-gray-500">Tahun Ajaran</span>
        <h4 class="mt-1 font-semibold text-sm text-gray-800 dark:text-white truncate">
            {{ $activeAcademicYear?->name ?? 'Belum Aktif' }}
        </h4>
        <div class="mt-3">
            <a href="{{ route('monitor-poin.index') }}"
                class="flex items-center justify-center rounded-lg bg-brand-500 py-2 px-3 text-xs font-medium text-white hover:bg-brand-600 transition">
                Monitor Siswa
            </a>
        </div>
    </div>
</div>
