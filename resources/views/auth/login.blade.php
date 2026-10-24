<x-guest-layout>
    @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label class="field-label" for="email">Email</label>
            <input id="email" class="field-input" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" placeholder="nama@sekolah.sch.id">
        </div>

        <div class="field">
            <label class="field-label" for="password">Kata Sandi</label>
            <input id="password" class="field-input" type="password" name="password" required
                   autocomplete="current-password" placeholder="Masukkan kata sandi">
        </div>

        <label class="check-row" for="remember_me">
            <input id="remember_me" type="checkbox" name="remember" value="1">
            Ingat saya
        </label>

        <button type="submit" class="btn">Masuk</button>

        @if (Route::has('password.request'))
            <div class="help-link">
                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
            </div>
        @endif
    </form>
</x-guest-layout>
