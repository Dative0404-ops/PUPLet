{{-- Admin page: settings for the DSS recommendation rules. --}}
@extends('layouts.app')

@section('title', 'DSS Settings')

@section('content')
    {{-- Page heading --}}
    <section class="dashboard-hero admin-hero">
        <div class="container">
            <span class="eyebrow">Admin panel</span>
            <h1>DSS Settings</h1>
            <p class="track-line">
                The rules behind the decision support recommendations students see on the
                DSS Recommendations page.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            {{-- Tab bar shared by every admin page. --}}
            @include('admin.partials.nav')

            <div class="admin-form-layout">
                {{-- Form holding the recommendation rules --}}
                <div class="form-card">
                    <form method="POST" action="{{ route('admin.dss-settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="field">
                            <label for="min_items">Items answered before a topic is judged</label>
                            <input type="number"
                                   id="min_items"
                                   name="min_items"
                                   value="{{ old('min_items', $settings['min_items']) }}"
                                   min="1"
                                   max="50"
                                   required>
                            <span class="field-help muted small">
                                A topic only appears as a weakness once the student has answered at least this many
                                items in it, so one short quiz cannot skew the list.
                            </span>
                        </div>

                        <div class="field">
                            <label for="weak_threshold">Weak topic threshold (%)</label>
                            <input type="number"
                                   id="weak_threshold"
                                   name="weak_threshold"
                                   value="{{ old('weak_threshold', $settings['weak_threshold']) }}"
                                   min="1"
                                   max="100"
                                   required>
                            <span class="field-help muted small">
                                Topics the student scores below this accuracy in are recommended for more practice.
                            </span>
                        </div>

                        <div class="field">
                            <label for="limit">Recommendations shown</label>
                            <input type="number"
                                   id="limit"
                                   name="limit"
                                   value="{{ old('limit', $settings['limit']) }}"
                                   min="1"
                                   max="12"
                                   required>
                            <span class="field-help muted small">
                                How many topics the ranked list holds. Weak topics are listed before topics the
                                student has not started.
                            </span>
                        </div>

                        <div class="field">
                            <label class="option-correct">
                                <input type="checkbox"
                                       name="include_untouched"
                                       value="1"
                                       @checked((string) old('include_untouched', $settings['include_untouched']) === '1')>
                                <span>Also recommend topics that have not been practised</span>
                            </label>
                            <span class="field-help muted small">
                                Fills the remaining places with topics inside the student's LET track that they have
                                never taken a quiz in.
                            </span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save the rules</button>
                            <a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Back to the dashboard</a>
                        </div>
                    </form>
                </div>

                {{-- Sidebar explaining how the rules shape the list --}}
                <aside class="admin-form-side">
                    <div class="card">
                        <h3>How the list is built</h3>
                        <ul class="plain-list">
                            <li>Every completed attempt is counted per topic.</li>
                            <li>Topics with fewer than {{ $settings['min_items'] }} answered items are left out.</li>
                            <li>Topics below {{ $settings['weak_threshold'] }}% are ranked weakest first.</li>
                            <li>
                                @if ($settings['include_untouched'] === '1')
                                    Topics the student has never practised fill the remaining places.
                                @else
                                    Unpractised topics are left out of the list.
                                @endif
                            </li>
                            <li>At most {{ $settings['limit'] }} topics are shown.</li>
                        </ul>
                    </div>

                    <div class="card">
                        <h3>Where the rules apply</h3>
                        <p class="muted small">
                            These rules drive the
                            <a href="{{ route('review.recommendations') }}">DSS Recommendations</a> page that students
                            open from their navigation. Changes take effect straight away.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
