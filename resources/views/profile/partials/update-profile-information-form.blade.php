<section>
    @php($isStudent = $user->hasRole('siswa'))

    <header>
        <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:4px">Informasi Profil</h2>
        <p class="text-sm text-muted">{{ $isStudent ? 'Data profil Anda dikelola oleh petugas sekolah.' : 'Perbarui data identitas diri, foto profil, dan alamat email Anda.' }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    @if($isStudent)
        <div style="margin-top:var(--sp-lg);display:flex;flex-direction:column;gap:var(--sp-md)">
    @else
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" style="margin-top:var(--sp-lg);display:flex;flex-direction:column;gap:var(--sp-md)">
            @csrf
            @method('patch')
    @endif

        <div style="display:flex;align-items:center;gap:var(--sp-md);padding-bottom:var(--sp-md);border-bottom:1px solid var(--border)">
            <div style="width:64px;height:64px;border-radius:var(--r-full);overflow:hidden;background:var(--surface);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover">
            </div>
            <div style="flex:1">
                @if($isStudent)
                    <span class="field-label">Foto Profil</span>
                    <div class="help-text" style="margin-top:4px">Foto profil siswa diatur oleh petugas sekolah.</div>
                @else
                    <label class="field-label" for="avatar">Foto Profil</label>
                    <input id="avatar" name="avatar" type="file" accept="image/*" class="field-input" style="font-size:.8125rem;padding:6px 10px">
                    <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
                        <span class="help-text">Format JPG/PNG/WebP, maks. 50MB</span>
                        @if($user->avatar_path)
                            <button type="submit" name="remove_avatar" value="1" aria-label="Hapus foto profil" class="help-text" style="display:inline-flex;align-items:center;gap:4px;cursor:pointer;color:var(--danger);background:none;border:0;padding:0;font:inherit">
                                <i data-lucide="trash-2" class="icon-sm" aria-hidden="true"></i>
                                <span>Hapus foto</span>
                            </button>
                        @endif
                    </div>
                    @error('avatar')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
                @endif
            </div>
        </div>

        <div class="field">
            <label class="field-label" for="name">Nama Lengkap</label>
            <input id="name" name="name" type="text" class="field-input {{ $isStudent ? 'cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500' : '' }}" value="{{ old('name', $user->name) }}" {{ $isStudent ? 'disabled' : 'required autofocus' }} autocomplete="name" style="{{ $isStudent ? 'background:var(--surface);color:var(--on-surface-muted);cursor:not-allowed' : '' }}">
            @if($isStudent)<span class="help-text">Perubahan nama hanya dapat dilakukan oleh petugas sekolah.</span>@endif
            @error('name')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        @unless($isStudent)
            <div class="field">
                <label class="field-label" for="nip">Nomor Induk Pegawai (NIP)</label>
                <input id="nip" name="nip" type="text" class="field-input" value="{{ old('nip', $user->nip) }}" placeholder="Contoh: 198001012005011001 (opsional)">
                <span class="help-text">Digunakan untuk keperluan tanda tangan dokumen penanganan dan rekap</span>
                @error('nip')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
            </div>
        @endunless

        <div class="field">
            <label class="field-label" for="email">Alamat Email</label>
            <input id="email" name="email" type="email" class="field-input {{ $isStudent ? 'cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500' : '' }}" value="{{ old('email', $user->email) }}" {{ $isStudent ? 'disabled' : 'required' }} autocomplete="username" style="{{ $isStudent ? 'background:var(--surface);color:var(--on-surface-muted);cursor:not-allowed' : '' }}">
            @if($isStudent)<span class="help-text">Perubahan email hanya dapat dilakukan oleh petugas sekolah.</span>@endif
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

        @unless($isStudent)
            <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-xs)">
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="save" class="icon-sm"></i> Simpan Profil</button>
                @if (session('status') === 'profile-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-muted">Tersimpan.</p>
                @endif
            </div>
        @endunless

    @if($isStudent)
        </div>
    @else
        </form>
    @endif
</section>
