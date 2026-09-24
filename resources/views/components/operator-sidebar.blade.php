<aside class="operator-sidebar">
    <a class="operator-brand" href="{{ route('dashboard') }}" aria-label="South West Equine Services dashboard">
        <span class="operator-brand-mark">SW</span>
        <span class="operator-brand-name">South West</span>
        <span class="operator-brand-subtitle">Equine Services</span>
    </a>

    <nav class="operator-nav" aria-label="Primary navigation">
        <div class="operator-nav-section">
            <a class="operator-nav-link {{ request()->routeIs('dashboard') || request()->routeIs('prototype.dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
                <x-prototype-icon name="home" />
                <span>Dashboard</span>
            </a>
            <a class="operator-nav-link {{ request()->routeIs('transport-days.*') ? 'is-active' : '' }}" href="{{ route('transport-days.index') }}">
                <x-prototype-icon name="calendar" />
                <span>Transport days</span>
            </a>
            <a class="operator-nav-link {{ request()->routeIs('loading-practice-quotes.*') ? 'is-active' : '' }}" href="{{ route('loading-practice-quotes.create') }}">
                <x-prototype-icon name="practice" />
                <span>Loading practice</span>
            </a>
        </div>

        <div class="operator-nav-section operator-nav-section--admin">
            <p class="operator-nav-label">Administration</p>
            <a class="operator-nav-link {{ request()->routeIs('admin.weekly-fuel-prices.*') ? 'is-active' : '' }}" href="{{ route('admin.weekly-fuel-prices.index') }}">
                <x-prototype-icon name="fuel" />
                <span>Weekly fuel</span>
            </a>
            <a class="operator-nav-link {{ request()->routeIs('admin.rate-settings.*') ? 'is-active' : '' }}" href="{{ route('admin.rate-settings.index') }}">
                <x-prototype-icon name="settings" />
                <span>Rate settings</span>
            </a>
            <a class="operator-nav-link {{ request()->routeIs('admin.operational-evidence.*') ? 'is-active' : '' }}" href="{{ route('admin.operational-evidence.index') }}">
                <x-prototype-icon name="chart" />
                <span>Operational evidence</span>
            </a>
        </div>
    </nav>

    <p class="operator-sidebar-note">Safe journeys.<br>Happier horses.</p>
</aside>
