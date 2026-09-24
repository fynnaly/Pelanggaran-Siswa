<section>
    <header>
        <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:4px">Informasi Profil</h2>
        <p class="text-sm text-muted">Perbarui informasi profil dan alamat email Anda.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" style="margin-top:var(--sp-lg);display:flex;flex-direction:column;gap:var(--sp-lg)">
        @csrf
        @method('patch')

        <div class="field">
            <label class="field-label" for="name">Nama</label>
            <input id="name" name="name" type="text" class="field-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label class="field-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="field-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div style="margin-top:var(--sp-sm)">
                    <p class="text-sm text-muted">
                        Email Anda belum terverifikasi.
                        <button form="send-verification" style="color:var(--tertiary);background:none;border:none;cursor:pointer;text-decoration:underline;font-family:inherit;font-size:.875rem">
                            Kirim ulang email verifikasi.
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="text-sm" style="color:var(--tertiary);margin-top:4px">Link verifikasi baru telah dikirim ke email Anda.</p>
                    @endif
                </div>
            @endif
        </div>

        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-muted">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
