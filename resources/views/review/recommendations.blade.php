{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title --}}
@section('title', 'DSS Recommendations')

{{-- Content section --}}
@section('content')
    {{-- PHP block: a closure mapping a percentage to a tone class, plus the ranked list --}}
    @php
        $tone = function (?int $percent) {
            if ($percent === null) {
                return '';
            }

            return $percent >= 75 ? 'is-good' : ($percent >= 60 ? 'is-fair' : 'is-poor');
        };

        $ranked = $recommendations['ranked'];
    @endphp

    {{-- Hero banner for the decision support page --}}
    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Decision support</span>
            <h1>What to review next</h1>
            {{-- LET track badge --}}
            <p class="track-line">
                LET track: <span class="badge">{{ $trackLabel }}</span>
            </p>
            {{-- Explains how the ranking is ordered --}}
            <p class="track-line">
                Ranked from your own quiz results, weakest topic first.
            </p>
        </div>
    </section>

    {{-- Main section: the rules explanation and the ranked recommendations --}}
    <section class="section">
        <div class="container">
            {{-- Card explaining the tunable DSS rules ($rules comes from DssSettings) --}}
            <div class="card">
                <h3>How this list is built</h3>
                <ul class="plain-list">
                    <li>
                        A topic is judged once you have answered at least
                        {{ $rules['min_items'] }} {{ Str::plural('item', $rules['min_items']) }} in it.
                    </li>
                    <li>
                        Topics you score below {{ $rules['weak_threshold'] }}% in are listed as needing more practice.
                    </li>
                    <li>
                        {{-- Wording depends on whether untouched topics are included by the rules --}}
                        @if ($rules['include_untouched'])
                            Topics you have never practised fill the remaining places.
                        @else
                            Topics you have never practised are not listed.
                        @endif
                    </li>
                    <li>
                        Up to {{ $rules['limit'] }} {{ Str::plural('topic', $rules['limit']) }} are recommended at a time.
                    </li>
                </ul>
            </div>

            {{-- Branch when the recommender returned no ranked topics --}}
            @if ($ranked === [])
                <div class="card">
                    <h3>No recommendations yet</h3>
                    <p class="muted">
                        Finish a practice quiz and the system will point at the topics that need your attention.
                    </p>
                    <p>
                        {{-- Link to start a practice quiz --}}
                        <a class="btn btn-primary" href="{{ route('review.practice') }}">Start a practice quiz</a>
                    </p>
                </div>
            {{-- Otherwise render the ranked recommendation cards --}}
            @else
                <div class="recommendation-list">
                    {{-- Loop over ranked items; $index is used to display the rank number --}}
                    @foreach ($ranked as $index => $item)
                        <div class="card recommendation-card">
                            {{-- Header: rank badge, topic info and a type pill --}}
                            <div class="recommendation-head">
                                {{-- 1-based rank number --}}
                                <span class="rank-badge">{{ $index + 1 }}</span>

                                <div class="recommendation-info">
                                    {{-- Topic name --}}
                                    <h3>{{ $item['topic'] }}</h3>
                                    {{-- Subject and LET area --}}
                                    <p class="muted small">
                                        {{ $item['subject'] }} &middot; {{ $item['area'] }}
                                    </p>
                                </div>

                                {{-- Pill: red for "weak" topics, neutral for "not started" ones --}}
                                <span class="pill {{ $item['type'] === 'weak' ? 'is-poor' : '' }}">
                                    {{ $item['type'] === 'weak' ? 'Needs practice' : 'Not started' }}
                                </span>
                            </div>

                            {{-- Plain-language reason for the recommendation --}}
                            <p class="recommendation-reason">{{ $item['reason'] }}.</p>

                            {{-- Score bar only when a percentage is known (untouched topics have none) --}}
                            @if ($item['percentage'] !== null)
                                <div class="progress">
                                    {{-- Bar tone and width (min 2% so a score still shows); width set inline --}}
                                    <div class="progress-bar {{ $tone($item['percentage']) }}"
                                         style="width: {{ max(2, $item['percentage']) }}%"></div>
                                </div>
                            @endif

                            {{-- Action row for this recommendation --}}
                            <div class="recommendation-actions">
                                {{-- One-click drill start when the topic has a quiz and published questions --}}
                                @if ($item['quiz_id'] && $item['available'] > 0)
                                    <form method="POST" action="{{ route('review.quiz.start', $item['quiz_id']) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Practise this topic</button>
                                    </form>
                                {{-- Otherwise explain that no questions are published yet --}}
                                @else
                                    <span class="muted small">No published questions for this topic yet.</span>
                                @endif

                                {{-- Link to open the topic page --}}
                                <a class="btn btn-secondary btn-sm" href="{{ route('review.topic', $item['topic_id']) }}">Open the topic</a>

                                {{-- How many questions are available for this topic --}}
                                <span class="muted small">
                                    {{ $item['available'] }} {{ Str::plural('question', $item['available']) }} in the bank
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
