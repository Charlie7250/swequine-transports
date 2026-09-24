@extends('layouts.app', ['title' => 'New transport day'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Scheduling</span>
            <h1 class="page-title">New transport day</h1>
            <p class="page-copy">Give the day a date, and optionally a name and depot, then add jobs to it.</p>
        </section>

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
            <form class="form-section" method="POST" action="{{ route('transport-days.store') }}">
                @csrf

                <div class="split-grid">
                    <label>
                        <span>Run date</span>
                        <input name="run_date" type="date" value="{{ old('run_date') }}" required>
                    </label>

                    <label>
                        <span>Name</span>
                        <input name="name" type="text" value="{{ old('name') }}">
                    </label>

                    <label>
                        <span>Depot postcode</span>
                        <input name="depot_postcode" type="text" value="{{ old('depot_postcode') }}">
                    </label>
                </div>

                <label>
                    <span>Notes</span>
                    <textarea name="notes">{{ old('notes') }}</textarea>
                </label>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Create transport day</button>
                    <a class="button button-outline" href="{{ route('transport-days.index') }}">Cancel</a>
                </div>
            </form>
        </section>
    </main>
@endsection
