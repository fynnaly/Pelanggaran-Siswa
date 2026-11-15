<section>
    <header>
        <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:4px;color:var(--danger)">Zona Bahaya: Hapus Akun</h2>
        <p class="text-sm text-muted">Pengajuan penghapusan ditinjau pengelola sekolah. Akun tetap aktif sampai permintaan disetujui.</p>
    </header>

    @php
        $userName = $user?->name ?? auth()->user()?->name ?? '';
    @endphp

    @if($user->deletion_requested_at)
        <div class="alert alert-warning" style="margin-top:var(--sp-md)">
            Permintaan penghapusan menunggu persetujuan oleh Admin.
        </div>
    @else
        <div x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }}, typedName: '' }" x-init="$nextTick(() => { if (window.lucide) lucide.createIcons(); })" style="margin-top:var(--sp-md)">
            <button type="button" class="btn btn-danger btn-sm" @click="open = true; $nextTick(() => { if (window.lucide) lucide.createIcons(); })">
                <i data-lucide="trash-2" class="icon-sm"></i> Ajukan Penghapusan
            </button>

            <template x-teleport="body">
                <div x-show="open" x-cloak class="modal-overlay" style="display:none">
                    <div style="position:fixed;inset:0;z-index:1" @click="open = false"></div>
                    <div x-transition style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-lg);padding:var(--sp-lg);max-width:500px;width:100%;position:relative;z-index:2;box-shadow:var(--sh-md)">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:var(--sp-sm)">
                            <div style="width:36px;height:36px;border-radius:var(--r-full);background:var(--ds);color:var(--dt);display:flex;align-items:center;justify-content:center">
                                <i data-lucide="alert-triangle" class="icon-sm"></i>
                            </div>
                            <div>
                                <h3 style="font-size:1.125rem;font-weight:600;margin:0">Konfirmasi Pengajuan Penghapusan</h3>
                                <span class="text-xs text-muted">Akun menunggu keputusan pengelola</span>
                            </div>
                        </div>

                        <div class="alert alert-warning" style="margin:var(--sp-md) 0;font-size:.8125rem;line-height:1.5">
                            Akun Anda tetap aktif sampai pengelola menyetujui permintaan ini.
                        </div>

                        <form method="post" action="{{ route('profile.destroy') }}" style="display:flex;flex-direction:column;gap:var(--sp-md)">
                            @csrf
                            @method('delete')

                            <div class="field">
                                <label class="field-label" style="font-size:.8125rem">
                                    Ketik <code style="background:var(--surface);border:1px solid var(--border);padding:2px 6px;border-radius:4px;user-select:all;color:var(--danger);font-weight:600">{{ $userName }}</code> untuk melanjutkan pengajuan:
                                </label>
                                <input type="text" name="confirm_name" x-model="typedName" class="field-input" placeholder="Ketik nama lengkap persis" autocomplete="off">
                                @error('userDeletion.confirm_name')
                                    <span class="help-text" style="color:var(--danger)">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="field">
                                <label class="field-label" for="delete_password" style="font-size:.8125rem">Kata Sandi Anda</label>
                                <input id="delete_password" name="password" type="password" class="field-input" placeholder="Masukkan kata sandi saat ini">
                                @error('userDeletion.password')
                                    <span class="help-text" style="color:var(--danger)">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display:flex;justify-content:flex-end;gap:var(--sp-sm);margin-top:var(--sp-sm)">
                                <button type="button" class="btn btn-secondary btn-sm" @click="open = false; typedName = ''">Batal</button>
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        :disabled="typedName.trim() !== @js($userName)"
                                        :style="typedName.trim() !== @js($userName) ? 'opacity:0.5;cursor:not-allowed' : ''">
                                    <i data-lucide="trash-2" class="icon-sm"></i> Kirim Permintaan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    @endif
</section>
