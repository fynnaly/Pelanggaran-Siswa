<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Glass Lab — Kumpulan Komponen Interaktif</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            document.documentElement.classList.add('touch-device');
        }
    </script>
    <style>
        /* ============ TOKENS ( bisa diubah live via panel ) ============ */
        :root {
            --blur-px: 18px;
            --radius-lg: 28px;
            --radius-md: 20px;
            --glass-bg: rgba(255, 255, 255, 0.62);
            --glass-bg-strong: rgba(255, 255, 255, 0.80);
            --glass-bg-subtle: rgba(255, 255, 255, 0.38);
            --glass-border: rgba(255, 255, 255, 0.55);
            --glass-border-strong: rgba(255, 255, 255, 0.70);
            --glass-shadow: 0 8px 32px rgba(15, 23, 42, 0.10);
            --glass-shadow-lg: 0 16px 48px rgba(15, 23, 42, 0.14);
            --accent: #7c3aed;
            --accent-soft: rgba(124, 58, 237, 0.16);
            --success: #059669;
            --success-soft: rgba(5, 150, 105, 0.14);
            --warning: #d97706;
            --warning-soft: rgba(217, 119, 6, 0.14);
            --danger: #dc2626;
            --danger-soft: rgba(220, 38, 38, 0.12);
            --ink: #0f172a;
            --ink-soft: #475569;
            --page-bg: linear-gradient(135deg, #e8e0f0 0%, #dce4f8 50%, #f0e8e4 100%);
        }
        .dark {
            --glass-bg: rgba(20, 18, 40, 0.62);
            --glass-bg-strong: rgba(20, 18, 40, 0.82);
            --glass-bg-subtle: rgba(20, 18, 40, 0.42);
            --glass-border: rgba(255, 255, 255, 0.14);
            --glass-border-strong: rgba(255, 255, 255, 0.22);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.32);
            --glass-shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.42);
            --accent: #a78bfa;
            --accent-soft: rgba(167, 139, 250, 0.20);
            --success: #34d399;
            --success-soft: rgba(52, 211, 153, 0.18);
            --warning: #fbbf24;
            --warning-soft: rgba(251, 191, 36, 0.18);
            --danger: #f87171;
            --danger-soft: rgba(248, 113, 113, 0.18);
            --ink: #f1f5f9;
            --ink-soft: #94a3b8;
            --page-bg: linear-gradient(135deg, #0f0a1e 0%, #0a0e1e 50%, #1a0f0e 100%);
        }

        body { font-family: 'Figtree', system-ui, sans-serif; color: var(--ink); }
        [x-cloak] { display: none !important; }

        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-px)) saturate(180%);
            -webkit-backdrop-filter: blur(var(--blur-px)) saturate(180%);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }
        .glass-strong {
            background: var(--glass-bg-strong);
            backdrop-filter: blur(calc(var(--blur-px) + 12px)) saturate(200%);
            -webkit-backdrop-filter: blur(calc(var(--blur-px) + 12px)) saturate(200%);
            border: 1px solid var(--glass-border-strong);
            box-shadow: var(--glass-shadow-lg);
        }
        .glass-subtle {
            background: var(--glass-bg-subtle);
            backdrop-filter: blur(var(--blur-px)) saturate(160%);
            -webkit-backdrop-filter: blur(var(--blur-px)) saturate(160%);
            border: 1px solid var(--glass-border);
        }
        .r-lg { border-radius: var(--radius-lg); }
        .r-md { border-radius: var(--radius-md); }

        /* Mesh background */
        .mesh-bg { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: var(--page-bg); transition: background 0.4s ease; }
        .mesh-blob { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.6; animation: drift 22s ease-in-out infinite alternate; }
        .dark .mesh-blob { opacity: 0.32; }
        .mesh-blob-1 { width: 520px; height: 520px; top: -12%; left: -6%; background: radial-gradient(circle, var(--blob-1, #c084fc) 0%, transparent 70%); }
        .mesh-blob-2 { width: 620px; height: 620px; bottom: -18%; right: -10%; background: radial-gradient(circle, var(--blob-2, #60a5fa) 0%, transparent 70%); animation-delay: -7s; }
        .mesh-blob-3 { width: 420px; height: 420px; top: 42%; left: 48%; background: radial-gradient(circle, var(--blob-3, #fb923c) 0%, transparent 70%); animation-delay: -14s; }
        .bg-sunset { --blob-1: #f472b6; --blob-2: #fb923c; --blob-3: #facc15; }
        .bg-ocean { --blob-1: #22d3ee; --blob-2: #3b82f6; --blob-3: #34d399; }
        .bg-candy { --blob-1: #c084fc; --blob-2: #f472b6; --blob-3: #60a5fa; }
        .bg-plain .mesh-blob { display: none; }
        @keyframes drift {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -20px) scale(1.05); }
            100% { transform: translate(-20px, 30px) scale(0.95); }
        }

        /* Motion */
        .press { transition: transform 0.18s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.18s ease, background-color 0.2s ease, border-radius 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .press:active { transform: scale(0.96); }
        .no-anim *, .no-anim *::before, .no-anim *::after { animation: none !important; transition-duration: 0.01ms !important; }

        /* QS tile */
        .qs-tile { transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .qs-off { background: var(--glass-bg-subtle); border: 1px solid var(--glass-border); color: var(--ink-soft); border-radius: 9999px; }
        .qs-on { background: var(--accent); border: 1px solid var(--accent); color: #fff; border-radius: var(--radius-md); box-shadow: 0 4px 20px rgba(124, 58, 237, 0.35); }

        .pill { border-radius: 9999px; }
        .seg-btn { transition: background-color 0.2s ease, color 0.2s ease; }
        .seg-on { background: var(--accent); color: #fff; }
        .seg-off { color: var(--ink-soft); }
        .seg-off:hover { background: rgba(127, 127, 160, 0.15); }

        /* Switch */
        .switch { width: 44px; height: 24px; border-radius: 9999px; position: relative; cursor: pointer; transition: background-color 0.2s ease; background: rgba(127,127,160,0.35); border: 1px solid var(--glass-border); flex-shrink: 0; }
        .switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.3); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .switch[aria-checked="true"] { background: var(--accent); }
        .switch[aria-checked="true"]::after { transform: translateX(20px); }

        /* Slider */
        input[type="range"].m3 { -webkit-appearance: none; appearance: none; width: 100%; height: 8px; border-radius: 4px; background: rgba(127,127,160,0.30); outline: none; cursor: pointer; }
        input[type="range"].m3::-webkit-slider-thumb { -webkit-appearance: none; width: 26px; height: 26px; border-radius: 50%; background: var(--accent); border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.25); cursor: pointer; transition: transform 0.15s ease; }
        .dark input[type="range"].m3::-webkit-slider-thumb { border-color: #1e1b4b; }
        input[type="range"].m3::-webkit-slider-thumb:active { transform: scale(1.15); }
        input[type="range"].m3::-moz-range-thumb { width: 22px; height: 22px; border-radius: 50%; background: var(--accent); border: 3px solid #fff; cursor: pointer; }

        .field { width: 100%; border-radius: 16px; padding: 10px 16px; font-size: 0.875rem; font-weight: 500; outline: none; background: var(--glass-bg-subtle); border: 1px solid var(--glass-border); color: var(--ink); transition: box-shadow 0.2s ease, border-color 0.2s ease; }
        .field::placeholder { color: var(--ink-soft); }
        .field:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        select.field option { color: #0f172a; }

        .th-sort { cursor: pointer; user-select: none; }
        .th-sort:hover { color: var(--accent); }

        .modal-overlay { background: rgba(2, 6, 23, 0.38); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        .dark .modal-overlay { background: rgba(0, 0, 0, 0.55); }

        .toast-in { animation: toast-in 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes toast-in { from { opacity: 0; transform: translateY(12px) scale(0.96); } to { opacity: 1; transform: none; } }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .pulse-dot { animation: pulse-dot 1.6s ease-in-out infinite; }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }

        /* Touch/Mobile: disable hover effects */
        .touch-device *:hover {
            color: revert !important;
            background-color: revert !important;
            border-color: revert !important;
            box-shadow: revert !important;
            transform: revert !important;
            opacity: revert !important;
        }
        .touch-device .group:hover .group-hover\:scale-105,
        .touch-device .group:hover .group-hover\:scale-110 {
            transform: none !important;
        }

        /* Mobile performance: lighter glass + no blob animation */
        @media (max-width: 640px) {
            :root { --blur-px: 10px; }
            .glass-strong { backdrop-filter: blur(12px) saturate(150%); -webkit-backdrop-filter: blur(12px) saturate(150%); }
            .mesh-blob { animation: none !important; opacity: 0.3 !important; filter: blur(60px); }
            .mesh-blob-1 { width: 300px; height: 300px; }
            .mesh-blob-2 { width: 350px; height: 350px; }
            .mesh-blob-3 { width: 250px; height: 250px; }
        }

        /* GPU hints for animated elements */
        .glass, .glass-strong, .mesh-blob { will-change: auto; contain: layout style; }
        .mesh-blob { content-visibility: auto; }
        .press { will-change: transform; }

        @supports not (backdrop-filter: blur(1px)) {
            .glass { background: rgba(255,255,255,0.94); }
            .glass-strong { background: rgba(255,255,255,0.97); }
            .glass-subtle { background: rgba(255,255,255,0.88); }
            .dark .glass { background: rgba(20,18,40,0.95); }
            .dark .glass-strong { background: rgba(20,18,40,0.97); }
            .dark .glass-subtle { background: rgba(20,18,40,0.90); }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
            .mesh-blob { animation: none; }
        }
    </style>
</head>
<body class="min-h-screen antialiased" x-data="glassLab()" :class="{ 'dark': dark, 'no-anim': !anim, 'bg-sunset': bg==='sunset', 'bg-ocean': bg==='ocean', 'bg-candy': bg==='candy', 'bg-plain': bg==='plain' }" x-init="init()">

    <div class="mesh-bg" aria-hidden="true">
        <div class="mesh-blob mesh-blob-1"></div>
        <div class="mesh-blob mesh-blob-2"></div>
        <div class="mesh-blob mesh-blob-3"></div>
    </div>

    <!-- ================= NAVBAR ================= -->
    <nav class="sticky top-3 sm:top-4 z-50 mx-3 sm:mx-4 lg:mx-auto lg:max-w-7xl rounded-[24px] glass-strong px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="font-extrabold text-sm sm:text-base truncate" style="color: var(--ink)">Glass Lab</p>
                <p class="text-[11px] hidden sm:block" style="color: var(--ink-soft)">Kumpulan komponen glass interaktif</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/" class="hidden md:inline-flex text-sm font-semibold px-4 py-2 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors" style="color: var(--ink-soft)">Beranda</a>
            <a href="/dashboard" class="hidden md:inline-flex text-sm font-semibold px-4 py-2 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors" style="color: var(--ink-soft)">Dashboard</a>
            <button @click="toggleDark()" class="w-10 h-10 rounded-full glass flex items-center justify-center press cursor-pointer" :aria-label="dark ? 'Mode terang' : 'Mode gelap'">
                <svg x-show="!dark" class="w-5 h-5" style="color: var(--ink)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="dark" x-cloak class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">

        <!-- ================= HERO ================= -->
        <header class="text-center pt-10 pb-6">
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight" style="color: var(--ink)">Glass Lab</h1>
            <p class="mt-3 text-sm sm:text-lg max-w-2xl mx-auto" style="color: var(--ink-soft)">
                Semua komponen dalam satu halaman. Setiap komponen bisa di-toggle, diubah propertinya, dan langsung terlihat hasilnya.
            </p>
        </header>

        <!-- ================= PANEL GLOBAL ================= -->
        <section class="glass-strong r-lg p-5 sm:p-6 mb-8" aria-label="Pengaturan global tema">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                <h2 class="font-bold text-sm sm:text-base" style="color: var(--ink)">Pengaturan Global</h2>
                <button @click="resetGlobal()" class="text-xs font-bold px-4 py-2 pill glass-subtle press cursor-pointer" style="color: var(--ink-soft)">Reset</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="flex items-center justify-between gap-3 glass-subtle r-md px-4 py-3">
                    <span class="text-sm font-semibold" style="color: var(--ink)">Mode gelap</span>
                    <div class="switch" role="switch" tabindex="0" :aria-checked="dark" @click="toggleDark()" @keydown.enter="toggleDark()" @keydown.space.prevent="toggleDark()"></div>
                </div>
                <div class="flex items-center justify-between gap-3 glass-subtle r-md px-4 py-3">
                    <span class="text-sm font-semibold" style="color: var(--ink)">Animasi</span>
                    <div class="switch" role="switch" tabindex="0" :aria-checked="anim" @click="anim=!anim" @keydown.enter="anim=!anim" @keydown.space.prevent="anim=!anim"></div>
                </div>
                <div class="glass-subtle r-md px-4 py-3">
                    <div class="flex justify-between text-sm font-semibold mb-2" style="color: var(--ink)">
                        <span>Blur</span><span x-text="blur+'px'" style="color: var(--accent)"></span>
                    </div>
                    <input type="range" min="0" max="32" step="1" x-model.number="blur" class="m3" aria-label="Intensitas blur">
                </div>
                <div class="glass-subtle r-md px-4 py-3">
                    <div class="flex justify-between text-sm font-semibold mb-2" style="color: var(--ink)">
                        <span>Radius</span><span x-text="radius+'px'" style="color: var(--accent)"></span>
                    </div>
                    <input type="range" min="12" max="32" step="1" x-model.number="radius" class="m3" aria-label="Radius sudut">
                </div>
            </div>
            <div class="mt-4">
                <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--ink-soft)">Background</p>
                <div class="inline-flex flex-wrap gap-1 p-1 pill glass-subtle" role="group" aria-label="Varian background">
                    <template x-for="b in bgs" :key="b.v">
                        <button @click="bg=b.v" class="seg-btn text-xs font-bold px-4 py-2 pill cursor-pointer" :class="bg===b.v ? 'seg-on' : 'seg-off'" x-text="b.l"></button>
                    </template>
                </div>
            </div>
        </section>

        <!-- ================= NAV SEKSI ================= -->
        <nav class="sticky top-[76px] sm:top-[84px] z-40 -mx-4 px-4 sm:mx-0 sm:px-0 mb-8" aria-label="Navigasi komponen">
            <div class="glass-strong r-lg px-3 py-2 flex gap-1 overflow-x-auto">
                <template x-for="s in sections" :key="s.id">
                    <a :href="'#'+s.id" @click="activeSection=s.id" class="whitespace-nowrap text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 pill transition-colors cursor-pointer hover:opacity-80" :style="activeSection===s.id ? 'background: var(--accent); color: #fff' : 'color: var(--ink-soft)'" x-text="s.l"></a>
                </template>
            </div>
        </nav>

        <!-- ================= 1. TOMBOL ================= -->
        <section id="tombol" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">01 — Tombol</h2>
            <div class="glass r-lg p-5 sm:p-7 grid grid-cols-1 lg:grid-cols-[1fr_270px] gap-6">
                <div>
                    <p class="text-sm mb-4" style="color: var(--ink-soft)">Preview live — ubah properti dari panel kontrol.</p>
                    <div class="glass-subtle r-md p-6 flex items-center justify-center min-h-[140px] mb-5">
                        <button :disabled="btn.disabled || btn.loading"
                            class="press font-bold tracking-wide cursor-pointer inline-flex items-center gap-2 focus:outline-none focus-visible:ring-2"
                            :class="[btn.size, btn.rounded, btn.block ? 'w-full justify-center' : '']"
                            :style="btnStyle()">
                            <svg x-show="btn.loading" class="w-4 h-4 spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <svg x-show="btn.icon && !btn.loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="btn.loading ? 'Memproses...' : btn.label"></span>
                        </button>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--ink-soft)">Semua varian</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="v in btnVariants" :key="v.v">
                            <button @click="btn.variant=v.v; pushToast('Varian tombol: '+v.l, 'info')" class="press text-xs font-bold px-4 py-2 pill cursor-pointer" :style="variantStyle(v.v)" x-text="v.l"></button>
                        </template>
                    </div>
                </div>
                <div class="space-y-3 lg:border-l lg:pl-6" :style="'border-color: var(--glass-border)'">
                    <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--ink-soft)">Kontrol</p>
                    <div>
                        <label class="text-xs font-semibold block mb-1" style="color: var(--ink)">Label</label>
                        <input type="text" x-model="btn.label" class="field" maxlength="24">
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1" style="color: var(--ink)">Ukuran</label>
                        <div class="flex gap-1 p-1 pill glass-subtle">
                            <template x-for="s in btnSizes" :key="s.v">
                                <button @click="btn.size=s.v" class="seg-btn text-xs font-bold px-3 py-1.5 pill flex-1 cursor-pointer" :class="btn.size===s.v?'seg-on':'seg-off'" x-text="s.l"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Ikon</span>
                        <div class="switch" role="switch" tabindex="0" :aria-checked="btn.icon" @click="btn.icon=!btn.icon" @keydown.enter="btn.icon=!btn.icon"></div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Pill penuh</span>
                        <div class="switch" role="switch" tabindex="0" :aria-checked="btn.rounded==='pill'" @click="btn.rounded = btn.rounded==='pill' ? 'r-md' : 'pill'" @keydown.enter="btn.rounded = btn.rounded==='pill' ? 'r-md' : 'pill'"></div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Loading</span>
                        <div class="switch" role="switch" tabindex="0" :aria-checked="btn.loading" @click="btn.loading=!btn.loading" @keydown.enter="btn.loading=!btn.loading"></div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Disabled</span>
                        <div class="switch" role="switch" tabindex="0" :aria-checked="btn.disabled" @click="btn.disabled=!btn.disabled" @keydown.enter="btn.disabled=!btn.disabled"></div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Lebar penuh</span>
                        <div class="switch" role="switch" tabindex="0" :aria-checked="btn.block" @click="btn.block=!btn.block" @keydown.enter="btn.block=!btn.block"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 2. CHIP & BADGE ================= -->
        <section id="chip" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">02 — Chip & Badge</h2>
            <div class="glass r-lg p-5 sm:p-7 grid grid-cols-1 lg:grid-cols-[1fr_270px] gap-6">
                <div>
                    <p class="text-sm mb-3" style="color: var(--ink-soft)">Filter aktif: <b x-text="chipActive" style="color: var(--accent)"></b></p>
                    <div class="flex flex-wrap gap-2 mb-5">
                        <template x-for="c in chips" :key="c">
                            <button @click="chipActive=c" class="press text-xs font-bold px-4 py-2 pill cursor-pointer inline-flex items-center gap-2" :style="chipActive===c ? 'background: var(--accent); color:#fff; border:1px solid var(--accent)' : 'background: var(--glass-bg-subtle); color: var(--ink-soft); border:1px solid var(--glass-border)'">
                                <span x-text="c"></span>
                                <span x-show="chipActive===c" class="w-1.5 h-1.5 rounded-full bg-white pulse-dot"></span>
                            </button>
                        </template>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--ink-soft)">Bisa dihapus</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="t in tags" :key="t">
                            <span class="text-xs font-bold px-4 py-2 pill glass-subtle inline-flex items-center gap-2" style="color: var(--ink)">
                                <span x-text="t"></span>
                                <button @click="removeTag(t)" class="w-4 h-4 rounded-full inline-flex items-center justify-center hover:bg-black/10 dark:hover:bg-white/20 cursor-pointer" aria-label="Hapus tag">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </span>
                        </template>
                        <button x-show="tags.length < 6" @click="resetTags()" class="text-xs font-bold px-4 py-2 pill press cursor-pointer" style="background: var(--accent-soft); color: var(--accent)">+ Reset</button>
                    </div>
                </div>
                <div class="space-y-3 lg:border-l lg:pl-6" :style="'border-color: var(--glass-border)'">
                    <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--ink-soft)">Kontrol</p>
                    <div>
                        <label class="text-xs font-semibold block mb-1" style="color: var(--ink)">Tambah chip filter</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="newChip" @keydown.enter="addChip()" placeholder="Nama chip..." class="field">
                            <button @click="addChip()" class="press pill px-4 text-sm font-bold text-white cursor-pointer flex-shrink-0" style="background: var(--accent)">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1" style="color: var(--ink)">Tambah tag</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="newTag" @keydown.enter="addTag()" placeholder="Nama tag..." class="field">
                            <button @click="addTag()" class="press pill px-4 text-sm font-bold text-white cursor-pointer flex-shrink-0" style="background: var(--accent)">+</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 3. FORM ================= -->
        <section id="form" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">03 — Form</h2>
            <div class="glass r-lg p-5 sm:p-7 grid grid-cols-1 lg:grid-cols-[1fr_270px] gap-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold block mb-1.5" style="color: var(--ink)">Nama siswa <span style="color: var(--danger)">*</span></label>
                            <input type="text" x-model="form.nama" placeholder="cth: Ahmad Rizki" class="field" maxlength="40">
                            <p class="text-[11px] mt-1 flex justify-between" style="color: var(--ink-soft)">
                                <span x-show="form.nama.length>0 && form.nama.length<3" style="color: var(--danger)">Minimal 3 karakter</span>
                                <span x-show="!(form.nama.length>0 && form.nama.length<3)">Valid</span>
                                <span x-text="form.nama.length+'/40'"></span>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-bold block mb-1.5" style="color: var(--ink)">Kelas</label>
                            <select x-model="form.kelas" class="field cursor-pointer">
                                <option value="">Pilih kelas...</option>
                                <option>X TKJ 1</option><option>X TKJ 2</option>
                                <option>XI TKJ 1</option><option>XI TKJ 2</option>
                                <option>XII TKJ 1</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold block mb-1.5" style="color: var(--ink)">Kata sandi</label>
                        <div class="relative">
                            <input :type="form.showPw ? 'text' : 'password'" x-model="form.pw" placeholder="••••••••" class="field pr-12">
                            <button @click="form.showPw=!form.showPw" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold cursor-pointer" style="color: var(--accent)" x-text="form.showPw ? 'Sembunyi' : 'Lihat'"></button>
                        </div>
                        <div class="h-1.5 rounded-full mt-2 overflow-hidden" style="background: rgba(127,127,160,0.25)">
                            <div class="h-full rounded-full transition-all duration-300" :style="'width:'+pwScore()+'%; background:'+(pwScore()>66?'var(--success)':pwScore()>33?'var(--warning)':'var(--danger)')"></div>
                        </div>
                        <p class="text-[11px] mt-1" style="color: var(--ink-soft)">Kekuatan sandi: <b x-text="pwLabel()"></b></p>
                    </div>
                    <div>
                        <label class="text-xs font-bold block mb-1.5" style="color: var(--ink)">Keterangan (<span x-text="form.note.length"></span>/120)</label>
                        <textarea rows="2" x-model="form.note" maxlength="120" placeholder="Deskripsi singkat..." class="field resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-bold mb-2" style="color: var(--ink)">Tingkat</p>
                            <div class="flex flex-col gap-2">
                                <template x-for="r in ['Ringan','Sedang','Berat']" :key="r">
                                    <label class="flex items-center gap-2 text-sm font-medium cursor-pointer" style="color: var(--ink)">
                                        <input type="radio" name="tingkat" :value="r" x-model="form.tingkat" class="w-4 h-4 accent-violet-600">
                                        <span x-text="r"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-bold mb-2" style="color: var(--ink)">Opsi</p>
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center justify-between gap-2 text-sm font-medium cursor-pointer" style="color: var(--ink)">
                                    <span>Hubungi wali</span>
                                    <div class="switch" role="switch" :aria-checked="form.wali" @click="form.wali=!form.wali"></div>
                                </label>
                                <label class="flex items-center justify-between gap-2 text-sm font-medium cursor-pointer" style="color: var(--ink)">
                                    <span>Butuh konseling</span>
                                    <div class="switch" role="switch" :aria-checked="form.konseling" @click="form.konseling=!form.konseling"></div>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-medium cursor-pointer" style="color: var(--ink)">
                                    <input type="checkbox" x-model="form.setuju" class="w-4 h-4 rounded accent-violet-600">
                                    <span>Data sudah benar</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:border-l lg:pl-6 space-y-3" :style="'border-color: var(--glass-border)'">
                    <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--ink-soft)">Ringkasan live</p>
                    <div class="glass-subtle r-md p-4 text-xs font-mono leading-relaxed whitespace-pre-wrap break-words" style="color: var(--ink)" x-text="formSummary()"></div>
                    <div class="flex gap-2">
                        <button @click="submitForm()" class="press pill px-5 py-2.5 text-sm font-bold text-white cursor-pointer flex-1" style="background: var(--accent)">Simpan</button>
                        <button @click="resetForm()" class="press pill px-5 py-2.5 text-sm font-bold glass-subtle cursor-pointer" style="color: var(--ink-soft)">Reset</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 4. QUICK SETTINGS ================= -->
        <section id="qs" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">04 — Quick Settings Tiles</h2>
            <div class="glass r-lg p-5 sm:p-7">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                    <p class="text-sm" style="color: var(--ink-soft)"><b x-text="qsOn().length" style="color: var(--accent)"></b> dari <span x-text="qs.length"></span> aktif</p>
                    <div class="flex gap-2">
                        <button @click="qs.forEach(q=>q.on=true)" class="text-xs font-bold px-4 py-2 pill glass-subtle press cursor-pointer" style="color: var(--ink)">Semua on</button>
                        <button @click="qs.forEach(q=>q.on=false)" class="text-xs font-bold px-4 py-2 pill glass-subtle press cursor-pointer" style="color: var(--ink)">Semua off</button>
                    </div>
                </div>
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    <template x-for="q in qs" :key="q.id">
                        <button @click="q.on=!q.on" class="qs-tile press flex flex-col items-center gap-2 py-4 px-2 cursor-pointer" :class="q.on?'qs-on':'qs-off'">
                            <span x-html="q.icon"></span>
                            <span class="text-[11px] sm:text-xs font-bold" x-text="q.label"></span>
                        </button>
                    </template>
                </div>
                <div class="mt-5">
                    <div class="flex justify-between text-sm font-bold mb-2" style="color: var(--ink)">
                        <span>Kecerahan</span><span x-text="brightness+'%'" style="color: var(--accent)"></span>
                    </div>
                    <input type="range" min="0" max="100" x-model.number="brightness" class="m3" aria-label="Kecerahan">
                </div>
            </div>
        </section>

        <!-- ================= 5. TABEL ================= -->
        <section id="tabel" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">05 — Tabel Interaktif</h2>
            <div class="glass r-lg p-5 sm:p-7">
                <!-- Toolbar -->
                <div class="flex flex-col lg:flex-row gap-3 lg:items-center mb-4">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color: var(--ink-soft)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" x-model="q" placeholder="Cari nama, kelas, jenis..." class="field pl-10" aria-label="Cari data">
                    </div>
                    <div class="flex gap-1 p-1 pill glass-subtle overflow-x-auto">
                        <template x-for="f in ['Semua','Proses','Selesai','Berat']" :key="f">
                            <button @click="fStatus=f; page=1" class="seg-btn text-xs font-bold px-3 sm:px-4 py-2 pill whitespace-nowrap cursor-pointer" :class="fStatus===f?'seg-on':'seg-off'" x-text="f"></button>
                        </template>
                    </div>
                </div>

                <!-- Bulk bar -->
                <div x-show="selected.length>0" x-cloak class="flex flex-wrap items-center gap-2 mb-3 p-3 r-md" style="background: var(--accent-soft)">
                    <span class="text-xs font-bold" style="color: var(--accent)" x-text="selected.length+' dipilih'"></span>
                    <button @click="bulkStatus('Selesai')" class="text-xs font-bold px-3 py-1.5 pill bg-white/70 dark:bg-black/30 press cursor-pointer" style="color: var(--success)">Tandai selesai</button>
                    <button @click="bulkDelete()" class="text-xs font-bold px-3 py-1.5 pill bg-white/70 dark:bg-black/30 press cursor-pointer" style="color: var(--danger)">Hapus</button>
                    <button @click="selected=[]" class="text-xs font-bold px-3 py-1.5 cursor-pointer" style="color: var(--ink-soft)">Batal</button>
                </div>

                <!-- Desktop table -->
                <div class="hidden md:block overflow-x-auto r-md glass-subtle">
                    <table class="w-full text-sm min-w-[640px]">
                        <thead>
                            <tr class="border-b text-left" style="border-color: var(--glass-border)">
                                <th class="py-3 px-4 w-10"><input type="checkbox" :checked="allChecked()" @change="toggleAll($event.target.checked)" class="w-4 h-4 rounded accent-violet-600 cursor-pointer" aria-label="Pilih semua"></th>
                                <th class="py-3 px-4 font-bold th-sort" @click="sortBy('nama')" style="color: var(--ink)">Nama <span x-text="sortIcon('nama')"></span></th>
                                <th class="py-3 px-4 font-bold th-sort" @click="sortBy('kelas')" style="color: var(--ink)">Kelas <span x-text="sortIcon('kelas')"></span></th>
                                <th class="py-3 px-4 font-bold" style="color: var(--ink)">Jenis</th>
                                <th class="py-3 px-4 font-bold" style="color: var(--ink)">Status</th>
                                <th class="py-3 px-4 font-bold text-right" style="color: var(--ink)">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in paged()" :key="r.id">
                                <tr class="border-b last:border-0 transition-colors hover:bg-black/5 dark:hover:bg-white/5" style="border-color: var(--glass-border)">
                                    <td class="py-3 px-4"><input type="checkbox" :value="r.id" x-model="selected" class="w-4 h-4 rounded accent-violet-600 cursor-pointer" aria-label="Pilih baris"></td>
                                    <td class="py-3 px-4 font-bold" style="color: var(--ink)" x-text="r.nama"></td>
                                    <td class="py-3 px-4" style="color: var(--ink-soft)" x-text="r.kelas"></td>
                                    <td class="py-3 px-4" style="color: var(--ink-soft)" x-text="r.jenis"></td>
                                    <td class="py-3 px-4">
                                        <button @click="cycleStatus(r)" class="text-[11px] font-bold px-3 py-1 pill press cursor-pointer" :style="statusStyle(r.status)" x-text="r.status" :title="'Klik untuk ubah status'"></button>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <button @click="delRow(r.id)" class="text-xs font-bold px-3 py-1.5 pill glass-subtle press cursor-pointer" style="color: var(--danger)">Hapus</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="filtered().length===0" class="p-10 text-center">
                        <p class="font-bold mb-1" style="color: var(--ink)">Tidak ada hasil</p>
                        <p class="text-sm mb-4" style="color: var(--ink-soft)">Coba ubah kata kunci atau filter.</p>
                        <button @click="q=''; fStatus='Semua'" class="text-xs font-bold px-4 py-2 pill press cursor-pointer text-white" style="background: var(--accent)">Reset filter</button>
                    </div>
                </div>

                <!-- Mobile cards -->
                <div class="md:hidden space-y-3">
                    <template x-for="r in paged()" :key="'m'+r.id">
                        <div class="glass-subtle r-md p-4">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <p class="font-bold text-sm" style="color: var(--ink)" x-text="r.nama"></p>
                                <button @click="cycleStatus(r)" class="text-[11px] font-bold px-3 py-1 pill press cursor-pointer flex-shrink-0" :style="statusStyle(r.status)" x-text="r.status"></button>
                            </div>
                            <p class="text-xs mb-3" style="color: var(--ink-soft)" x-text="r.kelas+' • '+r.jenis"></p>
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer" style="color: var(--ink-soft)">
                                    <input type="checkbox" :value="r.id" x-model="selected" class="w-4 h-4 rounded accent-violet-600"> Pilih
                                </label>
                                <button @click="delRow(r.id)" class="text-xs font-bold cursor-pointer" style="color: var(--danger)">Hapus</button>
                            </div>
                        </div>
                    </template>
                    <div x-show="filtered().length===0" class="glass-subtle r-md p-8 text-center">
                        <p class="font-bold mb-1" style="color: var(--ink)">Tidak ada hasil</p>
                        <button @click="q=''; fStatus='Semua'" class="mt-3 text-xs font-bold px-4 py-2 pill press cursor-pointer text-white" style="background: var(--accent)">Reset filter</button>
                    </div>
                </div>

                <!-- Pagination + tambah -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4">
                    <p class="text-xs font-semibold" style="color: var(--ink-soft)" x-text="pageInfo()"></p>
                    <div class="flex items-center gap-2">
                        <button @click="prevPage()" :disabled="page<=1" class="text-xs font-bold px-4 py-2 pill glass-subtle press cursor-pointer disabled:opacity-40" style="color: var(--ink)">‹ Prev</button>
                        <template x-for="p in pages()" :key="p">
                            <button @click="page=p" class="w-8 h-8 text-xs font-bold pill press cursor-pointer" :style="page===p ? 'background:var(--accent);color:#fff' : 'background:var(--glass-bg-subtle);color:var(--ink-soft)'" x-text="p"></button>
                        </template>
                        <button @click="nextPage()" :disabled="page>=maxPage()" class="text-xs font-bold px-4 py-2 pill glass-subtle press cursor-pointer disabled:opacity-40" style="color: var(--ink)">Next ›</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_160px_1fr_auto] gap-2 mt-4">
                    <input type="text" x-model="nNama" placeholder="Nama baru..." class="field">
                    <input type="text" x-model="nKelas" placeholder="Kelas..." class="field">
                    <input type="text" x-model="nJenis" placeholder="Jenis..." class="field">
                    <button @click="addRow()" class="press pill px-5 py-2.5 text-sm font-bold text-white cursor-pointer" style="background: var(--success)">+ Tambah</button>
                </div>
            </div>
        </section>

        <!-- ================= 6. OVERLAY: MODAL & TOAST ================= -->
        <section id="overlay" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">06 — Modal, Toast & Alert</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="glass r-lg p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">Modal</p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button @click="openModal('info')" class="press text-xs font-bold px-4 py-2 pill text-white cursor-pointer" style="background: var(--accent)">Info</button>
                        <button @click="openModal('confirm')" class="press text-xs font-bold px-4 py-2 pill glass-subtle cursor-pointer" style="color: var(--ink)">Konfirmasi</button>
                        <button @click="openModal('danger')" class="press text-xs font-bold px-4 py-2 pill text-white cursor-pointer" style="background: var(--danger)">Bahaya</button>
                    </div>
                    <div class="flex gap-1 p-1 pill glass-subtle w-max">
                        <template x-for="s in ['sm','md','lg']" :key="s">
                            <button @click="modalSize=s" class="seg-btn text-xs font-bold px-4 py-1.5 pill cursor-pointer uppercase" :class="modalSize===s?'seg-on':'seg-off'" x-text="s"></button>
                        </template>
                    </div>
                    <p class="text-xs mt-3" style="color: var(--ink-soft)">Ukuran: <b x-text="modalSize" style="color: var(--accent)"></b> • Tutup via tombol, backdrop, atau ESC.</p>
                </div>
                <div class="glass r-lg p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">Toast</p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button @click="pushToast('Data berhasil disimpan','success')" class="press text-xs font-bold px-4 py-2 pill text-white cursor-pointer" style="background: var(--success)">Sukses</button>
                        <button @click="pushToast('Periksa kembali isian form','warning')" class="press text-xs font-bold px-4 py-2 pill text-white cursor-pointer" style="background: var(--warning)">Peringatan</button>
                        <button @click="pushToast('Gagal menghapus data','danger')" class="press text-xs font-bold px-4 py-2 pill text-white cursor-pointer" style="background: var(--danger)">Error</button>
                        <button @click="pushToast('Ini info biasa','info')" class="press text-xs font-bold px-4 py-2 pill glass-subtle cursor-pointer" style="color: var(--ink)">Info</button>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Alert contoh bisa ditutup</span>
                        <div class="switch" role="switch" :aria-checked="showAlert" @click="showAlert=!showAlert"></div>
                    </div>
                    <div x-show="showAlert" x-cloak class="mt-3 r-md p-4 flex items-start gap-3" style="background: var(--warning-soft); border: 1px solid var(--glass-border)">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: var(--warning)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p class="text-xs font-medium flex-1" style="color: var(--ink)">3 pelanggaran butuh tindak lanjut minggu ini.</p>
                        <button @click="showAlert=false" class="cursor-pointer font-bold" style="color: var(--ink-soft)" aria-label="Tutup alert">✕</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 7. NAVIGASI: TAB, AKORDEON, BREADCRUMB ================= -->
        <section id="navigasi" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">07 — Tab, Akordeon & Breadcrumb</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="glass r-lg p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">Tab</p>
                    <div class="flex gap-1 p-1 pill glass-subtle mb-4 overflow-x-auto" role="tablist">
                        <template x-for="t in tabs" :key="t">
                            <button @click="tab=t" role="tab" :aria-selected="tab===t" class="seg-btn text-xs font-bold px-4 py-2 pill whitespace-nowrap cursor-pointer flex-1" :class="tab===t?'seg-on':'seg-off'" x-text="t"></button>
                        </template>
                    </div>
                    <div class="glass-subtle r-md p-4 text-sm min-h-[90px]" style="color: var(--ink)">
                        <p x-show="tab==='Ringkasan'"><b>Ringkasan:</b> 87 pelanggaran bulan ini, 72 selesai, 15 dalam proses.</p>
                        <p x-show="tab==='Tren'" x-cloak><b>Tren:</b> keterlambatan turun 12% dibanding bulan lalu.</p>
                        <p x-show="tab==='Kelas'" x-cloak><b>Per kelas:</b> X TKJ 1 tertinggi (21 kasus), XII TKJ 1 terendah (4 kasus).</p>
                    </div>
                    <p class="text-xs mt-4 mb-2" style="color: var(--ink-soft)"><b style="color: var(--ink)">Breadcrumb:</b></p>
                    <nav class="flex items-center gap-1.5 text-xs font-bold flex-wrap" aria-label="Breadcrumb">
                        <a href="#" class="hover:underline cursor-pointer" style="color: var(--ink-soft)">Home</a>
                        <span style="color: var(--ink-soft)">/</span>
                        <a href="#" class="hover:underline cursor-pointer" style="color: var(--ink-soft)">Pelanggaran</a>
                        <span style="color: var(--ink-soft)">/</span>
                        <span style="color: var(--accent)">Detail</span>
                    </nav>
                </div>
                <div class="glass r-lg p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--ink-soft)">Akordeon</p>
                        <label class="flex items-center gap-2 text-[11px] font-bold cursor-pointer" style="color: var(--ink-soft)">
                            <input type="checkbox" x-model="accMulti" class="w-3.5 h-3.5 rounded accent-violet-600"> Multi-buka
                        </label>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(a,i) in acc" :key="i">
                            <div class="glass-subtle r-md overflow-hidden">
                                <button @click="toggleAcc(i)" class="w-full flex items-center justify-between gap-2 px-4 py-3 text-sm font-bold cursor-pointer" style="color: var(--ink)" :aria-expanded="a.open">
                                    <span x-text="a.t"></span>
                                    <svg class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="a.open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="a.open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="px-4 pb-4 text-sm" style="color: var(--ink-soft)" x-text="a.c"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= 8. PROGRESS & AVATAR ================= -->
        <section id="extra" class="scroll-mt-40 mb-8">
            <h2 class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">08 — Progress, Avatar & Stat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="glass r-lg p-6">
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">Progress</p>
                    <div class="flex justify-between text-sm font-bold mb-2" style="color: var(--ink)">
                        <span>Tindak lanjut</span><span x-text="prog+'%'" style="color: var(--accent)"></span>
                    </div>
                    <div class="h-3 rounded-full overflow-hidden mb-3" style="background: rgba(127,127,160,0.25)">
                        <div class="h-full rounded-full transition-all duration-300" :style="'width:'+prog+'%; background: linear-gradient(90deg, var(--accent), #3b82f6)'"></div>
                    </div>
                    <input type="range" min="0" max="100" x-model.number="prog" class="m3 mb-4" aria-label="Progress">
                    <div class="flex items-center gap-3 glass-subtle r-md px-4 py-3">
                        <div x-show="loadingDemo" class="w-5 h-5 rounded-full border-[3px] spin flex-shrink-0" style="border-color: var(--accent-soft); border-top-color: var(--accent)"></div>
                        <span class="text-xs font-bold" style="color: var(--ink)" x-text="loadingDemo ? 'Memuat data...' : 'Idle'"></span>
                        <div class="switch ml-auto" role="switch" :aria-checked="loadingDemo" @click="loadingDemo=!loadingDemo"></div>
                    </div>
                </div>
                <div class="glass r-lg p-6">
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--ink-soft)">Avatar grup</p>
                    <div class="flex items-center mb-4">
                        <template x-for="(a,i) in avatars" :key="i">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-extrabold text-white border-2 -ml-2 first:ml-0" :style="'background:'+a.c+'; border-color: var(--glass-bg-strong)'" x-text="a.i" :title="a.n"></div>
                        </template>
                        <span class="ml-2 text-xs font-bold" style="color: var(--ink-soft)">+12 wali kelas</span>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--ink-soft)">Skeleton loading</p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold" style="color: var(--ink)">Tampilkan skeleton</span>
                        <div class="switch" role="switch" :aria-checked="skel" @click="skel=!skel"></div>
                    </div>
                    <div x-show="skel" class="mt-3 space-y-2">
                        <div class="h-3 rounded-full pulse-dot" style="background: rgba(127,127,160,0.30); width: 90%"></div>
                        <div class="h-3 rounded-full pulse-dot" style="background: rgba(127,127,160,0.30); width: 65%"></div>
                        <div class="h-3 rounded-full pulse-dot" style="background: rgba(127,127,160,0.30); width: 78%"></div>
                    </div>
                    <div x-show="!skel" class="mt-3 text-sm font-semibold" style="color: var(--ink)">Data dimuat: 1.248 siswa aktif.</div>
                </div>
                <div class="glass r-lg p-6 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--ink-soft)">Stat kartu</p>
                        <div class="switch" role="switch" :aria-checked="showTrend" @click="showTrend=!showTrend" title="Tampilkan tren"></div>
                    </div>
                    <p class="text-4xl font-extrabold" style="color: var(--accent)">87</p>
                    <p class="text-sm font-semibold" style="color: var(--ink)">Pelanggaran bulan ini</p>
                    <p x-show="showTrend" class="text-xs font-bold mt-2 inline-block px-3 py-1 pill" style="background: var(--success-soft); color: var(--success)">▼ 12% vs bulan lalu</p>
                </div>
            </div>
        </section>

        <footer class="text-center pt-4 pb-8">
            <p class="text-xs" style="color: var(--ink-soft)">Glass Lab — semua komponen interaktif via Alpine.js • Cek di 375px / 768px / 1024px / 1440px</p>
        </footer>
    </main>

    <!-- ================= MODAL ================= -->
    <div x-show="modal.open" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 modal-overlay"
        @click.self="modal.open=false" @keydown.escape.window="modal.open=false" role="dialog" aria-modal="true">
        <div x-show="modal.open"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="glass-strong p-6 sm:p-8 w-full" :class="modalSize==='sm'?'max-w-sm r-md':modalSize==='lg'?'max-w-2xl r-lg':'max-w-md r-lg'" @click.stop>
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0" :style="'background:'+modalCfg().soft">
                    <span x-html="modalCfg().icon"></span>
                </div>
                <h3 class="text-lg font-extrabold" style="color: var(--ink)" x-text="modalCfg().title"></h3>
            </div>
            <p class="text-sm leading-relaxed mb-6" style="color: var(--ink-soft)" x-text="modalCfg().desc"></p>
            <div class="flex gap-2 justify-end flex-wrap">
                <button @click="modal.open=false" class="press pill px-5 py-2.5 text-sm font-bold glass-subtle cursor-pointer" style="color: var(--ink-soft)">Batal</button>
                <button @click="confirmModal()" class="press pill px-5 py-2.5 text-sm font-bold text-white cursor-pointer" :style="'background:'+modalCfg().color" x-text="modalCfg().cta"></button>
            </div>
        </div>
    </div>

    <!-- ================= TOASTS ================= -->
    <div class="fixed bottom-4 right-4 z-[110] flex flex-col gap-2 w-[calc(100%-2rem)] max-w-sm" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="toast-in glass-strong r-md px-4 py-3 flex items-start gap-3">
                <span class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0" :style="'background:'+toastColor(t.type)"></span>
                <p class="text-sm font-semibold flex-1" style="color: var(--ink)" x-text="t.msg"></p>
                <button @click="dismissToast(t.id)" class="font-bold cursor-pointer" style="color: var(--ink-soft)" aria-label="Tutup notifikasi">✕</button>
            </div>
        </template>
    </div>

<script>
function glassLab() {
    const ICONS = {
        wifi: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>',
        bt: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
        plane: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>',
        moon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
        lamp: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
        bell: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>'
    };
    return {
        /* global */
        dark: false, anim: true, blur: 18, radius: 28, bg: 'candy',
        bgs: [{v:'candy',l:'Candy'},{v:'sunset',l:'Sunset'},{v:'ocean',l:'Ocean'},{v:'plain',l:'Polos'}],
        activeSection: 'tombol',
        sections: [
            {id:'tombol',l:'Tombol'},{id:'chip',l:'Chip'},{id:'form',l:'Form'},
            {id:'qs',l:'Quick Settings'},{id:'tabel',l:'Tabel'},{id:'overlay',l:'Modal & Toast'},
            {id:'navigasi',l:'Tab & Akordeon'},{id:'extra',l:'Extra'}
        ],
        init() {
            const t = localStorage.getItem('glasslab-theme');
            this.dark = t ? t==='dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.style.setProperty('--blur-px', this.blur+'px');
            document.documentElement.style.setProperty('--radius-lg', this.radius+'px');
            document.documentElement.style.setProperty('--radius-md', Math.max(12,this.radius-8)+'px');
            this.$watch('blur', v => document.documentElement.style.setProperty('--blur-px', v+'px'));
            this.$watch('radius', v => {
                document.documentElement.style.setProperty('--radius-lg', v+'px');
                document.documentElement.style.setProperty('--radius-md', Math.max(12,v-8)+'px');
            });
            /* scrollspy tanpa plugin: tandai seksi terdekat */
            const spy = () => {
                const ids = this.sections.map(s=>s.id);
                let cur = ids[0];
                for (const id of ids) {
                    const el = document.getElementById(id);
                    if (el && el.getBoundingClientRect().top < 220) cur = id;
                }
                this.activeSection = cur;
            };
            window.addEventListener('scroll', spy, { passive: true });
        },
        toggleDark() { this.dark = !this.dark; localStorage.setItem('glasslab-theme', this.dark?'dark':'light'); },
        resetGlobal() { this.dark=false; this.anim=true; this.blur=18; this.radius=28; this.bg='candy'; localStorage.removeItem('glasslab-theme'); },

        /* tombol */
        btn: { label:'Simpan Data', variant:'primer', size:'px-6 py-3 text-sm', rounded:'pill', icon:true, loading:false, disabled:false, block:false },
        btnVariants: [{v:'primer',l:'Primer'},{v:'sekunder',l:'Sekunder'},{v:'sukses',l:'Sukses'},{v:'bahaya',l:'Bahaya'},{v:'hantu',l:'Hantu'}],
        btnSizes: [{v:'px-4 py-2 text-xs',l:'S'},{v:'px-6 py-3 text-sm',l:'M'},{v:'px-8 py-4 text-base',l:'L'}],
        variantStyle(v) {
            const m = {
                primer:'background: var(--accent); color:#fff; border:1px solid var(--accent)',
                sekunder:'background: var(--glass-bg-subtle); color: var(--accent); border:1px solid var(--glass-border)',
                sukses:'background: var(--success); color:#fff; border:1px solid var(--success)',
                bahaya:'background: var(--danger); color:#fff; border:1px solid var(--danger)',
                hantu:'background: transparent; color: var(--ink-soft); border:1px dashed var(--ink-soft)'
            };
            return m[v] || m.primer;
        },
        btnStyle() {
            let s = this.variantStyle(this.btn.variant);
            if (this.btn.disabled || this.btn.loading) s += '; opacity:.55; cursor:not-allowed';
            return s;
        },

        /* chip */
        chips: ['Semua','Ringan','Sedang','Berat'], chipActive: 'Semua', newChip: '',
        tags: ['Terlambat','Bolos','Seragam'], newTag: '',
        addChip() { const v=this.newChip.trim(); if(v && !this.chips.includes(v)){ this.chips.push(v); this.chipActive=v; } this.newChip=''; },
        addTag() { const v=this.newTag.trim(); if(v && !this.tags.includes(v)) this.tags.push(v); this.newTag=''; },
        removeTag(t) { this.tags = this.tags.filter(x=>x!==t); },
        resetTags() { this.tags = ['Terlambat','Bolos','Seragam']; },

        /* form */
        form: { nama:'', kelas:'', pw:'', showPw:false, note:'', tingkat:'Sedang', wali:true, konseling:false, setuju:false },
        pwScore() { const p=this.form.pw; let s=0; if(p.length>=4)s+=25; if(p.length>=8)s+=25; if(/[A-Z]/.test(p)&&/[a-z]/.test(p))s+=25; if(/\d/.test(p)&&/[^A-Za-z0-9]/.test(p))s+=25; return s; },
        pwLabel() { const s=this.pwScore(); return s>66?'Kuat':s>33?'Sedang':this.form.pw?'Lemah':'-'; },
        formSummary() { return 'nama: '+(this.form.nama||'-')+'\nkelas: '+(this.form.kelas||'-')+'\ntingkat: '+this.form.tingkat+'\nwali: '+(this.form.wali?'ya':'tidak')+'\nkonseling: '+(this.form.konseling?'ya':'tidak')+'\nsetuju: '+(this.form.setuju?'ya':'tidak'); },
        submitForm() {
            if (this.form.nama.trim().length < 3) { this.pushToast('Nama minimal 3 karakter','danger'); return; }
            if (!this.form.setuju) { this.pushToast('Centang dulu "Data sudah benar"','warning'); return; }
            this.pushToast('Form '+this.form.nama+' tersimpan (demo)','success'); this.resetForm();
        },
        resetForm() { this.form = { nama:'', kelas:'', pw:'', showPw:false, note:'', tingkat:'Sedang', wali:true, konseling:false, setuju:false }; },

        /* QS */
        qs: [
            {id:1,label:'Wi-Fi',on:true,icon:ICONS.wifi},{id:2,label:'Bluetooth',on:false,icon:ICONS.bt},
            {id:3,label:'Pesawat',on:false,icon:ICONS.plane},{id:4,label:'Diam',on:true,icon:ICONS.moon},
            {id:5,label:'Senter',on:false,icon:ICONS.lamp},{id:6,label:'Notifikasi',on:true,icon:ICONS.bell}
        ],
        brightness: 65,
        qsOn() { return this.qs.filter(q=>q.on); },

        /* tabel */
        q:'', fStatus:'Semua', sortKey:'nama', sortDir:1, page:1, perPage:5, selected:[],
        rows: [
            {id:1,nama:'Ahmad Rizki',kelas:'X TKJ 1',jenis:'Terlambat',status:'Proses'},
            {id:2,nama:'Siti Nurhaliza',kelas:'XI TKJ 1',jenis:'Bolos',status:'Berat'},
            {id:3,nama:'Budi Santoso',kelas:'XII TKJ 1',jenis:'Seragam',status:'Selesai'},
            {id:4,nama:'Dewi Lestari',kelas:'X TKJ 2',jenis:'Terlambat',status:'Proses'},
            {id:5,nama:'Rudi Hartono',kelas:'XI TKJ 2',jenis:'HP di kelas',status:'Proses'},
            {id:6,nama:'Maya Putri',kelas:'X TKJ 1',jenis:'Bolos',status:'Selesai'},
            {id:7,nama:'Fajar Nugroho',kelas:'XII TKJ 1',jenis:'Merokok',status:'Berat'},
            {id:8,nama:'Intan Permata',kelas:'XI TKJ 1',jenis:'Seragam',status:'Selesai'}
        ],
        nNama:'', nKelas:'', nJenis:'',
        statusStyle(s) {
            if (s==='Selesai') return 'background: var(--success-soft); color: var(--success)';
            if (s==='Berat') return 'background: var(--danger-soft); color: var(--danger)';
            return 'background: var(--warning-soft); color: var(--warning)';
        },
        cycleStatus(r) { r.status = r.status==='Proses' ? 'Selesai' : r.status==='Selesai' ? 'Berat' : 'Proses'; },
        sortBy(k) { if(this.sortKey===k){ this.sortDir*=-1; } else { this.sortKey=k; this.sortDir=1; } },
        sortIcon(k) { return this.sortKey!==k ? '↕' : this.sortDir===1 ? '↑' : '↓'; },
        filtered() {
            const q = this.q.toLowerCase().trim();
            let out = this.rows.filter(r => {
                const matchQ = !q || (r.nama+' '+r.kelas+' '+r.jenis).toLowerCase().includes(q);
                const matchF = this.fStatus==='Semua' || r.status===this.fStatus;
                return matchQ && matchF;
            });
            const k = this.sortKey, d = this.sortDir;
            return out.slice().sort((a,b) => String(a[k]).localeCompare(String(b[k]), 'id') * d);
        },
        maxPage() { return Math.max(1, Math.ceil(this.filtered().length / this.perPage)); },
        pages() { return Array.from({length:this.maxPage()}, (_,i)=>i+1); },
        paged() {
            if (this.page > this.maxPage()) this.page = this.maxPage();
            const s = (this.page-1)*this.perPage;
            return this.filtered().slice(s, s+this.perPage);
        },
        pageInfo() {
            const n = this.filtered().length;
            if (!n) return '0 data';
            const s = (this.page-1)*this.perPage+1, e = Math.min(n, this.page*this.perPage);
            return s+'–'+e+' dari '+n+' data';
        },
        prevPage() { if(this.page>1) this.page--; },
        nextPage() { if(this.page<this.maxPage()) this.page++; },
        allChecked() { const p=this.paged(); return p.length>0 && p.every(r=>this.selected.includes(r.id)); },
        toggleAll(v) {
            const ids = this.paged().map(r=>r.id);
            this.selected = v ? [...new Set([...this.selected, ...ids])] : this.selected.filter(id=>!ids.includes(id));
        },
        delRow(id) { this.rows = this.rows.filter(r=>r.id!==id); this.selected = this.selected.filter(s=>s!==id); this.pushToast('1 baris dihapus','info'); },
        bulkDelete() { this.rows = this.rows.filter(r=>!this.selected.includes(r.id)); this.pushToast(this.selected.length+' baris dihapus','danger'); this.selected=[]; this.page=1; },
        bulkStatus(s) { this.rows.forEach(r=>{ if(this.selected.includes(r.id)) r.status=s; }); this.pushToast(this.selected.length+' baris → '+s,'success'); this.selected=[]; },
        addRow() {
            if (!this.nNama.trim()) { this.pushToast('Isi nama dulu','warning'); return; }
            const id = Math.max(0,...this.rows.map(r=>r.id))+1;
            this.rows.unshift({id, nama:this.nNama.trim(), kelas:this.nKelas.trim()||'-', jenis:this.nJenis.trim()||'-', status:'Proses'});
            this.nNama=''; this.nKelas=''; this.nJenis=''; this.page=1;
            this.pushToast('Baris baru ditambah','success');
        },

        /* overlay */
        modal: { open:false, type:'info' }, modalSize:'md', showAlert:true,
        modalTypes: {
            info: { title:'Informasi', desc:'Ini contoh modal info bergaya kaca. Backdrop ikut blur sehingga konteks halaman tetap terlihat.', cta:'Mengerti', color:'var(--accent)', soft:'var(--accent-soft)', icon:'<svg class="w-5 h-5" style="color:var(--accent)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            confirm: { title:'Konfirmasi', desc:'Apakah kamu yakin ingin menyimpan perubahan ini? Tindakan ini tercatat di log.', cta:'Ya, simpan', color:'var(--success)', soft:'var(--success-soft)', icon:'<svg class="w-5 h-5" style="color:var(--success)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' },
            danger: { title:'Hapus data?', desc:'Data yang dihapus tidak bisa dikembalikan. Pastikan kamu sudah yakin.', cta:'Ya, hapus', color:'var(--danger)', soft:'var(--danger-soft)', icon:'<svg class="w-5 h-5" style="color:var(--danger)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' }
        },
        modalCfg() { return this.modalTypes[this.modal.type]; },
        openModal(t) { this.modal.type=t; this.modal.open=true; },
        confirmModal() { this.modal.open=false; this.pushToast('Aksi "'+this.modalCfg().cta+'" dijalankan','success'); },
        toasts: [], toastId: 0,
        pushToast(msg, type='info') {
            const id = ++this.toastId;
            this.toasts.push({id, msg, type});
            setTimeout(()=>this.dismissToast(id), 3200);
        },
        dismissToast(id) { this.toasts = this.toasts.filter(t=>t.id!==id); },
        toastColor(t) { return t==='success'?'var(--success)':t==='warning'?'var(--warning)':t==='danger'?'var(--danger)':'var(--accent)'; },

        /* navigasi */
        tab:'Ringkasan', tabs:['Ringkasan','Tren','Kelas'],
        accMulti:false,
        acc: [
            {t:'Apa itu Glass Lab?', c:'Halaman uji berisi semua komponen glass yang dipakai aplikasi, lengkap dengan kontrol interaktif.', open:true},
            {t:'Apakah bisa dipakai di halaman asli?', c:'Ya. Token CSS (blur, radius, warna) yang dipakai di sini sama persis dengan yang akan dipakai di layout app.', open:false},
            {t:'Bagaimana mode gelap bekerja?', c:'Toggle di navbar / panel global mengganti class dark dan menyimpan pilihan ke localStorage.', open:false}
        ],
        toggleAcc(i) {
            if (!this.accMulti) this.acc.forEach((a,j)=>{ if(j!==i) a.open=false; });
            this.acc[i].open = !this.acc[i].open;
        },

        /* extra */
        prog: 72, loadingDemo: true, skel: true, showTrend: true,
        avatars: [
            {i:'AR',n:'Ahmad Rizki',c:'#7c3aed'},{i:'SN',n:'Siti Nurhaliza',c:'#059669'},
            {i:'BS',n:'Budi Santoso',c:'#d97706'},{i:'DL',n:'Dewi Lestari',c:'#dc2626'},{i:'RH',n:'Rudi Hartono',c:'#2563eb'}
        ]
    };
}
</script>
</body>
</html>
