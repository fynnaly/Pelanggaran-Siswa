<section>
    <header>
        <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:4px">Ubah Kata Sandi</h2>
        <p class="text-sm text-muted">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak untuk tetap aman.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" style="margin-top:var(--sp-lg);display:flex;flex-direction:column;gap:var(--sp-lg)">
        @csrf
        @method('put')

        <div class="field">
            <label class="field-label" for="update_password_current_password">Kata Sandi Saat Ini</label>
            <input id="update_password_current_password" name="current_password" type="password" class="field-input" autocomplete="current-password">
            @error('updatePassword.current_password')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label class="field-label" for="update_password_password">Kata Sandi Baru</label>
            <input id="update_password_password" name="password" type="password" class="field-input" autocomplete="new-password">
            @error('updatePassword.password')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label class="field-label" for="update_password_password_confirmation">Konfirmasi Kata Sandi</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="field-input" autocomplete="new-password">
            @error('updatePassword.password_confirmation')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-muted">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
