<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'South West Equine Services Quotes' }}</title>
        <style>
            :root {
                color-scheme: light;
                --navy-950: #0f1b2d;
                --navy-900: #16243a;
                --navy-800: #24334d;
                --gold-500: #b78b2f;
                --gold-300: #d8bf78;
                --stone-50: #f7f4ef;
                --stone-100: #ece7de;
                --stone-300: #c9c0b2;
                --ink-900: #1c1b18;
                --ink-700: #4d4a44;
                --danger-600: #a6402e;
                --surface-shadow: 0 18px 42px rgba(15, 27, 45, 0.12);
                font-family: "Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background:
                    radial-gradient(circle at top right, rgba(183, 139, 47, 0.22), transparent 32%),
                    linear-gradient(180deg, #fbfaf7 0%, var(--stone-50) 100%);
                color: var(--ink-900);
                font-family: "Avenir Next", "Segoe UI", sans-serif;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .shell {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }

            .topbar {
                background: linear-gradient(135deg, var(--navy-950), var(--navy-800));
                color: #fffaf0;
                border-bottom: 1px solid rgba(216, 191, 120, 0.35);
            }

            .topbar-inner,
            .page {
                width: min(1080px, calc(100% - 2rem));
                margin: 0 auto;
            }

            .topbar-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1.25rem 0;
            }

            .brand-mark {
                display: flex;
                align-items: center;
                gap: 0.9rem;
            }

            .brand-glyph {
                width: 2.75rem;
                height: 2.75rem;
                border-radius: 999px;
                display: grid;
                place-items: center;
                background: linear-gradient(135deg, rgba(216, 191, 120, 0.2), rgba(216, 191, 120, 0.45));
                border: 1px solid rgba(216, 191, 120, 0.5);
                font-size: 1.2rem;
                font-family: "Iowan Old Style", Georgia, serif;
            }

            .brand-copy {
                display: grid;
                gap: 0.12rem;
            }

            .brand-name {
                font-size: 1rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .brand-subtitle {
                color: rgba(255, 250, 240, 0.72);
                font-size: 0.85rem;
            }

            .topbar-actions {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .page {
                flex: 1;
                padding: 2rem 0 3rem;
            }

            .hero {
                background: rgba(255, 255, 255, 0.78);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(36, 51, 77, 0.09);
                border-radius: 1.5rem;
                box-shadow: var(--surface-shadow);
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.35rem 0.7rem;
                border-radius: 999px;
                background: rgba(183, 139, 47, 0.12);
                color: var(--navy-900);
                font-size: 0.8rem;
                font-weight: 600;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .page-title {
                margin: 1rem 0 0.4rem;
                font-size: clamp(2rem, 4vw, 3rem);
                font-family: "Iowan Old Style", Georgia, serif;
                line-height: 1.05;
            }

            .page-copy {
                max-width: 42rem;
                color: var(--ink-700);
                font-size: 1rem;
                line-height: 1.6;
            }

            .card-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }

            .panel,
            .metric,
            .form-panel {
                background: rgba(255, 255, 255, 0.92);
                border: 1px solid rgba(36, 51, 77, 0.09);
                border-radius: 1.2rem;
                box-shadow: var(--surface-shadow);
            }

            .metric {
                padding: 1.25rem;
            }

            .metric-label {
                color: var(--ink-700);
                font-size: 0.8rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .metric-value {
                margin-top: 0.55rem;
                font-size: 2rem;
                font-family: "Iowan Old Style", Georgia, serif;
                color: var(--navy-950);
            }

            .metric-copy {
                margin-top: 0.5rem;
                color: var(--ink-700);
                line-height: 1.5;
            }

            .panel {
                padding: 1.35rem;
                margin-top: 1.5rem;
            }

            .section-title {
                margin: 0 0 0.9rem;
                font-size: 1.2rem;
                font-family: "Iowan Old Style", Georgia, serif;
            }

            .bullet-list {
                margin: 0;
                padding-left: 1.1rem;
                color: var(--ink-700);
                line-height: 1.7;
            }

            .auth-shell {
                min-height: calc(100vh - 5.5rem);
                display: grid;
                place-items: center;
                padding: 2rem 0 3rem;
            }

            .form-panel {
                width: min(100%, 30rem);
                padding: 1.6rem;
            }

            .form-grid {
                display: grid;
                gap: 1rem;
            }

            label {
                display: grid;
                gap: 0.45rem;
                color: var(--ink-900);
                font-weight: 600;
            }

            input[type="email"],
            input[type="password"] {
                width: 100%;
                border: 1px solid rgba(36, 51, 77, 0.16);
                border-radius: 0.9rem;
                padding: 0.85rem 1rem;
                background: #fff;
                color: var(--ink-900);
                font: inherit;
            }

            input[type="checkbox"] {
                accent-color: var(--gold-500);
            }

            .inline-row {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                color: var(--ink-700);
                font-weight: 500;
            }

            .button {
                appearance: none;
                border: 0;
                border-radius: 999px;
                padding: 0.8rem 1.2rem;
                font: inherit;
                font-weight: 700;
                cursor: pointer;
                transition: transform 140ms ease, box-shadow 140ms ease;
            }

            .button:hover {
                transform: translateY(-1px);
            }

            .button-primary {
                background: linear-gradient(135deg, var(--gold-500), var(--gold-300));
                color: var(--navy-950);
                box-shadow: 0 14px 28px rgba(183, 139, 47, 0.28);
            }

            .button-secondary {
                background: rgba(255, 255, 255, 0.12);
                color: #fffaf0;
                border: 1px solid rgba(255, 255, 255, 0.18);
            }

            .error-banner {
                margin-bottom: 1rem;
                border-radius: 1rem;
                padding: 0.85rem 1rem;
                background: rgba(166, 64, 46, 0.09);
                border: 1px solid rgba(166, 64, 46, 0.2);
                color: var(--danger-600);
            }

            .muted {
                color: var(--ink-700);
            }

            @media (max-width: 720px) {
                .topbar-inner {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .topbar-actions {
                    width: 100%;
                    justify-content: flex-start;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <header class="topbar">
                <div class="topbar-inner">
                    <a class="brand-mark" href="{{ route('dashboard') }}">
                        <span class="brand-glyph">S</span>
                        <span class="brand-copy">
                            <span class="brand-name">South West Equine Services</span>
                            <span class="brand-subtitle">Horse quotes workspace</span>
                        </span>
                    </a>

                    @auth
                        <div class="topbar-actions">
                            <span class="brand-subtitle">{{ auth()->user()->email }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="button button-secondary" type="submit">Sign out</button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            @yield('content')
        </div>
    </body>
</html>
