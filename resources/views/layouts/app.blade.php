<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'South West Equine Services Quotes' }}</title>
        <style>
            :root {
                color-scheme: light;
                --navy-950: var(--prototype-navy-1000, #081625);
                --navy-900: var(--prototype-navy-900, #10243a);
                --navy-800: var(--prototype-navy-800, #17324d);
                --gold-700: var(--prototype-gold-700, #8c661d);
                --gold-600: var(--prototype-gold-600, #a57a23);
                --gold-500: var(--prototype-gold-500, #be9134);
                --gold-300: var(--prototype-gold-300, #e4c779);
                --paper: var(--prototype-paper, #fffefa);
                --stone-50: var(--prototype-cream-50, #fbf9f4);
                --stone-100: var(--prototype-stone-100, #f0ece4);
                --stone-200: var(--prototype-stone-200, #e7e1d7);
                --stone-300: var(--prototype-stone-300, #d8d2c7);
                --ink-900: var(--prototype-ink, #18283c);
                --ink-700: var(--prototype-muted, #687386);
                --danger-600: var(--prototype-red, #8a3232);
                --surface-shadow: var(--prototype-shadow, 0 18px 45px rgba(17, 31, 49, 0.08));
                font-family: Georgia, "Times New Roman", serif;
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
                font-family: "Instrument Sans", ui-sans-serif, system-ui, "Segoe UI", sans-serif;
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
                font-family: Georgia, "Times New Roman", serif;
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

            .nav-list {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .nav-link {
                padding: 0.55rem 0.85rem;
                border-radius: 999px;
                color: rgba(255, 250, 240, 0.8);
                font-size: 0.92rem;
                transition: background 140ms ease, color 140ms ease;
            }

            .nav-link.is-active,
            .nav-link:hover {
                background: rgba(255, 255, 255, 0.12);
                color: #fffaf0;
            }

            .page {
                flex: 1;
                padding: 2rem 0 3rem;
            }

            .hero {
                background: transparent;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0.5rem 0 0;
                margin-bottom: 1.5rem;
            }

            .hero--legacy {
                background: var(--paper);
                border: 1px solid var(--stone-200);
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
                font-family: Georgia, "Times New Roman", serif;
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
                background: var(--paper);
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
                font-family: Georgia, "Times New Roman", serif;
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

            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 1rem;
            }

            .section-title {
                margin: 0 0 0.9rem;
                font-size: 1.2rem;
                font-family: Georgia, "Times New Roman", serif;
            }

            .section-copy {
                margin: 0.35rem 0 0;
                color: var(--ink-700);
                line-height: 1.6;
            }

            .bullet-list {
                margin: 0;
                padding-left: 1.1rem;
                color: var(--ink-700);
                line-height: 1.7;
            }

            .auth-shell {
                min-height: 100vh;
                align-content: center;
                gap: 1.75rem;
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

            input[type="text"],
            input[type="number"],
            input[type="date"],
            input[type="email"],
            input[type="password"],
            select,
            textarea {
                width: 100%;
                border: 1px solid rgba(36, 51, 77, 0.16);
                border-radius: 0.9rem;
                padding: 0.85rem 1rem;
                background: #fff;
                color: var(--ink-900);
                font: inherit;
            }

            textarea {
                min-height: 7rem;
                resize: vertical;
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
                background: var(--gold-600);
                color: #fffdf7;
                box-shadow: 0 12px 24px rgba(140, 102, 29, 0.20);
            }

            .button-primary:hover {
                background: var(--gold-700);
            }

            .button-secondary {
                background: rgba(255, 255, 255, 0.12);
                color: #fffaf0;
                border: 1px solid rgba(255, 255, 255, 0.18);
            }

            .button-outline {
                background: var(--paper);
                color: var(--gold-700);
                border: 1px solid var(--stone-300);
            }

            .button-outline:hover {
                border-color: var(--gold-500);
            }

            .error-banner {
                margin-bottom: 1rem;
                border-radius: 1rem;
                padding: 0.85rem 1rem;
                background: rgba(166, 64, 46, 0.09);
                border: 1px solid rgba(166, 64, 46, 0.2);
                color: var(--danger-600);
            }

            .status-banner {
                margin-bottom: 1rem;
                border-radius: 1rem;
                padding: 0.85rem 1rem;
                background: rgba(32, 88, 53, 0.08);
                border: 1px solid rgba(32, 88, 53, 0.16);
                color: #205835;
            }

            .form-section {
                display: grid;
                gap: 1rem;
            }

            .split-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }

            .form-actions,
            .table-actions {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .field-help {
                color: var(--ink-700);
                font-size: 0.92rem;
                line-height: 1.5;
            }

            .field-error {
                color: var(--danger-600);
                font-size: 0.92rem;
                font-weight: 600;
                line-height: 1.5;
            }

            .field-input.is-invalid,
            select.is-invalid,
            textarea.is-invalid {
                border-color: rgba(166, 64, 46, 0.48);
                box-shadow: 0 0 0 3px rgba(166, 64, 46, 0.08);
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                border-radius: 999px;
                padding: 0.35rem 0.7rem;
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }

            .badge-active {
                background: var(--prototype-green-bg, #e4f2e4);
                color: var(--prototype-green, #27643b);
            }

            .badge-muted {
                background: var(--prototype-grey-bg, #eceef1);
                color: var(--prototype-grey, #555f6d);
            }

            .page-heading {
                margin-bottom: 1.25rem;
            }

            .page-heading-title {
                margin: 0;
                font-size: 1.65rem;
                font-family: Georgia, "Times New Roman", serif;
                line-height: 1.15;
                color: var(--navy-950);
            }

            .page-heading-subtitle {
                margin: 0.3rem 0 0;
                color: var(--ink-700);
                font-size: 0.95rem;
            }

            .stat-row {
                display: grid;
                gap: 0.75rem;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                margin-bottom: 0.85rem;
            }

            .stat {
                background: var(--paper);
                border: 1px solid rgba(36, 51, 77, 0.09);
                border-radius: 1rem;
                padding: 0.8rem 1rem;
            }

            .stat-label {
                color: var(--ink-700);
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.07em;
                text-transform: uppercase;
            }

            .stat-value {
                margin-top: 0.3rem;
                font-size: 1.5rem;
                font-family: Georgia, "Times New Roman", serif;
                color: var(--navy-950);
            }

            .active-strip {
                display: flex;
                flex-wrap: wrap;
                gap: 0.6rem;
                margin-bottom: 0.5rem;
            }

            .active-pill {
                display: inline-flex;
                align-items: baseline;
                gap: 0.55rem;
                padding: 0.5rem 0.9rem;
                border-radius: 999px;
                background: rgba(36, 51, 77, 0.05);
                border: 1px solid rgba(36, 51, 77, 0.1);
            }

            .active-pill-label {
                color: var(--ink-700);
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .active-pill-value {
                font-weight: 700;
                color: var(--navy-950);
            }

            .active-pill-meta {
                color: var(--ink-700);
                font-size: 0.85rem;
            }

            .status-badge { display: inline-flex; align-items: center; gap: 0.4rem; }
            .status-badge::before {
                content: "";
                width: 0.5rem;
                height: 0.5rem;
                border-radius: 999px;
                background: currentColor;
            }
            .status-badge--draft { background: var(--prototype-grey-bg, #eceef1); color: var(--prototype-grey, #555f6d); }
            .status-badge--quoted { background: var(--prototype-blue-bg, #e4edfc); color: var(--prototype-blue, #275aa2); }
            .status-badge--pending { background: var(--prototype-amber-bg, #fff1cf); color: var(--prototype-amber, #8a5b05); }
            .status-badge--booked { background: #e6e9f7; color: #3a4a8c; }
            .status-badge--completed { background: var(--prototype-green-bg, #e4f2e4); color: var(--prototype-green, #27643b); }
            .status-badge--lost { background: var(--prototype-red-bg, #f7e5e2); color: var(--prototype-red, #8a3232); }

            .status-badge--lg {
                font-size: 0.95rem;
                padding: 0.5rem 0.95rem;
                letter-spacing: 0.04em;
            }

            .status-chip--draft { background: rgba(36, 51, 77, 0.06); color: #4d4a44; }
            .status-chip--quoted { background: rgba(55, 138, 221, 0.10); color: #0c447c; }
            .status-chip--pending { background: rgba(239, 159, 39, 0.12); color: #633806; }
            .status-chip--booked { background: rgba(29, 158, 117, 0.12); color: #085041; }
            .status-chip--completed { background: rgba(99, 153, 34, 0.12); color: #27500a; }
            .status-chip--lost { background: rgba(166, 64, 46, 0.10); color: #791f1f; }

            .status-chip-strip {
                display: grid;
                gap: 0.6rem;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                margin-bottom: 1.35rem;
            }

            .day-block {
                padding: 1rem 0;
                border-top: 1px solid rgba(36, 51, 77, 0.09);
            }

            .day-block:first-of-type {
                border-top: 0;
                padding-top: 0;
            }

            .day-block-header {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
                margin-bottom: 0.5rem;
            }

            .day-block-header .section-title {
                margin: 0;
            }

            .status-chip {
                display: flex;
                flex-direction: column;
                gap: 0.3rem;
                padding: 0.75rem 0.85rem;
                border-radius: 0.9rem;
            }

            .status-chip-label {
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .status-chip-count {
                font-size: 1.6rem;
                font-family: Georgia, "Times New Roman", serif;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
            }

            .data-table th,
            .data-table td {
                padding: 0.85rem 0.9rem;
                border-bottom: 1px solid var(--stone-200);
                text-align: left;
                vertical-align: middle;
            }

            .data-table thead th {
                background: var(--stone-100);
                color: var(--ink-700);
                font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .data-table thead th:first-child {
                border-top-left-radius: 0.75rem;
                border-bottom-left-radius: 0.75rem;
            }

            .data-table thead th:last-child {
                border-top-right-radius: 0.75rem;
                border-bottom-right-radius: 0.75rem;
            }

            .data-table tr:last-child td {
                border-bottom: 0;
            }

            .button-inline {
                appearance: none;
                border: 0;
                background: transparent;
                padding: 0;
                color: var(--navy-900);
                font: inherit;
                font-weight: 700;
                cursor: pointer;
            }

            .summary-grid,
            .detail-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            }

            .summary-grid > .panel,
            .detail-grid > .panel {
                margin-top: 0;
            }

            .summary-stack {
                display: grid;
                gap: 0.4rem;
            }

            .detail-list {
                display: grid;
                gap: 0.5rem;
                margin: 0;
            }

            .detail-list div {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                color: var(--ink-700);
            }

            .detail-list dt {
                font-weight: 600;
                color: var(--ink-900);
            }

            .empty-state {
                color: var(--ink-700);
                line-height: 1.6;
            }

            .empty-state strong {
                color: var(--ink-900);
            }

            .action-links {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
                margin-top: 1rem;
            }

            .muted {
                color: var(--ink-700);
            }

            details.panel > summary {
                cursor: pointer;
                list-style: none;
            }

            details.panel > summary::-webkit-details-marker {
                display: none;
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

                .page-header {
                    flex-direction: column;
                }

                .detail-list div {
                    flex-direction: column;
                    gap: 0.2rem;
                }
            }

            /* --- Mockup-aligned refinements --- */
            .auth-brand {
                display: grid;
                justify-items: center;
            }

            .auth-brand .brand-logo {
                width: min(250px, 70vw);
                height: auto;
            }




            .form-panel .eyebrow {
                padding: 0;
                background: transparent;
                color: var(--gold-600);
                letter-spacing: 0.2em;
            }

            .password-field {
                position: relative;
                display: block;
            }

            .password-field input {
                padding-right: 3rem;
            }

            .password-toggle {
                position: absolute;
                top: 50%;
                right: 0.6rem;
                transform: translateY(-50%);
                appearance: none;
                border: 0;
                background: transparent;
                color: var(--ink-700);
                font: inherit;
                font-size: 0.8rem;
                font-weight: 600;
                cursor: pointer;
                padding: 0.35rem 0.5rem;
            }

            .auth-shell .button-primary {
                width: 100%;
                border-radius: 0.7rem;
            }

            input[type="text"]:focus,
            input[type="number"]:focus,
            input[type="date"]:focus,
            input[type="email"]:focus,
            input[type="password"]:focus,
            select:focus,
            textarea:focus {
                outline: none;
                border-color: var(--gold-500);
                box-shadow: 0 0 0 3px rgba(190, 145, 52, 0.15);
            }

            .section-title {
                display: flex;
                align-items: center;
                gap: 0.6rem;
            }

            .section-icon {
                display: inline-grid;
                place-items: center;
                width: 1.9rem;
                height: 1.9rem;
                border-radius: 0.6rem;
                background: rgba(190, 145, 52, 0.12);
                color: var(--gold-700);
                flex: none;
            }

            .section-icon svg { width: 1.05rem; height: 1.05rem; }

            .metric--accent {
                border-color: rgba(190, 145, 52, 0.55);
                box-shadow: 0 12px 30px rgba(190, 145, 52, 0.12);
                background:
                    linear-gradient(0deg, rgba(190, 145, 52, 0.06), rgba(190, 145, 52, 0.06)),
                    var(--paper);
            }

            .metric-value--accent { color: var(--navy-950); }

            /* Bordered icon table actions (Up / Down / Adjust / Remove) */
            .table-actions .button-inline {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.45rem 0.7rem;
                border: 1px solid var(--stone-300);
                border-radius: 0.6rem;
                background: var(--paper);
                color: var(--navy-900);
            }

            .table-actions .button-inline:hover:not(:disabled) {
                border-color: var(--gold-500);
                color: var(--gold-700);
            }

            .table-actions .button-inline:disabled {
                opacity: 0.45;
                cursor: not-allowed;
            }

            /* Depot start / finish route endpoints strip */
            .route-endpoints {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px dashed var(--stone-300);
                color: var(--ink-700);
                font-size: 0.9rem;
            }

            .route-endpoint {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                font-weight: 600;
                color: var(--navy-900);
            }

            /* Sticky mobile primary action bar */
            .mobile-action-bar { display: none; }

            @media (max-width: 720px) {
                .mobile-action-bar {
                    position: sticky;
                    bottom: 0;
                    display: flex;
                    gap: 0.6rem;
                    margin: 1rem -1rem -1rem;
                    padding: 0.85rem 1rem;
                    background: color-mix(in srgb, var(--paper) 92%, transparent);
                    border-top: 1px solid var(--stone-200);
                    backdrop-filter: blur(8px);
                }

                .mobile-action-bar .button { flex: 1; justify-content: center; }
            }
        </style>
        @vite('resources/css/app.css')
    </head>
    <body class="@auth prototype-body @endauth">
        @auth
            <div class="operator-shell">
                <x-operator-sidebar />

                <div class="operator-workspace">
                    <x-operator-topbar />

                    <div class="operator-main operator-main--application">
                        @yield('content')
                    </div>
                </div>
            </div>
        @else
            <div class="shell">
                @yield('content')
            </div>
        @endauth
    </body>
</html>
