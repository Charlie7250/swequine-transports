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

    {{-- Search and notifications are approved future features (see docs/product/proposed-enhancements.md);
         rendered here as visual-only placeholders so the shell matches the approved design. --}}
    <div class="operator-topbar-search" aria-hidden="true">
        <x-prototype-icon name="search" />
        <input type="text" placeholder="Search jobs, customers, postcodes…" disabled>
    </div>

    <div class="operator-profile">
        <button class="operator-bell" type="button" title="Notifications (coming soon)" aria-label="Notifications (coming soon)" disabled>
            <x-prototype-icon name="bell" />
            <span class="operator-bell-count">3</span>
        </button>
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
