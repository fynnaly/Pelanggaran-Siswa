<x-guest-layout>
    @if($errors->any())
        <div class="alert-error" role="alert">
            <i data-lucide="alert-circle" class="alert-icon"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label class="field-label" for="email">Email</label>
            <input id="email" class="field-input" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" placeholder="nama@sekolah.sch.id">
        </div>

        <div class="field">
            <label class="field-label" for="password">Kata sandi</label>
            <div class="password-box">
                <input id="password" class="field-input" type="password" name="password" required
                       autocomplete="current-password" placeholder="Masukkan kata sandi">
                <button type="button" class="toggle-pwd-btn" onclick="togglePassword()" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi" tabindex="-1">
                    <i data-lucide="eye" id="eyeIcon" class="icon-sm"></i>
                </button>
            </div>
        </div>

        <div class="options-row">
            <label class="remember-label" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember" value="1">
                <span>Ingat saya</span>
            </label>
        </div>

        <button type="submit" class="btn-primary">Masuk</button>
    </form>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            const btn = icon.parentElement;
            if (!input || !icon) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            btn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            btn.setAttribute('title', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            if (window.lucide) lucide.createIcons();
        }
    </script>
</x-guest-layout>
