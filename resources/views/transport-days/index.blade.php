@extends('layouts.app', ['title' => 'Transport days'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Scheduling</span>
            <h1 class="page-title">Transport days</h1>
            <p class="page-copy">
                Group individual transport jobs into a day's run and put them in the order they will be done.
            </p>
        </section>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">Recorded transport days</h2>
                    <p class="section-copy">Each day holds one or more jobs in sequence.</p>
                </div>
                <a class="button button-primary" href="{{ route('transport-days.create') }}">New transport day</a>
            </div>

            @if ($transportDays->isEmpty())
                <p class="empty-state">No transport days yet. Create one to start scheduling jobs into a run.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Jobs</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transportDays as $transportDay)
                            <tr>
                                <td>{{ $transportDay->run_date?->format('j M Y') }}</td>
                                <td>{{ $transportDay->name ?? 'Unnamed run' }}</td>
                                <td>{{ $transportDay->jobs_count }}</td>
                                <td>
                                    <a class="button-inline" href="{{ route('transport-days.show', ['transportDay' => $transportDay]) }}">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
@endsection
