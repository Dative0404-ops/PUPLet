{{-- Recommendations page: what to review next, ranked from your weakest topic. --}}
@extends('layouts.app')

@section('title', 'DSS Recommendations')

@section('content')
    {{-- Helper that maps a score to a colour class, plus the ranked list from the server --}}
    @php
        $tone = function (?int $percent) {
            if ($percent === null) {
                return '';
            }

            return $percent >= 75 ? 'is-good' : ($percent >= 60 ? 'is-fair' : 'is-poor');
        };

        $ranked = $recommendations['ranked'];
    @endphp

    {{-- Page header --}}
    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Decision support</span>
            <h1>What to review next</h1>
            <p class="track-line">
                LET track: <span class="badge">{{ $trackLabel }}</span>
            </p>
            <p class="track-line">
                Ranked from your own quiz results, weakest topic first.
            </p>
        </div>
    </section>

    {{-- How the list is built, then the ranked recommendations --}}
    <section class="section">
        <div class="container">
            {{-- The numbers here come from the DssSettings configuration --}}
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
                        {{-- The text changes depending on the "include untouched" rule --}}
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

            {{-- Shown when there are no ranked topics yet --}}
            @if ($ranked === [])
                <div class="card">
                    <h3>No recommendations yet</h3>
                    <p class="muted">
                        Finish a practice quiz and the system will point at the topics that need your attention.
                    </p>
                    <p>
                        <a class="btn btn-primary" href="{{ route('review.practice') }}">Start a practice quiz</a>
                    </p>
                </div>
            {{-- Ranked recommendation cards --}}
            @else
                <div class="recommendation-list">
                    @foreach ($ranked as $index => $item)
                        <div class="card recommendation-card">
                            <div class="recommendation-head">
                                <span class="rank-badge">{{ $index + 1 }}</span>

                                <div class="recommendation-info">
                                    <h3>{{ $item['topic'] }}</h3>
                                    <p class="muted small">
                                        {{ $item['subject'] }} &middot; {{ $item['area'] }}
                                    </p>
                                </div>

                                {{-- Red pill for weak topics, plain for ones not started --}}
                                <span class="pill {{ $item['type'] === 'weak' ? 'is-poor' : '' }}">
                                    {{ $item['type'] === 'weak' ? 'Needs practice' : 'Not started' }}
                                </span>
                            </div>

                            <p class="recommendation-reason">{{ $item['reason'] }}.</p>

                            {{-- Score bar shows only when a score is known; untouched topics have none --}}
                            @if ($item['percentage'] !== null)
                                <div class="progress">
                                    {{-- Width is at least 2% so even a low score stays visible --}}
                                    <div class="progress-bar {{ $tone($item['percentage']) }}"
                                         style="width: {{ max(2, $item['percentage']) }}%"></div>
                                </div>
                            @endif

                            <div class="recommendation-actions">
                                {{-- One-click drill when the topic has a quiz with published questions --}}
                                @if ($item['quiz_id'] && $item['available'] > 0)
                                    <form method="POST" action="{{ route('review.quiz.start', $item['quiz_id']) }}">
                                        {{-- @csrf is required on every POST form. --}}
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Practise this topic</button>
                                    </form>
                                @else
                                    <span class="muted small">No published questions for this topic yet.</span>
                                @endif

                                <a class="btn btn-secondary btn-sm" href="{{ route('review.topic', $item['topic_id']) }}">Open the topic</a>

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
