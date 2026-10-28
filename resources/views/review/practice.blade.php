{{-- Practice page: set up a topic drill and see recent practice sets. --}}
@extends('layouts.app')

@section('title', 'Practice Quiz')

@section('content')
    {{-- Page hero --}}
    <section class="dashboard-hero">
        <div class="container">
            <span class="eyebrow">Practice quiz</span>
            <h1>Start a practice drill</h1>
            <p class="track-line">
                LET track: <span class="badge">{{ $trackLabel }}</span>
            </p>
            <p class="track-line">
                Choose a topic and how many items to answer. You will see your score and the
                explanation for every item as soon as you submit.
            </p>
        </div>
    </section>

    {{-- Drill form and recent practice sets --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Shown when no topic has published questions --}}
            @if ($topicGroups->isEmpty())
                <div class="card">
                    <h3>No topics are ready yet</h3>
                    <p class="muted">
                        A practice drill needs published questions. Please check back once the question bank has
                        been filled for your track.
                    </p>
                    <p>
                        <a class="btn btn-secondary" href="{{ route('review.index') }}">Browse your subjects</a>
                    </p>
                </div>
            @else
                {{-- Drill setup form --}}
                <div class="card">
                    <h3>Set up your drill</h3>

                    <form method="POST" action="{{ route('review.practice.start') }}">
                        {{-- @csrf is required on every POST form. --}}
                        @csrf

                        <div class="field">
                            <label for="topic_id">Topic</label>
                            <select id="topic_id" name="topic_id" required class="{{ $errors->has('topic_id') ? 'is-invalid' : '' }}">
                                <option value="">Choose a topic</option>
                                @foreach ($topicGroups as $group)
                                    <optgroup label="{{ $group['subject']->areaLabel() }} &middot; {{ $group['subject']->name }}">
                                        @foreach ($group['topics'] as $topic)
                                            {{-- @selected keeps your choice after a failed submit --}}
                                            <option value="{{ $topic->id }}" @selected((int) old('topic_id') === $topic->id)>
                                                {{ $topic->name }} ({{ $topic->questions_count }}
                                                {{ Str::plural('item', $topic->questions_count) }} available)
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="items">Number of items</label>
                            <select id="items" name="items" required>
                                @foreach ($itemChoices as $choice)
                                    <option value="{{ $choice }}" @selected((int) old('items', $defaultItems) === $choice)>
                                        {{ $choice }} items
                                    </option>
                                @endforeach
                            </select>
                            <span class="field-help muted small">
                                If the topic holds fewer published items than you ask for, the drill uses all of them.
                            </span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Start the drill</button>
                            <a class="btn btn-secondary" href="{{ route('review.recommendations') }}">What should I review?</a>
                        </div>
                    </form>
                </div>

                {{-- Recent practice sets --}}
                <div class="card">
                    <h3>Recent practice sets</h3>

                    @forelse ($recentAttempts as $attempt)
                        <div class="list-row">
                            <div>
                                <strong>{{ $attempt->quiz->title }}</strong>
                                <small class="muted">
                                    {{ $attempt->quiz->topic?->subject?->name }} &middot;
                                    {{ $attempt->score }} / {{ $attempt->total_items }} correct
                                    &middot; {{ $attempt->completed_at?->diffForHumans() }}
                                </small>
                            </div>
                            <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">View results</a>
                        </div>
                    @empty
                        <p class="muted">You have not finished a practice set yet. Your results will be listed here.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </section>
@endsection
