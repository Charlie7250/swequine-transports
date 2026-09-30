@extends('layouts.app', ['title' => 'Sign in'])

@section('content')
    <main class="page auth-shell">
        <div class="auth-brand">
            <x-brand-logo tone="gold-deep" :width="250" />
        </div>

        <section class="form-panel">
            <span class="eyebrow">Internal access</span>
            <h1 class="page-title">Sign in to the quotes workspace</h1>
            <p class="page-copy">
                Access transparent, revisioned pricing and your transport enquiries.
            </p>

            @if ($errors->any())
                <div class="error-banner">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="form-grid" method="POST" action="/login">
                @csrf

                <label for="email">
                    Email address
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                </label>

                <label for="password">
                    Password
                    <span class="password-field">
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                        <button class="password-toggle" type="button" aria-controls="password" aria-label="Show password"
                            onclick="const f=document.getElementById('password');const show=f.type==='password';f.type=show?'text':'password';this.textContent=show?'Hide':'Show';this.setAttribute('aria-label',show?'Hide password':'Show password');">Show</button>
                    </span>
                </label>

                <label class="inline-row" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1">
                    Keep me signed in on this device
                </label>

                <button class="button button-primary" type="submit">Sign in</button>
            </form>
        </section>
    </main>
@endsection
