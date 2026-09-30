@php
    $journey = $issuedQuote['journey'] ?? [];
    $route = $issuedQuote['route'] ?? [];
    $pricing = $issuedQuote['pricing'] ?? [];
    $exceptions = $issuedQuote['exceptions'] ?? [];
    $routeWarnings = data_get($route, 'warnings', []);
    $normalisedLocations = data_get($route, 'normalisation.locations', []);
    $reviewDecisions = data_get($route, 'review_decisions', []);
    $issuedAt = $revision->issued_at;
    $routeLegLabels = [
        'depot_to_pickup' => 'Depot to pickup',
        'pickup_to_dropoff' => 'Pickup to drop-off',
        'dropoff_to_depot' => 'Drop-off to depot',
    ];
@endphp
<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Issued quote {{ $revision->issue_reference }}</title>
        <style>
            :root {
                color: #182437;
                font-family: Arial, sans-serif;
            }

            body {
                margin: 0;
                background: #f3f1ed;
            }

            .quote {
                width: min(900px, calc(100% - 2rem));
                margin: 2rem auto;
                padding: 3rem;
                background: #ffffff;
                box-shadow: 0 8px 32px rgba(15, 27, 45, 0.16);
            }

            .quote-header,
            .section-heading,
            .totals {
                display: flex;
                justify-content: space-between;
                gap: 1.5rem;
            }

            .quote-header {
                align-items: flex-start;
                padding-bottom: 1.5rem;
                border-bottom: 2px solid #b78b2f;
            }

            .eyebrow,
            th,
            dt {
                color: #526174;
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            h1,
            h2,
            p {
                margin-top: 0;
            }

            h1 {
                margin-bottom: 0.5rem;
                font-family: Georgia, serif;
                font-size: 2.4rem;
            }

            h2 {
                margin-bottom: 0.5rem;
                font-family: Georgia, serif;
                font-size: 1.35rem;
            }

            .quote-logo {
                display: block;
                width: 120px;
                height: auto;
                margin-bottom: 1rem;
            }

            .reference {
                min-width: 14rem;
                text-align: right;
            }

            .reference strong {
                display: block;
                margin-top: 0.3rem;
                font-size: 1.25rem;
            }

            .grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 2rem;
                margin-top: 2rem;
            }

            dl {
                margin: 0;
            }

            dl div {
                display: grid;
                grid-template-columns: 10rem 1fr;
                gap: 1rem;
                padding: 0.45rem 0;
                border-bottom: 1px solid #e4e1db;
            }

            dd {
                margin: 0;
            }

            section {
                margin-top: 2rem;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                padding: 0.75rem;
                border-bottom: 1px solid #e4e1db;
                text-align: left;
            }

            .totals {
                justify-content: flex-end;
                margin-top: 1.5rem;
            }

            .total {
                min-width: 12rem;
                padding: 1rem;
                background: #f4ead1;
                text-align: right;
            }

            .total strong {
                display: block;
                margin-top: 0.3rem;
                font-size: 1.45rem;
            }

            .actions {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                margin-top: 2.5rem;
            }

            .button {
                border: 0;
                border-radius: 0.3rem;
                padding: 0.75rem 1rem;
                background: #182437;
                color: #ffffff;
                cursor: pointer;
                font: inherit;
                font-weight: 700;
                text-decoration: none;
            }

            .button-secondary {
                background: #e4e1db;
                color: #182437;
            }

            @media (max-width: 640px) {
                .quote {
                    width: 100%;
                    margin: 0;
                    padding: 1.5rem;
                }

                .quote-header,
                .grid {
                    display: block;
                }

                .reference {
                    margin-top: 1.5rem;
                    text-align: left;
                }

                dl div {
                    grid-template-columns: 1fr;
                    gap: 0.25rem;
                }
            }

            @media print {
                body {
                    background: #ffffff;
                }

                .quote {
                    width: 100%;
                    margin: 0;
                    padding: 0;
                    box-shadow: none;
                }

                .actions {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <main class="quote">
            <header class="quote-header">
                <div>
                    <img class="quote-logo" src="{{ asset('images/brand/logo-full-navy.png') }}" alt="South West Equine Services" width="120" height="102">
                    <p class="eyebrow">South West Equine Services</p>
                    <h1>Issued quote</h1>
                    <p>This is the staff-held issued revision record for manual customer delivery.</p>
                </div>
                <div class="reference">
                    <span class="eyebrow">Quote reference</span>
                    <strong>{{ $revision->issue_reference }}</strong>
                    <p>Issued {{ $issuedAt?->format('j M Y H:i') }}</p>
                </div>
            </header>

            <div class="grid">
                <section>
                    <h2>Customer</h2>
                    <dl>
                        <div>
                            <dt>Name</dt>
                            <dd>{{ data_get($issuedQuote, 'customer.name') }}</dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd>{{ data_get($issuedQuote, 'customer.email') ?: 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Telephone</dt>
                            <dd>{{ data_get($issuedQuote, 'customer.phone') ?: 'Not recorded' }}</dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <h2>Journey</h2>
                    <dl>
                        <div>
                            <dt>Pickup</dt>
                            <dd>{{ data_get($journey, 'pickup_postcode') }}</dd>
                        </div>
                        <div>
                            <dt>Drop-off</dt>
                            <dd>{{ data_get($journey, 'dropoff_postcode') }}</dd>
                        </div>
                        <div>
                            <dt>Horses</dt>
                            <dd>{{ data_get($journey, 'horse_count') }}</dd>
                        </div>
                        <div>
                            <dt>Requested date</dt>
                            <dd>{{ data_get($journey, 'date_to_be_arranged') ? 'To be arranged' : data_get($journey, 'requested_date') }}</dd>
                        </div>
                        <div>
                            <dt>Constraints</dt>
                            <dd>{{ data_get($journey, 'constraints') ?: 'None known' }}</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section>
                <div class="section-heading">
                    <div>
                        <h2>Route basis</h2>
                        <p>Provider: {{ strtoupper((string) data_get($route, 'provider', 'Not recorded')) }}</p>
                    </div>
                    <p>{{ str_replace('_', ' ', (string) data_get($route, 'status', 'Not recorded')) }}</p>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Leg</th>
                            <th>Journey</th>
                            <th>Miles</th>
                            <th>Rate type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (data_get($route, 'legs', []) as $leg)
                            <tr>
                                <td>{{ $routeLegLabels[data_get($leg, 'label')] ?? str_replace('_', ' ', data_get($leg, 'label')) }}</td>
                                <td>{{ data_get($leg, 'start_postcode') }} to {{ data_get($leg, 'end_postcode') }}</td>
                                <td>{{ data_get($leg, 'miles') }} miles</td>
                                <td>{{ ucfirst((string) data_get($leg, 'rate_type')) }}</td>
                                <td>£{{ number_format((float) data_get($leg, 'amount'), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section>
                <h2>Pricing evidence</h2>
                <dl>
                    <div>
                        <dt>Fuel source</dt>
                        <dd>{{ data_get($pricing, 'fuel.source') ? \App\Models\FuelPriceSource::labelFor(data_get($pricing, 'fuel.source')) : 'Not recorded' }}</dd>
                    </div>
                    <div>
                        <dt>Rate setting</dt>
                        <dd>{{ data_get($pricing, 'rate_setting.name') ?: 'Not recorded' }}</dd>
                    </div>
                </dl>
                <div class="totals">
                    <div class="total">
                        <span class="eyebrow">Engine total</span>
                        <strong>£{{ number_format((float) data_get($pricing, 'engine_total'), 2) }}</strong>
                    </div>
                    <div class="total">
                        <span class="eyebrow">Final total</span>
                        <strong>£{{ number_format((float) data_get($pricing, 'final_total'), 2) }}</strong>
                    </div>
                </div>
            </section>

            <section>
                <h2>Route audit evidence</h2>
                <dl>
                    <div>
                        <dt>Routing version</dt>
                        <dd>{{ data_get($route, 'provider_metadata.routing_version') ?: 'Not recorded' }}</dd>
                    </div>
                    <div>
                        <dt>Transport mode</dt>
                        <dd>{{ data_get($route, 'provider_metadata.transport_mode') ?: 'Not recorded' }}</dd>
                    </div>
                    <div>
                        <dt>Provider response status</dt>
                        <dd>{{ data_get($route, 'provider_metadata.http_status') ?: 'Not recorded' }}</dd>
                    </div>
                </dl>
                <table>
                    <thead>
                        <tr>
                            <th>Leg</th>
                            <th>Entered route</th>
                            <th>Resolved route</th>
                            <th>Provider route</th>
                            <th>Resolution status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (data_get($route, 'legs', []) as $leg)
                            <tr>
                                <td>{{ $routeLegLabels[data_get($leg, 'label')] ?? str_replace('_', ' ', data_get($leg, 'label')) }}</td>
                                <td>{{ data_get($leg, 'origin_input') }} to {{ data_get($leg, 'destination_input') }}</td>
                                <td>{{ data_get($leg, 'resolved_origin.postcode') ?: 'Not recorded' }} to {{ data_get($leg, 'resolved_destination.postcode') ?: 'Not recorded' }}</td>
                                <td>{{ data_get($leg, 'provider_route_id') ?: 'Not recorded' }}</td>
                                <td>{{ str_replace('_', ' ', (string) data_get($leg, 'resolution_status')) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($routeWarnings !== [])
                    <h3>Route warnings</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Warning</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($routeWarnings as $warning)
                                <tr>
                                    <td>{{ data_get($warning, 'code') }}</td>
                                    <td>{{ data_get($warning, 'message') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($normalisedLocations !== [])
                    <h3>Normalised locations</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Postcode</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($normalisedLocations as $location)
                                <tr>
                                    <td>{{ data_get($location, 'postcode') }}</td>
                                    <td>{{ data_get($location, 'latitude') }}</td>
                                    <td>{{ data_get($location, 'longitude') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($reviewDecisions !== [])
                    <h3>Route review decisions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Decision</th>
                                <th>Operator</th>
                                <th>Recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reviewDecisions as $decision)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', (string) data_get($decision, 'decision'))) }}</td>
                                    <td>{{ data_get($decision, 'operator') ?: 'Not recorded' }}</td>
                                    <td>{{ data_get($decision, 'recorded_at') ?: 'Not recorded' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section>
                <h2>Exceptions</h2>
                @if ($exceptions === [])
                    <p>No route or pricing exceptions were recorded for this revision.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Original value</th>
                                <th>Replacement value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exceptions as $exception)
                                <tr>
                                    <td>{{ str_replace('_', ' ', data_get($exception, 'type')) }}</td>
                                    <td>{{ data_get($exception, 'reason_category') }}: {{ data_get($exception, 'explanation') }}</td>
                                    <td>{{ data_get($exception, 'original_value') ?: 'Not recorded' }}</td>
                                    <td>{{ data_get($exception, 'replacement_value') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <div class="actions">
                <a class="button button-secondary" href="{{ route('jobs.revisions.show', ['job' => $job, 'revision' => $revision]) }}">Return to quote workspace</a>
                <button class="button" type="button" onclick="window.print()">Print or save as PDF</button>
            </div>
        </main>
    </body>
</html>
