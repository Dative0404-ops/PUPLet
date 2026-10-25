{{-- Render inside the shared layout --}}
@extends('layouts.app')

{{-- Page title --}}
@section('title', 'Practice Quiz')

{{-- Content section --}}
@section('content')
    {{-- Hero banner for the quick-start practice drill page --}}
    <section class="dashboard-hero">
        <div class="container">
            {{-- Small label above the heading --}}
            <span class="eyebrow">Practice quiz</span>
            {{-- Heading --}}
            <h1>Start a practice drill</h1>
            {{-- Shows the student's LET track badge --}}
            <p class="track-line">
                LET track: <span class="badge">{{ $trackLabel }}</span>
            </p>
            {{-- Explains what the drill does --}}
            <p class="track-line">
                Choose a topic and how many items to answer. You will see your score and the
                explanation for every item as soon as you submit.
            </p>
        </div>
    </section>

    {{-- Main section with the setup form and recent practice sets --}}
    <section class="section">
        <div class="container container-narrow">
            {{-- Branch when no topic has published questions yet --}}
            @if ($topicGroups->isEmpty())
                {{-- Informational card prompting the student to return later --}}
                <div class="card">
                    <h3>No topics are ready yet</h3>
                    <p class="muted">
                        A practice drill needs published questions. Please check back once the question bank has
                        been filled for your track.
                    </p>
                    <p>
                        {{-- Link back to the subject catalogue --}}
                        <a class="btn btn-secondary" href="{{ route('review.index') }}">Browse your subjects</a>
                    </p>
                </div>
            {{-- At least one topic is available for practice --}}
            @else
                {{-- Card holding the drill setup form --}}
                <div class="card">
                    <h3>Set up your drill</h3>

                    {{-- POST form that starts a practice set --}}
                    <form method="POST" action="{{ route('review.practice.start') }}">
                        {{-- CSRF token required by Laravel for the POST --}}
                        @csrf

                        {{-- Topic picker field --}}
                        <div class="field">
                            <label for="topic_id">Topic</label>
                            {{-- Select gains an is-invalid class when validation failed for topic_id --}}
                            <select id="topic_id" name="topic_id" required class="{{ $errors->has('topic_id') ? 'is-invalid' : '' }}">
                                {{-- Placeholder option prompting the student to choose --}}
                                <option value="">Choose a topic</option>
                                {{-- Group topics under their subject using optgroup --}}
                                @foreach ($topicGroups as $group)
                                    <optgroup label="{{ $group['subject']->areaLabel() }} &middot; {{ $group['subject']->name }}">
                                        {{-- Each topic becomes a labelled option --}}
                                        @foreach ($group['topics'] as $topic)
                                            {{-- @selected keeps the option selected after a validation round-trip --}}
                                            <option value="{{ $topic->id }}" @selected((int) old('topic_id') === $topic->id)>
                                                {{ $topic->name }} ({{ $topic->questions_count }}
                                                {{ Str::plural('item', $topic->questions_count) }} available)
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        {{-- Item-count picker field --}}
                        <div class="field">
                            <label for="items">Number of items</label>
                            <select id="items" name="items" required>
                                {{-- Offer each preset count; select the old input or the default --}}
                                @foreach ($itemChoices as $choice)
                                    <option value="{{ $choice }}" @selected((int) old('items', $defaultItems) === $choice)>
                                        {{ $choice }} items
                                    </option>
                                @endforeach
                            </select>
                            {{-- Helper text explaining the fallback to all available items --}}
                            <span class="field-help muted small">
                                If the topic holds fewer published items than you ask for, the drill uses all of them.
                            </span>
                        </div>

                        {{-- Form buttons: start the drill or open the recommendations page --}}
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Start the drill</button>
                            <a class="btn btn-secondary" href="{{ route('review.recommendations') }}">What should I review?</a>
                        </div>
                    </form>
                </div>

                {{-- Card listing the student's most recent practice sets --}}
                <div class="card">
                    <h3>Recent practice sets</h3>

                    {{-- Loop over recent attempts, with an @empty fallback --}}
                    @forelse ($recentAttempts as $attempt)
                        {{-- One row per finished attempt --}}
                        <div class="list-row">
                            <div>
                                {{-- Quiz (practice set) title --}}
                                <strong>{{ $attempt->quiz->title }}</strong>
                                {{-- Subject, raw score and relative completion time --}}
                                <small class="muted">
                                    {{ $attempt->quiz->topic?->subject?->name }} &middot;
                                    {{ $attempt->score }} / {{ $attempt->total_items }} correct
                                    &middot; {{ $attempt->completed_at?->diffForHumans() }}
                                </small>
                            </div>
                            {{-- Link to the full results page for this attempt --}}
                            <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">View results</a>
                        </div>
                    {{-- Shown when the student has no finished practice sets --}}
                    @empty
                        <p class="muted">You have not finished a practice set yet. Your results will be listed here.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </section>
@endsection
