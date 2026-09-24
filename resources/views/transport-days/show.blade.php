@extends('layouts.app', ['title' => 'Transport day'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Scheduling</span>
            <h1 class="page-title">{{ $transportDay->name ?? 'Transport day' }}</h1>
            <p class="page-copy">{{ $transportDay->run_date?->format('l j F Y') }}@if ($transportDay->depot_postcode) · depot {{ $transportDay->depot_postcode }}@endif</p>
        </section>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-banner">
                <ul class="bullet-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">Jobs in this day</h2>
                    <p class="section-copy">Listed in the order they will be done. Grouping a job here does not change its price.</p>
                </div>
                <span class="badge {{ $memberJobs->isNotEmpty() ? 'badge-active' : 'badge-muted' }}">{{ $memberJobs->count() }}</span>
            </div>

            @if ($memberJobs->isEmpty())
                <p class="empty-state">No jobs in this day yet. Add one from the list below.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Route</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($memberJobs as $index => $job)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><x-status-badge :status="$job->status" /></td>
                                <td>{{ $job->customer->name }}</td>
                                <td>{{ $job->currentWorkingRevision?->pickup_postcode ?? 'Pending' }} to {{ $job->currentWorkingRevision?->dropoff_postcode ?? 'Pending' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <form method="POST" action="{{ route('transport-days.jobs.move-up', ['transportDay' => $transportDay, 'job' => $job]) }}">
                                            @csrf
                                            <button class="button-inline" type="submit" @disabled($loop->first)>Up</button>
                                        </form>
                                        <form method="POST" action="{{ route('transport-days.jobs.move-down', ['transportDay' => $transportDay, 'job' => $job]) }}">
                                            @csrf
                                            <button class="button-inline" type="submit" @disabled($loop->last)>Down</button>
                                        </form>
                                        @if ($job->currentWorkingRevision)
                                            <a class="button-inline" href="{{ route('jobs.revisions.show', ['job' => $job, 'revision' => $job->currentWorkingRevision]) }}">Adjust</a>
                                        @endif
                                        <form method="POST" action="{{ route('transport-days.jobs.remove', ['transportDay' => $transportDay, 'job' => $job]) }}">
                                            @csrf
                                            <button class="button-inline" type="submit">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="panel">
            <h2 class="section-title">Add a job to this day</h2>

            @if ($assignableJobs->isEmpty())
                <p class="empty-state">No unassigned jobs are available to add.</p>
            @else
                <form class="form-section" method="POST" action="{{ route('transport-days.jobs.add', ['transportDay' => $transportDay]) }}">
                    @csrf
                    <div class="split-grid">
                        <label>
                            <span>Job</span>
                            <select name="job_id" required>
                                @foreach ($assignableJobs as $assignableJob)
                                    <option value="{{ $assignableJob->id }}">
                                        {{ $assignableJob->customer->name }} — {{ $assignableJob->currentWorkingRevision?->pickup_postcode ?? 'Pending' }} to {{ $assignableJob->currentWorkingRevision?->dropoff_postcode ?? 'Pending' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="button button-primary" type="submit">Add to day</button>
                    </div>
                </form>
            @endif
        </section>

        <div class="action-links">
            <a class="button-inline" href="{{ route('transport-days.index') }}">Back to transport days</a>
        </div>
    </main>
@endsection
