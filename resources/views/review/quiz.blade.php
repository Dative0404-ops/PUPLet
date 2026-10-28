{{-- Quiz page: instructions and the start/resume button. --}}
@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
    {{-- Page header --}}
    <section class="page-header">
        <div class="container">
            {{-- Subject and topic links show only for a topic quiz, not a mock exam --}}
            <p class="breadcrumb">
                <a href="{{ route('review.index') }}">Reviewer</a>
                @if ($quiz->topic)
                    <span>&rsaquo;</span>
                    <a href="{{ route('review.subject', $quiz->topic->subject) }}">{{ $quiz->topic->subject->name }}</a>
                    <span>&rsaquo;</span>
                    <a href="{{ route('review.topic', $quiz->topic) }}">{{ $quiz->topic->name }}</a>
                @endif
            </p>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $quiz->typeLabel() }} &middot; {{ $quiz->item_count }} items
                @if ($timeLimit)
                    &middot; {{ $timeLimit }} minute time limit
                @else
                    &middot; no time limit
                @endif
            </p>
        </div>
    </section>

    {{-- Instructions and start button --}}
    <section class="section">
        <div class="container container-narrow">
            <div class="card">
                <h3>Before you begin</h3>
                <ul class="plain-list">
                    <li>Each item is multiple choice. Pick the best answer.</li>
                    <li>After you submit, you will see your score, the correct answers, and an explanation for each item.</li>
                    @if ($timeLimit)
                        <li>
                            The timer starts as soon as you begin. A live countdown stays on screen and your
                            answers are submitted automatically when time runs out.
                        </li>
                    @else
                        <li>You may take as long as you need.</li>
                    @endif
                    <li>You can save difficult questions to your bookmarks and revisit them later.</li>
                </ul>

                {{-- Shown when an attempt is still open --}}
                @if ($inProgress)
                    <div class="notice">
                        <p>You have a set that is still open. Resume it to keep your saved answers.</p>
                    </div>
                @endif

                {{-- Shown when there are no questions --}}
                @if ($available === 0)
                    <div class="notice">
                        <p>
                            This practice quiz has no questions yet. Please check back soon.
                        </p>
                    </div>
                @endif

                {{-- Start button and back link --}}
                <div class="quiz-actions">
                    <form method="POST" action="{{ route('review.quiz.start', $quiz) }}">
                        {{-- @csrf is required on every POST form. --}}
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            {{ $inProgress ? 'Resume the quiz' : 'Start the quiz' }}
                        </button>
                    </form>

                    @if ($quiz->topic)
                        <a class="btn btn-secondary" href="{{ route('review.topic', $quiz->topic) }}">Back to the topic</a>
                    @endif
                </div>

                <p class="muted small">
                    {{ $available }} {{ Str::plural('question', $available) }} available in the bank for this topic.
                </p>
            </div>

            {{-- Past attempts --}}
            <div class="card" style="margin-top: 20px;">
                <h3>Past attempts</h3>

                @forelse ($pastAttempts as $attempt)
                    <div class="list-row">
                        <div>
                            <strong>{{ $attempt->score }} / {{ $attempt->total_items }} correct</strong>
                            <small class="muted">
                                {{ $attempt->accuracy() }}% &middot; finished {{ $attempt->completed_at?->diffForHumans() }}
                            </small>
                        </div>
                        <a class="btn btn-secondary btn-sm" href="{{ route('review.results', $attempt) }}">View results</a>
                    </div>
                @empty
                    <p class="muted">You have not finished this quiz yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
