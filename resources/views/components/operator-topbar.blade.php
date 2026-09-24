@php
    $profileName = trim((string) auth()->user()?->name);
@endphp

<header class="operator-topbar">
    <details class="operator-mobile-nav">
        <summary aria-label="Open navigation"><x-prototype-icon name="menu" /></summary>
        <div class="operator-mobile-panel">
            <x-operator-sidebar />
        </div>
    </details>

    <span class="operator-topbar-label">Operations</span>
    <div class="operator-profile">
        <span class="operator-avatar">{{ $profileName === '' ? 'SW' : strtoupper(substr($profileName, 0, 1)) }}</span>
        @if ($profileName !== '')
            <span>{{ $profileName }}</span>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="operator-sign-out" type="submit">Sign out</button>
        </form>
    </div>
</header>
