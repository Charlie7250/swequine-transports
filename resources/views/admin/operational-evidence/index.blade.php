@extends('layouts.app', ['title' => 'Operational evidence'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Administration</span>
            <h1 class="page-title">Operational evidence</h1>
            <p class="page-copy">Read-only route and quote evidence for operational review.</p>
        </section>

        <section class="panel">
            <div class="stat-row">
                <article class="stat">
                    <div class="stat-label">Route attempts</div>
                    <div class="stat-value">{{ $operationalEvidence['attempts']['total'] }}</div>
                </article>
                <article class="stat">
                    <div class="stat-label">Issued quote revisions</div>
                    <div class="stat-value">{{ $operationalEvidence['issued_quote_revisions'] }}</div>
                </article>
                <article class="stat">
                    <div class="stat-label">Enquiry to quote-ready</div>
                    <div class="stat-value">{{ $operationalEvidence['turnaround']['quote_ready']['display'] }}</div>
                </article>
                <article class="stat">
                    <div class="stat-label">Enquiry to issue</div>
                    <div class="stat-value">{{ $operationalEvidence['turnaround']['issued']['display'] }}</div>
                </article>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Route outcome</th>
                        <th>Attempts</th>
                        <th>Failure categories</th>
                        <th>Exception reasons</th>
                        <th>Manual fallback rate</th>
                        <th>Route-leg override rate</th>
                        <th>Final-total override rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            @forelse ($operationalEvidence['attempts']['statuses'] as $status)
                                <div>{{ $status['status'] }}: {{ $status['count'] }}</div>
                            @empty
                                <span class="muted">N/A</span>
                            @endforelse
                        </td>
                        <td>{{ $operationalEvidence['attempts']['total'] }}</td>
                        <td>
                            @forelse ($operationalEvidence['attempts']['failure_categories'] as $category)
                                <div>{{ $category['category'] }}: {{ $category['count'] }}</div>
                            @empty
                                <span class="muted">N/A</span>
                            @endforelse
                        </td>
                        <td>
                            @forelse ($operationalEvidence['exception_reasons'] as $reason)
                                <div>{{ $reason['category'] }}: {{ $reason['count'] }}</div>
                            @empty
                                <span class="muted">No transport exception reasons recorded.</span>
                            @endforelse
                        </td>
                        <td>{{ $operationalEvidence['rates']['manual_route_fallback']['display'] }}</td>
                        <td>{{ $operationalEvidence['rates']['route_leg_override']['display'] }}</td>
                        <td>{{ $operationalEvidence['rates']['final_total_override']['display'] }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
@endsection
