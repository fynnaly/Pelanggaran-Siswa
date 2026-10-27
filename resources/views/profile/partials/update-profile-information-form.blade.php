<section>
    <header>
        <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:4px">Informasi Profil</h2>
        <p class="text-sm text-muted">Perbarui data identitas diri, foto profil, dan alamat email Anda.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="margin-top:var(--sp-lg);display:flex;flex-direction:column;gap:var(--sp-md)">
        @csrf
        @method('patch')

        <div style="display:flex;align-items:center;gap:var(--sp-md);padding-bottom:var(--sp-md);border-bottom:1px solid var(--border)">
            <div style="width:64px;height:64px;border-radius:var(--r-full);overflow:hidden;background:var(--surface);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                @if($user->avatar_path)
                    <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover">
                @else
                    <span style="font-size:1.5rem;font-weight:700;color:var(--tertiary)">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
            </div>
            <div style="flex:1">
                <label class="field-label" for="avatar">Foto Profil</label>
                <input id="avatar" name="avatar" type="file" accept="image/*" class="field-input" style="font-size:.8125rem;padding:6px 10px">
                <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
                    <span class="help-text">Format JPG/PNG/WebP, maks. 2MB</span>
                    @if($user->avatar_path)
                        <label class="help-text" style="display:inline-flex;align-items:center;gap:4px;cursor:pointer;color:var(--danger)">
                            <input type="checkbox" name="remove_avatar" value="1"> Hapus foto
                        </label>
                    @endif
                </div>
                @error('avatar')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="field">
            <label class="field-label" for="name">Nama Lengkap</label>
            <input id="name" name="name" type="text" class="field-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label class="field-label" for="nip">Nomor Induk Pegawai (NIP)</label>
            <input id="nip" name="nip" type="text" class="field-input" value="{{ old('nip', $user->nip) }}" placeholder="Contoh: 198001012005011001 (opsional)">
            <span class="help-text">Digunakan untuk keperluan tanda tangan dokumen penanganan dan rekap</span>
            @error('nip')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label class="field-label" for="email">Alamat Email</label>
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

        <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-xs)">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="save" class="icon-sm"></i> Simpan Profil</button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-muted">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
