@extends('layouts.app', ['title' => 'Sign in'])

@section('content')
    <main class="page auth-shell">
        <section class="form-panel">
            <span class="eyebrow">Internal access</span>
            <h1 class="page-title">Sign in to the quotes workspace</h1>
            <p class="page-copy">
                This first release keeps pricing logic transparent, revisioned, and separate from the spreadsheet.
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
                    <input id="password" name="password" type="password" required autocomplete="current-password">
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
