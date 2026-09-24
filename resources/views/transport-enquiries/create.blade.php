@extends('layouts.app', ['title' => 'New transport enquiry'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Transport quotes</span>
            <h1 class="page-title">New transport enquiry</h1>
            <p class="page-copy">Record the customer and journey details before calculating the route.</p>
        </section>

        <section class="panel">
            <p class="section-copy">Loading practice is separate from transport quoting.</p>

            <form class="form-grid" method="POST" action="{{ route('transport-enquiries.resolve') }}">
                @csrf
                <input name="submission_token" type="hidden" value="{{ $submissionToken }}">

                <div class="split-grid">
                    <label>
                        Enquiry source
                        <select @class(['is-invalid' => $errors->has('source')]) name="source">
                            <option value="">Select a source</option>
                            <option @selected(old('source') === 'direct_contact') value="direct_contact">Direct contact</option>
                            <option @selected(old('source') === 'social_media') value="social_media">Social media</option>
                            <option @selected(old('source') === 'word_of_mouth') value="word_of_mouth">Word of mouth</option>
                            <option @selected(old('source') === 'haynet') value="haynet">HayNet</option>
                            <option @selected(old('source') === 'other') value="other">Other</option>
                        </select>
                    </label>
                    <label>
                        Customer name
                        <input @class(['field-input', 'is-invalid' => $errors->has('customer_name')]) name="customer_name" type="text" value="{{ old('customer_name') }}">
                    </label>
                    <label>
                        Email
                        <input @class(['field-input', 'is-invalid' => $errors->has('email')]) name="email" type="email" value="{{ old('email') }}">
                    </label>
                    <label>
                        Phone
                        <input @class(['field-input', 'is-invalid' => $errors->has('phone')]) name="phone" type="text" value="{{ old('phone') }}">
                    </label>
                    <label>
                        Pickup postcode
                        <input @class(['field-input', 'is-invalid' => $errors->has('pickup_postcode')]) name="pickup_postcode" type="text" value="{{ old('pickup_postcode') }}">
                    </label>
                    <label>
                        Drop-off postcode
                        <input @class(['field-input', 'is-invalid' => $errors->has('dropoff_postcode')]) name="dropoff_postcode" type="text" value="{{ old('dropoff_postcode') }}">
                    </label>
                    <label>
                        Horse count
                        <input @class(['field-input', 'is-invalid' => $errors->has('horse_count')]) min="1" name="horse_count" type="number" value="{{ old('horse_count') }}">
                    </label>
                    <label>
                        Requested date
                        <input @class(['field-input', 'is-invalid' => $errors->has('requested_date')]) name="requested_date" type="date" value="{{ old('requested_date') }}">
                    </label>
                </div>

                <label class="inline-row">
                    <input @checked(old('date_to_be_arranged')) name="date_to_be_arranged" type="checkbox" value="1">
                    Date to be arranged
                </label>

                <label>
                    Special transport constraints
                    <textarea name="special_constraints">{{ old('special_constraints') }}</textarea>
                </label>

                <label class="inline-row">
                    <input @checked(old('special_constraints_acknowledged')) name="special_constraints_acknowledged" type="checkbox" value="1">
                    No special constraints are known, or the known constraints are recorded above.
                </label>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Resolve route and miles</button>
                </div>
            </form>

            @if ($errors->any())
                <ul class="bullet-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    </main>
@endsection
