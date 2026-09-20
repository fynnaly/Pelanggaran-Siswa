<section>
    <header>
        <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:4px;color:var(--danger)">Hapus Akun</h2>
        <p class="text-sm text-muted">Setelah akun dihapus, semua data akan dihapus permanen. Unduh data yang ingin Anda pertahankan sebelum menghapus.</p>
    </header>

    <div x-data="{ open: false }" style="margin-top:var(--sp-md)">
        <button type="button" class="btn btn-danger btn-sm" @click="open = true">Hapus Akun</button>

        <div x-show="open" x-cloak style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center">
            <div x-show="open" style="position:fixed;inset:0;background:rgba(0,0,0,.5)" @click="open = false"></div>
            <div x-show="open" x-transition style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-lg);padding:var(--sp-lg);max-width:480px;width:90%;position:relative;z-index:101">
                <h3 style="font-size:1.125rem;font-weight:600;margin-bottom:var(--sp-sm)">Anda yakin ingin menghapus akun?</h3>
                <p class="text-sm text-muted" style="margin-bottom:var(--sp-lg)">Semua data akan dihapus permanen. Masukkan kata sandi untuk konfirmasi.</p>

                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="field">
                        <label class="field-label" for="password">Kata Sandi</label>
                        <input id="password" name="password" type="password" class="field-input" placeholder="Masukkan kata sandi Anda">
                        @error('userDeletion.password')<span class="help-text" style="color:var(--danger)">{{ $message }}</span>@enderror
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:var(--sp-sm)">
                        <button type="button" class="btn btn-secondary btn-sm" @click="open = false">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm">Hapus Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
