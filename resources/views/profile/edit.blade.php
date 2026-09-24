<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Pengaturan Profil</h1></div>
        </div>
    </x-slot>

    <div class="main-wrap">
        <div class="card" style="max-width:640px;margin-bottom:var(--sp-lg)">
            <h2 style="margin-bottom:var(--sp-lg)">Informasi Profil</h2>
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card" style="max-width:640px;margin-bottom:var(--sp-lg)">
            <h2 style="margin-bottom:var(--sp-lg)">Ubah Kata Sandi</h2>
            @include('profile.partials.update-password-form')
        </div>

        <div class="card" style="max-width:640px">
            <h2 style="margin-bottom:var(--sp-lg);color:var(--danger)">Hapus Akun</h2>
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
