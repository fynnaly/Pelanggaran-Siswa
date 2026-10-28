<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Pengaturan Akun</h1>
                <p class="text-muted text-sm">Kelola profil pribadi, kata sandi, dan keamanan akun</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap" x-data="{ activeTab: '{{ $errors->userDeletion->isNotEmpty() ? 'danger' : 'account' }}' }">
        <div class="profile-layout" style="display:flex;gap:var(--sp-lg);max-width:960px;margin:0 auto;align-items:flex-start">
            <!-- Sidebar Navigasi Kategori Profil -->
            <div class="profile-sidebar" style="width:240px;flex-shrink:0">
                <div class="card" style="padding:var(--sp-sm)">
                    <div style="display:flex;flex-direction:column;gap:4px">
                        <button type="button" 
                                @click="activeTab = 'account'" 
                                class="profile-tab-btn" 
                                :class="activeTab === 'account' ? 'is-active' : ''"
                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--r-sm);border:none;cursor:pointer;font-family:inherit;font-size:.875rem;width:100%;text-align:left;transition:background .15s">
                            <i data-lucide="user" class="icon-sm" style="flex-shrink:0"></i>
                            <span style="font-weight:500">Informasi Akun</span>
                        </button>

                        <div style="height:1px;background:var(--border);margin:4px 0"></div>

                        <button type="button" 
                                @click="activeTab = 'danger'" 
                                class="profile-tab-btn tab-danger" 
                                :class="activeTab === 'danger' ? 'is-active-danger' : ''"
                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--r-sm);border:none;cursor:pointer;font-family:inherit;font-size:.875rem;width:100%;text-align:left;transition:background .15s">
                            <i data-lucide="alert-triangle" class="icon-sm" style="flex-shrink:0"></i>
                            <span style="font-weight:500">Zona Bahaya</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel Konten Kategori -->
            <div class="profile-content" style="flex:1;min-width:0">
                <!-- Tab: Informasi Akun (Profil + Kata Sandi) -->
                <div x-show="activeTab === 'account'" x-cloak class="card">
                    <!-- Ringkasan Akun -->
                    <div style="display:flex;align-items:center;gap:var(--sp-md);padding-bottom:var(--sp-lg);border-bottom:1px solid var(--border);margin-bottom:var(--sp-lg)">
                        <div style="width:56px;height:56px;border-radius:var(--r-full);overflow:hidden;background:var(--surface);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.25rem;color:var(--tertiary);flex-shrink:0">
                            @if($user->avatar_path)
                                <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover">
                            @else
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </div>
                        <div style="flex:1">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <h2 style="font-size:1.125rem;font-weight:600;margin:0">{{ $user->name }}</h2>
                                @if(method_exists($user, 'getRoleNames') && $user->getRoleNames()->isNotEmpty())
                                    <span class="badge badge-info" style="font-size:.75rem">{{ ucfirst($user->getRoleNames()->first()) }}</span>
                                @endif
                                @php
                                    $activeClass = method_exists($user, 'activeHomeroomClass') ? $user->activeHomeroomClass() : null;
                                @endphp
                                @if($activeClass)
                                    <span class="badge badge-success" style="font-size:.75rem">Wali Kelas {{ $activeClass->name }}</span>
                                @endif
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;margin-top:2px;flex-wrap:wrap">
                                <span class="text-sm text-muted">{{ $user->email }}</span>
                                @if($user->nip)
                                    <span class="text-sm text-muted">&bull; NIP: {{ $user->nip }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Form Informasi Profil -->
                    @include('profile.partials.update-profile-information-form')

                    <div style="height:1px;background:var(--border);margin:var(--sp-xl) 0 var(--sp-lg)"></div>

                    <!-- Form Ubah Kata Sandi -->
                    @include('profile.partials.update-password-form')
                </div>

                <!-- Tab: Zona Bahaya -->
                <div x-show="activeTab === 'danger'" x-cloak class="card" style="border-color:var(--ds)">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-content > .card { margin-top: 0 !important; }
        .profile-tab-btn { background: transparent; color: var(--on-surface-muted); }
        .profile-tab-btn:hover { background: var(--surface); color: var(--on-surface); }
        .profile-tab-btn.is-active { background: var(--surface); color: var(--tertiary); font-weight: 600 !important; }
        .profile-tab-btn.tab-danger { color: var(--danger); }
        .profile-tab-btn.tab-danger:hover { background: var(--ds); color: var(--dt); }
        .profile-tab-btn.is-active-danger { background: var(--ds) !important; color: var(--dt) !important; font-weight: 600 !important; }

        @media (max-width: 767px) {
            .profile-layout { flex-direction: column !important; }
            .profile-sidebar { width: 100% !important; }
            .profile-sidebar .card > div { flex-direction: row !important; overflow-x: auto; padding-bottom: 2px; }
            .profile-tab-btn { white-space: nowrap; width: auto !important; }
        }
    </style>
    <script>document.addEventListener('DOMContentLoaded', () => { lucide.createIcons(); });</script>
</x-app-layout>
